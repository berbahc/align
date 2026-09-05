<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Die gefragte Seite hakt ihre Verabredung ab.
 *
 * Eigener Weg und nicht {@see HabitCompletionController}, weil es eine andere
 * Sache ist: Dort wird eine Gewohnheit erfüllt, hier eine Zusage eingelöst.
 * Wer gefragt wurde, führt die Gewohnheit nicht — ein Haken an ihr hätte die
 * Erfüllung der fragenden Person gemeldet.
 *
 * Der Haken bleibt bei der gefragten Person: Er zählt nicht in die
 * Konsistenzrate der fremden Gewohnheit, und die fragende Seite sieht ihn
 * nicht (community_feature3.md §6).
 */
class AppointmentCompletionController extends Controller
{
    /**
     * Abgehakt.
     *
     * Ohne Prüfung auf einen bestehenden Haken: Zweimal denselben Zeitpunkt zu
     * schreiben ist kein Fehler, und ein Doppelklick soll nicht abgewiesen
     * werden.
     */
    public function store(Appointment $appointment): RedirectResponse
    {
        Gate::authorize('complete', $appointment);

        $day = Carbon::parse($appointment->scheduled_for)->startOfDay();

        $appointment->completed_at = $day->isToday() ? Carbon::now() : $day->endOfDay();
        $appointment->save();

        return back();
    }

    /**
     * Zurückgenommen.
     *
     * Ein Fehlgriff darf keinen Zustand erzeugen, den man nicht mehr los wird
     * — dieselbe Begründung wie beim Abhaken einer Gewohnheit.
     */
    public function destroy(Appointment $appointment): RedirectResponse
    {
        Gate::authorize('complete', $appointment);

        $appointment->completed_at = null;
        $appointment->save();

        return back();
    }
}
