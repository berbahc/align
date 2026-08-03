<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Der persönliche Grund hinter einer Gewohnheit.
     *
     * Grundlage: Interviewauswertung, Idee 4 (wertebasierte Verankerung).
     * Hannah: „die Routine muss zu mir passen […] und wofür ich stehe."
     * Bewusst optional — eine Pflichtfrage nach dem Warum wäre eine Hürde
     * an der Stelle, an der Ngocanh ohnehin schon abbricht.
     */
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->string('motivation')->nullable()->after('trigger_situation');
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn('motivation');
        });
    }
};
