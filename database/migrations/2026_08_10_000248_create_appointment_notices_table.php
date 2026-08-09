<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Die Einmal-Notiz: „Passt Silas diesmal nicht."
     *
     * community_feature3.md §5 verlangt sie ausdrücklich — beim Fragenden soll
     * eine Absage ankommen statt spurlos zu verschwinden. Zugleich hält §9
     * fest, dass Absagen nicht gespeichert werden. Beides zusammen geht nur so:
     * Die Notiz existiert, bis sie einmal gelesen ist, und wird dann gelöscht.
     *
     * Deshalb steht hier **kein** Verweis auf die absagende Person, sondern nur
     * ihr Name als Text. Ohne `user_id` auf der Gegenseite lässt sich aus
     * diesen Zeilen keine Quote bilden, auch nicht nachträglich — eine
     * Absage-Statistik wäre bei Schuldgefühl ø 3,92 die schärfste denkbare
     * Bestrafung (§9). Aus demselben Grund gibt es keine `appointment_id`: Die
     * Verabredung ist in derselben Sekunde gelöscht.
     *
     * Was diese Tabelle bewusst NICHT hat:
     * - keinen Grund: eine Absage geht ohne Text raus (§5)
     * - keinen Zähler und kein `read_at`: gelesen heißt gelöscht, nicht
     *   markiert. Eine Zeile, die bleibt, wäre der Anfang einer Historie
     */
    public function up(): void
    {
        Schema::create('appointment_notices', function (Blueprint $table) {
            $table->id();

            // Wer sie zu sehen bekommt — die einzige Person in dieser Tabelle.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Absage einer offenen Anfrage oder Auflösen einer zugesagten
            // Verabredung. Der Satz lautet je nach Fall anders.
            $table->string('kind');

            // Als Text, nicht als Verweis: siehe oben.
            $table->string('companion_name');
            $table->string('habit_title');

            // „heute", „morgen" oder der Wochentag — schon fertig formuliert,
            // weil das Datum nach dem Löschen der Verabredung niemanden mehr
            // etwas angeht.
            $table->string('day');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_notices');
    }
};
