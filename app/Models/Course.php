<?php

namespace App\Models;

use App\Enums\CourseKind;
use App\Support\DayPlan;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Eine Veranstaltung, die jede Woche am selben Tag zur selben Zeit läuft.
 *
 * Sie gehört keiner Gewohnheit und wird nie abgehakt: Der Kurs ist kein
 * Vorsatz, sondern eine Tatsache, um die herum geplant wird.
 *
 * @property int $id
 * @property int $semester_id
 * @property string $title
 * @property CourseKind $kind
 * @property int $weekday
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string|null $location
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Semester $semester
 * @property-read Collection<int, CourseException> $exceptions
 */
#[Fillable(['title', 'kind', 'weekday', 'starts_at', 'ends_at', 'location'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /**
     * Wie viele Kurse ein Semester tragen darf.
     *
     * Nicht als Sparzwang, sondern weil ein Stundenplan mit dreißig Zeilen
     * kein Plan mehr ist. Wer so viel einträgt, trägt etwas ein, das nicht
     * jede Woche stattfindet.
     */
    public const int MaxPerSemester = 20;

    /**
     * @return BelongsTo<Semester, $this>
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * @return HasMany<CourseException, $this>
     */
    public function exceptions(): HasMany
    {
        return $this->hasMany(CourseException::class);
    }

    /** Die Minute seit Mitternacht, zu der der Kurs regulär beginnt. */
    public function startMinute(): int
    {
        return DayPlan::toMinutes($this->starts_at->format('H:i'));
    }

    /** Die Minute, zu der er regulär endet. */
    public function endMinute(): int
    {
        return DayPlan::toMinutes($this->ends_at->format('H:i'));
    }

    public function durationMinutes(): int
    {
        return $this->endMinute() - $this->startMinute();
    }

    /** „08:00 – 09:30". */
    public function timeRangeLabel(): string
    {
        return sprintf(
            '%s – %s',
            $this->starts_at->format('H:i'),
            $this->ends_at->format('H:i'),
        );
    }

    protected function casts(): array
    {
        return [
            'kind' => CourseKind::class,
            'starts_at' => 'datetime:H:i',
            'ends_at' => 'datetime:H:i',
        ];
    }
}
