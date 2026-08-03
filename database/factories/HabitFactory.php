<?php

namespace Database\Factories;

use App\Enums\BehaviorType;
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

    public function graduated(): static
    {
        return $this->state(fn (): array => ['graduated_at' => now()]);
    }

    public function uncommitted(): static
    {
        return $this->state(fn (): array => ['committed_at' => null]);
    }
}
