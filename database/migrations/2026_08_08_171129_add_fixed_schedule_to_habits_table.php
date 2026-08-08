<?php

use App\Enums\ScheduleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zweiter Weg, eine Gewohnheit im Tag zu verankern: die feste Uhrzeit.
     *
     * time-blocking.md begründet die Situation als Standard und bleibt dabei —
     * eine Situation löst Verhalten von selbst aus. Für Gewohnheiten, die real
     * an einem Zeitpunkt hängen (Kurs, Schlafenszeit), war die Situation aber
     * eine erzwungene Umschreibung. Deshalb: zwei Wege, einer voreingestellt.
     *
     * `trigger_situation` wird dadurch optional — bei fester Uhrzeit gibt es
     * keine Situation, und eine leere Zeichenkette wäre eine Lüge im Schema.
     */
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->string('schedule_type')
                ->default(ScheduleType::Dynamic->value)
                ->after('title');

            $table->time('scheduled_time')->nullable()->after('trigger_situation');

            // ISO-Wochentage (1 = Montag … 7 = Sonntag), passend zu Carbons
            // `dayOfWeekIso`. Nur bei fester Uhrzeit gesetzt; dynamische
            // Gewohnheiten gelten an jedem Tag.
            $table->json('scheduled_days')->nullable()->after('scheduled_time');

            $table->string('trigger_situation')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn(['schedule_type', 'scheduled_time', 'scheduled_days']);
            $table->string('trigger_situation')->nullable(false)->change();
        });
    }
};
