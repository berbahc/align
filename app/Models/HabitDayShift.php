<?php

namespace App\Models;

use App\Support\DayPlan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Eine Gewohnheit liegt an einem einzelnen Tag woanders.
 *
 * „Heute mache ich das später" ist keine Planänderung. Diese Zeile gilt für ein
 * Datum und verfällt danach von selbst — der reguläre Zeitpunkt der Gewohnheit
 * bleibt unangetastet.
 *
 * `start_minute` sind Minuten seit Mitternacht und dürfen über 1440 liegen: Bei
 * einer Schlafenszeit nach Mitternacht reicht der Rahmen des Tages dorthin
 * ({@see DayPlan::frame()}).
 *
 * @property int $id
 * @property int $habit_id
 * @property Carbon $shifted_on
 * @property int $start_minute
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['shifted_on', 'start_minute'])]
class HabitDayShift extends Model
{
    /**
     * @return BelongsTo<Habit, $this>
     */
    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    protected function casts(): array
    {
        return [
            'shifted_on' => 'date',
            'start_minute' => 'integer',
        ];
    }
}
