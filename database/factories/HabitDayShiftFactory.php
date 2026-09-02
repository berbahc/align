<?php

namespace Database\Factories;

use App\Models\Habit;
use App\Models\HabitDayShift;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<HabitDayShift>
 */
class HabitDayShiftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'habit_id' => Habit::factory(),
            'shifted_on' => Carbon::today(),
            'scheduled_time' => '18:00',
        ];
    }
}
