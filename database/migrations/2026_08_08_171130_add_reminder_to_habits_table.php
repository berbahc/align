<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Erinnerung zehn Minuten vor der festen Uhrzeit.
     *
     * Bewusst standardmäßig aus: die App fordert nichts ein, und eine
     * Benachrichtigung ist der aufdringlichste Kanal, den sie hat. Wer sie
     * will, schaltet sie ein — einzeln oder für alle auf einmal.
     *
     * Das Flag sitzt an der Gewohnheit, nicht am Nutzer. Ein Sammelschalter
     * setzt es für alle Gewohnheiten mit fester Uhrzeit; der Zustand bleibt
     * dadurch an genau einer Stelle und kann nicht auseinanderlaufen.
     */
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->boolean('reminder_enabled')->default(false)->after('scheduled_days');
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn('reminder_enabled');
        });
    }
};
