<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProposeAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentNotice;
use App\Models\Habit;
use App\Support\AppointmentFit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

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
     *
     * Zugesagt wird nur in einen freien Platz. Wer zur selben Zeit schon etwas
     * vorhat, sieht das auf der Karte und kann seine eigene Gewohnheit für
     * diesen einen Tag verschieben; solange er das nicht tut, wäre die Zusage
     * eine Doppelbuchung. Der Riegel steht hier und nicht nur in der
     * Oberfläche: Ein Knopf, der nichts tut, ist keine Regel.
     */
    public function update(Appointment $appointment, Request $request): RedirectResponse
    {
        Gate::authorize('accept', $appointment);

        $conflict = AppointmentFit::conflict($appointment, $request->user());

        if ($conflict !== null) {
            throw ValidationException::withMessages([
                'appointment' => sprintf(
                    'Um diese Zeit läuft bei dir schon „%s". Verschiebe sie für diesen Tag, dann kannst du zusagen.',
                    $conflict->habit()->title,
                ),
            ]);
        }

        $appointment->accepted_at = Carbon::now();
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
