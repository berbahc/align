<?php

namespace Database\Factories;

use App\Enums\HabitTemplate;
use App\Enums\MeasureUnit;
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
        /** @var HabitTemplate $template */
        $template = fake()->randomElement(HabitTemplate::cases());

        return [
            'user_id' => User::factory(),
            'title' => $template->title(),
            'template_key' => $template->value,
            'trigger_situation' => fake()->randomElement([
                'nach dem Aufstehen',
                'nach der Morgenvorlesung',
                'nach dem Mittagessen',
                'wenn ich nach Hause komme',
                'vor dem Schlafengehen',
            ]),
            'behavior_type' => $template->behaviorType(),
            'target_amount' => $template->defaultMinutes(),
            'target_unit' => MeasureUnit::Minutes,
            'position' => 0,
            'committed_at' => now(),
        ];
    }

    /**
     * Gewohnheit aus einer bestimmten Vorlage des Katalogs.
     */
    public function fromTemplate(HabitTemplate $template): static
    {
        return $this->state(fn (): array => [
            'title' => $template->title(),
            'template_key' => $template->value,
            'behavior_type' => $template->behaviorType(),
            'target_amount' => $template->defaultMinutes(),
            'target_unit' => MeasureUnit::Minutes,
        ]);
    }

    /**
     * Gewohnheit mit einem festgelegten Umfang.
     */
    public function withMeasure(float $amount, MeasureUnit $unit = MeasureUnit::Minutes): static
    {
        return $this->state(fn (): array => [
            'target_amount' => $amount,
            'target_unit' => $unit,
        ]);
    }

    /**
     * Gewohnheit ohne Umfang — wie sie die freie Eingabe hinterlassen hat.
     *
     * Neu anlegen lässt sich so etwas nicht mehr; der Zustand existiert aber
     * in alten Daten und muss weiter funktionieren.
     */
    public function withoutMeasure(): static
    {
        return $this->state(fn (): array => [
            'target_amount' => null,
            'target_unit' => null,
        ]);
    }

    /**
     * Gewohnheit aus der Zeit der freien Eingabe — ohne Vorlage im Katalog.
     */
    public function legacy(string $title = 'Wäsche sortieren'): static
    {
        return $this->state(fn (): array => [
            'title' => $title,
            'template_key' => null,
        ]);
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
