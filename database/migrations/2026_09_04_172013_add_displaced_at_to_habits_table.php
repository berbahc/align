<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eine Gewohnheit, die ihren Platz verloren hat, aber nicht sich selbst.
     *
     * Alle sechs Monate kommt ein neuer Stundenplan, und seine Kurse landen
     * dort, wo längst Gewohnheiten laufen. Bis hierher wehrte sich die App
     * dagegen: Der Kurs wurde abgewiesen, solange dort etwas lag. Für den
     * Einzelfall war das richtig — zweimal im Jahr ist es falsch herum, denn
     * dann müsste man erst von Hand jede Gewohnheit wegräumen, um seinen
     * eigenen Stundenplan eintragen zu dürfen.
     *
     * Jetzt gewinnt der Kurs, und die Gewohnheit wird geparkt: Sie behält ihre
     * Uhrzeit als Erinnerung daran, wann sie lief, hat aber keine Stelle mehr
     * im Tag. Damit liegt weiterhin nichts übereinander, und trotzdem geht
     * nichts verloren — was die App aufgebaut hat, ist die Routine, und die
     * darf ein Semesterwechsel nicht kosten.
     *
     * Ein Zeitstempel und kein Schalter, wie bei `committed_at` und
     * `graduated_at`: Wann etwas verdrängt wurde, ist eine Auskunft; dass es
     * verdrängt wurde, ergibt sich daraus.
     *
     * Was diese Spalte bewusst NICHT hat: keinen Verweis auf den Kurs, der
     * verdrängt hat. Was gerade an der alten Stelle liegt, lässt sich jederzeit
     * ausrechnen — ein gespeicherter Verweis wäre eine zweite Wahrheit, die
     * veraltet, sobald sich der Stundenplan wieder ändert.
     */
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->timestamp('displaced_at')->nullable()->after('graduated_at');
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn('displaced_at');
        });
    }
};
