<?php

namespace App\Ai\Agents;

use App\Enums\BehaviorType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

/**
 * Formuliert den kleinsten nächsten Schritt zu einer Gewohnheit.
 *
 * Die Starthilfe ist mit ø 4,16 die bestbewertete Funktion der Umfrage. Sie
 * beantwortet nicht die Frage „was ist mein Ziel?", sondern „was ist der eine
 * Handgriff, mit dem es losgeht?" — Ngocanh in den Interviews: „Ich weiß oft
 * nicht, wo ich anfangen soll, dann werde ich überfordert und fange erst gar
 * nicht an."
 *
 * Zwei Betriebsarten, dieselbe Aufgabe: ohne `$tooBig` schlägt der Agent
 * Schritte für eine neue Gewohnheit vor, mit `$tooBig` zerlegt er einen Schritt
 * weiter, der sich im Moment noch zu groß anfühlt.
 *
 * Der Zeitrahmen ist knapp gehalten: der Vorschlag erscheint mitten im
 * Anlege-Ablauf, und ein Wizard, der zwanzig Sekunden steht, ist kaputt.
 */
#[Provider(Lab::Anthropic)]
#[Model('claude-sonnet-5')]
#[Temperature(1.0)]
#[Timeout(20)]
class SuggestSmallestStep implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Wie viele Vorschläge der Agent liefern soll.
     *
     * Drei ist die Obergrenze aus dem Entwurf (K4): genug zur Wahl, zu wenig,
     * um wieder zur Überforderung zu werden, die die Starthilfe auflösen soll.
     */
    public const int SuggestionCount = 3;

    /**
     * Obergrenze für einen Schritt, in Zeichen — dieselbe wie in der Datenbank.
     */
    public const int MaxStepLength = 160;

    public function __construct(
        private readonly string $title,
        private readonly BehaviorType $behaviorType,
        private readonly ?string $situation = null,
        private readonly ?string $tooBig = null,
    ) {}

    /**
     * Die Haltung der KI, wie sie ki-assistent-design.md §2 festlegt:
     * beobachtend statt wertend, ein Angebot statt einer Ansage.
     */
    public function instructions(): string
    {
        return <<<'PROMPT'
        Du hilfst Studierenden dabei, mit einer Gewohnheit tatsächlich anzufangen.

        Deine einzige Aufgabe: den ersten winzigen körperlichen Handgriff
        formulieren, mit dem die Gewohnheit beginnt. Nicht die Gewohnheit selbst,
        nicht einen Plan, nicht einen Ratschlag.

        Regeln für jeden Schritt:
        - Eine einzige Handlung, die in unter zwei Minuten getan ist.
        - Körperlich und konkret: etwas anfassen, hinstellen, hinlegen, öffnen,
          anziehen. „Motiviere dich" ist kein Schritt, „Stell das Glas ans Bett"
          schon.
        - So klein, dass ein Nein sich albern anfühlt.
        - Ein kurzer Satz, höchstens 120 Zeichen, auf Deutsch, in Du-Form.
        - Kein Ausrufezeichen, kein Lob, keine Motivationssprache, keine Emojis.
          Ruhig und sachlich.
        - Die Schritte müssen sich deutlich voneinander unterscheiden.

        Antworte ausschließlich mit den Schritten selbst, ohne Einleitung und
        ohne Nummerierung.
        PROMPT;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'steps' => $schema->array()
                ->items($schema->string()->max(self::MaxStepLength))
                ->min(1)
                ->max(self::SuggestionCount)
                ->description('Die vorgeschlagenen ersten Handgriffe.')
                ->required(),
        ];
    }

    /**
     * Holt die Vorschläge und gibt sie als saubere Liste zurück.
     *
     * Die Antwort wird nachgeprüft, statt ihr zu vertrauen: leere Strings,
     * Dubletten und zu lange Sätze fliegen raus. Bleibt danach nichts übrig,
     * gilt der Aufruf als fehlgeschlagen — ein leeres Feld wäre für den Nutzer
     * dasselbe wie ein Fehler, nur ohne Erklärung.
     *
     * @return list<string>
     *
     * @throws RuntimeException wenn die Antwort keinen brauchbaren Schritt enthält
     */
    public function suggest(): array
    {
        $response = $this->prompt($this->question());

        // Ein Anbieter, der das Schema ignoriert, liefert Fließtext statt
        // Struktur. Das gilt als Ausfall — geraten wird hier nichts.
        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Die Antwort kam ohne die erwartete Struktur.');
        }

        $candidates = $response->structured['steps'] ?? null;

        if (! is_array($candidates)) {
            throw new RuntimeException('Die Antwort enthielt keine Liste von Schritten.');
        }

        /** @var list<string> $steps */
        $steps = [];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $step = trim($candidate);

            if ($step === '' || mb_strlen($step) > self::MaxStepLength) {
                continue;
            }

            if (in_array($step, $steps, strict: true)) {
                continue;
            }

            $steps[] = $step;

            if (count($steps) === self::SuggestionCount) {
                break;
            }
        }

        if ($steps === []) {
            throw new RuntimeException('Die Antwort enthielt keinen brauchbaren Schritt.');
        }

        return $steps;
    }

    /**
     * Der Kontext, den der Agent über diese eine Gewohnheit bekommt.
     *
     * Die Situation wird mitgegeben, weil ein Schritt an ihr hängt: „Leg die
     * Schuhe an die Tür" passt zu „wenn ich nach Hause komme", nicht zu „nach
     * dem Aufstehen".
     */
    private function question(): string
    {
        $lines = [
            'Gewohnheit: '.$this->title,
            'Bereich: '.$this->behaviorType->label(),
        ];

        if ($this->situation !== null && $this->situation !== '') {
            $lines[] = 'Sie beginnt in dieser Situation: '.$this->situation;
        }

        if ($this->tooBig !== null && $this->tooBig !== '') {
            $lines[] = 'Dieser Schritt fühlt sich gerade noch zu groß an: '.$this->tooBig;
            $lines[] = 'Zerlege ihn weiter. Gib genau einen noch kleineren Schritt, '
                .'der ein Teil davon ist und deutlich weniger verlangt.';

            return implode("\n", $lines);
        }

        $lines[] = sprintf('Gib %d mögliche erste Schritte.', self::SuggestionCount);

        return implode("\n", $lines);
    }
}
