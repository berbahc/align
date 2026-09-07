<?php

namespace Database\Factories;

use App\Enums\HabitTemplate;
use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use App\Http\Requests\Concerns\ChecksSituation;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Habit>
 */
class HabitFactory extends Factory
{
    /**
     * Wie viele Gewohnheiten diese Factory schon erzeugt hat.
     *
     * Situationen werden reihum vergeben statt gewürfelt: Eine Situation
     * trägt genau eine Gewohnheit ({@see ChecksSituation}),
     * und ein Würfel aus fünf Werten erzeugt bei fünf Gewohnheiten fast sicher
     * eine Dublette — der Test scheiterte dann an seinen eigenen Daten statt
     * an dem, was er prüft.
     */
    private static int $created = 0;

    /**
     * Setzt die Vergabe zurück — je Test, aufgerufen in `tests/Pest.php`.
     *
     * Ohne das liefe der Zähler über die ganze Suite weiter, und welcher
     * Moment und welche Vorlage eine Gewohnheit trifft, hinge davon ab, wie
     * viele Tests vorher liefen. Ein Test, dessen Daten von seiner Position
     * abhängen, ist kein Test.
     */
    public static function resetRotation(): void
    {
        self::$created = 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Es gibt nur noch drei Situationen, und jede traegt genau eine
        // Gewohnheit. Wer mehr als drei erzeugt — die Fuenfergrenze etwa —
        // bekommt danach feste Uhrzeiten, sonst legte die Fabrik einen Moment
        // doppelt und verletzte damit die Regel, die sie testen soll.
        $situations = array_keys(Habit::TriggerSuggestions);
        $index = self::$created++;
        $situation = $situations[$index] ?? null;

        // Die Vorlage lief reihum genauso wenig wie der Moment: Sie wurde
        // **gewürfelt**. Seit eine Vorlage nur noch eine laufende Gewohnheit
        // traegt, hiess das ein Test, der mal grün und mal rot ist — traf der
        // Würfel zufaellig die Vorlage, die der Test gleich selbst anlegt,
        // wies das Formular sie ab. Bei neunzehn Vorlagen ist das jeder
        // zwanzigste Lauf, und ein Test, der von einem Würfel abhaengt, prueft
        // nichts.
        //
        // Derselbe Zaehler wie beim Moment: Die ersten neunzehn Gewohnheiten
        // eines Tests bekommen verschiedene Vorlagen, und welche es sind,
        // steht fest.
        $templates = HabitTemplate::cases();
        $template = $templates[$index % count($templates)];

        return [
            'user_id' => User::factory(),
            'title' => $template->title(),
            'template_key' => $template->value,
            'schedule_type' => $situation === null ? ScheduleType::Fixed : ScheduleType::Dynamic,
            'trigger_situation' => $situation,
            'scheduled_time' => $situation === null
                ? sprintf('%02d:00', 9 + ($index % 8))
                : null,
            // Auch eine Situation hat Tage: Testdaten sollen sagen, was sie
            // meinen, statt sich auf den `null`-Fall zu verlassen.
            'scheduled_days' => [1, 2, 3, 4, 5, 6, 7],
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
