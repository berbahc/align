<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wann jemand die Serie dieser Gewohnheit von der Übersicht genommen hat.
     *
     * Die Übersicht zeigt bis zu drei laufende Serien. Das ist ein Angebot und
     * keine Auflage: Wer auf die eigene Zahl nicht schauen will, soll sie
     * wegnehmen können, ohne die Gewohnheit selbst anzurühren. Die Serie läuft
     * weiter, sie wird nur nicht mehr vorgezeigt — `progress-tracking.md`
     * verlangt für den Fortschritt „einen ehrlichen, nicht strafenden
     * Kontext", und dazu gehört, ihn wegklicken zu dürfen.
     *
     * Ein Zeitstempel und kein Schalter, wie bei `committed_at`,
     * `graduated_at` und `displaced_at`: Wann etwas ausgeblendet wurde, ist
     * eine Auskunft; dass es ausgeblendet ist, ergibt sich daraus.
     *
     * Je Gewohnheit und nicht je Nutzer: Das × sitzt auf einer Karte, und wer
     * eine davon wegnimmt, meint diese eine.
     */
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->timestamp('streak_hidden_at')->nullable()->after('displaced_at');
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn('streak_hidden_at');
        });
    }
};
