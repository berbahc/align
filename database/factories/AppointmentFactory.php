<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'habit_id' => Habit::factory(),
            'requester_id' => User::factory(),
            'invitee_id' => User::factory(),
            'scheduled_for' => Carbon::today(),
            'accepted_at' => null,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => ['accepted_at' => now()]);
    }
}
