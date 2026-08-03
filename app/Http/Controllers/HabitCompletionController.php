<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class HabitCompletionController extends Controller
{
    /**
     * Hakt die Gewohnheit für heute ab.
     *
     * `firstOrCreate` statt `create`: ein Doppelklick oder ein doppelt
     * abgeschickter Request darf nicht am eindeutigen Index scheitern.
     */
    public function store(Habit $habit): RedirectResponse
    {
        Gate::authorize('complete', $habit);

        $habit->completions()->firstOrCreate(
            ['completed_on' => Carbon::today()],
            ['completed_at' => now()],
        );

        return back();
    }

    /**
     * Nimmt das Abhaken für heute zurück.
     *
     * Rückgängig machen muss möglich sein — ein Fehlgriff darf keinen
     * Zustand erzeugen, den man nicht mehr los wird. progress-tracking.md:
     * die App bestraft nichts, also auch keinen Fehlklick.
     */
    public function destroy(Habit $habit): RedirectResponse
    {
        Gate::authorize('complete', $habit);

        $habit->completions()
            ->whereDate('completed_on', Carbon::today())
            ->delete();

        return back();
    }
}
