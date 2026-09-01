<?php

use App\Models\Habit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * „nach der Morgenvorlesung" heißt jetzt „nach der Vorlesung".
     *
     * Der alte Wortlaut war gestelzt und dazu enger als nötig — nicht jede
     * Vorlesung ist eine Morgenvorlesung.
     *
     * Der Text wandert mit, statt nur in der Vorschlagsliste ausgetauscht zu
     * werden: `trigger_situation` speichert die Situation als Zeichenkette,
     * und eine Zeile mit dem alten Wortlaut gälte fortan als selbst getippte
     * Situation — sie wäre im Picker nicht mehr markiert und sortierte sich
     * über {@see Habit::UnknownAnchorHour} mittags ein statt um elf.
     */
    public function up(): void
    {
        DB::table('habits')
            ->where('trigger_situation', 'nach der Morgenvorlesung')
            ->update(['trigger_situation' => 'nach der Vorlesung']);
    }

    public function down(): void
    {
        DB::table('habits')
            ->where('trigger_situation', 'nach der Vorlesung')
            ->update(['trigger_situation' => 'nach der Morgenvorlesung']);
    }
};
