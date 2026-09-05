<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auch die gefragte Person hakt ab — an ihrer Verabredung, nicht an der
     * fremden Gewohnheit.
     *
     * Wer zusagt, macht mit und hat danach dasselbe getan wie die andere
     * Person. Bis hierher konnte er es nirgends abhaken: Eine Erfüllung hängt
     * an einer Gewohnheit, und die gehört der fragenden Seite. Ein Haken dort
     * hätte deren Gewohnheit als erledigt gemeldet — die fremde Leistung als
     * eigene verbucht.
     *
     * Deshalb steht der Haken hier, an der Verabredung. Sie ist der Teil, der
     * wirklich beiden gehört, und sie gilt für genau einen Tag — ein Datum
     * braucht die Spalte also nicht.
     *
     * Was diese Spalte bewusst NICHT tut: Sie sagt der **fragenden** Seite
     * nichts. Deren Zeile zeigt weiterhin nur, dass jemand mitmacht, nicht wie
     * es bei ihm lief. Ein sichtbarer Fremdfortschritt wäre der Dauerstatus,
     * den community_feature3.md §6 ausschließt — und die Zusage würde damit zu
     * einer Zusage, beobachtet zu werden.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
