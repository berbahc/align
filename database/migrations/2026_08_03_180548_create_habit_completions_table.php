<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nur erfüllte Tage werden gespeichert.
     *
     * progress-tracking.md: einzelne Aussetzer haben laut Lally et al. keine
     * messbaren Langzeitkosten und dürfen nie als Zustand markiert werden.
     * Ein fehlender Tag ist deshalb die Abwesenheit einer Zeile — es gibt
     * bewusst kein Feld "nicht geschafft".
     */
    public function up(): void
    {
        Schema::create('habit_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habit_id')->constrained()->cascadeOnDelete();
            $table->date('completed_on');
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['habit_id', 'completed_on']);
            $table->index('completed_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_completions');
    }
};
