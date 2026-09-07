<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Der Rahmen eines einzelnen Tages — abweichend vom Wochenplan.
 *
 * „Heute bin ich später aufgestanden" ist keine Änderung am Rhythmus, sondern
 * eine Ausnahme davon. Sie gilt für ein Datum und läuft mit ihm aus; morgen
 * gilt wieder {@see SleepSchedule}.
 *
 * Gespeichert wird nur, was abweicht: Wer nur später aufsteht, lässt seine
 * Schlafenszeit leer, und {@see User::sleepWindowOn()} nimmt sie weiter aus
 * dem Wochenplan. Eine mitgeschriebene Kopie veraltete still, sobald der Plan
 * sich ändert.
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon $on_date
 * @property Carbon|null $wake_time
 * @property Carbon|null $bedtime
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['on_date', 'wake_time', 'bedtime'])]
class SleepDayOverride extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sagt diese Zeile überhaupt noch etwas?
     *
     * Eine Ausnahme ohne Abweichung ist keine — sie stünde als Zeile in der
     * Tabelle und änderte nichts. Wer beide Zeiten zurücksetzt, löscht sie
     * damit.
     */
    public function isEmpty(): bool
    {
        return $this->wake_time === null && $this->bedtime === null;
    }

    protected function casts(): array
    {
        return [
            'on_date' => 'date',
            'wake_time' => 'datetime:H:i',
            'bedtime' => 'datetime:H:i',
        ];
    }
}
