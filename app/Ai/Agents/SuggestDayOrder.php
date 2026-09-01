<?php

namespace App\Ai\Agents;

use App\Ai\Agents\Concerns\SpeaksForAlign;
use App\Support\DayPlan;
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
 * Ordnet den ganzen Tag neu — alle Gewohnheiten auf einmal.
 *
 * Die Einzelanpassung ({@see SuggestBetterAnchor}) verschiebt eine Gewohnheit
 * und lässt die übrigen, wo sie sind. Das reicht, solange nur eine nicht
 * passt; sobald sich der Tag insgesamt verschoben hat — eine längere Dauer,
 * ein anderer Schlafrhythmus —, ist die Frage nicht mehr „wohin mit dieser",
 * sondern „wie liegt der Tag".
 *
 * Der Agent bekommt den Rahmen und alle Gewohnheiten mit ihrer Dauer und gibt
 * eine Reihenfolge mit Uhrzeiten zurück. Er erfindet nichts: Weder Gewohnheiten
 * noch Zeiten außerhalb des Rahmens, und die Summe muss hineinpassen — das
 * rechnet {@see DayPlan} vorher aus, nicht der Agent.
 *
 * Vorgeschlagen, nicht gesetzt: Der Nutzer sieht den neuen Tag neben dem alten
 * und entscheidet (ki-assistent-design.md §2).
 */
#[MaxTokens(1024)]
#[Temperature(1.0)]
#[Timeout(25)]
class SuggestDayOrder implements Agent, HasStructuredOutput
{
    use Promptable, SpeaksForAlign;

    /**
     * @param  string  $weekdayName  Der Wochentag, um den es geht
     * @param  array{from: int, to: int}  $frame  Der wache Teil des Tages, in Minuten
     * @param  list<array{id: int, title: string, minutes: int, anchor: string}>  $habits  Was an diesem Tag ansteht
     */
    public function __construct(
        private readonly string $weekdayName,
        private readonly array $frame,
        private readonly array $habits,
    ) {}

    public function instructions(): string
    {
        return <<<'PROMPT'
        Du ordnest den Tag einer studierenden Person neu.

        Du bekommst ihren wachen Tag als Zeitspanne und alle Gewohnheiten, die
        an diesem Tag anstehen, mit ihrer Dauer. Gib für jede eine Uhrzeit
        zurück, so dass ein Tag entsteht, der sich durchhalten lässt.

        Feste Regeln — ein Vorschlag, der eine verletzt, ist unbrauchbar:

        - Jede Gewohnheit kommt genau einmal vor. Erfinde keine, lass keine weg.
        - `id` ist die mitgegebene Kennung, unverändert.
        - `time` ist eine Uhrzeit im Format HH:MM und liegt im wachen Tag.
        - Zwischen zwei Gewohnheiten liegen mindestens 15 Minuten Luft. Die
          Dauer der vorigen zählt dabei mit.
        - Keine zwei Gewohnheiten überschneiden sich.

        Und die Haltung dahinter:

        - Was Ruhe braucht, gehört an den Rand des Tages; was Schwung braucht,
          nach vorn. Eine Mahlzeit liegt dort, wo man isst.
        - Ein Tag ohne Luft ist kein Plan, sondern eine Taktung. Verteile, statt
          alles aneinanderzureihen.
        - Ein Satz Begründung für den ganzen Tag, höchstens 140 Zeichen. Er sagt,
          worin die Ordnung liegt — nicht, was vorher falsch war.
        PROMPT."\n\n".$this->voice();
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'order' => $schema->array()
                ->items($schema->object([
                    'id' => $schema->integer()->description('Die Kennung der Gewohnheit.')->required(),
                    'time' => $schema->string()->description('Uhrzeit im Format HH:MM.')->required(),
                ]))
                ->description('Alle Gewohnheiten mit ihrer neuen Uhrzeit.')
                ->required(),
            'reason' => $schema->string()
                ->description('Ein Satz, worin die Ordnung liegt.')
                ->required(),
        ];
    }

    /**
     * Die neue Ordnung — geprüft, nicht geglaubt.
     *
     * Jede Zeile wird gegen den Rahmen, gegen die Dauern und gegeneinander
     * geprüft. Was die Oberfläche als Vorschlag zeigt, muss beim Übernehmen
     * auch durch die Validierung gehen — sonst führt ein Vorschlag in eine
     * Fehlermeldung.
     *
     * @return array{order: list<array{id: int, title: string, time: string, minutes: int}>, reason: string}
     *
     * @throws RuntimeException wenn die Antwort keine brauchbare Ordnung enthält
     */
    public function order(): array
    {
        $response = $this->prompt($this->question());

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Die Antwort kam ohne die erwartete Struktur.');
        }

        $rows = $response->structured['order'] ?? null;

        if (! is_array($rows)) {
            throw new RuntimeException('Die Antwort enthielt keine Ordnung.');
        }

        $byId = collect($this->habits)->keyBy('id');
        $placed = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = is_int($row['id'] ?? null) ? $row['id'] : null;
            $time = is_string($row['time'] ?? null) ? trim($row['time']) : '';

            if ($id === null || ! $byId->has($id) || isset($placed[$id])) {
                continue;
            }

            if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) !== 1) {
                continue;
            }

            /** @var array{id: int, title: string, minutes: int, anchor: string} $habit */
            $habit = $byId->get($id);
            $start = DayPlan::toMinutes($time);

            // Außerhalb des wachen Tages ist keine Ordnung, sondern Nacht.
            if ($start < $this->frame['from'] || $start + $habit['minutes'] > $this->frame['to']) {
                continue;
            }

            $placed[$id] = [
                'id' => $id,
                'title' => $habit['title'],
                'time' => $time,
                'minutes' => $habit['minutes'],
            ];
        }

        // Ein Tag, in dem eine Gewohnheit fehlt, ist keine Umordnung, sondern
        // ein Verlust — dann lieber gar kein Vorschlag.
        if (count($placed) !== count($this->habits)) {
            throw new RuntimeException('Die Ordnung ließ Gewohnheiten aus.');
        }

        /** @var list<array{id: int, title: string, time: string, minutes: int}> $order */
        $order = collect($placed)
            ->sortBy(fn (array $row): int => DayPlan::toMinutes($row['time']))
            ->values()
            ->all();

        $this->assertNoOverlap($order);

        return [
            'order' => $order,
            'reason' => mb_substr(
                is_string($response->structured['reason'] ?? null) ? trim($response->structured['reason']) : '',
                0,
                160,
            ),
        ];
    }

    /**
     * Zwei Gewohnheiten zur selben Zeit sind kein Plan.
     *
     * @param  list<array{id: int, title: string, time: string, minutes: int}>  $order
     *
     * @throws RuntimeException
     */
    private function assertNoOverlap(array $order): void
    {
        $previousEnd = null;

        foreach ($order as $row) {
            $start = DayPlan::toMinutes($row['time']);

            if ($previousEnd !== null && $start < $previousEnd) {
                throw new RuntimeException('Die Ordnung überschneidet sich.');
            }

            $previousEnd = $start + $row['minutes'];
        }
    }

    /**
     * Was der Agent über den Tag erfährt.
     */
    private function question(): string
    {
        $lines = [
            'Wochentag: '.$this->weekdayName,
            sprintf(
                'Wacher Tag: %s bis %s.',
                DayPlan::toTime($this->frame['from']),
                DayPlan::toTime($this->frame['to']),
            ),
            '',
            'Gewohnheiten an diesem Tag:',
        ];

        foreach ($this->habits as $habit) {
            $lines[] = sprintf(
                '- [%d] %s, %d Minuten (bisher: %s)',
                $habit['id'],
                $habit['title'],
                $habit['minutes'],
                $habit['anchor'],
            );
        }

        return implode("\n", $lines);
    }
}
