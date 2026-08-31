<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jede Gewohnheit entsteht ab jetzt aus einer Vorlage des Katalogs.
     *
     * Die Spalte hält fest, aus welcher — für die Kategorie im Verzeichnis und
     * dafür, dass eine Übernahme dieselbe Vorlage trifft statt nur denselben
     * Text. Der Titel bleibt trotzdem denormalisiert auf der Zeile: Er gehört
     * zur Gewohnheit, seit sie angelegt wurde, und eine später umbenannte
     * Vorlage darf bestehende Einträge nicht umschreiben.
     *
     * Nullable, weil es Gewohnheiten aus der Zeit der freien Eingabe gibt.
     * Sie laufen unverändert weiter — nur neue Einträge müssen aus dem
     * Katalog kommen.
     */
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->string('template_key')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn('template_key');
        });
    }
};
