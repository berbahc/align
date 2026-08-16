<?php

namespace Database\Seeders;

use App\Enums\BehaviorType;
use App\Enums\MeasureUnit;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Die vier Gewohnheiten entsprechen dem mobilen Mockup („Heutige
     * Gewohnheiten"), damit die Oberfläche gegen realistische Daten entwickelt
     * werden kann statt gegen erfundene Konstanten im Frontend.
     *
     * Die vier decken zugleich die drei Zustände ab, die der Umfang kennt:
     * ohne Umfang, in Minuten, und in einer anderen Einheit als Minuten.
     *
     * @var list<array{title: string, trigger_situation: string, behavior_type: BehaviorType, target_amount: float|null, target_unit: MeasureUnit|null, consistency: float}>
     */
    private const array DemoHabits = [
        [
            'title' => 'Morgentraining',
            'trigger_situation' => 'nach dem Aufstehen',
            'behavior_type' => BehaviorType::Movement,
            'target_amount' => null,
            'target_unit' => null,
            'consistency' => 0.85,
        ],
        [
            'title' => 'Lesen',
            'trigger_situation' => 'vor dem Schlafengehen',
            'behavior_type' => BehaviorType::Learning,
            'target_amount' => 10,
            'target_unit' => MeasureUnit::Pages,
            'consistency' => 0.6,
        ],
        [
            'title' => 'Wasser trinken',
            'trigger_situation' => 'nach dem Mittagessen',
            'behavior_type' => BehaviorType::Nutrition,
            'target_amount' => 2,
            'target_unit' => MeasureUnit::Liters,
            'consistency' => 0.4,
        ],
        [
            'title' => 'Meditieren',
            'trigger_situation' => 'wenn ich nach Hause komme',
            'behavior_type' => BehaviorType::Other,
            'target_amount' => 15,
            'target_unit' => MeasureUnit::Minutes,
            'consistency' => 0.25,
        ],
    ];

    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'onboarded_at' => now(),
        ]);

        $this->seedHabitsFor($user);
    }

    private function seedHabitsFor(User $user): void
    {
        $today = Carbon::today();

        foreach (self::DemoHabits as $position => $demo) {
            $habit = $user->habits()->create([
                'title' => $demo['title'],
                'trigger_situation' => $demo['trigger_situation'],
                'behavior_type' => $demo['behavior_type'],
                'target_amount' => $demo['target_amount'],
                'target_unit' => $demo['target_unit'],
                'position' => $position,
                'committed_at' => now(),
            ]);

            // Rückwirkend anlegen, damit die Konsistenzrate ein volles
            // 30-Tage-Fenster hat statt nur den heutigen Tag.
            $habit->forceFill(['created_at' => $today->copy()->subDays(45)])->save();

            // Wie im Mockup: die erste Gewohnheit ist heute erledigt, die
            // übrigen stehen noch offen — beide Zustände sind so sichtbar.
            $this->seedCompletionsFor($habit, $demo['consistency'], $today, $position === 0);
        }
    }

    private function seedCompletionsFor(Habit $habit, float $consistency, Carbon $today, bool $completedToday): void
    {
        // Deterministisch statt zufällig: gleiche Datenbank bei jedem Seed,
        // sonst springt die Oberfläche bei jedem Reset.
        $step = (int) round(1 / $consistency);

        for ($daysAgo = 0; $daysAgo < 30; $daysAgo++) {
            if ($daysAgo % $step !== 0) {
                continue;
            }

            if ($daysAgo === 0 && ! $completedToday) {
                continue;
            }

            $date = $today->copy()->subDays($daysAgo);

            $habit->completions()->create([
                'completed_on' => $date,
                'completed_at' => $date->copy()->setTime(7, 30),
            ]);
        }
    }
}
