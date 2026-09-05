<?php

namespace Database\Factories;

use App\Enums\CourseKind;
use App\Models\Course;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Ein Kurs am Montagvormittag.
     *
     * Nichts daran ist gewürfelt: Wochentag und Uhrzeit entscheiden, ob ein
     * Block an einem Tag erscheint und ob er mit einer Gewohnheit kollidiert.
     * Ein zufälliger Anfang wäre ein Test, der mal durchgeht und mal nicht.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'semester_id' => Semester::factory(),
            'title' => 'Analysis I',
            'kind' => CourseKind::Vorlesung,
            'weekday' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:30',
            'location' => null,
        ];
    }

    /**
     * An welchem ISO-Wochentag der Kurs läuft, 1 = Montag.
     */
    public function onWeekday(int $weekday): self
    {
        return $this->state(fn (): array => ['weekday' => $weekday]);
    }

    /**
     * Von wann bis wann, als „H:i".
     */
    public function at(string $startsAt, string $endsAt): self
    {
        return $this->state(fn (): array => [
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }
}
