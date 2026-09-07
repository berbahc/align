<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Ob die Serie einer Gewohnheit auf der Übersicht steht.
 *
 * Zwei Wege für einen Zustand, nicht zwei Handlungen mit eigenen Verben —
 * dieselbe Form wie {@see HabitGraduationController}: Das × auf der Karte
 * nimmt sie weg, der Eintrag im ⋯-Menü der Gewohnheit holt sie zurück.
 */
class HabitStreakCardController extends Controller
{
    /**
     * Nimmt die Karte von der Übersicht.
     *
     * Die Serie selbst bleibt unberührt: Sie läuft weiter, wird gezählt und
     * kann jederzeit wieder gezeigt werden. Weggenommen wird die Anzeige,
     * nicht der Fortschritt.
     */
    public function destroy(Request $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('update', $habit);

        if ($habit->streak_hidden_at === null) {
            $habit->forceFill(['streak_hidden_at' => Carbon::now()])->save();
        }

        return back();
    }

    /** Holt sie zurück. */
    public function store(Request $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('update', $habit);

        if ($habit->streak_hidden_at !== null) {
            $habit->forceFill(['streak_hidden_at' => null])->save();
        }

        return back();
    }
}
