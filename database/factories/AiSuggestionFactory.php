<?php

namespace Database\Factories;

use App\Enums\SuggestionKind;
use App\Models\AiSuggestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiSuggestion>
 */
class AiSuggestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'habit_id' => null,
            'kind' => SuggestionKind::SmallestStep,
            'label' => fake()->randomElement([
                'Stell das Glas ans Bett.',
                'Zieh die Laufschuhe an.',
                'Leg das Buch aufs Kopfkissen.',
            ]),
            'payload' => null,
            'accepted_at' => null,
        ];
    }

    /**
     * Ein vorgeschlagener Zeitpunkt statt eines Schritts.
     */
    public function anchor(string $situation = 'nach dem Aufstehen'): static
    {
        return $this->state(fn (): array => [
            'kind' => SuggestionKind::Anchor,
            'label' => $situation,
            'payload' => ['situation' => $situation, 'reason' => 'Da ist ohnehin eine Pause.'],
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => ['accepted_at' => now()]);
    }
}
