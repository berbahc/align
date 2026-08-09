<?php

namespace Database\Factories;

use App\Enums\AppointmentNoticeKind;
use App\Models\AppointmentNotice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentNotice>
 */
class AppointmentNoticeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'kind' => AppointmentNoticeKind::Declined,
            'companion_name' => $this->faker->firstName(),
            'habit_title' => 'Laufen gehen',
            'day' => 'morgen',
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['kind' => AppointmentNoticeKind::Cancelled]);
    }
}
