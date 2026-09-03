<?php

use App\Enums\CourseKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eine Veranstaltung, die jede Woche am selben Tag zur selben Zeit läuft.
     *
     * Der Stundenplan wiederholt sich wöchentlich — das ist die Form, in der
     * ihn auch jede Hochschule ausgibt. Was davon abweicht (Feiertag, Ausfall,
     * Nachholtermin), steht als Ausnahme in `course_exceptions` und nicht als
     * eigene Zeile hier. Sonst wäre ein Semester nicht sechs Kurse, sondern
     * neunzig Termine, und ein verschobener Raum hieße neunzig Änderungen.
     *
     * Eine Veranstaltung, die zweimal die Woche läuft, ist zwei Zeilen mit
     * demselben Titel. Der Kalender zeichnet Blöcke, keine Module.
     *
     * Was diese Tabelle bewusst NICHT hat:
     * - keinen Dozenten, keine Modulnummer, keine Credit Points: Das steht im
     *   Campussystem und hat mit der Frage, wann Platz ist, nichts zu tun
     * - keinen 14-tägigen Rhythmus: Wer eine Übung in geraden Wochen hat, trägt
     *   die ungeraden als Ausfall ein. Ein Rhythmusfeld wäre eine zweite Art
     *   von Wiederholung, und zwei Arten sind eine zu viel
     * - keine eigene Gültigkeit je Kurs: Ein Blockseminar in der ersten Hälfte
     *   ist heute eine Reihe von Ausfällen. Reicht es nicht mehr, kommen
     *   `starts_on`/`ends_on` nullable dazu — nicht vorher
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();

            $table->string('title');

            // Vorlesung, Übung, Seminar … — für das Auge, nicht für die
            // Rechnung: Belegte Zeit ist belegte Zeit.
            $table->string('kind')->default(CourseKind::Vorlesung->value);

            // ISO-Wochentag, 1 = Montag. Dieselbe Zählung wie im Schlafplan
            // und in `habits.scheduled_days` — zwei Zählungen wären zwei
            // Gelegenheiten, sich um einen Tag zu vertun.
            $table->unsignedTinyInteger('weekday');

            $table->time('starts_at');
            $table->time('ends_at');

            // Hörsaal oder Raum, wenn die Person ihn wissen will.
            $table->string('location')->nullable();

            $table->timestamps();

            $table->index(['semester_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
