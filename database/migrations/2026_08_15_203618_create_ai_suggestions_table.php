<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Das Gedächtnis der KI — was sie vorgeschlagen hat und wie es ausging.
 *
 * align.md Z. 31 nennt eine KI, „die … daraus lernt". Ohne diese Tabelle
 * beginnt jeder Aufruf bei null: derselbe Anker kann zum dritten Mal
 * vorgeschlagen werden, derselbe Schritt zum dritten Mal zu groß sein.
 *
 * Gespeichert werden **Entscheidungen, keine Gesprächsverläufe**. Die
 * `Conversation`-Modelle aus `laravel/ai` wären der bequemere Weg, sind aber
 * für Chats gebaut und schicken die ganze Historie bei jedem Aufruf mit. Align
 * stellt einmalige, strukturierte Anfragen — gebraucht wird das Ergebnis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();

            // Das Gedächtnis gehört der Person und stirbt mit ihrem Konto.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Null, solange es die Gewohnheit noch nicht gibt: die Starthilfe
            // schlägt schon im Anlege-Ablauf Schritte vor, und dort ist nichts
            // gespeichert, woran ein Vorschlag hängen könnte.
            $table->foreignId('habit_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('kind');

            // Der Vorschlag als lesbare Zeile — genau das, was später im Prompt
            // steht. Ein Anker wird dafür wie in `Habit::scheduleLabel()`
            // formatiert („17:00 · Mo–Fr").
            $table->string('label');

            // Die strukturierte Alternative, nur bei `anchor`. Der Prompt liest
            // sie nicht; sie steht hier, damit sich nachvollziehen lässt, was
            // genau angeboten wurde.
            $table->json('payload')->nullable();

            // Null heißt: angeboten, nicht genommen.
            //
            // Es gibt bewusst kein `dismissed_at`. Ein eigener Ablehn-Endpunkt
            // würde „Lass so" einen Netzwerk-Aufruf kosten, den „Übernehmen"
            // nicht kostet — genau das Ungleichgewicht, das
            // ki-assistent-design.md §2 verbietet („Ablehnen ist genauso leicht
            // wie Annehmen"). Was offen bleibt, wurde nicht genommen; als
            // ausdrückliche Ablehnung wird es nirgends ausgegeben.
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            // Der Zugriff im Prompt: die jüngsten Zeilen einer Person zu einer Art.
            $table->index(['user_id', 'kind', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_suggestions');
    }
};
