<?php

namespace App\Models;

use App\Enums\ShiftOrigin;
use Database\Factories\HabitDayShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Eine Gewohnheit liegt an genau einem Tag woanders.
 *
 * Der Anlass ist die Verabredung: Wer zusagen will, muss Platz haben — und
 * Platz zu machen darf nicht heißen, den eigenen Plan für immer umzustellen.
 * Die Ausnahme gilt für ein Datum und verfällt mit ihm.
 *
 * @property int $id
 * @property int $habit_id
 * @property Carbon $shifted_on
 * @property Carbon $scheduled_time
 * @property ShiftOrigin $origin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['habit_id', 'shifted_on', 'scheduled_time', 'origin'])]
class HabitDayShift extends Model
{
    /** @use HasFactory<HabitDayShiftFactory> */
    use HasFactory;

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
            'scheduled_time' => 'datetime:H:i',
            'origin' => ShiftOrigin::class,
        ];
    }
}
