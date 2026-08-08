<?php

namespace Database\Factories;

use App\Enums\BehaviorType;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Habit>
 */
class HabitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->randomElement([
                '10 Seiten lesen',
                'Morgentraining',
                'Trinken',
                'Meditation',
                'Spaziergang',
            ]),
            'trigger_situation' => fake()->randomElement([
                'nach dem Aufstehen',
                'nach der Morgenvorlesung',
                'nach dem Mittagessen',
                'wenn ich nach Hause komme',
                'vor dem Schlafengehen',
            ]),
            'behavior_type' => fake()->randomElement(BehaviorType::cases()),
            'focus_minutes' => fake()->optional()->randomElement([10, 15, 30, 45]),
            'position' => 0,
            'committed_at' => now(),
        ];
    }

    /**
     * Gewohnheit mit fester Uhrzeit statt Situation.
     *
     * @param  list<int>  $days  ISO-Wochentage, standardmäßig Mo–Fr.
     */
    public function fixedSchedule(string $time = '17:00', array $days = [1, 2, 3, 4, 5]): static
    {
        return $this->state(fn (): array => [
            'schedule_type' => ScheduleType::Fixed,
            'trigger_situation' => null,
            'scheduled_time' => $time,
            'scheduled_days' => $days,
        ]);
    }

    /**
     * Erinnerung eingeschaltet — setzt eine feste Uhrzeit voraus, weil das
     * Flag sonst einen Zustand beschriebe, den es nicht geben kann.
     */
    public function withReminder(): static
    {
        return $this->fixedSchedule()->state(fn (): array => [
            'reminder_enabled' => true,
        ]);
    }

    public function graduated(): static
    {
        return $this->state(fn (): array => ['graduated_at' => now()]);
    }

    public function uncommitted(): static
    {
        return $this->state(fn (): array => ['committed_at' => null]);
    }
}
