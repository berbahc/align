<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleType;
use App\Http\Requests\ShiftHabitDayRequest;
use App\Models\Habit;
use App\Models\HabitDayShift;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\PromiseLock;
use App\Support\SlotConflict;
use App\Support\Timetable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class HabitDayShiftController extends Controller
{
    /**
     * Wie fein sich von Hand schieben lässt.
     *
     * Eine Viertelstunde — derselbe Takt wie {@see DayPlan::BreatherMinutes}
     * und fein genug für jede Gewohnheit im Katalog. Minutengenau zu schieben
     * hieße, mit dem Finger eine Genauigkeit zu verlangen, die niemand hat.
     */
    private const int SnapMinutes = 15;

    /** Der geladene Stundenplan, damit `always()` ihn nicht siebenmal holt. */
    private ?Timetable $timetable = null;

    /**
     * Eine Gewohnheit für einen einzigen Tag woanders hinlegen.
     *
     * Der Anlass ist die Verabredung: Wer um 7:30 gefragt wird und um 7:30
     * selbst etwas vorhat, hatte bisher nur die Wahl zwischen Absagen und
     * Doppelbuchung. Der dritte Weg ist, den eigenen Tag einmal umzustellen —
     * einmal, nicht für immer. Die Gewohnheit selbst bleibt, wo sie ist.
     *
     * Ersetzt statt anzulegen: Zwei Uhrzeiten für denselben Tag wären zwei
     * Pläne, und die Tabelle lässt sie deshalb gar nicht erst zu.
     */
    public function store(ShiftHabitDayRequest $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('update', $habit);

        /** @var Carbon $date */
        $date = $request->shiftedOn();

        // Das Datum als Carbon und nicht als Zeichenkette: Der `date`-Cast legt
        // „2026-09-05 00:00:00" ab, und ein Vergleich gegen „2026-09-05" fände
        // die eigene Zeile nicht — `updateOrCreate` legte dann eine zweite an
        // und liefe in den eindeutigen Schlüssel. Dieselbe Falle wie in
        // {@see CourseExceptionController::store()}.
        HabitDayShift::query()->updateOrCreate(
            ['habit_id' => $habit->id, 'shifted_on' => $date],
            ['scheduled_time' => $request->string('scheduled_time')->toString()],
        );

        return back()->with('success', sprintf(
            '„%s" liegt an diesem Tag um %s.',
            $habit->title,
            $request->string('scheduled_time')->toString(),
        ));
    }

    /**
     * Von Hand verschieben — die Geste im Stundenraster.
     *
     * Anders als {@see store()} fragt dieser Weg hinterher, wie weit die
     * Änderung reichen soll. „Heute mache ich das später" und „ab jetzt immer
     * um zwei" sehen als Geste gleich aus und meinen völlig Verschiedenes:
     *
     * - **heute** legt dieselbe Ausnahme an wie die Verabredung. Auslöser und
     *   Kette bleiben, wie sie waren.
     * - **immer** schreibt die Uhrzeit an die Gewohnheit und macht sie damit zu
     *   einer festen. Derselbe Schritt, den {@see DayOrderController::store()}
     *   für einen ganzen Tag auf einmal tut: Auslöser und Kette fallen weg,
     *   weil eine Gewohnheit nur einen Zeitpunkt haben kann.
     *
     * Vor dem dauerhaften Umstellen wird jeder künftige Wochentag geprüft. Zwei
     * Gewohnheiten zur selben Zeit sind kein Plan, und die Antwort darauf ist
     * kein „geht nicht", sondern der Satz, der sagt, was zu tun ist.
     */
    public function move(Request $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('update', $habit);

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'start_minute' => ['required', 'integer', 'min:0', 'max:1439'],
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

        $date = Carbon::createFromFormat('!Y-m-d', $validated['date']);

        // Auch das Zurücknehmen verschiebt: Die Gewohnheit spränge an ihre
        // gewohnte Zeit, und für den Tag ist eine andere ausgemacht.
        $this->guardPromise($request->user(), $habit, [$date]);

        $habit->dayShifts()->whereDate('shifted_on', $date)->delete();

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

        // Das Datum als Carbon und nicht als Zeichenkette: Der `date`-Cast legt
        // „2026-09-05 00:00:00" ab, und ein Vergleich gegen „2026-09-05" fände
        // die eigene Zeile nicht — `updateOrCreate` legte dann eine zweite an
        // und liefe in den eindeutigen Schlüssel. Dieselbe Falle wie in
        // {@see CourseExceptionController::store()}.
        HabitDayShift::query()->updateOrCreate(
            ['habit_id' => $habit->id, 'shifted_on' => $date],
            ['scheduled_time' => DayPlan::toTime($start)],
        );
    }

    /**
     * Ab jetzt immer.
     *
     * Geprüft wird jeder Wochentag, an dem die Gewohnheit künftig läuft — das
     * ist der Fall „vielleicht liegt da in der Zukunft schon etwas".
     *
     * **Und zwar nicht nur nächste Woche.** Geprüft wurde einmal allein der
     * nächste Montag; ein Kurs, der erst mit der Vorlesungszeit beginnt, lag
     * damit außerhalb des Blickfelds. Die Gewohnheit rutschte anstandslos auf
     * zehn Uhr und stand ab Oktober neben der Vorlesung im Raster, als wäre
     * dort Platz für beides. {@see SlotConflict::datesFor()} nimmt deshalb je
     * Wochentag zwei Daten: das nächste Vorkommen und das erste im Semester.
     *
     * Die Tage bleiben, wie sie waren; eine Gewohnheit ohne eigene Tage läuft
     * täglich. Dieselbe Regel wie in {@see DayOrderController::store()}, und
     * aus demselben Grund: Umgeordnet wird der Tag, nicht die Woche.
     */
    private function always(User $user, Habit $habit, int $start): void
    {
        $days = $habit->activeWeekdays() ?: Habit::EveryDay;

        $dates = SlotConflict::datesFor($days, $this->timetable($user));

        // Dauerhaft umstellen trifft auch die Tage, für die schon etwas
        // ausgemacht ist.
        $this->guardPromise($user, $habit, $dates);

        foreach ($dates as $date) {
            $this->guard($user, $habit, $date, $start);
        }

        $habit->update([
            'schedule_type' => ScheduleType::Fixed,
            // Siehe oben: eine Uhrzeit für alle Tage.
            'scheduled_times' => null,
            'scheduled_time' => DayPlan::toTime($start),
            'scheduled_days' => $days,
            'trigger_situation' => null,
            'chained_to_habit_id' => null,
        ]);

        // Die dauerhafte Zeit hebt die Einzelfall-Regel auf: Zwei Antworten für
        // denselben Tag wären eine zu viel.
        $habit->dayShifts()->delete();
        $habit->takeAPlace();
    }

    /**
     * Der Stundenplan dieses Nutzers — einmal je Anfrage.
     *
     * `always()` prüft bis zu sieben Wochentage. Ohne dieses Merken wären das
     * sieben Ladevorgänge desselben Plans.
     */
    private function timetable(User $user): Timetable
    {
        return $this->timetable ??= Timetable::for($user);
    }

    /**
     * Weist ab, was für einen dieser Tage ausgemacht ist.
     *
     * Vor jeder anderen Prüfung: Ob an der neuen Stelle Platz wäre, ist
     * gleichgültig, wenn die alte gar nicht frei wird ({@see PromiseLock}).
     *
     * @param  list<Carbon>  $dates
     */
    private function guardPromise(User $user, Habit $habit, array $dates): void
    {
        $locked = PromiseLock::on($user, $habit, $dates);

        if ($locked !== null) {
            throw ValidationException::withMessages([
                'start_minute' => PromiseLock::message($locked['appointment'], $locked['date'], $user),
            ]);
        }
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
        $this->guardPromise($user, $habit, [$date]);

        $others = $this->othersOn($user, $habit, $date);
        $window = $user->sleepWindowOn($date);
        $plan = DayPlan::forDate($others, $date, $user->sleepWindowsOn($date), $this->timetable($user)->blocksOn($date));
        $frame = $plan->frame();

        // Die Ausnahme wird als Uhrzeit gespeichert. Alles jenseits von
        // Mitternacht läse sich beim nächsten Aufschlagen als früher Vormittag
        // — wer nach Mitternacht ins Bett geht, kann bis dorthin schieben und
        // keine Minute weiter.
        $latest = min($frame['to'], DayPlan::MinutesPerDay);

        foreach ($habit->spansFrom($start) as $span) {
            if ($span['from'] < $frame['from'] || $span['to'] > $latest) {
                throw ValidationException::withMessages([
                    'start_minute' => sprintf(
                        '%s läge dann außerhalb deines Tages (%s bis %s Uhr). Passe die Zeit an — oder deinen Schlafplan.',
                        $span['title'] === $habit->title ? 'Die Gewohnheit' : sprintf('„%s"', $span['title']),
                        $window['wakeTime'],
                        DayPlan::toTime($latest),
                    ),
                ]);
            }

            $conflict = $plan->collisionWith($span['from'], $span['to']);

            if ($conflict !== null) {
                $istKurs = Timetable::isCourseBlock($conflict);

                // Derselbe Satz wie überall sonst — nur das Ende ist hier ein
                // anderes, weil es um eine dauerhafte Uhrzeit geht. Ein Kurs
                // lässt sich nicht wegschieben; der Satz bietet das nicht an.
                throw ValidationException::withMessages([
                    'start_minute' => SlotConflict::message(
                        $conflict,
                        $date,
                        $istKurs
                            ? 'Such der Gewohnheit eine andere Zeit — der Kurs rückt nicht.'
                            : 'Verschiebe die zuerst, dann lässt sich die Zeit hier umstellen.',
                    ),
                    // Der Tag, an dem es klemmt. Beim Kurs ist er der einzige
                    // Weg weiter: Verschieben lässt er sich nicht, also muss
                    // die Gewohnheit dort eine andere Zeit bekommen — und
                    // dorthin zu kommen soll kein Suchen sein. Der Konflikt
                    // liegt oft Wochen entfernt, weil das Semester erst
                    // beginnt.
                    'conflict_date' => $date->toDateString(),
                ]);
            }
        }
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
        $moving = array_column($habit->spansFrom(0), 'id');

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
}
