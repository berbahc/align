<?php

namespace App\Ai\Agents\Concerns;

use App\Ai\UserContext;

/**
 * Die Stimme, in der Align spricht — an genau einer Stelle.
 *
 * ki-assistent-design.md §2 legt vier Regeln fest, die auf allen Screens
 * gelten. Bis hierher schrieb jeder Agent sie einzeln aus, und zwei Kopien
 * derselben Haltung driften auseinander, sobald jemand eine davon anfasst.
 *
 * Aufgabenspezifische Regeln bleiben bei ihrem Agenten. Hier steht nur, was
 * für jede Äußerung der KI gilt.
 */
trait SpeaksForAlign
{
    /**
     * Haltung und Ton, unabhängig davon, worum es geht.
     *
     * Der Hinweis auf die Perspektive ist kein Beiwerk: Der Kontext beschreibt
     * die Person in der dritten Person („sie hakt meistens abends ab"), die
     * Antwort spricht sie in der zweiten an („stell das Glas ans Bett"). Ohne
     * diesen Satz rutscht das Modell in die Perspektive, die es zuletzt gelesen
     * hat.
     */
    protected function voice(): string
    {
        return <<<'PROMPT'
        Haltung:
        - Beobachtend, nie wertend. Kein „du hast versäumt", kein Lob, kein
          Ausrufezeichen, keine Motivationssprache, keine Emojis.
        - Deutsch, Du-Form. Was du über die Person erfährst, ist in der dritten
          Person formuliert — deine Antwort spricht sie trotzdem mit „du" an.
        - Sprich nie darüber, was du über die Person weißt. Der Kontext prägt
          deinen Vorschlag, er kommt in ihm nicht vor.
        PROMPT;
    }

    /**
     * Der Kontext als Block für die Frage — leer, wenn nichts bekannt ist.
     *
     * Ist über jemanden nichts bekannt, steht hier auch nichts. Eine
     * Überschrift ohne Inhalt wäre eine Behauptung von Wissen.
     *
     * @return list<string>
     */
    protected function contextLines(UserContext $context): array
    {
        if ($context->isEmpty()) {
            return [];
        }

        return ['', 'Was über diese Person bekannt ist:', ...$context->lines()];
    }
}
