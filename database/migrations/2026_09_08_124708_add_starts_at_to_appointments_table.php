<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Die Verabredung bekommt eine eigene Uhrzeit.
 *
 * Bis hierher lieh sie sich den Anker der fragenden Gewohnheit — und ein Anker
 * wie „nach dem Aufstehen" bedeutet bei jedem etwas anderes. Berkay steht um
 * sieben auf, Aylin frühstückt um neun: Beide Kalender zeichneten korrekt ihren
 * eigenen Tag, und in den beiden Tagen stand dieselbe Verabredung an zwei
 * verschiedenen Stellen. Zwei Zeiten für einen Morgen sind eine zu viel.
 *
 * Beim Vorschlagen wird die Stelle im Tag der fragenden Person einmal zu einer
 * echten Uhrzeit und bleibt es. Festgenagelt und nicht jedes Mal neu gerechnet:
 * Sonst wanderte eine längst zugesagte Verabredung mit, wenn die fragende
 * Person später ihren Wecker verstellt — im Kalender der anderen, ohne dass sie
 * davon erführe.
 *
 * Nullable für die Zeilen, die es vorher schon gab: Für sie gilt weiter die
 * alte Rechnung ({@see Appointment::startMinute()}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->time('starts_at')->nullable()->after('scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('starts_at');
        });
    }
};
