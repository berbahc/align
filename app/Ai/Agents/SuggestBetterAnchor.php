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
 * Schlägt einen anderen Platz im Tag für eine Gewohnheit vor.
 *
 * Die dynamische Anpassung ist mit ø 4,04 die zweitbestbewertete Funktion der
 * Umfrage und bei der Einzelwahl mit 7/25 auf Platz 2. Sie beantwortet den
 * Vorwurf, den das Projekt bestehenden Apps macht: „Bestehende Apps sind sehr
 * statisch … wenn eine Gewohnheit nicht eingehalten wird, hat das keinen
 * Einfluss" (align.md).
 *
 * **Angeboten werden beide Formen nebeneinander**: freie Momente im Tagesablauf
 * und freie Zeitfenster. Früher blieb jede Gewohnheit in der Form, in der sie
 * angelegt war — das klang nach Respekt vor der Entscheidung des Nutzers, war
 * aber vor allem eine Einschränkung: Wenn ein Moment nicht trägt, ist eine
 * Uhrzeit manchmal genau die Antwort, und umgekehrt. Die Entscheidung bleibt
 * beim Nutzer, nur die Auswahl ist jetzt vollständig.
 *
 * Der Vorschlag ist nie eine Setzung: Er wird angeboten, begründet und kann in
 * einem Tap abgelehnt werden (ki-assistent-design.md §2).
 *
 * Zum Token-Deckel siehe {@see SuggestSmallestStep}: Ohne ihn reserviert
 * OpenRouter das Modell-Maximum und lehnt bei knappem Guthaben mit 402 ab.
 */
#[MaxTokens(1024)]
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
     * @param  array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>  $sleepWindows  Der Rahmen je Wochentag
     * @param  list<string>  $availableSituations  Die Momente, die noch frei sind — mehr gibt es nicht
     * @param  list<string>  $freeWindows  Die Zeitfenster, in die die Dauer wirklich passt
     */
    public function __construct(
        private readonly Habit $habit,
        private readonly array $misses,
        private readonly array $sleepWindows = [],
        private readonly array $availableSituations = [],
        private readonly array $freeWindows = [],
        private readonly ?UserContext $context = null,
    ) {}

    public function instructions(): string
    {
        return <<<'PROMPT'
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

        Ein Zeitpunkt hat zwei mögliche Formen, und du darfst beide anbieten —
        auch nebeneinander in derselben Antwort:

        **Ein Moment im Tagesablauf.** Trage ihn in `situation` ein, lass `time`
        leer und `days` leer. **Wähle ausschließlich aus den unten aufgezählten
        freien Momenten** und gib den Wortlaut genau so zurück, wie er dort
        steht. Erfinde keine eigenen: Der Tag dieser Person besteht aus ihren
        Gewohnheiten und ihrem Schlafrhythmus, und ein Moment, den es dort nicht
        gibt, lässt sich nicht einplanen.

        **Eine feste Uhrzeit.** Trage sie in `time` ein (Format HH:MM), dazu in
        `days` die Wochentage, an denen die Gewohnheit künftig stattfinden soll
        — nicht die, die wegfallen (ISO: 1 = Montag bis 7 = Sonntag). Lass
        `situation` leer. **Die Uhrzeit muss in eines der unten genannten freien
        Fenster fallen, und zwar so, dass die ganze Dauer hineinpasst.** Alles
        andere überschneidet sich mit etwas, das dort schon steht.

        - Nimm die Wochentage ernst, an denen es bisher nicht geklappt hat:
          manchmal ist nicht die Uhrzeit falsch, sondern der Tag. Weniger Tage
          sind eine zulässige Alternative.
        - Die Begründung muss zu den Tagen passen, die du einträgst. Nenne keine
          Tage, die nicht in `days` stehen.

        **Mische die beiden Formen.** Solange unten beides steht, gehört
        mindestens ein freier Moment und mindestens ein freies Zeitfenster in
        deine Antwort — nicht dreimal dasselbe. Ein Moment löst Verhalten von
        selbst aus; eine Uhrzeit trägt dort, wo der Tag ohnehin getaktet ist.
        Welche davon passt, entscheidet die Person, und sie kann es nur, wenn
        sie beide sieht. Wie die Gewohnheit bisher geplant war, spielt dabei
        keine Rolle.
        PROMPT."\n\n".$this->voice();
    }

    /**
     * Ein Schema für beide Formen.
     *
     * Alle Felder sind Pflicht, auch die jeweils ungenutzten: Strukturierte
     * Ausgabe verträgt keine wahlweise fehlenden Schlüssel, und ein leerer
     * String ist eine ehrlichere Antwort als ein Feld, das mal da ist und mal
     * nicht. Welche Form gemeint war, sagt der Inhalt.
     *
     * Claudes strukturierte Ausgabe verträgt außerdem kein `minItems`/`maxItems`
     * auf Array-Typen — die Anzahl steuert deshalb die Anweisung im Prompt,
     * geprüft wird sie in {@see alternatives()}.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $item = $schema->object([
            'situation' => $schema->string()
                ->description('Der Moment im Tagesablauf — leer, wenn du eine Uhrzeit vorschlägst.')
                ->required(),
            'time' => $schema->string()
                ->description('Uhrzeit im Format HH:MM — leer, wenn du einen Moment vorschlägst.')
                ->required(),
            'days' => $schema->array()
                ->items($schema->integer())
                ->description('ISO-Wochentage zur Uhrzeit, 1 = Montag bis 7 = Sonntag. Leer bei einem Moment.')
                ->required(),
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
     * muss eine Uhrzeit sein, ein Wochentag zwischen 1 und 7 liegen, ein Moment
     * in der Liste der freien stehen. Was die Oberfläche als wählbar anbietet,
     * muss die Validierung beim Übernehmen auch akzeptieren — sonst führt ein
     * Vorschlag in eine Fehlermeldung.
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

            // Was der Vorschlag trägt, sagt, welche Form er meint — nicht die
            // Planungsart, in der die Gewohnheit gerade steht.
            $alternative = $this->text($candidate, 'time') !== ''
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
        $situation = $this->text($candidate, 'situation');

        // Ein Vorschlag, der dem aktuellen Anker entspricht, ist keiner.
        if ($situation === '' || $situation === $this->habit->trigger_situation) {
            return null;
        }

        // Nur Momente, die es wirklich gibt und die frei sind. Bis hierher
        // durfte die KI welche erfinden — „nachdem ich die Laufschuhe
        // ausgezogen habe" klingt plausibel, steht aber in keinem Tag und in
        // keiner Auswahl. Der Vergleich ist unempfindlich gegen Schreibweise,
        // zurückgegeben wird der Wortlaut aus der Liste.
        foreach ($this->availableSituations as $available) {
            if (mb_strtolower($available) === mb_strtolower($situation)) {
                return ['situation' => $available, 'reason' => $this->reason($candidate)];
            }
        }

        return null;
    }

    /**
     * @param  array<mixed>  $candidate
     * @return array{time: string, days: list<int>, reason: string}|null
     */
    private function fixedAlternative(array $candidate): ?array
    {
        $time = $this->text($candidate, 'time');

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

        // Dieselbe Uhrzeit an denselben Tagen ist kein anderer Zeitpunkt.
        if ($time === $this->habit->scheduled_time?->format('H:i') && $days === $this->habit->scheduled_days) {
            return null;
        }

        // Was außerhalb des Schlafrahmens läge, weist die Validierung beim
        // Übernehmen ab — ein Vorschlag, der in eine Fehlermeldung führt, ist
        // schlechter als einer weniger. Dieselbe Begründung wie oben bei der
        // ungültigen Uhrzeit.
        foreach ($days as $day) {
            $window = $this->sleepWindows[$day] ?? null;

            if ($window !== null && ! SleepSchedule::containsTime($window['wakeTime'], $window['bedtime'], $time)) {
                return null;
            }
        }

        // Und was sich mit etwas überschneidet, das dort schon steht, ist kein
        // Time-Blocking, sondern eine Doppelbuchung. Geprüft wird die ganze
        // Dauer, nicht nur der Beginn: Eine Stunde, die um 16:50 anfängt,
        // passt nicht in ein Fenster, das um 17:00 endet.
        if (! $this->fitsInFreeWindow($time)) {
            return null;
        }

        return ['time' => $time, 'days' => $days, 'reason' => $this->reason($candidate)];
    }

    /**
     * Passt die Gewohnheit mit ihrer ganzen Dauer in eines der freien Fenster?
     *
     * Ohne bekannte Fenster wird nicht geprüft — dann trägt allein der
     * Schlafrahmen die Grenze, wie vor dieser Rechnung auch.
     */
    private function fitsInFreeWindow(string $time): bool
    {
        if ($this->freeWindows === []) {
            return true;
        }

        $start = DayPlan::toMinutes($time);
        $end = $start + ($this->habit->durationMinutes() ?? 0);

        foreach ($this->freeWindows as $window) {
            [$from, $to] = array_map(DayPlan::toMinutes(...), explode(' bis ', $window));

            if ($start >= $from && $end <= $to) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ein getrimmtes Textfeld aus der Antwort — leer, wenn es keins war.
     *
     * @param  array<mixed>  $candidate
     */
    private function text(array $candidate, string $key): string
    {
        return is_string($candidate[$key] ?? null) ? trim($candidate[$key]) : '';
    }

    /**
     * @param  array<mixed>  $candidate
     */
    private function reason(array $candidate): string
    {
        return mb_substr($this->text($candidate, 'reason'), 0, 160);
    }

    /**
     * Der Schlafrahmen als eine Zeile für den Prompt — oder nichts.
     *
     * Gleiche Zeiten an allen Tagen werden zu einem Satz zusammengezogen; wo
     * sie sich unterscheiden, steht jeder Tag einzeln. Die Zeile gilt für jede
     * Gewohnheit, seit auch eine situative eine Uhrzeit vorgeschlagen bekommen
     * kann.
     */
    private function frameLine(): ?string
    {
        if ($this->sleepWindows === []) {
            return null;
        }

        $spans = collect($this->sleepWindows)
            ->map(fn (array $window): string => $window['wakeTime'].' bis '.$window['bedtime']);

        if ($spans->unique()->count() === 1) {
            return 'Der Tag dieser Person geht von '.$spans->first().' Uhr. Schlage nichts außerhalb vor.';
        }

        $perDay = collect($this->sleepWindows)
            ->map(fn (array $window): string => sprintf(
                '%s %s bis %s',
                Habit::WeekdayAbbreviations[$window['weekday']],
                $window['wakeTime'],
                $window['bedtime'],
            ))
            ->implode(', ');

        return 'Der Tag dieser Person geht je Wochentag verschieden lang ('.$perDay.'). Schlage nichts außerhalb vor.';
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

        // Der Rahmen gehört in den Prompt, nicht nur in die Nachprüfung: Ein
        // Vorschlag um sechs, wenn der Tag um sieben beginnt, ist keine
        // Alternative — er wird verworfen, und die Person bekommt eine
        // Auswahl weniger, ohne zu erfahren, warum.
        $frame = $this->frameLine();

        if ($frame !== null) {
            $lines[] = $frame;
        }

        // Die Auswahl selbst, nicht nur ihre Grenzen: Der Tag besteht aus dem
        // Rahmen und den Gewohnheiten, die schon darin stehen — was es dort
        // nicht gibt, lässt sich nicht einplanen. Beide Listen reisen mit,
        // weil beide Formen angeboten werden dürfen.
        if ($this->availableSituations !== []) {
            $lines[] = 'Freie Momente, aus denen du wählen musst: '
                .implode(', ', $this->availableSituations).'.';
        }

        if ($this->freeWindows !== []) {
            $lines[] = sprintf(
                'Freie Fenster im Tag (die Gewohnheit dauert %d Minuten und muss ganz hineinpassen): %s.',
                $this->habit->durationMinutes() ?? 0,
                implode(', ', $this->freeWindows),
            );
        }

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
