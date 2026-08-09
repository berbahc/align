<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Der kleinste nächste Schritt — ein einzelner physischer Handgriff.
     *
     * Die Starthilfe ist mit ø 4,16 die bestbewertete Funktion der Umfrage und
     * adressiert den gemessenen Abstand zwischen Vorsatz und Handlung (ø 3,80):
     * Nicht das Ziel fehlt, sondern der Anstoß.
     *
     * Der Schritt wird schon beim Anlegen formuliert, nicht erst im Alltag —
     * wenn der Anstoß das Problem ist, muss er vorbereitet sein, bevor der
     * kritische Moment eintritt. Er bleibt trotzdem optional: bei ø 3,92
     * Schuldgefühl darf kein weiteres Pflichtfeld entstehen.
     */
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->string('smallest_step', 160)->nullable()->after('motivation');
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn('smallest_step');
        });
    }
};
