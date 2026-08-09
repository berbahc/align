<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProposeAppointmentRequest;
use App\Models\Appointment;
use App\Models\Habit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AppointmentController extends Controller
{
    /**
     * Eine Verabredung vorschlagen — Screen A1.
     *
     * Drei Taps, ein Ergebnis: wer, wann, fragen. Kein Kalender, keine
     * Uhrzeitwahl, keine Terminfindung (Danial: „Ich will, dass die App simpel
     * ist. Wir Menschen haben eine richtig kurze Aufmerksamkeitsspanne.").
     */
    public function store(ProposeAppointmentRequest $request, Habit $habit): RedirectResponse
    {
        // Die Berechtigung prüft ProposeAppointmentRequest::authorize() —
        // vor der Validierung, nicht danach.
        $friend = $request->friend();

        Appointment::query()->create([
            'habit_id' => $habit->id,
            'requester_id' => $request->user()->id,
            'invitee_id' => $friend->id,
            'scheduled_for' => $request->scheduledFor(),
        ]);

        return back()->with('success', sprintf('%s bekommt deine Anfrage.', $friend->name));
    }

    /**
     * Zusagen — Screen A2, „Passt mir".
     */
    public function update(Appointment $appointment): RedirectResponse
    {
        Gate::authorize('accept', $appointment);

        $appointment->accepted_at = now();
        $appointment->save();

        return back();
    }

    /**
     * Absagen, zurückziehen oder auflösen.
     *
     * Die absagende Person bleibt unsichtbar: Beim Fragenden verschwindet die
     * Verabredung, ohne Zähler und ohne Historie (§5).
     */
    public function destroy(Appointment $appointment): RedirectResponse
    {
        Gate::authorize('delete', $appointment);

        $appointment->delete();

        return back();
    }
}
