<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProposeAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentNotice;
use App\Models\Habit;
use App\Models\HabitDayShift;
use App\Models\User;
use App\Support\AppointmentFit;
use App\Support\DayPlan;
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

        // Die Stelle im eigenen Tag wird hier einmal zu einer echten Uhrzeit
        // und bleibt es ({@see Appointment::startMinute()}). „Nach dem
        // Aufstehen" hat keine Uhr, sondern einen Platz — und den rechnet
        // jeder aus seinem eigenen Schlafplan aus. Ohne dieses Festnageln
        // stand derselbe Morgen in zwei Kalendern an zwei Stellen.
        $habit->setRelation('user', $request->user());

        Appointment::query()->create([
            'habit_id' => $habit->id,
            'requester_id' => $request->user()->id,
            'invitee_id' => $friend->id,
            'scheduled_for' => $request->scheduledFor(),
            'starts_at' => Appointment::startTimeFor($habit, $request->scheduledFor()),
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
     *
     * Liegt ein Kurs im Weg, gibt es keinen Weg vorbei — den Satz dazu
     * formuliert {@see AppointmentFit::message()}, weil er von der Art des
     * Konflikts abhängt und nicht davon, wer fragt.
     */
    public function update(Appointment $appointment, Request $request): RedirectResponse
    {
        Gate::authorize('accept', $appointment);

        $conflict = AppointmentFit::conflict($appointment, $request->user());

        if ($conflict !== null) {
            throw ValidationException::withMessages([
                'appointment' => $conflict->message(),
            ]);
        }

        $appointment->accepted_at = Carbon::now();
        $appointment->save();

        $this->moveOwnHabitAlong($appointment, $request->user());

        return back();
    }

    /**
     * Die eigene Gewohnheit zieht für diesen einen Tag auf die gemeinsame Zeit.
     *
     * Wer selbst um neun frühstückt und um sieben mit jemandem verabredet ist,
     * frühstückt an dem Tag um sieben. Die Zeile bleibt, wo sie ist — sie
     * steht nur einmal woanders, und das sagt sie auch („nur an diesem Tag").
     *
     * Dieselbe Ausnahme, die auch das Platzmachen schreibt
     * ({@see HabitDayShiftController::store()}): eine Mechanik für „heute
     * liegt es anders", nicht zwei. Deshalb erscheint der Umzug im Kalender,
     * im Raster und in der Kollisionsprüfung, ohne dass eine davon etwas von
     * Verabredungen wissen müsste.
     *
     * Ohne Uhrzeit auf einer der beiden Seiten gibt es nichts zu verschieben:
     * Eine Situation („nach dem Aufstehen") hat keinen Zeitpunkt, und einen zu
     * erfinden hieße, eine Genauigkeit zu behaupten, die es nie gab.
     */
    private function moveOwnHabitAlong(Appointment $appointment, User $user): void
    {
        $replaced = $appointment->replacementFor($user);

        if ($replaced === null) {
            return;
        }

        $date = Carbon::parse($appointment->scheduled_for)->startOfDay();
        $together = DayPlan::toTime($appointment->startMinute());

        if ($together === $replaced->startsAt($date)?->format('H:i')) {
            return;
        }

        HabitDayShift::query()->updateOrCreate(
            ['habit_id' => $replaced->id, 'shifted_on' => $date],
            ['scheduled_time' => $together],
        );
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

        // Und der Umzug fällt mit weg: Ohne das gemeinsame Frühstück gilt
        // wieder die eigene Zeit. Nur die Ausnahme, die zu dieser Verabredung
        // gehört — wer seinen Tag von Hand umgestellt hat, behält das.
        $this->undoOwnHabitMove($appointment, $request->user());

        $appointment->delete();

        return back();
    }

    /**
     * Den Umzug zurücknehmen — der Tag gilt wieder wie geplant.
     *
     * Nur die eigene Ausnahme dieser Verabredung: Verglichen wird gegen die
     * gemeinsame Uhrzeit, damit eine von Hand gesetzte Ausnahme stehen bleibt.
     * Wer seinen Tag selbst umgestellt hat, hat das nicht wegen dieser Zusage
     * getan.
     */
    private function undoOwnHabitMove(Appointment $appointment, User $user): void
    {
        $replaced = $appointment->replacementFor($user);

        if ($replaced === null) {
            return;
        }

        $date = Carbon::parse($appointment->scheduled_for)->startOfDay();
        $together = DayPlan::toTime($appointment->startMinute());

        $replaced->dayShifts()
            ->whereDate('shifted_on', $date)
            ->where('scheduled_time', $together)
            ->delete();
    }
}
