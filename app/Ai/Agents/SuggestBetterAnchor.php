<?php

namespace App\Ai\Agents;

use App\Ai\Agents\Concerns\SpeaksForAlign;
use App\Ai\UserContext;
use App\Models\Habit;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

/**
 * Schlägt einen anderen Platz im Tag für eine Gewohnheit vor.
 *
 * Die dynamische Anpassung ist mit ø 4,04 die zweitbestbewertete Funktion der
 * Umfrage und bei der Einzelwahl mit 7/25 auf Platz 2. Sie beantwortet den
 * Vorwurf, den das Projekt bestehenden Apps macht: „Bestehende Apps sind sehr
 * statisch … wenn eine Gewohnheit nicht eingehalten wird, hat das keinen
 * Einfluss" (align.md).
 *
 * Vorgeschlagen wird immer **in der Form, die der Nutzer selbst gewählt hat**:
 * für eine situative Gewohnheit andere Situationen, für eine feste andere
 * Uhrzeiten. Die Planungsart zu wechseln ist keine Anpassung, sondern eine
 * andere Entscheidung — und die trifft niemand außer dem Nutzer.
 *
 * Der Vorschlag ist nie eine Setzung: Er wird angeboten, begründet und kann in
 * einem Tap abgelehnt werden (ki-assistent-design.md §2).
 */
#[Temperature(1.0)]
#[Timeout(20)]
class SuggestBetterAnchor implements Agent, HasStructuredOutput
{
    /**
     * Wie viele Alternativen der Agent anbietet.
     *
     * Zwei bis drei: genug zur Wahl, wenig genug, um nicht zur nächsten
     * Überforderung zu werden.
     */
    public const int AlternativeCount = 3;

    use Promptable, SpeaksForAlign;

    /**
     * @param  list<array{date: string, label: string}>  $misses  Tage, an denen die Gewohnheit anstand und nichts geschah
     */
    public function __construct(
        private readonly Habit $habit,
        private readonly array $misses,
        private readonly ?UserContext $context = null,
    ) {}

    public function instructions(): string
    {
        $shared = <<<'PROMPT'
        Du hilfst Studierenden dabei, eine Gewohnheit an einer Stelle im Tag zu
        verankern, an der sie tatsächlich stattfindet.

        Eine Gewohnheit klappt seit einiger Zeit nicht. Nicht, weil die Person
        zu wenig will, sondern weil der Zeitpunkt nicht trägt. Schlage andere
        Zeitpunkte vor.

        - Die Begründung sagt, warum der neue Zeitpunkt tragen könnte — nicht,
          was die Person falsch gemacht hat.
        - Ein kurzer Satz je Begründung, höchstens 100 Zeichen.
        - Schlage keinen Zeitpunkt vor, der schon einmal vorgeschlagen und nicht
          übernommen wurde, und keinen, der dem aktuellen entspricht.
        PROMPT."\n\n".$this->voice();

        if ($this->habit->schedule_type->hasClockTime()) {
            return $shared."\n\n".<<<'PROMPT'
            Diese Gewohnheit hängt an einer festen Uhrzeit. Schlage andere
            Uhrzeiten und Wochentage vor.

            - `time` ist eine Uhrzeit im Format HH:MM.
            - `days` sind die Wochentage, an denen die Gewohnheit künftig
              stattfinden soll — nicht die, die wegfallen. ISO-Nummern:
              1 = Montag bis 7 = Sonntag.
            - Nimm die Wochentage ernst, an denen es bisher nicht geklappt hat:
              manchmal ist nicht die Uhrzeit falsch, sondern der Tag. Weniger
              Tage sind eine zulässige Alternative.
            - Die Begründung muss zu den Tagen passen, die du einträgst. Nenne
              keine Tage, die nicht in `days` stehen.
            PROMPT;
        }

        return $shared."\n\n".<<<'PROMPT'
        Diese Gewohnheit hängt an einer Situation im Tagesablauf, nicht an einer
        Uhr. Schlage andere Situationen vor.

        - Eine Situation ist ein wiederkehrender Moment des Alltags, der von
          selbst eintritt: „nach dem Aufstehen", „wenn ich nach Hause komme".
        - Formuliere sie kleingeschrieben, ohne Punkt am Ende, so dass sie sich
          in den Satz „Wenn …, dann …" einsetzen lässt.
        - Wenn andere Gewohnheiten der Person genannt sind, darf eine
          Alternative daran koppeln („nach dem Zähneputzen"). Eine bestehende
          Gewohnheit ist der zuverlässigste Auslöser, den es gibt.
        PROMPT;
    }

    /**
     * Das Schema richtet sich nach der Art der Gewohnheit.
     *
     * Claudes strukturierte Ausgabe verträgt kein `minItems`/`maxItems` auf
     * Array-Typen — die Anzahl steuert deshalb die Anweisung im Prompt, geprüft
     * wird sie in {@see alternatives()}.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $item = $this->habit->schedule_type->hasClockTime()
            ? $schema->object([
                'time' => $schema->string()->description('Uhrzeit im Format HH:MM.')->required(),
                'days' => $schema->array()
                    ->items($schema->integer())
                    ->description('ISO-Wochentage, 1 = Montag bis 7 = Sonntag.')
                    ->required(),
                'reason' => $schema->string()->description('Ein kurzer Satz, warum das tragen könnte.')->required(),
            ])
            : $schema->object([
                'situation' => $schema->string()->description('Der neue Moment im Tagesablauf.')->required(),
                'reason' => $schema->string()->description('Ein kurzer Satz, warum das tragen könnte.')->required(),
            ]);

        return [
            'alternatives' => $schema->array()
                ->items($item)
                ->description('Die vorgeschlagenen Zeitpunkte.')
                ->required(),
        ];
    }

    /**
     * Holt die Alternativen und gibt zurück, was davon brauchbar ist.
     *
     * Jeder Vorschlag wird nachgeprüft, statt ihm zu vertrauen: eine Uhrzeit
     * muss eine Uhrzeit sein, ein Wochentag zwischen 1 und 7 liegen. Was die
     * Oberfläche als wählbar anbietet, muss die Validierung beim Übernehmen
     * auch akzeptieren — sonst führt ein Vorschlag in eine Fehlermeldung.
     *
     * @return list<array{situation?: string, time?: string, days?: list<int>, reason: string}>
     *
     * @throws RuntimeException wenn die Antwort keine brauchbare Alternative enthält
     */
    public function alternatives(): array
    {
        $response = $this->prompt($this->question());

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Die Antwort kam ohne die erwartete Struktur.');
        }

        $candidates = $response->structured['alternatives'] ?? null;

        if (! is_array($candidates)) {
            throw new RuntimeException('Die Antwort enthielt keine Liste von Alternativen.');
        }

        $alternatives = [];

        foreach ($candidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }

            $alternative = $this->habit->schedule_type->hasClockTime()
                ? $this->fixedAlternative($candidate)
                : $this->dynamicAlternative($candidate);

            if ($alternative === null) {
                continue;
            }

            $alternatives[] = $alternative;

            if (count($alternatives) === self::AlternativeCount) {
                break;
            }
        }

        if ($alternatives === []) {
            throw new RuntimeException('Die Antwort enthielt keine brauchbare Alternative.');
        }

        return $alternatives;
    }

    /**
     * @param  array<mixed>  $candidate
     * @return array{situation: string, reason: string}|null
     */
    private function dynamicAlternative(array $candidate): ?array
    {
        $situation = is_string($candidate['situation'] ?? null) ? trim($candidate['situation']) : '';
        $reason = $this->reason($candidate);

        // Ein Vorschlag, der dem aktuellen Anker entspricht, ist keiner.
        if ($situation === '' || mb_strlen($situation) > 120 || $situation === $this->habit->trigger_situation) {
            return null;
        }

        return ['situation' => $situation, 'reason' => $reason];
    }

    /**
     * @param  array<mixed>  $candidate
     * @return array{time: string, days: list<int>, reason: string}|null
     */
    private function fixedAlternative(array $candidate): ?array
    {
        $time = is_string($candidate['time'] ?? null) ? trim($candidate['time']) : '';

        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) !== 1) {
            return null;
        }

        if (! is_array($candidate['days'] ?? null)) {
            return null;
        }

        /** @var list<int> $days */
        $days = collect($candidate['days'])
            ->filter(fn (mixed $day): bool => is_int($day) && $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($days === []) {
            return null;
        }

        return ['time' => $time, 'days' => $days, 'reason' => $this->reason($candidate)];
    }

    /**
     * @param  array<mixed>  $candidate
     */
    private function reason(array $candidate): string
    {
        $reason = is_string($candidate['reason'] ?? null) ? trim($candidate['reason']) : '';

        return mb_substr($reason, 0, 160);
    }

    /**
     * Was der Agent über diese Gewohnheit erfährt.
     */
    private function question(): string
    {
        $lines = [
            'Gewohnheit: '.$this->habit->title,
            'Bereich: '.$this->habit->behavior_type->label(),
            'Bisheriger Zeitpunkt: '.$this->habit->scheduleLabel(),
        ];

        if ($this->misses !== []) {
            $weekdays = collect($this->misses)
                ->map(fn (array $miss): string => $miss['label'])
                ->implode(', ');

            $lines[] = sprintf(
                'Sie stand zuletzt %d× an, ohne dass etwas geschah — an diesen Tagen: %s.',
                count($this->misses),
                $weekdays,
            );
        }

        if ($this->context !== null) {
            $lines = [...$lines, ...$this->contextLines($this->context), ''];
        }

        $lines[] = sprintf('Gib %d Alternativen.', self::AlternativeCount);

        return implode("\n", $lines);
    }
}
