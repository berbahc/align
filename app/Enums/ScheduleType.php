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
 * `Opportunistic` ist die dritte Form und die einzige **ohne** Platz im Tag.
 * „Treppe statt Aufzug" oder „eine Station früher aussteigen" haben weder
 * Uhrzeit noch Tagesanker: Sie ergeben sich, wo die Gelegenheit auftaucht, und
 * das kann mehrmals am Tag oder an manchen Tagen gar nicht sein. Felix in der
 * Interviewauswertung beschreibt genau das — „an der Uni bewegt er sich
 * automatisch mehr, weil der Kontext (Wege, Treppen, Campus) das Verhalten
 * auslöst". Der Kontext löst aus, nicht die Uhr.
 *
 * Ihnen eine Stunde zuzuweisen wäre eine erfundene Position, und sie wie
 * geplante Gewohnheiten zu messen ein Vorwurf: Wer nicht an jedem Tag vor einem
 * Aufzug steht, hat nichts versäumt.
 *
 * Nur `Fixed` kann erinnert werden: ohne Zeitpunkt gibt es kein „10 Minuten
 * vorher".
 */
enum ScheduleType: string
{
    case Dynamic = 'dynamic';
    case Fixed = 'fixed';
    case Opportunistic = 'opportunistic';

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
            self::Opportunistic => 'Wenn es sich ergibt',
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
            self::Opportunistic => 'Taucht auf, wann sie will.',
        };
    }

    /**
     * Hat die Gewohnheit überhaupt eine Stelle im Tag?
     *
     * Das ist die Trennlinie, an der fast alles hängt: Was nicht geplant ist,
     * steht nicht auf der Tagesachse, wird nicht erinnert, hat keine
     * Konsistenzrate und kann nichts versäumen.
     *
     * Der Code war bis hierher als `=== Fixed` gegen **alles andere**
     * geschrieben. Eine dritte Form würde darin stillschweigend als „dynamisch"
     * gelten — kein Fehler, nur falsches Verhalten. Deshalb fragen die
     * Verzweigungen ab jetzt diese Prädikate, nicht mehr den Fall.
     */
    public function isPlanned(): bool
    {
        return $this !== self::Opportunistic;
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
