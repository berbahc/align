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

        return Inertia::render('dashboard', [
            'greeting' => $this->greeting($today),
            'today' => $localisedToday->isoFormat('dddd, D. MMMM'),
            'consistency' => $this->consistencyRate($habits),
            'habits' => $habits->map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'title' => $habit->title,
                'triggerSituation' => $habit->trigger_situation,
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
     * Gemeinsame Konsistenzrate über alle aktiven Gewohnheiten der letzten 30 Tage.
     *
     * progress-tracking.md schreibt bewusst eine Konsistenzrate statt eines
     * Streaks vor: ein Streak bricht bei einem einzigen Fehltag zusammen,
     * obwohl einzelne Aussetzer laut Lally et al. (2010) keine messbaren
     * Langzeitkosten haben. Ohne aktive Gewohnheiten gibt es keine Rate —
     * dann zeigt die Oberfläche den leeren Zustand statt „0 %".
     *
     * @param  Collection<int, Habit>  $habits
     */
    private function consistencyRate(Collection $habits): ?int
    {
        if ($habits->isEmpty()) {
            return null;
        }

        $possible = $habits->count() * 30;
        $completed = $habits->sum('completions_last_30_days');

        return (int) round($completed / $possible * 100);
    }
}
