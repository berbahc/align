<?php

namespace Database\Seeders;

use App\Enums\HabitTemplate;
use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Vier Gewohnheiten aus dem Katalog, damit die Oberfläche gegen
     * realistische Daten entwickelt werden kann statt gegen erfundene
     * Konstanten im Frontend.
     *
     * Die vier decken die Planungsarten ab: feste Uhrzeit und Situation,
     * verteilt über den Tag. Alle mit Dauer — seit dem Katalog gibt es
     * nichts anderes mehr.
     *
     * @var list<array{template: HabitTemplate, schedule: ScheduleType, trigger: string|null, time: string|null, days: list<int>|null, consistency: float}>
     */
    private const array DemoHabits = [
        [
            'template' => HabitTemplate::Joggen,
            'schedule' => ScheduleType::Fixed,
            'trigger' => null,
            'time' => '07:30',
            'days' => [1, 3, 5],
            'consistency' => 0.85,
        ],
        [
            'template' => HabitTemplate::VorlesungNachbereiten,
            'schedule' => ScheduleType::Dynamic,
            'trigger' => 'nach der Vorlesung',
            'time' => null,
            'days' => null,
            'consistency' => 0.6,
        ],
        [
            'template' => HabitTemplate::EssenVorkochen,
            'schedule' => ScheduleType::Fixed,
            'trigger' => null,
            'time' => '17:00',
            'days' => [1, 2, 3, 4, 5],
            'consistency' => 0.4,
        ],
        [
            'template' => HabitTemplate::Meditieren,
            'schedule' => ScheduleType::Dynamic,
            'trigger' => 'vor dem Schlafengehen',
            'time' => null,
            'days' => null,
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

        $this->seedSleepScheduleFor($user);
        $this->seedHabitsFor($user);
    }

    /**
     * Ein realistischer Studentenrhythmus: unter der Woche früh raus mit
     * Wecker, am Wochenende später — genau der Fall, für den es den Plan je
     * Wochentag gibt.
     */
    private function seedSleepScheduleFor(User $user): void
    {
        foreach (range(1, 7) as $weekday) {
            $weekend = $weekday >= 6;

            $user->sleepSchedules()->create([
                'weekday' => $weekday,
                'wake_time' => $weekend ? '09:00' : '07:00',
                'bedtime' => $weekend ? '23:30' : '23:00',
                'alarm_enabled' => ! $weekend,
            ]);
        }
    }

    private function seedHabitsFor(User $user): void
    {
        $today = Carbon::today();

        foreach (self::DemoHabits as $position => $demo) {
            $template = $demo['template'];

            $habit = $user->habits()->create([
                'title' => $template->title(),
                'template_key' => $template->value,
                'behavior_type' => $template->behaviorType(),
                'schedule_type' => $demo['schedule'],
                'trigger_situation' => $demo['trigger'],
                'scheduled_time' => $demo['time'],
                'scheduled_days' => $demo['days'],
                'target_amount' => $template->defaultMinutes(),
                'target_unit' => MeasureUnit::Minutes,
                'position' => $position,
                'committed_at' => now(),
            ]);

            // Rückwirkend anlegen, damit die Konsistenzrate ein volles
            // 30-Tage-Fenster hat statt nur den heutigen Tag.
            $habit->forceFill(['created_at' => $today->copy()->subDays(45)])->save();

            // Die erste Gewohnheit ist heute erledigt, die übrigen stehen noch
            // offen — beide Zustände sind so sichtbar.
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

            // Nur an vorgesehenen Tagen: Eine Erfüllung am Dienstag zu einer
            // Mo/Mi/Fr-Gewohnheit würde die Konsistenzrate über 100 % treiben.
            if (! $habit->isScheduledOn($date)) {
                continue;
            }

            $habit->completions()->create([
                'completed_on' => $date,
                'completed_at' => $date->copy()->setTime(7, 30),
            ]);
        }
    }
}
