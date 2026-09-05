<?php

namespace App\Ai\Agents;

use App\Ai\Agents\Concerns\SpeaksForAlign;
use App\Ai\UserContext;
use App\Models\Habit;
use App\Models\SleepSchedule;
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
 * Sucht neue Plätze für Gewohnheiten, die der Stundenplan verdrängt hat.
 *
 * Alle auf einmal, nicht eine nach der anderen: Einzeln gefragt wüsste kein
 * Vorschlag vom anderen, und zwei Gewohnheiten landeten im selben freien
 * Fenster. Hier sieht das Modell die ganze Liste und plant sie gemeinsam —
 * was es der einen gibt, ist für die andere weg.
 *
 * Das Gegenteil von {@see SuggestBetterAnchor}: Dort ist die bisherige Zeit
 * ausdrücklich egal, weil sie nicht funktioniert hat. Hier ist sie das
 * Wertvollste, was es gibt — sie *hat* funktioniert, und nur ein Kurs liegt
 * jetzt darauf. Die Routine soll überleben, nicht neu erfunden werden.
 *
 * Nur Uhrzeiten, keine Situationen. `Habit::situationChoicesFor()` kennt
 * sechs Momente, jeder trägt genau eine Gewohnheit, und eine Reservierung
 * über mehrere Vorschläge hinweg gibt es nicht. Eine verdrängte Gewohnheit
 * hatte ohnehin eine Uhrzeit.
 *
 * Der Server rechnet, das Modell wählt: Die freien Fenster kommen je
 * Wochentag und schon beschnitten auf das, was die Gewohnheit zulässt — ein
 * Frühstück bekommt nur den Morgen zur Wahl. Was das Modell zurückgibt, wird
 * trotzdem nachgeprüft, Zeile für Zeile, und die Zeilen gegeneinander.
 */
#[MaxTokens(1024)]
#[Temperature(1.0)]
#[Timeout(25)]
class SuggestNewPlaces implements Agent, HasStructuredOutput
{
    use Promptable, SpeaksForAlign;

    /** Wie viel Luft zwischen zwei neu gesetzten Gewohnheiten bleibt. */
    private const int BreatherMinutes = DayPlan::BreatherMinutes;

    /**
     * @param  list<array{id: int, title: string, minutes: int, previousTime: string, previousDays: list<int>, band: array{from: int, to: int}|null, bandIsHard: bool, windows: array<int, list<array{from: int, to: int}>>}>  $habits  Je Gewohnheit: was sie ist, wann sie lief, und wo je Wochentag noch Platz ist
     * @param  array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>  $sleepWindows
     */
    public function __construct(
        private readonly array $habits,
        private readonly array $sleepWindows,
        private readonly ?UserContext $context = null,
    ) {}

    public function instructions(): string
    {
        return <<<'PROMPT'
        Du suchst neue Plätze für Gewohnheiten, die aus dem Tag gefallen sind.

        Eine studierende Person hat ihren neuen Stundenplan eingetragen. Die
        Kurse liegen jetzt dort, wo vorher ihre Gewohnheiten lagen. Die
        Gewohnheiten sind deshalb ohne Platz — nicht abgeschafft, nur ohne
        Stelle im Tag. Deine Aufgabe ist, ihre Routine über das neue Semester
        zu retten.

        **Die bisherige Uhrzeit ist dein wichtigster Hinweis.** Sie steht bei
        jeder Gewohnheit dabei, und sie hat funktioniert. Eine Routine trägt,
        weil sie an derselben Stelle im Tag liegt. Bleib so nah wie möglich an
        der bisherigen Zeit. Weiche nur ab, wenn es einen inhaltlichen Grund
        gibt — Nachbereiten direkt nach der Vorlesung trägt besser als drei
        Stunden davor — und schreib den Grund hin.

        **Wo eine Tageszeit dabeisteht, ist sie eine Grenze und keine
        Empfehlung.** Ein Frühstück gehört an den Morgen, auch wenn mittags
        mehr Platz ist. Die Fenster, die du bekommst, liegen schon innerhalb
        dieser Grenze; außerhalb davon gibt es nichts zu wählen.

        Feste Regeln — ein Vorschlag, der eine verletzt, wird verworfen:

        - `id` ist die mitgegebene Kennung, unverändert.
        - `time` ist eine Uhrzeit im Format HH:MM.
        - `days` sind ISO-Wochentage (1 = Montag … 7 = Sonntag) und dürfen
          nur Tage enthalten, an denen die Gewohnheit bisher lief. Du darfst
          Tage weglassen, keine hinzufügen. Klemmt es nur an einem Tag, ist
          „an den anderen weiter zur alten Zeit" oft die beste Antwort.
        - Die Uhrzeit muss an jedem gewählten Tag in eines der Fenster fallen,
          die für diesen Tag dastehen — mit der ganzen Dauer.
        - Zwei Gewohnheiten dürfen nicht übereinanderliegen. Du planst alle
          auf einmal: Was du der einen gibst, ist für die andere weg. Lass
          dazwischen mindestens 15 Minuten Luft.

        Und:

        - Findest du für eine Gewohnheit keinen Platz, der all das erfüllt,
          lass sie weg. Ein falscher Platz ist schlechter als keiner — die
          Person legt sie dann selbst hin.
        - Ein Satz Begründung je Gewohnheit, höchstens 100 Zeichen. Er sagt,
          warum der neue Platz trägt — nicht, was der Stundenplan angerichtet
          hat.
        PROMPT."\n\n".$this->voice();
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'places' => $schema->array()
                ->items($schema->object([
                    'id' => $schema->integer()->description('Die Kennung der Gewohnheit, unverändert.')->required(),
                    'time' => $schema->string()->description('Uhrzeit im Format HH:MM.')->required(),
                    'days' => $schema->array()->items($schema->integer())
                        ->description('ISO-Wochentage, an denen sie künftig läuft — höchstens die bisherigen.')->required(),
                    'reason' => $schema->string()->description('Ein kurzer Satz, warum der neue Platz trägt.')->required(),
                ]))
                ->description('Je Gewohnheit, für die du einen Platz gefunden hast, eine Zeile.')
                ->required(),
        ];
    }

    /**
     * Die neuen Plätze — geprüft, nicht geglaubt.
     *
     * Anders als {@see SuggestDayOrder::order()} wird hier nicht verworfen, was
     * unvollständig ist: Ein halber Erfolg ist hier die Regel, nicht der
     * Fehler. Was das Modell weglässt oder was die Prüfung wegwirft, legt die
     * Person selbst hin. Nur wenn nichts übrig bleibt, gibt es nichts.
     *
     * @return list<array{id: int, time: string, days: list<int>, minutes: int, reason: string}>
     *
     * @throws RuntimeException wenn die Antwort keinen brauchbaren Platz enthält
     */
    public function places(): array
    {
        $response = $this->prompt($this->question());

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Die Antwort kam ohne die erwartete Struktur.');
        }

        $rows = $response->structured['places'] ?? null;

        if (! is_array($rows)) {
            throw new RuntimeException('Die Antwort enthielt keine Plätze.');
        }

        $byId = collect($this->habits)->keyBy('id');
        $placed = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = is_int($row['id'] ?? null) ? $row['id'] : null;

            if ($id === null || ! $byId->has($id) || isset($placed[$id])) {
                continue;
            }

            /** @var array{id: int, title: string, minutes: int, previousTime: string, previousDays: list<int>, band: array{from: int, to: int}|null, bandIsHard: bool, windows: array<int, list<array{from: int, to: int}>>} $habit */
            $habit = $byId->get($id);

            $place = $this->checkedPlace($habit, $row);

            if ($place === null) {
                continue;
            }

            $placed[$id] = $place;
        }

        /** @var list<array{id: int, time: string, days: list<int>, minutes: int, reason: string}> $places */
        $places = array_values($placed);

        $places = $this->withoutOverlaps($places);

        if ($places === []) {
            throw new RuntimeException('Die Antwort enthielt keinen brauchbaren Platz.');
        }

        return $places;
    }

    /**
     * Eine Zeile gegen alles prüfen, was für diese Gewohnheit gilt.
     *
     * @param  array{id: int, title: string, minutes: int, previousTime: string, previousDays: list<int>, band: array{from: int, to: int}|null, bandIsHard: bool, windows: array<int, list<array{from: int, to: int}>>}  $habit
     * @param  array<mixed>  $row
     * @return array{id: int, time: string, days: list<int>, minutes: int, reason: string}|null
     */
    private function checkedPlace(array $habit, array $row): ?array
    {
        $time = is_string($row['time'] ?? null) ? trim($row['time']) : '';

        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) !== 1) {
            return null;
        }

        $days = $this->days($row['days'] ?? null, $habit['previousDays']);

        if ($days === []) {
            return null;
        }

        $start = DayPlan::toMinutes($time);
        $end = $start + $habit['minutes'];

        // Die Tageszeit als Grenze — nur, wo sie eine ist. Ein Fenster, das
        // vor dem Aufstehen läge, ist zum Hinweis geworden und sperrt nichts.
        if ($habit['bandIsHard'] && $habit['band'] !== null
            && ($start < $habit['band']['from'] || $end > $habit['band']['to'])) {
            return null;
        }

        foreach ($days as $day) {
            $window = $this->sleepWindows[$day] ?? null;

            if ($window !== null && ! SleepSchedule::containsTime($window['wakeTime'], $window['bedtime'], $time)) {
                return null;
            }

            if (! $this->fitsOnDay($habit['windows'][$day] ?? [], $start, $end)) {
                return null;
            }
        }

        return [
            'id' => $habit['id'],
            'time' => $time,
            'days' => $days,
            'minutes' => $habit['minutes'],
            'reason' => mb_substr(is_string($row['reason'] ?? null) ? trim($row['reason']) : '', 0, 160),
        ];
    }

    /**
     * Nur bisherige Tage, keine neuen; leer heißt: keine brauchbaren.
     *
     * @param  list<int>  $previous
     * @return list<int>
     */
    private function days(mixed $raw, array $previous): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $days = array_values(array_unique(array_filter(
            array_map(fn (mixed $day): int => is_int($day) ? $day : (int) $day, $raw),
            fn (int $day): bool => $day >= 1 && $day <= 7 && in_array($day, $previous, strict: true),
        )));
        sort($days);

        return $days;
    }

    /**
     * @param  list<array{from: int, to: int}>  $windows
     */
    private function fitsOnDay(array $windows, int $start, int $end): bool
    {
        foreach ($windows as $window) {
            if ($start >= $window['from'] && $end <= $window['to']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Zwei neu gesetzte Gewohnheiten dürfen sich nicht überschneiden.
     *
     * Je Wochentag: Was an verschiedenen Tagen liegt, kann sich nicht
     * treffen. Bei einem Treffer bleibt die frühere Zeile stehen — sie war
     * zuerst da, und das Modell hat sie zuerst gesetzt.
     *
     * @param  list<array{id: int, time: string, days: list<int>, minutes: int, reason: string}>  $places
     * @return list<array{id: int, time: string, days: list<int>, minutes: int, reason: string}>
     */
    private function withoutOverlaps(array $places): array
    {
        $kept = [];

        foreach ($places as $place) {
            $start = DayPlan::toMinutes($place['time']);
            $end = $start + $place['minutes'];

            foreach ($kept as $other) {
                if (array_intersect($place['days'], $other['days']) === []) {
                    continue;
                }

                $otherStart = DayPlan::toMinutes($other['time']);
                $otherEnd = $otherStart + $other['minutes'];

                if ($start < $otherEnd + self::BreatherMinutes && $end + self::BreatherMinutes > $otherStart) {
                    continue 2;
                }
            }

            $kept[] = $place;
        }

        return $kept;
    }

    /**
     * Was der Agent über die verdrängten Gewohnheiten erfährt.
     */
    private function question(): string
    {
        $lines = ['Gewohnheiten ohne Platz:'];

        foreach ($this->habits as $habit) {
            $lines[] = '';
            $lines[] = sprintf(
                '- [%d] %s, %d Minuten. Lief bisher um %s an: %s.',
                $habit['id'],
                $habit['title'],
                $habit['minutes'],
                $habit['previousTime'],
                implode(', ', array_map(fn (int $day): string => Habit::WeekdayAbbreviations[$day], $habit['previousDays'])),
            );

            if ($habit['band'] !== null) {
                $lines[] = sprintf(
                    $habit['bandIsHard']
                        ? '  Gehört in die Zeit von %s bis %s — das ist eine Grenze.'
                        : '  Gehört eigentlich in die Zeit von %s bis %s; der Tag dieser Person beginnt später, nimm es als Richtung.',
                    DayPlan::toTime($habit['band']['from']),
                    DayPlan::toTime($habit['band']['to']),
                );
            }

            foreach ($habit['windows'] as $day => $windows) {
                if ($windows === []) {
                    continue;
                }

                $lines[] = sprintf(
                    '  Frei am %s: %s.',
                    Habit::WeekdayAbbreviations[$day],
                    implode(', ', array_map(
                        fn (array $window): string => DayPlan::toTime($window['from']).' bis '.DayPlan::toTime($window['to']),
                        $windows,
                    )),
                );
            }
        }

        if ($this->context !== null) {
            $lines = [...$lines, ...$this->contextLines($this->context)];
        }

        $lines[] = '';
        $lines[] = 'Gib je Gewohnheit einen Platz — oder lass sie weg.';

        return implode("\n", $lines);
    }
}
