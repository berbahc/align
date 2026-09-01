<?php

namespace App\Http\Controllers;

use App\Ai\Agents\SuggestDayOrder;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\User;
use App\Support\DayPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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
            return response()->json([
                'message' => 'Zum Ordnen braucht es mindestens zwei Gewohnheiten an einem Tag.',
            ], 422);
        }

        $plan = DayPlan::for($due, $date->dayOfWeekIso, $request->user()->sleepWindows());
        $frame = $plan->frame();

        // Passt der Tag überhaupt? Die Summe aller Dauern plus die Luft
        // dazwischen muss in den wachen Teil passen — sonst gibt es keine
        // Ordnung, sondern zu viel für einen Tag. Das rechnet der Server, nicht
        // die KI: Eine Absage aus einem Modell wäre eine Meinung, diese hier
        // ist eine Tatsache.
        $needed = $due->sum(fn (Habit $habit): int => $habit->durationMinutes() ?? 0)
            + (($due->count() - 1) * DayPlan::BreatherMinutes);

        if ($needed > $frame['to'] - $frame['from']) {
            return response()->json([
                'message' => sprintf(
                    'Deine Gewohnheiten brauchen zusammen %d Minuten — dein Tag hat zwischen %s und %s nur %d.',
                    $needed,
                    DayPlan::toTime($frame['from']),
                    DayPlan::toTime($frame['to']),
                    $frame['to'] - $frame['from'],
                ),
            ], 422);
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
                'scheduled_days' => $habit->scheduled_days ?? [1, 2, 3, 4, 5, 6, 7],
                'trigger_situation' => null,
                'chained_to_habit_id' => null,
            ]);
        }

        Inertia::flash('dayReordered', ['count' => count($validated['order'])]);

        return back();
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

        return $habits->filter(fn (Habit $habit): bool => $habit->isScheduledOn($date))->values();
    }
}
