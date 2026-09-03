<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eine Gewohnheit, die an genau einem Tag woanders liegt.
     *
     * Bis hierher hatte eine Gewohnheit eine Uhrzeit und die galt an allen
     * ihren Tagen. Für eine Verabredung reicht das nicht: Wer am Mittwoch um
     * 7:30 mitlaufen will, aber selbst um 7:30 liest, müsste sein Lesen sonst
     * für immer verschieben — wegen eines einzigen Tages.
     *
     * Deshalb eine Ausnahme mit Datum: Sie gilt an diesem einen Tag, danach
     * liegt die Gewohnheit wieder, wo sie lag. Kein zweiter Plan, keine
     * zweite Wahrheit — nur eine Uhrzeit, die für ein Datum die andere
     * überschreibt.
     *
     * Was diese Tabelle bewusst NICHT hat:
     * - keinen Grund und keinen Verweis auf die Verabredung: Warum jemand
     *   seinen Tag umstellt, geht die App nichts an (community_feature3.md §9)
     * - kein Verschieben ohne Uhrzeit: Eine Situation hat keinen Zeitpunkt,
     *   den man verrücken könnte
     */
    public function up(): void
    {
        Schema::create('habit_day_shifts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('habit_id')->constrained()->cascadeOnDelete();

            // Der eine Tag, für den die Ausnahme gilt.
            $table->date('shifted_on');

            // Wohin sie an diesem Tag rückt.
            $table->time('scheduled_time');

            $table->timestamps();

            // Zwei Uhrzeiten für denselben Tag wären zwei Pläne. Das Setzen
            // ersetzt deshalb, es legt nicht daneben.
            $table->unique(['habit_id', 'shifted_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_day_shifts');
    }
};
