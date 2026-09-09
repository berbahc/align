<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wird gesetzt, sobald der Auftakt einmal gelaufen ist.
     *
     * Eigenes Feld neben `onboarded_at`, weil es eine andere Frage beantwortet:
     * Der Auftakt erklärt, warum die App gleich nach Aufstehzeit und erster
     * Gewohnheit fragt — wer ihn gesehen hat, hat deshalb noch nichts
     * eingerichtet. Und wer das Onboarding überspringt, ist trotzdem mit dem
     * Film fertig; darum setzt `skip()` beide.
     *
     * Serverseitig und nicht im Browser gemerkt, weil die Onboarding-Seite ihre
     * Stufe ohnehin aus dem Serverzustand wählt: Ein Film, der nach jedem
     * Neuladen von vorn beginnt, wäre kein Auftakt, sondern eine Sperre.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('intro_seen_at')->nullable()->after('onboarded_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('intro_seen_at');
        });
    }
};
