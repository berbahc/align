<?php

use App\Support\DayPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Die Ausnahme für einen einzelnen Tag.
     *
     * „Heute mache ich das später" ist keine Planänderung. Wer einen Block im
     * Kalender verschiebt, meint meistens genau diesen Tag — und eine App, die
     * daraus eine dauerhafte Uhrzeit macht, hat nicht zugehört. Eine Zeile hier
     * gilt für ein Datum und verfällt danach von selbst.
     *
     * `start_minute` statt einer `time`-Spalte: Bei einer Schlafenszeit nach
     * Mitternacht reicht der Rahmen eines Tages über 1440 hinaus
     * ({@see DayPlan::frame()}). Eine Uhrzeit bräche dort auf 00:10
     * um und verlöre, dass sie zum Abend dieses Tages gehört. Das Raster
     * rechnet ohnehin in Minuten.
     *
     * Wie bei {@see habit_completions} ist die Abwesenheit einer Zeile der
     * Normalfall: kein Eintrag heißt, es gilt der reguläre Zeitpunkt.
     */
    public function up(): void
    {
        Schema::create('habit_day_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habit_id')->constrained()->cascadeOnDelete();
            $table->date('shifted_on');
            $table->unsignedSmallInteger('start_minute');
            $table->timestamps();

            // Ein Tag, eine Ausnahme — ein zweiter Eintrag wäre zwei Zeiten
            // für dieselbe Gewohnheit am selben Tag.
            $table->unique(['habit_id', 'shifted_on']);
            $table->index('shifted_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_day_shifts');
    }
};
