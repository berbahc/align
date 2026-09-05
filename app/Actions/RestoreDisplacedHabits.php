<?php

namespace App\Actions;

use App\Models\Habit;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\SlotConflict;
use Illuminate\Support\Carbon;

/**
 * Holt geparkte Gewohnheiten an ihren alten Platz zurück, wo er frei ist.
 *
 * Der Anlass ist ein Kurs, der verschwindet oder umzieht: Was ihn verdrängt
 * hat, ist weg — die Gewohnheit kann zurück. Aber nur dorthin, wo inzwischen
 * nichts anderes liegt. Geprüft wird vor dem Zurückholen, nicht danach; sonst
 * käme der Überlapp durch die Hintertür, die diese Runde gerade geschlossen
 * hat.
 */
class RestoreDisplacedHabits
{
    /**
     * @return list<Habit> Was seinen alten Platz zurückbekommen hat
     */
    public function handle(User $user): array
    {
        $restored = [];

        // Auch die ohne Uhrzeit: Eine Gewohnheit an einer Situation belegt im
        // Tag die Stunde ihres Ankers, wird darüber verdrängt — und hinge
        // ohne diesen Weg für immer, weil sie kein `scheduled_time` hat.
        $parked = $user->habits()
            ->active()
            ->whereNotNull('displaced_at')
            ->with('chainedTo.chainedTo')
            ->get();

        $parked->each(fn (Habit $habit) => $habit->setRelation('user', $user));

        foreach ($parked as $habit) {
            $start = $this->oldStart($habit);

            if ($start === null) {
                continue;
            }

            $spans = $habit->spansFrom($start);

            $conflict = SlotConflict::find(
                $user,
                $spans,
                $habit->scheduled_days ?? [1, 2, 3, 4, 5, 6, 7],
                array_column($spans, 'id'),
            );

            if ($conflict !== null) {
                continue;
            }

            $habit->takeAPlace();
            $restored[] = $habit;
        }

        return $restored;
    }

    /**
     * Den Platz für **einen** Tag zurückgeben.
     *
     * Fällt die Vorlesung an einem Datum aus, ist die Zeit an diesem Tag
     * wieder frei — aber nur an ihm. Der Vermerk bleibt deshalb stehen; was
     * die Gewohnheit bekommt, ist ein Umzug für genau diesen Tag
     * ({@see HabitDayShift}), und der sagt in der App ohnehin „nur heute
     * hier". Wird der Ausfall zurückgenommen, fällt er wieder weg.
     *
     * @return list<Habit> Was für diesen Tag zurückkonnte
     */
    public function handleOn(User $user, Carbon $date): array
    {
        $restored = [];

        $parked = $user->habits()
            ->active()
            ->displaced()
            ->with('chainedTo.chainedTo')
            ->get();

        $parked->each(fn (Habit $habit) => $habit->setRelation('user', $user));

        foreach ($parked as $habit) {
            $start = $this->oldStart($habit);

            if ($start === null || ! $habit->isScheduledOn($date) || ! $habit->isDisplaced($date)) {
                continue;
            }

            $spans = $habit->spansFrom($start);

            if (SlotConflict::findOn($user, $spans, $date, array_column($spans, 'id')) !== null) {
                continue;
            }

            $habit->dayShifts()->updateOrCreate(
                ['shifted_on' => $date->toDateString()],
                ['scheduled_time' => DayPlan::toTime($start)],
            );

            $restored[] = $habit;
        }

        return $restored;
    }

    /**
     * Den geliehenen Tag wieder einziehen.
     *
     * Der Ausfall ist zurückgenommen, der Kurs läuft wieder — was für diesen
     * Tag zurückgeliehen war, muss weichen. Betroffen sind nur Umzüge
     * geparkter Gewohnheiten: Wer keinen Platz hat, kann auch keinen selbst
     * verschoben haben.
     */
    public function withdrawDay(User $user, Carbon $date): void
    {
        $user->habits()
            ->active()
            ->displaced()
            ->get()
            ->each(fn (Habit $habit) => $habit->dayShifts()
                ->whereDate('shifted_on', $date)
                ->delete());
    }

    /**
     * Wo sie lag, als sie noch einen Platz hatte — in Minuten.
     *
     * Eine feste Uhrzeit sagt es selbst. Eine Gewohnheit an einer Situation
     * hat keine; sie belegte die volle Stunde ihres Ankers, und mit derselben
     * Näherung wird geprüft, ob dort wieder Platz ist. Null heißt: Sie hat
     * gar keine Stelle, die sich zurückgeben ließe.
     */
    private function oldStart(Habit $habit): ?int
    {
        if ($habit->scheduled_time !== null) {
            return DayPlan::toMinutes($habit->scheduled_time->format('H:i'));
        }

        $hour = $habit->plannedAnchorHour();

        return $hour === null ? null : $hour * 60;
    }
}
