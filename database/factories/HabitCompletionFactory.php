<?php

namespace Database\Factories;

use App\Models\Habit;
use App\Models\HabitCompletion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<HabitCompletion>
 */
class HabitCompletionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $completedOn = Carbon::today();

        return [
            'habit_id' => Habit::factory(),
            'completed_on' => $completedOn,
            'completed_at' => $completedOn->copy()->setTime(7, 30),
        ];
    }

    public function on(Carbon $date): static
    {
        return $this->state(fn (): array => [
            'completed_on' => $date->copy()->startOfDay(),
            'completed_at' => $date->copy()->setTime(7, 30),
        ]);
    }
}
