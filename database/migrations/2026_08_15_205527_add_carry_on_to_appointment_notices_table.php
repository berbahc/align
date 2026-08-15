<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Womit es nach einer Absage weitergehen kann.
     *
     * Eine Absage ließ bisher nur einen Satz zurück. Wer den Tag um die Zusage
     * herum geplant hatte, stand danach vor nichts — dabei ist die Gewohnheit
     * das Einzige, was von der Verabredung übrig bleibt und ohne die andere
     * Person weiterläuft. Beide Spalten tragen genau diesen einen Weg.
     *
     * Sie schließen einander aus, und das ist der Punkt:
     *
     * - `habit_id` steht **nur**, wenn die Gewohnheit der Person gehört, die
     *   die Notiz bekommt. Dann führt der Weg zu ihrer eigenen Zeile — es gibt
     *   nichts anzulegen. Ein Verweis auf die eigene Gewohnheit verrät nichts
     *   über die andere Person.
     * - `habit_blueprint` steht **nur**, wenn sie ihr nicht gehört. Dann ist der
     *   Weg das Übernehmen, und dafür braucht die Oberfläche Titel, Richtung und
     *   Anker als Vorbelegung.
     *
     * Warum eine Kopie statt eines Verweises: Die ursprüngliche Migration
     * begründet ausführlich, dass hier keine Spalte auf die absagende Person
     * zeigen darf — über `habits.user_id` wäre genau das wieder möglich, und aus
     * gespeicherten Absagen ließe sich eine Quote bilden (§9). Die Kopie trägt
     * dieselbe Information für den Zweck, den sie hat, und lässt sich mit
     * niemandem verknüpfen. Sie überlebt außerdem, dass die andere Person ihre
     * Gewohnheit löscht — die Notiz stünde sonst ohne ihren einzigen Inhalt da.
     */
    public function up(): void
    {
        Schema::table('appointment_notices', function (Blueprint $table) {
            $table->foreignId('habit_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->json('habit_blueprint')->nullable()->after('habit_title');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_notices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('habit_id');
            $table->dropColumn('habit_blueprint');
        });
    }
};
