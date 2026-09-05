<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\SemesterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Der Zeitraum, in dem ein Stundenplan gilt.
 *
 * Er ist der zweite Rahmen neben dem Schlafplan: Der eine sagt, wann der Tag
 * anfängt und aufhört, der andere, wann in diesem Tag nichts geht. Beide sagen
 * nicht, was zu tun ist — das bleibt die Sache der Gewohnheiten.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, Course> $courses
 */
#[Fillable(['title', 'starts_on', 'ends_on'])]
class Semester extends Model
{
    /** @use HasFactory<SemesterFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Liegt dieses Datum in der Vorlesungszeit?
     *
     * Verglichen wird über die Datumszeichenkette und nicht über Carbon: Ein
     * `date`-Cast bringt eine Uhrzeit mit, die niemand gesetzt hat, und über
     * Zeitzonengrenzen hinweg entscheidet die dann über einen ganzen Tag.
     */
    public function covers(CarbonInterface $date): bool
    {
        $day = $date->toDateString();

        return $day >= $this->starts_on->toDateString()
            && $day <= $this->ends_on->toDateString();
    }

    /**
     * Der Zeitraum als Zeile — „13.10. – 07.02.".
     */
    public function rangeLabel(): string
    {
        return sprintf(
            '%s – %s',
            $this->starts_on->format('d.m.'),
            $this->ends_on->format('d.m.'),
        );
    }

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }
}
