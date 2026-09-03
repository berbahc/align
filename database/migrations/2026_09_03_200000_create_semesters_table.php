<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Der Zeitraum, in dem der Stundenplan überhaupt gilt.
     *
     * Der Schlafplan sagt, wann der Tag anfängt und aufhört. Für wen studiert,
     * fehlt darin ein zweiter Rahmen: Zwischen 10:00 und 11:30 liegt dienstags
     * eine Vorlesung und donnerstags nicht. Bis hierher wusste die App davon
     * nichts und schlug Zeitpunkte vor, an denen jemand im Hörsaal saß.
     *
     * Der Zeitraum steht hier und nicht am einzelnen Kurs, weil er allen
     * gemeinsam ist: Sechs Kurse hätten sonst sechs Kopien desselben Datums,
     * und die vorlesungsfreie Zeit wäre sechs unabhängige Abläufe statt eines
     * Zustands.
     *
     * Was diese Tabelle bewusst NICHT hat:
     * - keinen Studiengang, keine Hochschule: Align plant Zeit, es verwaltet
     *   kein Studium
     * - kein „aktiv"-Feld: Welches Semester gilt, sagt das Datum. Ein Schalter
     *   daneben wäre eine zweite Wahrheit, die irgendwann der ersten
     *   widerspricht
     */
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Wie die Person es nennt — „Wintersemester 25/26".
            $table->string('title');

            // Erster und letzter Tag der Vorlesungszeit.
            $table->date('starts_on');
            $table->date('ends_on');

            $table->timestamps();

            $table->index(['user_id', 'starts_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
