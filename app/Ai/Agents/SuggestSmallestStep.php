<?php

namespace App\Ai\Agents;

use App\Ai\Agents\Concerns\SpeaksForAlign;
use App\Ai\UserContext;
use App\Enums\BehaviorType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
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
 *
 * Anbieter und Modell stehen bewusst nicht hier, sondern in `config/ai.php`:
 * ob der Weg zu Claude über OpenRouter oder direkt über Anthropic führt, ist
 * eine Frage des Schlüssels, nicht des Verhaltens.
 *
 * Der Token-Deckel ist keine Sparmaßnahme, sondern eine Bedingung: Ohne ihn
 * nimmt OpenRouter das Modell-Maximum an (65536) und verlangt Deckung dafür,
 * bevor die Anfrage überhaupt läuft — bei knappem Guthaben antwortet der
 * Anbieter dann mit 402, obwohl real drei kurze Sätze zurückkommen. Der Wert
 * liegt großzügig über dem, was {@see SuggestionCount} × {@see MaxStepLength}
 * je brauchen kann.
 */
#[MaxTokens(1024)]
#[Temperature(1.0)]
#[Timeout(20)]
class SuggestSmallestStep implements Agent, HasStructuredOutput
{
    use Promptable, SpeaksForAlign;

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
        private readonly ?UserContext $context = null,
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
        - Ein kurzer Satz, höchstens 120 Zeichen.
        - Die Schritte müssen sich deutlich voneinander unterscheiden.
        - Wiederhole keinen Schritt, der schon vorgeschlagen und nicht
          übernommen wurde. Er hat für diese Person nicht getragen; derselbe
          Satz noch einmal trägt genauso wenig.

        Antworte ausschließlich mit den Schritten selbst, ohne Einleitung und
        ohne Nummerierung.
        PROMPT."\n\n".$this->voice();
    }

    /**
     * Claudes natives Structured-Output-Format lehnt `minItems`/`maxItems`
     * auf Array-Typen ab („property 'maxItems' is not supported") — die
     * Anzahl steuert deshalb allein die Anweisung im Prompt, geprüft wird sie
     * anschließend in {@see suggest()}.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'steps' => $schema->array()
                ->items($schema->string()->max(self::MaxStepLength))
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
     *
     * Danach folgt, was Align über die Person weiß — der Warum-Satz, ihr
     * Tagesablauf und was ihr schon einmal vorgeschlagen wurde. Ohne diesen
     * Block wäre jeder Aufruf ein Kaltstart, und die KI könnte denselben
     * Schritt zum dritten Mal anbieten.
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

        if ($this->context !== null) {
            $lines = [...$lines, ...$this->contextLines($this->context), ''];
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
