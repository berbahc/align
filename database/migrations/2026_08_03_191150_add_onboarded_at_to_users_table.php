<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wird gesetzt, sobald das Onboarding abgeschlossen ODER übersprungen wurde.
     *
     * Beides landet im selben Feld: wer abbricht, soll nicht bei jedem Aufruf
     * erneut in den Ablauf gedrängt werden. Das entspricht der Grundhaltung des
     * Konzepts — die App fordert nichts ein.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('onboarded_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarded_at');
        });
    }
};
