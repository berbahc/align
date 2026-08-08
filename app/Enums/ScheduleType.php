<?php

namespace App\Enums;

/**
 * Wie eine Gewohnheit im Tag verankert ist.
 *
 * time-blocking.md setzt situative Cues als Standard: eine Situation löst
 * Verhalten von selbst aus, eine Uhrzeit muss aktiv erinnert werden. Das
 * bleibt die Empfehlung — `Fixed` ist die Ausnahme für Gewohnheiten, die
 * ohnehin an einem festen Zeitpunkt hängen (Kurs, Termin, Schlafenszeit).
 *
 * Nur `Fixed` kann erinnert werden: ohne Zeitpunkt gibt es kein „10 Minuten
 * vorher".
 */
enum ScheduleType: string
{
    case Dynamic = 'dynamic';
    case Fixed = 'fixed';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
        ], self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Dynamic => 'Dynamisch',
            self::Fixed => 'Feste Uhrzeit',
        };
    }
}
