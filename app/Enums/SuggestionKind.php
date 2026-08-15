<?php

namespace App\Enums;

/**
 * Wovon ein gemerkter KI-Vorschlag handelt.
 *
 * Die beiden Rollen der KI, die überhaupt etwas vorschlagen: „Anstoßen" (der
 * kleinste nächste Schritt) und „Anpassen" (ein anderer Platz im Tag).
 * „Verankern" fehlt bewusst — dort schlägt keine KI vor, dort wählt der Nutzer
 * aus `Habit::TriggerSuggestions` (ki-assistent-design.md §1).
 *
 * Die Art trennt das Gedächtnis in zwei Fächer: ein abgelehnter Zeitpunkt sagt
 * nichts über einen zu großen Schritt, und beides in einem Topf würde jeden
 * Prompt mit Zeilen füllen, die nicht zur Frage gehören.
 */
enum SuggestionKind: string
{
    case SmallestStep = 'smallest_step';
    case Anchor = 'anchor';

    /**
     * Wie der Prompt die bereits angebotenen Zeilen einleitet.
     *
     * Bewusst „nicht genommen" statt „abgelehnt": Die App erfasst keinen
     * ausdrücklichen Ablehn-Tap (das würde „Lass so" teurer machen als
     * „Übernehmen"). Was hier steht, ist deshalb eine Beobachtung, keine
     * Unterstellung — dieselbe Haltung, die die KI selbst einhalten soll.
     */
    public function memoryIntro(): string
    {
        return match ($this) {
            self::SmallestStep => 'Diese Schritte wurden dieser Person schon vorgeschlagen und nicht übernommen',
            self::Anchor => 'Diese Zeitpunkte wurden schon vorgeschlagen und nicht übernommen',
        };
    }
}
