<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\HabitDayShift;
use App\Models\User;
use App\Support\DayPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Einen Block im Tag verschieben — mit der Hand, nicht über die KI.
 *
 * Der Kalender zeigt seit dem Stundenraster, **wo** eine Gewohnheit liegt.
 * Diese Strecke ist die dazugehörige Geste: anfassen, woandershin legen. Sie
 * ist der einzige Weg in der App, der einen Zeitpunkt ohne Vorschlag ändert —
 * und deshalb der einzige, der hinterher fragt, wie weit die Änderung reichen
 * soll.
 *
 * Zwei Reichweiten, zwei völlig verschiedene Dinge:
 *
 * - **heute** legt eine Ausnahme für dieses eine Datum an
 *   ({@see HabitDayShift}). Auslöser und Kette bleiben, wie sie waren — „heute
 *   mache ich das später" ist keine Planänderung.
 * - **immer** schreibt die Uhrzeit an die Gewohnheit und macht sie damit zu
 *   einer festen. Derselbe Schritt, den {@see DayOrderController::store()} für
 *   einen ganzen Tag auf einmal tut: Auslöser und Kette fallen weg, weil eine
 *   Gewohnheit nur einen Zeitpunkt haben kann.
 *
 * Vor dem dauerhaften Umstellen wird jeder künftige Wochentag geprüft. Zwei
 * Gewohnheiten zur selben Zeit sind kein Plan, und die Antwort darauf ist
 * kein „geht nicht", sondern der Satz, der sagt, was zu tun ist.
 */
class HabitShiftController extends Controller
{
    /**
     * Wie fein sich schieben lässt.
     *
     * Eine Viertelstunde — derselbe Takt wie {@see DayPlan::BreatherMinutes}
     * und fein genug für jede Gewohnheit im Katalog. Minutengenau zu schieben
     * hieße, mit dem Finger eine Genauigkeit zu verlangen, die niemand hat.
     */
    private const int SnapMinutes = 15;

    public function store(Request $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('update', $habit);

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            // Bis zum Ende eines Tages, der nach Mitternacht endet.
            'start_minute' => ['required', 'integer', 'min:0', 'max:1740'],
            'scope' => ['required', 'in:today,always'],
        ]);

        $date = Carbon::createFromFormat('!Y-m-d', $validated['date']);
        $start = (int) round($validated['start_minute'] / self::SnapMinutes) * self::SnapMinutes;

        // Ein vergangener Tag ist vorbei. Ihn umzuräumen änderte nichts mehr an
        // ihm und wäre nur eine Nachbesserung der eigenen Geschichte.
        if ($date->lessThan(Carbon::today())) {
            throw ValidationException::withMessages([
                'date' => 'Vergangene Tage lassen sich nicht mehr umlegen.',
            ]);
        }

        $validated['scope'] === 'always'
            ? $this->always($request->user(), $habit, $start)
            : $this->today($request->user(), $habit, $date, $start);

        return back();
    }

    /**
     * Die Ausnahme zurücknehmen — der Tag gilt wieder wie geplant.
     */
    public function destroy(Request $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('update', $habit);

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $habit->dayShifts()->whereDate('shifted_on', $validated['date'])->delete();

        return back();
    }

    /**
     * Nur dieser eine Tag.
     *
     * Geprüft wird nur er: Was an anderen Tagen gilt, ändert sich nicht, und
     * eine Warnung über den Dienstag wäre hier eine Antwort auf eine Frage,
     * die niemand gestellt hat.
     */
    private function today(User $user, Habit $habit, Carbon $date, int $start): void
    {
        $this->guard($user, $habit, $date, $start);

        $habit->dayShifts()->updateOrCreate(
            ['shifted_on' => $date->toDateString()],
            ['start_minute' => $start],
        );
    }

    /**
     * Ab jetzt immer.
     *
     * Geprüft wird jeder Wochentag, an dem die Gewohnheit künftig läuft — das
     * ist der Fall „vielleicht liegt da in der Zukunft schon etwas".
     *
     * Die Tage bleiben, wie sie waren; eine Gewohnheit ohne eigene Tage läuft
     * täglich. Dieselbe Regel wie in {@see DayOrderController::store()}, und
     * aus demselben Grund: Umgeordnet wird der Tag, nicht die Woche.
     */
    private function always(User $user, Habit $habit, int $start): void
    {
        $days = $habit->scheduled_days ?? [1, 2, 3, 4, 5, 6, 7];

        foreach ($days as $weekday) {
            $this->guard($user, $habit, $this->nextWeekday($weekday), $start);
        }

        $habit->update([
            'schedule_type' => ScheduleType::Fixed,
            'scheduled_time' => DayPlan::toTime($start),
            'scheduled_days' => $days,
            'trigger_situation' => null,
            'chained_to_habit_id' => null,
        ]);

        // Die dauerhafte Zeit hebt die Einzelfall-Regel auf: Zwei Antworten für
        // denselben Tag wären eine zu viel.
        $habit->dayShifts()->delete();
    }

    /**
     * Weist ab, was an diesem Tag nicht ginge — mit dem Grund und dem Ausweg.
     *
     * Zwei Grenzen: der Schlafrahmen und die Blöcke, die dort schon liegen.
     * Beide gelten auch für die Gewohnheiten, die an dieser hängen — sie
     * rutschen mit, und eine Kette, die um Mitternacht endet, ist keine
     * Planung.
     */
    private function guard(User $user, Habit $habit, Carbon $date, int $start): void
    {
        $others = $this->othersOn($user, $habit, $date);
        $window = $user->sleepWindowFor($date->dayOfWeekIso);
        $plan = DayPlan::for($others, $date->dayOfWeekIso, $user->sleepWindows(), $date);
        $frame = $plan->frame();

        foreach ($this->spans($habit, $start) as $span) {
            if ($span['from'] < $frame['from'] || $span['to'] > $frame['to']) {
                throw ValidationException::withMessages([
                    'start_minute' => sprintf(
                        '%s läge dann außerhalb deines Tages (%s bis %s Uhr). Passe die Zeit an — oder deinen Schlafplan.',
                        $span['title'] === $habit->title ? 'Die Gewohnheit' : sprintf('„%s"', $span['title']),
                        $window['wakeTime'],
                        $window['bedtime'],
                    ),
                ]);
            }

            $conflict = $plan->collisionWith($span['from'], $span['to']);

            if ($conflict !== null) {
                throw ValidationException::withMessages([
                    'start_minute' => sprintf(
                        '„%s" liegt %s schon um %s. Verschiebe die zuerst, dann lässt sich die Zeit hier umstellen.',
                        $conflict['title'],
                        $this->weekdayLabel($date),
                        DayPlan::toTime($conflict['from']),
                    ),
                ]);
            }
        }
    }

    /**
     * Die Spannen, die der Zug belegt: die Gewohnheit selbst und alles, was an
     * ihr hängt.
     *
     * Gerechnet wie {@see Habit::placementOn()} es täte — nur eben mit der
     * neuen Minute statt der alten. Die Kette selbst zu durchlaufen ist hier
     * nötig, weil die Gewohnheit die neue Zeit noch gar nicht trägt.
     *
     * @return list<array{id: int, title: string, from: int, to: int}>
     */
    private function spans(Habit $habit, int $start): array
    {
        $spans = [];
        $current = $habit;
        $cursor = $start;

        for ($depth = 0; $depth < Habit::MaxChainDepth; $depth++) {
            $minutes = $current->durationMinutes() ?? DayPlan::AssumedMinutes;

            $spans[] = [
                'id' => $current->id,
                'title' => $current->title,
                'from' => $cursor,
                'to' => $cursor + $minutes,
            ];
            $cursor += $minutes;

            $next = $current->chainedHabits()
                ->whereNull('graduated_at')
                ->orderBy('position')
                ->first();

            if ($next === null) {
                break;
            }

            $current = $next;
        }

        return $spans;
    }

    /**
     * Die anderen Gewohnheiten dieses Tages — ohne die verschobene und ohne
     * das, was an ihr hängt.
     *
     * Die Nachfolger fehlen, weil sie mitrutschen: Sie mitzuzählen hieße, die
     * Gewohnheit gegen ihren eigenen Anhang zu prüfen.
     *
     * @return Collection<int, Habit>
     */
    private function othersOn(User $user, Habit $habit, Carbon $date): Collection
    {
        $moving = array_column($this->spans($habit, 0), 'id');

        $habits = $user->habits()
            ->active()
            ->with(['chainedTo.chainedTo', 'dayShifts' => fn ($query) => $query->whereDate('shifted_on', $date)])
            ->get();

        $habits->each(fn (Habit $other) => $other->setRelation('user', $user));

        return $habits
            ->filter(fn (Habit $other): bool => $other->isScheduledOn($date))
            ->reject(fn (Habit $other): bool => in_array($other->id, $moving, strict: true))
            ->values();
    }

    /**
     * Der nächste Tag mit diesem Wochentag, heute eingeschlossen.
     *
     * Geprüft wird an einem konkreten Datum, weil der Schlafrahmen und die
     * Ausnahmen daran hängen — „montags" allein hat keinen Rahmen.
     */
    private function nextWeekday(int $weekday): Carbon
    {
        $day = Carbon::today();

        for ($step = 0; $step < 7; $step++) {
            if ($day->dayOfWeekIso === $weekday) {
                return $day;
            }

            $day->addDay();
        }

        return $day;
    }

    /**
     * „montags", „heute" — je nachdem, wie weit der Tag weg ist.
     */
    private function weekdayLabel(Carbon $date): string
    {
        if ($date->isSameDay(Carbon::today())) {
            return 'heute';
        }

        $localised = $date->copy();
        $localised->locale('de');

        return mb_strtolower($localised->isoFormat('dddd')).'s';
    }
}
