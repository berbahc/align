<?php

namespace App\Actions;

use App\Models\Habit;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\SlotConflict;

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
