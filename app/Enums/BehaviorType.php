<?php

namespace App\Enums;

/**
 * Verhaltenstyp einer Gewohnheit.
 *
 * Grundlage: habit-journey.md — Lally et al. (2010) zeigen, dass die Dauer bis
 * zur Automatisierung je nach Verhaltenstyp deutlich schwankt (Median 66 Tage,
 * Spanne 18–254). Sportgewohnheiten brauchen rund 1,5-mal so lange wie Ess-
 * und Trinkgewohnheiten. Der Typ wird beim Anlegen einmalig abgefragt und
 * steuert die Plateau-Schätzung.
 */
enum BehaviorType: string
{
    case Nutrition = 'nutrition';
    case Movement = 'movement';
    case Learning = 'learning';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Nutrition => 'Essen & Trinken',
            self::Movement => 'Bewegung & Sport',
            self::Learning => 'Lernen & Lesen',
            self::Other => 'Sonstiges',
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
