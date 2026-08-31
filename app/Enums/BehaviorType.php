<?php

namespace App\Enums;

/**
 * Verhaltenstyp einer Gewohnheit — die fachliche Einordnung hinter dem Katalog.
 *
 * Er steuert die Plateau-Schätzung aus habit-journey.md (Lally et al.:
 * Sportgewohnheiten brauchen rund 1,5-mal so lange wie Ess- und
 * Trinkgewohnheiten) und benennt der KI den Bereich einer Gewohnheit.
 *
 * Gewählt wird er nicht mehr: Seit dem festen Katalog bringt jede Vorlage
 * ihren Typ mit ({@see HabitTemplate::behaviorType()}). Die Kategorie, die
 * der Nutzer sieht, ist {@see HabitCategory} — sie sortiert nach
 * Lebensbereich, dieser Typ nach der Art des Verhaltens.
 */
enum BehaviorType: string
{
    case Movement = 'movement';
    case Learning = 'learning';
    case Nutrition = 'nutrition';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Movement => 'Bewegung & Sport',
            self::Learning => 'Lernen & Fokus',
            self::Nutrition => 'Essen & Trinken',
            self::Other => 'Schlaf & Erholung',
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
