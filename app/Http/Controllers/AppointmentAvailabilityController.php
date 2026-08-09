<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppointmentAvailabilityController extends Controller
{
    /**
     * Verabredungen ganz abstellen — Screen A5.
     *
     * Die Umfrage-Auswertung §8 verlangt, dass Community vollständig
     * abschaltbar ist, „ohne dass die App sich unvollständig anfühlt".
     * Bestehende Freundschaften bleiben deshalb erhalten: Wer wieder
     * einschaltet, steht nicht vor einer leeren Liste.
     *
     * Offene Anfragen verfallen still, weil sie nicht mehr angezeigt werden —
     * kein Hinweis an die andere Seite, kein Nachfassen (community_feature3.md
     * §5, Ngocanh: einseitiges Motivieren demotiviert beide).
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->appointments_enabled = $validated['enabled'];
        $user->save();

        return back();
    }
}
