<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Die drei Situationen, die auf Annahmen ruhten, bekommen eine Uhrzeit.
     *
     * „Nach dem Frühstück", „nach dem Mittagessen" und „wenn ich nach Hause
     * komme" gibt es nicht mehr zu wählen: Die App konnte sie nirgends
     * hinlegen, ohne zu raten — 45 Minuten nach dem Aufstehen, um eins, um
     * fünf. Für niemanden ist das richtig, und ein Kalender, der eine
     * Gewohnheit an eine erfundene Uhrzeit legt, ist unzuverlässig an genau der
     * Stelle, an der er verlässlich sein müsste.
     *
     * Wer eine davon gewählt hat, verliert sie hier — aber nicht seinen Platz
     * im Tag. Die Gewohnheit bekommt die Uhrzeit, an der sie ohnehin
     * einsortiert wurde, und wird damit zu dem, was sie in Wahrheit war: ein
     * fester Zeitpunkt, nur ohne Zahl daran.
     *
     * Täglich, weil eine situative Gewohnheit an keinen Wochentag gebunden war.
     *
     * Kein `down()` mit Inhalt: Die Situationen sind weg, und eine Uhrzeit in
     * einen Moment zurückzuverwandeln hieße, wieder zu raten.
     */
    public function up(): void
    {
        $times = [
            'nach dem Frühstück' => '08:00',
            'nach dem Mittagessen' => '13:00',
            'wenn ich nach Hause komme' => '17:00',
        ];

        foreach ($times as $situation => $time) {
            DB::table('habits')
                ->where('schedule_type', 'dynamic')
                ->where('trigger_situation', $situation)
                ->update([
                    'schedule_type' => 'fixed',
                    'scheduled_time' => $time,
                    'scheduled_days' => json_encode([1, 2, 3, 4, 5, 6, 7]),
                    'trigger_situation' => null,
                ]);
        }

        // Was sonst noch in der Spalte stand, war selbst getippt und lässt sich
        // erst recht nicht verorten. Es bekommt die Stunde, auf die der
        // Kalender es ohnehin gelegt hat: die Mitte des Tages.
        DB::table('habits')
            ->where('schedule_type', 'dynamic')
            ->whereNotNull('trigger_situation')
            ->whereNotIn('trigger_situation', ['nach dem Aufstehen', 'nach der Vorlesung', 'vor dem Schlafengehen'])
            ->update([
                'schedule_type' => 'fixed',
                'scheduled_time' => '12:00',
                'scheduled_days' => json_encode([1, 2, 3, 4, 5, 6, 7]),
                'trigger_situation' => null,
            ]);
    }

    public function down(): void {}
};
