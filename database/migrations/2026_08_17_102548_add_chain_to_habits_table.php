<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Das Domino-Prinzip als Spalte — eine Gewohnheit hängt an einer anderen.
     *
     * time-blocking.md §Domino-Prinzip beschreibt Habit Chains seit der
     * Konzeptphase, ki-assistent-designbegruendung.md zeichnet sie als dritte
     * Ankerart neben Situation und Uhrzeit. Gebaut war davon nichts: Die KI
     * durfte eine Kopplung als Freitext vorschlagen („nach dem Zähneputzen"),
     * aber die beiden Gewohnheiten wussten nichts voneinander, und die
     * gekoppelte sortierte sich mangels bekannter Situation auf Stunde 12.
     *
     * Alissa im Interview beschreibt genau das, ohne den Begriff zu kennen:
     * „Wenn ich dann im Bett bin, kann ich es direkt machen."
     *
     * `nullOnDelete` ist die Notbremse, nicht der geplante Weg: Fällt eine
     * Gewohnheit weg, erben ihre Nachfolger vorher ihren Anker
     * (ReleaseChainedHabits). Die Spalte fängt nur ab, was daran vorbeikommt.
     */
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->foreignId('chained_to_habit_id')
                ->nullable()
                ->after('scheduled_days')
                ->constrained('habits')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chained_to_habit_id');
        });
    }
};
