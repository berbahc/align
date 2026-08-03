<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');

            // time-blocking.md: "Ohne Trigger keine Gewohnheit." Der Wenn-Teil
            // der Wenn-Dann-Planung ist bewusst eine Situation, keine Uhrzeit —
            // Situationen lösen Verhalten automatisch aus, Uhrzeiten nicht.
            $table->string('trigger_situation');

            $table->string('behavior_type');
            $table->unsignedSmallInteger('focus_minutes')->nullable();

            // Reihenfolge in der Tagesliste; progress-tracking.md begrenzt auf
            // maximal 5 gleichzeitig aktive Gewohnheiten.
            $table->unsignedTinyInteger('position')->default(0);

            // time-blocking.md: das explizite "Ich nehme mir das vor" ist der
            // eine bewusste Willensakt, der laut Gollwitzer die Gewohnheit anstößt.
            $table->timestamp('committed_at')->nullable();

            // progress-tracking.md: gefestigte Gewohnheiten machen Platz für neue,
            // statt gelöscht zu werden — der Verlauf bleibt erhalten.
            $table->timestamp('graduated_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'graduated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habits');
    }
};
