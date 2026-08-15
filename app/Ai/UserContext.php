<?php

namespace App\Ai;

use App\Enums\SuggestionKind;
use App\Models\AiSuggestion;
use App\Models\Habit;
use App\Models\HabitCompletion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Was Align über eine Person weiß — abgeleitet, nie erfragt.
 *
 * align.md §2.3 fragt: „Wie viel Zeit habe ich gerade? In welcher Phase des
 * Semesters befinde ich mich? Was hilft mir persönlich, dranzubleiben?" Die App
 * stellt keine dieser Fragen — sie beantwortet sie aus dem, was ohnehin
 * entsteht: dem Warum-Satz, den übrigen Ankern, der Uhrzeit der Haken und dem
 * Gedächtnis der KI.
 *
 * Alles wird in {@see for()} geholt, damit die Agenten nur noch formatieren.
 * Dieselbe Aufteilung gilt schon für `misses` und `otherAnchors`: der Controller
 * beschafft, der Agent formuliert.
 *
 * **Im Zweifel schweigen.** Reicht die Datenlage nicht, entfällt die Zeile
 * ersatzlos. Ein erfundener Rhythmus wäre dieselbe Vertrauensverletzung, die
 * `SmallestStepController::answer()` mit seinem Verzicht auf Ersatzvorschläge
 * bereits vermeidet — was nach Wissen aussieht, muss Wissen sein.
 */
final readonly class UserContext
{
    /**
     * Wie weit der Rhythmus zurückschaut.
     *
     * Dasselbe Fenster wie `Habit::consistencyRate()`: Der Rhythmus soll das
     * aktuelle Leben beschreiben, nicht das vom letzten Semester.
     */
    public const int RhythmWindowDays = 30;

    /**
     * Wie lange sich die KI an einen eigenen Vorschlag erinnert.
     *
     * Länger als das Rhythmus-Fenster — ein Zeitpunkt, den jemand nicht wollte,
     * bleibt länger unerwünscht, als ein Muster im Tagesablauf gilt.
     */
    public const int MemoryWindowDays = 60;

    /**
     * Unter wie vielen Datenpunkten kein Rhythmus benannt wird.
     *
     * Unter zehn ist ein „Muster" geraten, und geratene Beobachtungen sind
     * genau das, was die KI laut ki-assistent-design.md §2 nicht tun darf.
     */
    public const int MinimumCompletionsForRhythm = 10;

    /**
     * Ab welchem Anteil eine Tageszeit als die vorherrschende gilt.
     */
    public const float DominantShare = 0.6;

    /**
     * Wie viele frühere Vorschläge in den Prompt wandern.
     *
     * Ein Deckel gegen einen Prompt, der mit jedem Aufruf länger wird.
     */
    public const int RecentSuggestions = 12;

    /**
     * Ab wie vielen früheren Anläufen zu derselben Gewohnheit die KI hört,
     * dass sie deutlich kleiner ansetzen soll.
     *
     * Gezählt werden die Vorschläge, die **vor** diesem Aufruf schon standen.
     * Einer davon heißt: das hier ist der zweite Anlauf. Der erste „noch
     * kleiner" ist ein schwacher Tag; ab dem zweiten liegt es nicht mehr am
     * Tag, sondern am Schritt.
     */
    public const int EarlierAttemptsBeforeGoingSmaller = 1;

    /**
     * @param  list<string>  $lines
     */
    private function __construct(
        private array $lines,
    ) {}

    /**
     * Sammelt alles, was zu dieser Person und dieser Frage bekannt ist.
     *
     * `$habit` ist null, solange die Gewohnheit noch nicht existiert — im
     * Anlege-Ablauf ist nichts gespeichert, woran Kontext hängen könnte.
     */
    public static function for(User $user, SuggestionKind $kind, ?Habit $habit = null): self
    {
        $habits = $user->habits()->active()->get();
        $completions = self::completions($habits);

        $lines = array_values(array_filter([
            self::motivationLine($habit),
            self::otherAnchorsLine($habits, $habit),
            self::timeOfDayLine($completions),
            self::weekdayLine($habits, $completions),
            ...self::memoryLines($user, $kind, $habit),
            self::tenureLine($user),
        ]));

        return new self($lines);
    }

    /**
     * Ein leerer Kontext — für Aufrufe, die bewusst ohne Vorgeschichte laufen.
     */
    public static function none(): self
    {
        return new self([]);
    }

    /**
     * Die fertigen Prompt-Zeilen, in der Reihenfolge, in der sie gelesen werden.
     *
     * @return list<string>
     */
    public function lines(): array
    {
        return $this->lines;
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /**
     * Der Warum-Satz — der persönlichste Satz, den die App besitzt.
     *
     * Er wird im Anlege-Ablauf erhoben (ki-assistent-design.md, Screen 1b) und
     * stand bislang in keinem Prompt. Ein Schritt, der auf „damit ich den Kopf
     * freikriege" antwortet, ist ein anderer als einer, der nur den Titel kennt.
     */
    private static function motivationLine(?Habit $habit): ?string
    {
        $motivation = $habit?->motivation;

        if ($motivation === null || trim($motivation) === '') {
            return null;
        }

        return 'Warum ihr das wichtig ist, in ihren eigenen Worten: „'.trim($motivation).'"';
    }

    /**
     * Die übrigen Gewohnheiten mit ihren Ankern.
     *
     * Grundlage für einen Ketten-Vorschlag: eine bestehende Gewohnheit ist der
     * zuverlässigste Auslöser, den es gibt (Domino-Prinzip, time-blocking.md).
     * Für den kleinsten Schritt sagen sie etwas anderes, aber nichts
     * Geringeres — sie zeigen, wie der Tag dieser Person aussieht.
     *
     * @param  Collection<int, Habit>  $habits
     */
    private static function otherAnchorsLine(Collection $habits, ?Habit $habit): ?string
    {
        $others = $habits
            ->when($habit !== null, fn (Collection $all): Collection => $all->except([$habit?->getKey()]))
            ->map(fn (Habit $other): string => $other->title.' → '.$other->scheduleLabel());

        if ($others->isEmpty()) {
            return null;
        }

        return 'Ihre übrigen Gewohnheiten und deren Zeitpunkte: '.$others->implode('; ').'.';
    }

    /**
     * Zu welcher Tageszeit diese Person Dinge tatsächlich erledigt.
     *
     * Gelesen wird die Uhrzeit des Hakens. Nachgetragene Erfüllungen müssen
     * dabei draußen bleiben: `HabitCompletionController` setzt für sie
     * `endOfDay()`, und sie würden jede Person als Abendmenschen erscheinen
     * lassen. Erkennbar sind sie daran, dass der Eintrag an einem späteren Tag
     * entstanden ist als der, den er beschreibt.
     *
     * @param  Collection<int, HabitCompletion>  $completions
     */
    private static function timeOfDayLine(Collection $completions): ?string
    {
        $sameDay = $completions->filter(
            fn (HabitCompletion $completion): bool => $completion->created_at !== null
                && $completion->created_at->isSameDay($completion->completed_on),
        );

        if ($sameDay->count() < self::MinimumCompletionsForRhythm) {
            return null;
        }

        $buckets = $sameDay
            ->countBy(fn (HabitCompletion $completion): string => match (true) {
                $completion->completed_at->hour < 12 => 'vormittags',
                $completion->completed_at->hour < 18 => 'nachmittags',
                default => 'abends',
            })
            ->sortDesc();

        $share = $buckets->first() / $sameDay->count();

        // Ohne klare Mehrheit gibt es keine Tageszeit, sondern nur Streuung.
        if ($share < self::DominantShare) {
            return null;
        }

        return 'Sie hakt ihre Gewohnheiten meistens '.$buckets->keys()->first().' ab.';
    }

    /**
     * An welchen Wochentagen es dieser Person schwerfällt.
     *
     * Gemessen gegen die **vorgesehenen** Termine, nicht gegen Kalendertage:
     * Ein Samstag ohne Mo–Fr-Gewohnheit ist kein schwacher Tag, an ihm war
     * schlicht nichts vorgesehen — dieselbe Unterscheidung wie in
     * `Habit::consistencyRate()`.
     *
     * Benannt wird nur, was deutlich unter dem eigenen Durchschnitt liegt.
     * Sonst stünde in jedem Prompt ein Tag, nur weil irgendeiner der
     * schlechteste sein muss.
     *
     * @param  Collection<int, Habit>  $habits
     * @param  Collection<int, HabitCompletion>  $completions
     */
    private static function weekdayLine(Collection $habits, Collection $completions): ?string
    {
        $until = Carbon::today();
        $completed = $completions
            ->map(fn (HabitCompletion $completion): string => $completion->completed_on->toDateString())
            ->countBy();

        /** @var array<int, array{scheduled: int, done: int}> $perWeekday */
        $perWeekday = [];

        foreach (range(self::RhythmWindowDays - 1, 0) as $offset) {
            $date = $until->copy()->subDays($offset);
            $key = $date->dayOfWeekIso;

            foreach ($habits as $habit) {
                // Was es noch nicht gab, kann niemand versäumt haben —
                // dieselbe Grenze wie in `Habit::recentMisses()`.
                if ($habit->created_at?->startOfDay()->greaterThan($date)) {
                    continue;
                }

                if (! $habit->isScheduledOn($date)) {
                    continue;
                }

                $perWeekday[$key]['scheduled'] = ($perWeekday[$key]['scheduled'] ?? 0) + 1;
                $perWeekday[$key]['done'] = ($perWeekday[$key]['done'] ?? 0)
                    + ($habit->completions
                        ->contains(fn (HabitCompletion $completion): bool => $completion->completed_on->isSameDay($date)) ? 1 : 0);
            }
        }

        $scheduled = array_sum(array_column($perWeekday, 'scheduled'));
        $done = array_sum(array_column($perWeekday, 'done'));

        if ($scheduled < self::MinimumCompletionsForRhythm || $done < 1) {
            return null;
        }

        $average = $done / $scheduled;

        $weak = collect($perWeekday)
            // Ein einzelner Termin an einem Wochentag ist kein Tagesmuster.
            ->filter(fn (array $day): bool => $day['scheduled'] >= 3)
            ->filter(fn (array $day): bool => $average / 2 > $day['done'] / $day['scheduled'])
            ->keys()
            ->map(fn (int $weekday): string => Habit::WeekdayAbbreviations[$weekday]);

        if ($weak->isEmpty()) {
            return null;
        }

        return 'Über alle Gewohnheiten hinweg klappt es an diesen Wochentagen am seltensten: '
            .$weak->implode(', ').'.';
    }

    /**
     * Was die KI dieser Person schon einmal gesagt hat.
     *
     * Der Kern des Gedächtnisses. Ohne diese Zeilen kann derselbe Anker zum
     * dritten Mal vorgeschlagen werden — und eine KI, die sich wiederholt, hat
     * nicht mitgedacht, sondern nur geantwortet.
     *
     * Der Zuschnitt folgt der Frage: Anker gelten je Gewohnheit (derselbe
     * Zeitpunkt kann für eine andere Gewohnheit völlig richtig sein), Schritte
     * im Anlege-Ablauf gelten für die Person, weil es die Gewohnheit noch nicht
     * gibt.
     *
     * @return list<string>
     */
    private static function memoryLines(User $user, SuggestionKind $kind, ?Habit $habit): array
    {
        $remembered = $user->aiSuggestions()
            ->ofKind($kind)
            ->when(
                $habit !== null,
                fn ($query) => $query->where('habit_id', $habit?->getKey()),
                fn ($query) => $query->whereNull('habit_id'),
            )
            ->where('created_at', '>=', Carbon::now()->subDays(self::MemoryWindowDays))
            ->latest()
            ->limit(self::RecentSuggestions)
            ->get();

        if ($remembered->isEmpty()) {
            return [];
        }

        $lines = [];

        $notTaken = $remembered
            ->whereNull('accepted_at')
            ->map(fn (AiSuggestion $suggestion): string => '„'.$suggestion->label.'"')
            ->unique();

        if ($notTaken->isNotEmpty()) {
            $lines[] = $kind->memoryIntro().': '.$notTaken->implode(', ').'.';
        }

        $taken = $remembered
            ->whereNotNull('accepted_at')
            ->map(fn (AiSuggestion $suggestion): string => '„'.$suggestion->label.'"')
            ->unique();

        if ($taken->isNotEmpty()) {
            $lines[] = 'Übernommen wurde dagegen: '.$taken->implode(', ').'.';
        }

        // Nur für die Starthilfe und nur mit bestehender Gewohnheit: wie oft
        // schon nach einem Schritt gefragt wurde. Wer zum wiederholten Mal
        // fragt, hat kein Motivationsproblem — der Schritt ist zu groß.
        if ($kind === SuggestionKind::SmallestStep && $habit !== null
            && $remembered->count() >= self::EarlierAttemptsBeforeGoingSmaller) {
            $lines[] = sprintf(
                'Für diese Gewohnheit wurde schon %d× ein Schritt gesucht — geh diesmal deutlich kleiner an.',
                $remembered->count(),
            );
        }

        return $lines;
    }

    /**
     * Wie lange diese Person Align schon benutzt.
     *
     * Woche eins und Monat drei sind verschiedene Gespräche: am Anfang trägt
     * ein Vorschlag, der fast nichts verlangt, später darf er etwas mehr sein.
     */
    private static function tenureLine(User $user): ?string
    {
        $since = $user->created_at;

        if ($since === null) {
            return null;
        }

        $days = (int) $since->copy()->startOfDay()->diffInDays(Carbon::today());

        return match (true) {
            $days < 7 => 'Sie hat gerade erst mit Align angefangen.',
            $days < 28 => 'Sie nutzt Align seit ein paar Wochen.',
            $days < 90 => 'Sie nutzt Align seit ein paar Monaten.',
            default => 'Sie nutzt Align seit mehreren Monaten.',
        };
    }

    /**
     * Die Erfüllungen der aktiven Gewohnheiten im Rhythmus-Fenster.
     *
     * Wird zweimal gelesen (Tageszeit und Wochentag) und deshalb einmal geholt.
     * Die Zeilen werden zusätzlich an ihre Gewohnheit gehängt, damit der
     * Wochentag-Blick sie ohne zweite Abfrage findet.
     *
     * @param  Collection<int, Habit>  $habits
     * @return Collection<int, HabitCompletion>
     */
    private static function completions(Collection $habits): Collection
    {
        if ($habits->isEmpty()) {
            return collect();
        }

        $completions = HabitCompletion::query()
            ->whereIn('habit_id', $habits->pluck('id')->all())
            ->where('completed_on', '>=', Carbon::today()->subDays(self::RhythmWindowDays - 1)->startOfDay())
            ->get();

        $byHabit = $completions->groupBy('habit_id');

        foreach ($habits as $habit) {
            $habit->setRelation('completions', $byHabit->get($habit->getKey(), collect()));
        }

        return $completions;
    }
}
