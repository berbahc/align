<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseException;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<CourseException>
 */
class CourseExceptionFactory extends Factory
{
    /**
     * Voreingestellt ein Ausfall — der weitaus häufigste Fall.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'on_date' => Carbon::today(),
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    /**
     * An diesem Datum findet nichts statt.
     */
    public function cancelledOn(Carbon|string $date): self
    {
        return $this->state(fn (): array => [
            'on_date' => $date,
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }

    /**
     * An diesem Datum liegt der Kurs stattdessen hier.
     *
     * Fällt das Datum auf einen anderen Wochentag als der Kurs, ist es ein
     * Nachholtermin — die Ausnahme trägt beides, ohne es zu unterscheiden.
     */
    public function movedTo(Carbon|string $date, string $startsAt, string $endsAt): self
    {
        return $this->state(fn (): array => [
            'on_date' => $date,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }
}
