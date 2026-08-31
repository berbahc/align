<?php

use App\Enums\ScheduleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Gewohnheiten ohne Platz im Tag gibt es nicht mehr.
     *
     * „Treppe statt Aufzug" war der Sonderfall, der überall Ausnahmen
     * erzwang: kein Anker, keine Quote, keine Erinnerung, ein eigener Block
     * unter der Kalenderachse. Mit dem festen Katalog fällt die Form weg —
     * er enthält nur planbare Aktivitäten mit Dauer, und der `opportunistic`-
     * Fall verschwindet aus {@see ScheduleType}.
     *
     * Die Zeilen werden gelöscht, nicht umgedeutet: Eine erfundene Situation
     * oder Uhrzeit wäre eine Lüge im Schema, und ein Enum-Cast auf einen
     * Fall, den es nicht mehr gibt, würfe bei jedem Lesen. Die abgehakten
     * Tage gehen über die Fremdschlüssel-Kaskade mit.
     */
    public function up(): void
    {
        DB::table('habits')->where('schedule_type', 'opportunistic')->delete();
    }

    public function down(): void
    {
        // Gelöschte Zeilen kommen nicht zurück — es gibt nichts wiederherzustellen.
    }
};
