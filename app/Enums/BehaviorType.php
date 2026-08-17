<?php

namespace App\Enums;

/**
 * Verhaltenstyp einer Gewohnheit — im Ablauf „Richtung" genannt.
 *
 * Zwei Aufgaben in einem: fachlich steuert der Typ die Plateau-Schätzung aus
 * habit-journey.md (Lally et al.: Sportgewohnheiten brauchen rund 1,5-mal so
 * lange wie Ess- und Trinkgewohnheiten). Im Ablauf ist er der Einstieg — der
 * Nutzer wählt zuerst eine Richtung und bekommt daraufhin konkrete Vorschläge,
 * statt vor einem leeren Feld zu stehen.
 *
 * Ngocanh in der Interviewauswertung: „Ich weiß oft nicht, wo ich anfangen
 * soll, dann werde ich überfordert und fange erst gar nicht an."
 */
enum BehaviorType: string
{
    case Movement = 'movement';
    case Learning = 'learning';
    case Nutrition = 'nutrition';
    case Other = 'other';

    /**
     * @return list<array{value: string, label: string, description: string, suggestions: list<array{title: string, amount: float|null, unit: string|null, plannable: bool}>}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
            'suggestions' => $type->suggestions(),
        ], self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Movement => 'Bewegung & Sport',
            self::Learning => 'Lernen & Fokus',
            self::Nutrition => 'Essen & Trinken',
            self::Other => 'Schlaf & Erholung',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Movement => 'Mehr Bewegung in einen Tag bringen, der viel im Sitzen stattfindet.',
            self::Learning => 'Dem Selbststudium eine feste Form geben, statt es aufzuschieben.',
            self::Nutrition => 'Essen und Trinken als verlässlichen Anker im Tag.',
            self::Other => 'Zur Ruhe kommen — die Grundlage, auf der alles andere steht.',
        };
    }

    /**
     * Vorschläge aus dem Studienalltag, abgeleitet aus den Interviews.
     *
     * Felix bewegt sich an der Uni von selbst mehr, aber nicht abends aus
     * eigenem Antrieb. Aylins Meal Prep ist ihr Tagesanker. Danial nennt Schlaf
     * die Voraussetzung für alles andere. Ngocanh braucht den ersten kleinen
     * Schritt, nicht das große Ziel — deshalb sind alle Vorschläge klein.
     *
     * Titel und Umfang stehen getrennt, statt als ein Satz („20 Minuten
     * spazieren"). Die Kachel zeigt weiterhin beides und bleibt damit genauso
     * konkret wie vorher — nur ist die Zahl jetzt ein Stepper und keine fremde
     * Vorgabe mehr. Wo kein Umfang passt, steht keiner: „Treppe statt Aufzug"
     * misst sich nicht.
     *
     * `plannable` sagt, ob der Vorschlag überhaupt eine Stelle im Tag haben
     * kann. „Treppe statt Aufzug" und „eine Station früher aussteigen" haben
     * keine: Sie hängen an einer Gelegenheit, die auftaucht, wann sie will —
     * für sie steht Schritt 3 auf `Opportunistic` statt auf einer erfundenen
     * Uhrzeit. Vorgewählt, nicht erzwungen: Wer einen festen Platz dafür hat,
     * stellt um.
     *
     * @return list<array{title: string, amount: float|null, unit: string|null, plannable: bool}>
     */
    public function suggestions(): array
    {
        return match ($this) {
            self::Movement => [
                ['title' => 'Spazieren gehen', 'amount' => 20, 'unit' => MeasureUnit::Minutes->value, 'plannable' => true],
                ['title' => 'Dehnen', 'amount' => 10, 'unit' => MeasureUnit::Minutes->value, 'plannable' => true],
                ['title' => 'Eine Station früher aussteigen', 'amount' => null, 'unit' => null, 'plannable' => false],
                ['title' => 'Treppe statt Aufzug', 'amount' => null, 'unit' => null, 'plannable' => false],
            ],
            self::Learning => [
                ['title' => 'Lesen', 'amount' => 10, 'unit' => MeasureUnit::Pages->value, 'plannable' => true],
                ['title' => 'Fokussiert lernen', 'amount' => 25, 'unit' => MeasureUnit::Minutes->value, 'plannable' => true],
                ['title' => 'Vorlesung nachbereiten', 'amount' => null, 'unit' => null, 'plannable' => true],
                ['title' => 'Karteikarten wiederholen', 'amount' => null, 'unit' => null, 'plannable' => true],
            ],
            self::Nutrition => [
                ['title' => 'Wasser trinken', 'amount' => 2, 'unit' => MeasureUnit::Liters->value, 'plannable' => true],
                ['title' => 'Essen für morgen vorbereiten', 'amount' => null, 'unit' => null, 'plannable' => true],
                ['title' => 'Frühstücken statt auslassen', 'amount' => null, 'unit' => null, 'plannable' => true],
                ['title' => 'Obst als Snack einpacken', 'amount' => null, 'unit' => null, 'plannable' => true],
            ],
            self::Other => [
                ['title' => 'Zur gleichen Zeit ins Bett', 'amount' => null, 'unit' => null, 'plannable' => true],
                ['title' => 'Handy vor dem Schlafen weglegen', 'amount' => 30, 'unit' => MeasureUnit::Minutes->value, 'plannable' => true],
                ['title' => 'Meditieren', 'amount' => 10, 'unit' => MeasureUnit::Minutes->value, 'plannable' => true],
                // Zwischen zwei Vorlesungen — ob und wann es die gibt, weiß der
                // Stundenplan, nicht die App.
                ['title' => 'Kurze Pause zwischen Vorlesungen', 'amount' => null, 'unit' => null, 'plannable' => false],
            ],
        };
    }

    /**
     * Erwartete Wochen bis zum Plateau, als Spanne.
     *
     * @return array{int, int}
     */
    public function expectedWeeksToPlateau(): array
    {
        return match ($this) {
            self::Nutrition => [6, 8],
            self::Movement => [10, 15],
            self::Learning => [8, 12],
            self::Other => [8, 12],
        };
    }
}
