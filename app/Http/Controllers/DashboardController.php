<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $today = Carbon::today();

        // Die Oberfläche ist durchgängig deutsch; die App-Locale ist es nicht,
        // deshalb wird sie hier gezielt für die Datumsausgabe gesetzt.
        $localisedToday = $today->copy();
        $localisedToday->locale('de');

        $habits = $request->user()
            ->habits()
            ->active()
            ->with(['completions' => fn (Relation $query) => $query->whereDate('completed_on', $today)])
            ->withCount(['completions as completions_last_30_days' => fn (Builder $query) => $query
                ->where('completed_on', '>=', $today->copy()->subDays(29)->startOfDay()),
            ])
            ->orderBy('position')
            ->get();

        // Die Tagesliste zeigt nur, was heute ansteht. Eine Mo–Fr-Gewohnheit
        // ist am Samstag nicht offen, sondern nicht vorgesehen — sie dennoch
        // als unerledigt zu zeigen wäre eine Forderung, die niemand erhoben hat.
        $todaysHabits = $habits->filter(
            fn (Habit $habit): bool => $habit->isScheduledOn($today),
        )->values();

        return Inertia::render('dashboard', [
            'greeting' => $this->greeting($today),
            'today' => $localisedToday->isoFormat('dddd, D. MMMM'),
            'todayProgress' => $this->todayProgress($todaysHabits),
            'consistency' => $this->consistencyRate($habits, $today),
            // Eine leere Tagesliste heißt nicht, dass es keine Gewohnheiten
            // gibt — eine Mo–Fr-Gewohnheit ist am Samstag schlicht nicht
            // vorgesehen. Ohne diese Zahl könnte die Oberfläche die beiden
            // Fälle nicht auseinanderhalten und würde am Wochenende zum
            // Anlegen auffordern, obwohl längst fünf Gewohnheiten laufen.
            'activeCount' => $habits->count(),
            'maxActive' => Habit::MaxActivePerUser,
            'habits' => $todaysHabits->map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'title' => $habit->title,
                'scheduleLabel' => $habit->scheduleLabel(),
                'behaviorType' => $habit->behavior_type->value,
                'focusMinutes' => $habit->focus_minutes,
                'completedAt' => $habit->completions->first()?->completed_at->format('H:i'),
            ])->all(),
        ]);
    }

    private function greeting(Carbon $now): string
    {
        return match (true) {
            $now->hour < 11 => 'Guten Morgen',
            $now->hour < 18 => 'Schönen Tag',
            default => 'Guten Abend',
        };
    }

    /**
     * Fortschritt des heutigen Tages.
     *
     * Das Tagesziel ist schlicht die Anzahl der aktiven Gewohnheiten — es gibt
     * keine separate Zielgröße, die man verfehlen könnte. Formuliert wird
     * immer, was erledigt ist, nie was fehlt (Designsprache §1.5).
     *
     * @param  Collection<int, Habit>  $habits
     * @return array{completed: int, total: int, percentage: int}
     */
    private function todayProgress(Collection $habits): array
    {
        $total = $habits->count();
        $completed = $habits->filter(
            fn (Habit $habit): bool => $habit->completions->isNotEmpty(),
        )->count();

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total === 0 ? 0 : (int) round($completed / $total * 100),
        ];
    }

    /**
     * Gemeinsame Konsistenzrate über alle aktiven Gewohnheiten der letzten 30 Tage.
     *
     * progress-tracking.md schreibt bewusst eine Konsistenzrate statt eines
     * Streaks vor: ein Streak bricht bei einem einzigen Fehltag zusammen,
     * obwohl einzelne Aussetzer laut Lally et al. (2010) keine messbaren
     * Langzeitkosten haben. Ohne aktive Gewohnheiten gibt es keine Rate —
     * dann zeigt die Oberfläche den leeren Zustand statt „0 %".
     *
     * Die Zahl der möglichen Tage wird pro Gewohnheit ermittelt, nicht pauschal
     * mit 30 multipliziert: eine Mo–Fr-Gewohnheit hat in dreißig Tagen rund
     * zweiundzwanzig vorgesehene Tage. Über alle Tage zu rechnen würde sie
     * dauerhaft unter 72 % halten, obwohl sie lückenlos erfüllt wurde.
     *
     * @param  Collection<int, Habit>  $habits
     */
    private function consistencyRate(Collection $habits, Carbon $today): ?int
    {
        if ($habits->isEmpty()) {
            return null;
        }

        $start = $today->copy()->subDays(29);

        $possible = $habits->sum(
            fn (Habit $habit): int => $habit->scheduledDaysBetween($start, $today),
        );

        if ($possible < 1) {
            return null;
        }

        $completed = $habits->sum('completions_last_30_days');

        return (int) round($completed / $possible * 100);
    }
}
