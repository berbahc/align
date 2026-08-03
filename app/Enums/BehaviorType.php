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
     * @return list<array{value: string, label: string, description: string, suggestions: list<string>}>
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
     * @return list<string>
     */
    public function suggestions(): array
    {
        return match ($this) {
            self::Movement => [
                '20 Minuten spazieren',
                '10 Minuten dehnen',
                'Eine Station früher aussteigen',
                'Treppe statt Aufzug',
            ],
            self::Learning => [
                '10 Seiten lesen',
                '25 Minuten fokussiert lernen',
                'Vorlesung nachbereiten',
                'Karteikarten wiederholen',
            ],
            self::Nutrition => [
                '2 Liter Wasser trinken',
                'Essen für morgen vorbereiten',
                'Frühstücken statt auslassen',
                'Obst als Snack einpacken',
            ],
            self::Other => [
                'Zur gleichen Zeit ins Bett',
                'Handy 30 Minuten vor dem Schlafen weglegen',
                '10 Minuten meditieren',
                'Kurze Pause zwischen Vorlesungen',
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
