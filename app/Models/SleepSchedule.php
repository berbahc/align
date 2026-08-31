<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Aufsteh- und Schlafenszeit eines Wochentags — der Rahmen des Tages.
 *
 * Der Schlafplan ist kein Tracking und keine Gewohnheit: Er wird nicht
 * abgehakt, hat keine Serie und keine Quote. Er setzt den Rahmen, in dem
 * alles andere stattfindet — Gewohnheiten lassen sich nur zwischen Aufstehen
 * und Schlafenszeit planen, die Erinnerung vor der Schlafenszeit beendet den
 * Tag, der Wecker beginnt ihn.
 *
 * @property int $id
 * @property int $user_id
 * @property int $weekday
 * @property Carbon $wake_time
 * @property Carbon $bedtime
 * @property bool $alarm_enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['weekday', 'wake_time', 'bedtime', 'alarm_enabled'])]
class SleepSchedule extends Model
{
    /**
     * Der Rahmen, solange niemand einen eigenen gesetzt hat.
     *
     * 07:00 bis 23:00 ist keine Empfehlung, sondern eine Annahme, die selten
     * ganz falsch liegt — und die dafür sorgt, dass der Rahmen von Anfang an
     * existiert, statt erst nach einem Einrichtungsschritt. Gespeichert wird
     * nur die Abweichung.
     */
    public const string DefaultWakeTime = '07:00';

    public const string DefaultBedtime = '23:00';

    /**
     * Vorlauf der Erinnerung vor der Schlafenszeit, in Minuten.
     *
     * Zwanzig statt der zehn der Gewohnheits-Erinnerungen: Schlafen gehen
     * braucht einen Auslauf — Zähne, Handy weg, Licht aus — und keine Reaktion
     * auf die Minute.
     */
    public const int BedtimeReminderLeadMinutes = 20;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Liegt die Uhrzeit im wachen Teil des Tages?
     *
     * Eine Schlafenszeit vor der Aufstehzeit meint „nach Mitternacht":
     * 07:00 bis 00:30 ist ein Rahmen, der über den Tagesrand hinausreicht —
     * dann ist wach, was **nach** dem Aufstehen oder **vor** der
     * Schlafenszeit liegt, statt dazwischen.
     */
    public static function containsTime(string $wake, string $bedtime, string $time): bool
    {
        if ($bedtime > $wake) {
            return $time >= $wake && $time <= $bedtime;
        }

        return $time >= $wake || $time <= $bedtime;
    }

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'wake_time' => 'datetime:H:i',
            'bedtime' => 'datetime:H:i',
            'alarm_enabled' => 'boolean',
        ];
    }
}
