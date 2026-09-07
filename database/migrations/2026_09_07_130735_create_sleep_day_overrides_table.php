<?php

use App\Models\HabitDayShift;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Der Rahmen eines einzelnen Tages, abweichend vom Wochenplan.
     *
     * Bis hierher kannte der Schlafplan nur Wochentage. Wer einmal später
     * aufsteht, hatte deshalb nur die Wahl, den Montag für immer umzustellen
     * — wegen eines Morgens. Das ist dieselbe Not, aus der schon
     * {@see HabitDayShift} entstanden ist, eine Ebene höher:
     * nicht die Gewohnheit liegt heute woanders, sondern der Tag selbst.
     *
     * Beide Zeiten sind nullbar, weil nur die Abweichung gespeichert wird.
     * Wer nur später aufsteht, ändert nichts an seiner Schlafenszeit — und
     * eine mitgeschriebene Kopie des Wochenplans wäre eine zweite Wahrheit,
     * die beim nächsten Ändern des Plans still veraltet.
     *
     * Vergangene Zeilen bleiben stehen: Der Kalender zeigt auch alte Tage,
     * und ein Tag, an dem jemand um elf aufgestanden ist, hat um elf
     * angefangen. Ihn rückwirkend auf sieben zu ziehen wäre eine Korrektur
     * der eigenen Geschichte.
     */
    public function up(): void
    {
        Schema::create('sleep_day_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Der eine Tag, für den die Ausnahme gilt.
            $table->date('on_date');

            $table->time('wake_time')->nullable();
            $table->time('bedtime')->nullable();

            $table->timestamps();

            // Zwei Rahmen für denselben Tag wären zwei Tage. Das Setzen
            // ersetzt deshalb, es legt nicht daneben.
            $table->unique(['user_id', 'on_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sleep_day_overrides');
    }
};
