<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Die Verabredung — ein einzelner gemeinsamer Termin.
     *
     * community_feature3.md §2: „Nicht sehen, was andere machen. Sich
     * verabreden, es zusammen zu machen." Eine Verabredung ist ein Ereignis,
     * kein Zustand — deshalb hängt sie an genau einem Tag und verschwindet
     * danach.
     *
     * Was diese Tabelle bewusst NICHT hat (§9):
     * - keine Wiederholung: das wäre faktisch der gemeinsame Kalender, der mit
     *   Top-2 46 % und sieben Hard-No-Stimmen abgelehnt wurde
     * - keinen Status „abgelehnt": eine Absage löscht die Zeile. Eine
     *   Absage-Historie wäre bei Schuldgefühl ø 3,92 die schärfste denkbare
     *   Bestrafung
     * - keine Uhrzeit: der Zeitpunkt kommt aus dem Anker der Gewohnheit. Die
     *   Verabredung erfindet keine Zeitlogik, sie nutzt die vorhandene. Genau
     *   darum ist sie kein gemeinsamer Kalender — es wird kein Zeitraum
     *   abgeglichen, sondern ein Anker geteilt (offener Punkt §11.4)
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // Die Gewohnheit gehört immer der fragenden Seite. Ihr Anker
            // bestimmt den Zeitpunkt, ihr Titel den Text der Anfrage.
            $table->foreignId('habit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invitee_id')->constrained('users')->cascadeOnDelete();

            $table->date('scheduled_for');

            // Offen, solange null — wie bei der Freundschaft.
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            // Eine Gewohnheit, ein Tag, eine Verabredung. Verhindert zugleich,
            // dass jemand dieselbe Bitte mehrfach schickt.
            $table->unique(['habit_id', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
