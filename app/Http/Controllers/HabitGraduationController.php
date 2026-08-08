<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class HabitGraduationController extends Controller
{
    /**
     * Beendet eine Gewohnheit: sie verlässt die Tagesliste und gibt ihren Platz frei.
     *
     * Bewusst kein Löschen. progress-tracking.md verwirft den Streak, weil ein
     * einzelner Fehltag nicht alles zunichtemachen darf — eine Gewohnheit
     * aufzugeben und dabei jeden abgehakten Tag zu verlieren, wäre dieselbe
     * Bestrafung in größer. Die Erfüllungen bleiben, `reminder_enabled` auch:
     * beim Wiederaufnehmen steht die Gewohnheit exakt so da wie vorher. Dass
     * beendete Gewohnheiten nicht mehr erinnern, erledigt der `active()`-Filter
     * in HandleInertiaRequests von selbst.
     */
    public function store(Habit $habit): RedirectResponse
    {
        Gate::authorize('graduate', $habit);

        // Direkt gesetzt statt über `update()`: `graduated_at` steht bewusst
        // nicht in der Fillable-Liste — der Zustand gehört dem Ablauf, nicht
        // dem Formular.
        if ($habit->graduated_at === null) {
            $habit->graduated_at = now();
            $habit->save();
        }

        return back();
    }

    /**
     * Nimmt eine beendete Gewohnheit wieder auf.
     *
     * Der Platz muss frei sein — sonst stünden über den Umweg des Archivs mehr
     * als die fünf Gewohnheiten in der Liste, die StoreHabitRequest beim
     * Anlegen verhindert.
     */
    public function destroy(Request $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('graduate', $habit);

        if ($request->user()->habits()->active()->count() >= Habit::MaxActivePerUser) {
            throw ValidationException::withMessages([
                'habit' => sprintf(
                    'Du hast bereits %d aktive Gewohnheiten. Beende zuerst eine andere.',
                    Habit::MaxActivePerUser,
                ),
            ]);
        }

        $habit->graduated_at = null;
        $habit->save();

        return back();
    }
}
