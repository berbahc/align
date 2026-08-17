<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class HabitCompletionController extends Controller
{
    /**
     * Hakt die Gewohnheit ab — heute oder an einem der letzten Tage.
     *
     * `firstOrCreate` statt `create`: ein Doppelklick oder ein doppelt
     * abgeschickter Request darf nicht am eindeutigen Index scheitern.
     */
    public function store(Request $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('complete', $habit);

        $date = $this->completionDate($request, $habit);

        $habit->completions()->firstOrCreate(
            ['completed_on' => $date],
            ['completed_at' => $date->isToday() ? now() : $date->copy()->endOfDay()],
        );

        return back();
    }

    /**
     * Nimmt das Abhaken zurück.
     *
     * Rückgängig machen muss möglich sein — ein Fehlgriff darf keinen
     * Zustand erzeugen, den man nicht mehr los wird. progress-tracking.md:
     * die App bestraft nichts, also auch keinen Fehlklick.
     */
    public function destroy(Request $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('complete', $habit);

        $habit->completions()
            ->whereDate('completed_on', $this->completionDate($request, $habit))
            ->delete();

        return back();
    }

    /**
     * Der Tag, um den es geht. Ohne Angabe ist es heute.
     *
     * Nachtragen ist auf das Fenster begrenzt, das der Wochenstreifen zeigt.
     * Weiter zurück wäre kein Nachtragen mehr, sondern das Erfinden einer
     * Vergangenheit — und die Konsistenzrate soll etwas messen.
     *
     * Tage, an denen die Gewohnheit nicht vorgesehen ist, werden abgewiesen:
     * sie zählen nicht in den Nenner der Rate, eine Erfüllung dort würde sie
     * über 100 % treiben.
     */
    private function completionDate(Request $request, Habit $habit): Carbon
    {
        if (! $request->has('completed_on')) {
            return Carbon::today();
        }

        $validated = $request->validate([
            'completed_on' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:'.Carbon::today()->toDateString(),
                'after_or_equal:'.Carbon::today()->subDays(Habit::WeekOverviewDays - 1)->toDateString(),
            ],
        ]);

        $date = Carbon::parse($validated['completed_on'])->startOfDay();

        // `isAvailableOn` statt `isScheduledOn`: Eine Mo–Fr-Gewohnheit war
        // samstags nicht vorgesehen und lässt sich dort nicht nachtragen — was
        // sich ergibt, war an keinem Tag vorgesehen, kann aber an jedem
        // vorgekommen sein.
        if (! $habit->isAvailableOn($date)) {
            throw ValidationException::withMessages([
                'completed_on' => 'An diesem Tag war die Gewohnheit nicht vorgesehen.',
            ]);
        }

        return $date;
    }
}
