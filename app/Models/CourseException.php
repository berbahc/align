<?php

namespace App\Models;

use Database\Factories\CourseExceptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Was an einem einzelnen Datum anders ist als jede Woche.
 *
 * Ohne Zeiten heißt sie: An diesem Tag findet nichts statt. Mit Zeiten: An
 * diesem Tag findet es dann statt — und wenn das Datum auf einen anderen
 * Wochentag fällt als der Kurs, ist es ein Nachholtermin.
 *
 * @property int $id
 * @property int $course_id
 * @property Carbon $on_date
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Course $course
 */
#[Fillable(['on_date', 'starts_at', 'ends_at'])]
class CourseException extends Model
{
    /** @use HasFactory<CourseExceptionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Fällt der Kurs an diesem Tag aus?
     *
     * Geprüft wird auf den Beginn: Der Validator lässt eine Ausnahme mit nur
     * einer der beiden Zeiten gar nicht erst durch, und eine Endzeit ohne
     * Anfang wäre auch als Termin nicht zu gebrauchen.
     */
    public function isCancellation(): bool
    {
        return $this->starts_at === null;
    }

    /**
     * Der Tag als deutsche Zeile — „Mo, 7. September".
     *
     * `settings()` statt `locale('de')`: Beide stellen dieselbe Sprache ein,
     * aber `locale()` ist bei Carbon zugleich der Weg, die aktuelle Sprache zu
     * erfragen, und gibt dann eine Zeichenkette zurück. Die statische Analyse
     * kann die beiden Bedeutungen nicht auseinanderhalten und hält jede
     * Anschlussmethode für einen Aufruf auf einem String.
     */
    public function dateLabel(): string
    {
        return $this->on_date->settings(['locale' => 'de'])->isoFormat('dd, D. MMMM');
    }

    protected function casts(): array
    {
        return [
            'on_date' => 'date',
            'starts_at' => 'datetime:H:i',
            'ends_at' => 'datetime:H:i',
        ];
    }
}
