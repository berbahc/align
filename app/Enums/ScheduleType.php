<?php

namespace App\Enums;

/**
 * Wie eine Gewohnheit im Tag verankert ist.
 *
 * time-blocking.md setzt situative Cues als Standard: eine Situation löst
 * Verhalten von selbst aus, eine Uhrzeit muss aktiv erinnert werden. Das
 * bleibt die Empfehlung — `Fixed` ist die Ausnahme für Gewohnheiten, die
 * ohnehin an einem festen Zeitpunkt hängen (Kurs, Termin), `Chained` das
 * Domino-Prinzip: eine Gewohnheit hängt an einer anderen.
 *
 * Eine vierte Form — `Opportunistic`, „wenn es sich ergibt" — gab es, und sie
 * ist bewusst wieder weg. „Treppe statt Aufzug" hat weder Uhrzeit noch Dauer
 * und ließ sich in kein Time-Blocking einbetten; der feste Katalog
 * ({@see HabitTemplate}) enthält nur noch planbare Aktivitäten. Jede
 * Gewohnheit hat damit wieder eine Stelle im Tag.
 *
 * Nur `Fixed` kann erinnert werden: ohne Zeitpunkt gibt es kein „10 Minuten
 * vorher".
 */
enum ScheduleType: string
{
    case Dynamic = 'dynamic';
    case Fixed = 'fixed';
    case Chained = 'chained';

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
        ], self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Dynamic => 'Situation',
            self::Fixed => 'Feste Uhrzeit',
            self::Chained => 'Nach einer Gewohnheit',
        };
    }

    /**
     * Ein Satz unter der Kachel — die Wahl ist ohne ihn nicht zu treffen.
     */
    public function description(): string
    {
        return match ($this) {
            self::Dynamic => 'Hängt an einem Moment im Tag.',
            self::Fixed => 'Steht ohnehin im Kalender.',
            self::Chained => 'Hängt an einer, die schon läuft.',
        };
    }

    /**
     * Trägt die Gewohnheit ihren Anker selbst?
     *
     * `Chained` ist geplant, leiht sich die Stelle im Tag aber von der
     * Gewohnheit, an der sie hängt: „nach dem Spaziergang" ist nur so genau,
     * wie der Spaziergang es ist. Deshalb hat sie weder Situation noch Uhrzeit
     * — und deshalb muss überall dort, wo eine der beiden Spalten gelesen wird,
     * erst diese Frage stehen.
     */
    public function hasOwnAnchor(): bool
    {
        return $this === self::Dynamic || $this === self::Fixed;
    }

    /**
     * Dieselbe Frage als Filter für eine Abfrage.
     *
     * Wer nach belegten Situationen sucht, muss die geketteten Gewohnheiten
     * ausschließen: Sie tragen die Spalte womöglich noch, belegen den Moment
     * aber nicht — gelesen wird er bei ihnen nirgends.
     *
     * @return list<string>
     */
    public static function withOwnAnchor(): array
    {
        return array_values(array_map(
            fn (self $type): string => $type->value,
            array_filter(self::cases(), fn (self $type): bool => $type->hasOwnAnchor()),
        ));
    }

    /**
     * Bringt die Gewohnheit eine Uhrzeit mit — und damit einen Zeitpunkt, an
     * dem sich erinnern und eine Spanne berechnen lässt?
     */
    public function hasClockTime(): bool
    {
        return $this === self::Fixed;
    }
}
