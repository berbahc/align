<?php

namespace App\Http\Controllers;

use App\Ai\Agents\SuggestDayOrder;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\SlotConflict;
use App\Support\Timetable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

/**
 * Den ganzen Tag neu ordnen — vorgeschlagen von der KI, entschieden vom Nutzer.
 *
 * Die Einzelanpassung verschiebt eine Gewohnheit; hier geht es um die Frage
 * danach: Wie liegt der Tag insgesamt? Sie stellt sich, sobald sich etwas
 * geändert hat — eine längere Dauer, ein anderer Schlafrhythmus, eine neue
 * Gewohnheit.
 *
 * Zwei Schritte wie überall bei der KI: erst zeigen, dann übernehmen. Der
 * Vorschlag legt alle Gewohnheiten auf feste Uhrzeiten — das ist der Punkt am
 * Time-Blocking: Eine Reihenfolge, die niemand ausrechnen kann, ist keine.
 */
class DayOrderController extends Controller
{
    /**
     * Wie wenige Gewohnheiten sich zu ordnen lohnen.
     *
     * Bei einer gibt es keine Reihenfolge, und bei zweien ist sie in einem
     * Blick zu sehen — die KI dafür zu fragen wäre Aufwand ohne Ertrag.
     */
    private const int MinimumHabits = 2;

    /**
     * Der Vorschlag für einen Tag.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = isset($validated['date'])
            ? Carbon::parse($validated['date'])->startOfDay()
            : Carbon::today();

        $due = $this->habitsOn($request->user(), $date);

        if ($due->count() < self::MinimumHabits) {
            // 409 statt 422: Das ist kein Formularfehler, sondern der Zustand
            // des Tages — und der Unterschied ist nicht bloß Semantik. Inertia
            // behandelt **jede** 422 als Validierungsantwort, schickt sie an
            // `onError` und ruft `onHttpException` nie auf. Der Satz hier kam
            // deshalb nirgends an: Das Sheet blieb leer stehen, mit einem
            // aktiven „Übernehmen" über nichts.
            return response()->json([
                'message' => 'Zum Ordnen braucht es mindestens zwei Gewohnheiten an einem Tag.',
            ], 409);
        }

        $busy = Timetable::for($request->user())->blocksOn($date);

        $plan = DayPlan::for($due, $date->dayOfWeekIso, $request->user()->sleepWindowsOn($date), $date, $busy);
        $frame = $plan->frame();

        // Passt der Tag überhaupt? Die Summe aller Dauern plus die Luft
        // dazwischen muss in den wachen Teil passen — abzüglich dessen, was
        // ohnehin belegt ist. Sonst gibt es keine Ordnung, sondern zu viel für
        // einen Tag. Das rechnet der Server, nicht die KI: Eine Absage aus
        // einem Modell wäre eine Meinung, diese hier ist eine Tatsache.
        $needed = $due->sum(fn (Habit $habit): int => $habit->durationMinutes() ?? 0)
            + (($due->count() - 1) * DayPlan::BreatherMinutes);

        $available = $frame['to'] - $frame['from'] - $this->busyMinutesWithin($busy, $frame);

        if ($needed > $available) {
            return response()->json([
                'message' => sprintf(
                    'Deine Gewohnheiten brauchen zusammen %d Minuten — dein Tag hat zwischen %s und %s nur %d frei.',
                    $needed,
                    DayPlan::toTime($frame['from']),
                    DayPlan::toTime($frame['to']),
                    max(0, $available),
                ),
            ], 409);
        }

        // Die App-Locale ist nicht deutsch, die Oberfläche schon — dasselbe
        // Muster wie im CalendarController.
        $localised = $date->copy();
        $localised->locale('de');

        /** @var list<array{id: int, title: string, minutes: int, anchor: string}> $habits */
        $habits = $due->map(fn (Habit $habit): array => [
            'id' => $habit->id,
            'title' => $habit->title,
            'minutes' => $habit->durationMinutes() ?? 0,
            'anchor' => $habit->scheduleLabel(),
        ])->values()->all();

        try {
            $suggestion = (new SuggestDayOrder(
                weekdayName: $localised->isoFormat('dddd'),
                frame: $frame,
                habits: $habits,
                // Der Stundenplan als Belegung: Ohne ihn legte die Ordnung
                // eine Gewohnheit mitten in eine Vorlesung, und der Kalender
                // wiese sie beim Übernehmen wieder ab.
                busy: array_map(fn (array $block): array => [
                    'title' => $block['title'],
                    'from' => $block['from'],
                    'to' => $block['to'],
                ], $busy),
            ))->order();
        } catch (Throwable $exception) {
            Log::warning('Vorschlag für die Tagesordnung fehlgeschlagen.', [
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Der Vorschlag lässt sich gerade nicht laden.',
            ], 503);
        }

        return response()->json([
            'reason' => $suggestion['reason'],
            'order' => array_map(fn (array $row): array => [
                'id' => $row['id'],
                'title' => $row['title'],
                'time' => $row['time'],
                'timeRange' => $row['time'].' – '.DayPlan::toTime(
                    DayPlan::toMinutes($row['time']) + $row['minutes'],
                ),
            ], $suggestion['order']),
        ]);
    }

    /**
     * Die vorgeschlagene Ordnung übernehmen.
     *
     * Alle betroffenen Gewohnheiten bekommen eine feste Uhrzeit an den Tagen,
     * an denen sie ohnehin anstehen. Eine Situation geht dabei verloren — das
     * ist der Preis einer ausgerechneten Reihenfolge und steht so auch im
     * Vorschlag.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'order' => ['required', 'array', 'min:1'],
            'order.*.id' => ['required', 'integer'],
            'order.*.time' => ['required', 'date_format:H:i'],
        ]);

        $date = Carbon::parse($validated['date'])->startOfDay();
        $due = $this->habitsOn($request->user(), $date)->keyBy('id');

        $this->guard($request->user(), $date, $validated['order'], $due);

        foreach ($validated['order'] as $row) {
            $habit = $due->get((int) $row['id']);

            if ($habit === null) {
                continue;
            }

            // Die Tage bleiben, wie sie waren: Umgeordnet wird der Tag, nicht
            // die Woche. Eine Gewohnheit ohne eigene Tage läuft täglich.
            $habit->update([
                'schedule_type' => ScheduleType::Fixed,
                'scheduled_time' => $row['time'],
                'scheduled_days' => $habit->activeWeekdays() ?: Habit::EveryDay,
                'trigger_situation' => null,
                'chained_to_habit_id' => null,
            ]);
            $habit->takeAPlace();
        }

        Inertia::flash('dayReordered', ['count' => count($validated['order'])]);

        return back();
    }

    /**
     * Weist ab, was sich beim Übernehmen überschneiden würde.
     *
     * Der Vorschlag wird geprüft, bevor er gezeigt wird — hier wird geprüft,
     * was ankommt. Zwischen beidem liegt eine Entscheidung, und in der Zeit
     * kann ein Kurs dazugekommen sein; und eine zusammengesetzte Anfrage kommt
     * ohnehin nie durch einen Vorschlag.
     *
     * Zwei Vergleiche, weil es zwei Arten von Nachbarn gibt: die Blöcke, die
     * der Tag ohnehin trägt — Kurse und Gewohnheiten, die nicht mitgeordnet
     * werden —, und die geordneten untereinander.
     *
     * @param  list<array{id: int|string, time: string}>  $order
     * @param  Collection<int, Habit>  $due
     */
    private function guard(User $user, Carbon $date, array $order, Collection $due): void
    {
        $spans = [];

        foreach ($order as $row) {
            $habit = $due->get((int) $row['id']);

            if ($habit === null) {
                continue;
            }

            $from = DayPlan::toMinutes($row['time']);

            $spans[] = [
                'id' => $habit->id,
                'title' => $habit->title,
                'from' => $from,
                'to' => $from + ($habit->durationMinutes() ?? DayPlan::AssumedMinutes),
            ];
        }

        $conflict = SlotConflict::find(
            $user,
            $spans,
            [$date->dayOfWeekIso],
            array_column($spans, 'id'),
        );

        if ($conflict !== null) {
            throw ValidationException::withMessages([
                'order' => SlotConflict::message(
                    $conflict['block'],
                    $conflict['date'],
                    Timetable::isCourseBlock($conflict['block'])
                        ? 'Die Ordnung lässt sich so nicht übernehmen — der Kurs rückt nicht.'
                        : 'Die Ordnung lässt sich so nicht übernehmen.',
                ),
            ]);
        }

        $this->assertOrderDoesNotOverlapItself($spans);
    }

    /**
     * Zwei geordnete Gewohnheiten zur selben Zeit sind keine Ordnung.
     *
     * {@see SlotConflict} kann das nicht sehen: Dort zählen die geordneten
     * bewusst nicht mit, weil sie sich ja gerade bewegen.
     *
     * @param  list<array{id: int, title: string, from: int, to: int}>  $spans
     */
    private function assertOrderDoesNotOverlapItself(array $spans): void
    {
        usort($spans, fn (array $a, array $b): int => $a['from'] <=> $b['from']);

        foreach ($spans as $index => $span) {
            $next = $spans[$index + 1] ?? null;

            if ($next !== null && $next['from'] < $span['to']) {
                throw ValidationException::withMessages([
                    'order' => sprintf(
                        '„%s" und „%s" lägen übereinander. Die Ordnung lässt sich so nicht übernehmen.',
                        $span['title'],
                        $next['title'],
                    ),
                ]);
            }
        }
    }

    /**
     * Wie viele Minuten des wachen Tages schon vergeben sind.
     *
     * Nur der Teil innerhalb des Rahmens zählt: Eine Vorlesung, die vor dem
     * Aufstehen läge, nähme dem Tag nichts weg, den er hätte.
     *
     * @param  list<array{id: int, title: string, from: int, to: int}>  $busy
     * @param  array{from: int, to: int}  $frame
     */
    private function busyMinutesWithin(array $busy, array $frame): int
    {
        return array_sum(array_map(
            fn (array $block): int => max(
                0,
                min($block['to'], $frame['to']) - max($block['from'], $frame['from']),
            ),
            $busy,
        ));
    }

    /**
     * Die Gewohnheiten, die an diesem Tag anstehen — mit geladenem Nutzer.
     *
     * @return Collection<int, Habit>
     */
    private function habitsOn(User $user, Carbon $date): Collection
    {
        $habits = $user->habits()->active()->with('chainedTo')->orderBy('position')->get();
        $habits->each(fn (Habit $habit) => $habit->setRelation('user', $user));

        // Dieselbe Frage wie im Kalender: **steht sie an diesem Tag wirklich
        // an?** `isScheduledOn()` allein reicht dafür nicht — „nach der
        // Vorlesung" gilt an jedem Wochentag, aber ohne Vorlesung gibt es den
        // Auslöser nicht. Der Tag zeigte die Gewohnheit deshalb gar nicht, und
        // die Neuordnung plante sie trotzdem ein: ein Samstagstermin für etwas,
        // das samstags nicht stattfindet — und mit „Übernehmen" wäre daraus
        // eine feste Uhrzeit geworden.
        //
        // `isDueOn()` fragt alle drei Bedingungen auf einmal, den Parkvermerk
        // eingeschlossen: Was keinen Platz hat, bekommt ihn auf dem eigenen Weg
        // — und nicht nebenbei mit einer Uhrzeit, die den Vermerk stehen ließe.
        return $habits
            ->filter(fn (Habit $habit): bool => $habit->isDueOn($date))
            ->values();
    }
}
