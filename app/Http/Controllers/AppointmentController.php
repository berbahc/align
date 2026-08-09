<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProposeAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentNotice;
use App\Models\Habit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Alle drei enden gleich — der Eintrag verschwindet, ohne Zähler und ohne
     * Historie (§5). Zwei von ihnen lassen aber eine Einmal-Notiz zurück, damit
     * die andere Seite nicht vor einer Lücke steht; welche, entscheidet
     * `AppointmentNotice::afterRemoval()`.
     *
     * Die absagende Person bleibt dabei unsichtbar im Sinne von §5: Es steht
     * dort, dass etwas nicht stattfindet, nirgends warum.
     */
    public function destroy(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('delete', $appointment);

        // Vor dem Löschen: Danach sind Name, Titel und Tag nicht mehr zu haben.
        AppointmentNotice::afterRemoval($appointment, $request->user());

        $appointment->delete();

        return back();
    }
}
