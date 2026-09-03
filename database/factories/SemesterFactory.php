<?php

namespace Database\Factories;

use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Semester>
 */
class SemesterFactory extends Factory
{
    /**
     * Ein Semester, das heute läuft.
     *
     * Feste Ränder um `today` statt gewürfelter Daten: Ein Test, der prüft, ob
     * ein Kurs an einem Tag stattfindet, soll nicht daran scheitern, dass die
     * Vorlesungszeit zufällig gestern endete.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => 'Wintersemester 25/26',
            'starts_on' => Carbon::today()->subMonth(),
            'ends_on' => Carbon::today()->addMonths(3),
        ];
    }

    /**
     * Ein Semester, das schon vorbei ist — die vorlesungsfreie Zeit.
     */
    public function past(): self
    {
        return $this->state(fn (): array => [
            'title' => 'Sommersemester 25',
            'starts_on' => Carbon::today()->subMonths(6),
            'ends_on' => Carbon::today()->subMonths(2),
        ]);
    }

    /**
     * Ein Semester mit ausdrücklichen Rändern.
     */
    public function between(string $startsOn, string $endsOn): self
    {
        return $this->state(fn (): array => [
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
        ]);
    }
}
