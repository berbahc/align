<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Was an einem einzelnen Datum anders ist als jede Woche.
     *
     * Dasselbe Muster wie `habit_day_shifts`: eine Ausnahme mit Datum, die an
     * diesem einen Tag gilt und danach nichts mehr bedeutet. Nur reicht eine
     * Uhrzeit hier nicht — ein Nachholtermin liegt an einem anderen Tag, nicht
     * bloß zu einer anderen Stunde. Deshalb zwei Zeiten, und beide dürfen
     * fehlen:
     *
     * | Zeiten         | Wochentag von `on_date` | Bedeutung          |
     * |----------------|-------------------------|--------------------|
     * | beide null     | = `courses.weekday`     | entfällt           |
     * | beide gesetzt  | = `courses.weekday`     | an dem Tag anders  |
     * | beide gesetzt  | ≠ `courses.weekday`     | Ersatztermin       |
     * | beide null     | ≠ `courses.weekday`     | ungültig           |
     *
     * Ein Termin von Montag auf Dienstag zu schieben sind damit zwei Zeilen —
     * der Ausfall am Montag und der Ersatz am Dienstag. Der eindeutige
     * Schlüssel lässt beide zu, weil sie verschiedene Daten tragen. Die
     * Oberfläche zeigt es trotzdem als eine Handlung.
     *
     * Was diese Tabelle bewusst NICHT hat:
     * - keinen Grund: Warum eine Vorlesung ausfällt, geht die App nichts an
     * - keine Wiederholung: Eine Ausnahme, die sich wiederholt, ist keine
     *   Ausnahme mehr, sondern ein anderer Kurs
     */
    public function up(): void
    {
        Schema::create('course_exceptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            // Der eine Tag, für den die Ausnahme gilt.
            $table->date('on_date');

            // Beide null heißt: An diesem Tag findet nichts statt.
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();

            $table->timestamps();

            // Zwei Ausnahmen für denselben Tag wären zwei Antworten auf eine
            // Frage. Das Setzen ersetzt deshalb, es legt nicht daneben.
            $table->unique(['course_id', 'on_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_exceptions');
    }
};
