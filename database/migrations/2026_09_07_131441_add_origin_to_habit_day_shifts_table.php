<?php

use App\Enums\ShiftOrigin;
use App\Models\SleepDayOverride;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Woher der Umzug kommt.
     *
     * Bisher gab es nur einen Weg an diese Tabelle: die eigene Hand — im
     * Raster gezogen oder für eine Verabredung Platz gemacht. Mit dem Rahmen,
     * der einzelne Tage kennt ({@see SleepDayOverride}), kommt ein
     * zweiter dazu, und die beiden müssen sich unterscheiden lassen.
     *
     * Nicht wegen der Anzeige — im Kalender sieht ein Umzug wie der andere aus
     * —, sondern wegen des Rückwegs: Wer seine Aufstehzeit für heute wieder
     * zurücksetzt, will die mitgezogenen Züge los und die eigenen behalten.
     *
     * Alles, was schon da ist, kommt von Hand: Den anderen Weg gab es noch
     * nicht.
     */
    public function up(): void
    {
        Schema::table('habit_day_shifts', function (Blueprint $table) {
            $table->string('origin')->default(ShiftOrigin::Manual->value)->after('scheduled_time');
        });
    }

    public function down(): void
    {
        Schema::table('habit_day_shifts', function (Blueprint $table) {
            $table->dropColumn('origin');
        });
    }
};
