<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Der Umfang einer Gewohnheit als eigenes Feld — Menge und Einheit.
     *
     * Bis hierher steckte die Zahl im Titel („20 Minuten spazieren") und war
     * damit unverstellbar. Sie wandert in zwei Spalten, der Titel benennt nur
     * noch die Handlung.
     *
     * `focus_minutes` geht darin auf. Die Spalte war der halbfertige Vorläufer
     * genau dieses Feldes: fillable, validiert, angezeigt — aber von keinem
     * Formular je befüllt. Was in ihr steht, sind Minuten, also wandert es
     * eins zu eins herüber.
     *
     * `decimal(5,1)` statt Integer, weil 1,5 Liter ein üblicheres Trinkziel ist
     * als 1 oder 2.
     */
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->decimal('target_amount', 5, 1)->nullable()->after('behavior_type');
            $table->string('target_unit')->nullable()->after('target_amount');
        });

        DB::table('habits')
            ->whereNotNull('focus_minutes')
            ->update([
                'target_amount' => DB::raw('focus_minutes'),
                'target_unit' => 'minutes',
            ]);

        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn('focus_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->unsignedSmallInteger('focus_minutes')->nullable()->after('behavior_type');
        });

        // Zurück gehen nur die Minuten — Seiten und Liter hatten in der alten
        // Spalte nie eine Entsprechung und gehen dabei verloren.
        DB::table('habits')
            ->where('target_unit', 'minutes')
            ->update(['focus_minutes' => DB::raw('target_amount')]);

        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn(['target_amount', 'target_unit']);
        });
    }
};
