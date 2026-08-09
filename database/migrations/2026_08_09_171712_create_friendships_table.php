<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Freundschaften — der Unterbau für die Verabredung.
     *
     * community_feature3.md zeigt in Screen 1 drei Personen zur Auswahl, setzt
     * sie aber voraus. Ohne bestätigte Freundschaft gäbe es keinen Kreis, aus
     * dem gewählt werden könnte, und jede fremde Person könnte Anfragen
     * schicken.
     *
     * Beidseitig bestätigt, weil die Umfrage genau das trägt: 21/25 wollen mit
     * engen Freunden teilen, 3/25 mit Bekannten, 2/25 anonym. „Eng" ist keine
     * Eigenschaft, die eine Seite allein feststellen kann.
     *
     * Eine abgelehnte Anfrage wird gelöscht, nicht auf „abgelehnt" gesetzt.
     * Ein gespeichertes Nein wäre der Anfang einer Absage-Historie — und die
     * schließt community_feature3.md §9 bei Schuldgefühl ø 3,92 ausdrücklich
     * aus. Gegen wiederholtes Anfragen schützt die Drosselung der Route, nicht
     * ein Eintrag in der Datenbank.
     */
    public function up(): void
    {
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('addressee_id')->constrained('users')->cascadeOnDelete();

            // Offen, solange null. Ein eigenes Status-Feld wäre eine dritte
            // Möglichkeit, die es nicht gibt: abgelehnt heißt gelöscht.
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            // Verhindert die doppelte Anfrage in derselben Richtung. Die
            // Gegenrichtung fängt der Controller ab — er nimmt eine bereits
            // offene Anfrage der anderen Seite als Zusage an, statt eine
            // zweite anzulegen.
            $table->unique(['requester_id', 'addressee_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            /**
             * Screen A5: „Wer keine Verabredungen möchte, sieht den Knopf nicht
             * mehr." Die Umfrage-Auswertung §8 verlangt, dass Community
             * vollständig abschaltbar ist, ohne dass die App unvollständig
             * wirkt — 2/25 lehnen Teilen klar ab, 4/25 sind ambivalent.
             *
             * Der Schalter sperrt auch eingehende Freundschaftsanfragen: Wer
             * das Soziale abgestellt hat, soll nicht über den Umweg einer
             * Anfrage doch wieder angesprochen werden.
             */
            $table->boolean('appointments_enabled')->default(true)->after('onboarded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friendships');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('appointments_enabled');
        });
    }
};
