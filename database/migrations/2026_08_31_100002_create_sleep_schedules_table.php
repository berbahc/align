<?php

use App\Models\SleepSchedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Der Schlafrhythmus — Aufsteh- und Schlafenszeit je Wochentag.
     *
     * Er ist der Rahmen, in dem Gewohnheiten geplant werden: Was vor dem
     * Aufstehen oder nach der Schlafenszeit läge, weist die Validierung ab.
     * Eine Zeile pro Wochentag, weil Dienstag und Samstag selten derselbe
     * Tag sind — wer will, trägt überall dasselbe ein.
     *
     * Ohne Zeile gilt die Voreinstellung aus {@see SleepSchedule}:
     * Der Rahmen existiert immer, gespeichert wird nur die Abweichung.
     *
     * `alarm_enabled` sitzt an der Zeile, nicht am Nutzer: Der Wecker für
     * Montag ist eine andere Entscheidung als der für Sonntag.
     *
     * Die Erinnerung vor der Schlafenszeit dagegen ist eine Haltung, kein
     * Tagesdetail — sie hängt als ein Schalter am Nutzer.
     */
    public function up(): void
    {
        Schema::create('sleep_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // ISO-Wochentag (1 = Montag … 7 = Sonntag), passend zu Carbons
            // `dayOfWeekIso` und den `scheduled_days` der Gewohnheiten.
            $table->unsignedTinyInteger('weekday');

            $table->time('wake_time');
            $table->time('bedtime');
            $table->boolean('alarm_enabled')->default(false);

            $table->timestamps();

            $table->unique(['user_id', 'weekday']);
        });

        Schema::table('users', function (Blueprint $table) {
            // An, solange niemand sie abstellt: Die Erinnerung 20 Minuten vor
            // der Schlafenszeit ist der Grund, warum es den Schlafplan gibt —
            // sie standardmäßig stummzuschalten hieße, das Feature zu
            // verstecken. Anders als die Gewohnheits-Erinnerungen fordert sie
            // nichts ein, sie kündigt nur das Ende des Tages an.
            $table->boolean('bedtime_reminder_enabled')->default(true)->after('appointments_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sleep_schedules');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bedtime_reminder_enabled');
        });
    }
};
