<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eine Uhrzeit je Wochentag.
 *
 * `scheduled_time` galt für alle Tage einer Gewohnheit. Das reicht für die
 * meisten, aber nicht für den Alltag, den es abbilden soll: Wer dienstags um
 * acht Vorlesung hat und donnerstags um zehn, lernt nicht an beiden Tagen zur
 * selben Zeit nach.
 *
 * Die Spalte trägt eine Abbildung `Wochentag => "HH:MM"` und ist **leer,
 * solange alle Tage dieselbe Zeit haben** — dann gilt weiter `scheduled_time`.
 * So bleibt der Regelfall eine Zahl statt sieben, und jede Zeile aus der Zeit
 * davor bedeutet unverändert dasselbe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table): void {
            $table->json('scheduled_times')->nullable()->after('scheduled_days');
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table): void {
            $table->dropColumn('scheduled_times');
        });
    }
};
