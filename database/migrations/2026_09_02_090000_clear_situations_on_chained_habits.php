<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Eine gekettete Gewohnheit hat keine eigene Situation — auch nicht in der
     * Datenbank.
     *
     * Sie leiht sich ihre Stelle im Tag von der Gewohnheit, an der sie hängt.
     * `trigger_situation` liest bei ihr niemand: {@see Habit::scheduleLabel()}
     * fragt zuerst nach {@see ScheduleType::hasOwnAnchor()} und zeigt „nach
     * ‚Vorgänger'". Ein Wert in der Spalte wäre also unsichtbar — aber nicht
     * folgenlos: Der Moment gilt als vergeben und ist für jede andere
     * Gewohnheit gesperrt.
     *
     * Die alte Anpassungs-Strecke konnte genau das anrichten, weil sie nur
     * zwei Planungsarten kannte und `Chained` in den situativen Zweig fallen
     * ließ. Der Zweig ist repariert; hier wird aufgeräumt, was er hinterlassen
     * hat.
     *
     * Kein `down()` mit Inhalt: Der alte Zustand war ein Fehler, und einen
     * Fehler stellt man nicht wieder her.
     */
    public function up(): void
    {
        DB::table('habits')
            ->where('schedule_type', 'chained')
            ->whereNotNull('trigger_situation')
            ->update(['trigger_situation' => null]);
    }

    public function down(): void {}
};
