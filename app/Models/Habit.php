<?php

namespace App\Models;

use App\Enums\BehaviorType;
use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use Carbon\CarbonInterface;
use Database\Factories\HabitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property ScheduleType $schedule_type
 * @property string|null $trigger_situation
 * @property Carbon|null $scheduled_time
 * @property list<int>|null $scheduled_days
 * @property bool $reminder_enabled
 * @property string|null $motivation
 * @property string|null $smallest_step
 * @property BehaviorType $behavior_type
 * @property float|null $target_amount
 * @property MeasureUnit|null $target_unit
 * @property int $position
 * @property Carbon|null $committed_at
 * @property Carbon|null $graduated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'schedule_type', 'trigger_situation', 'scheduled_time', 'scheduled_days', 'reminder_enabled', 'motivation', 'smallest_step', 'behavior_type', 'target_amount', 'target_unit', 'position', 'committed_at'])]
class Habit extends Model
{
    /** @use HasFactory<HabitFactory> */
    use HasFactory;

    /**
     * Was gilt, solange die Datenbank noch nichts gesagt hat.
     *
     * Die Spalte hat denselben Default, aber der greift erst beim Einfügen.
     * Ohne diese Zeile hätte eine noch nicht gespeicherte Gewohnheit gar keine
     * Planungsart — und jede Frage danach liefe ins Leere, statt die
     * Voreinstellung des Systems zu bekommen (time-blocking.md: die Situation
     * ist der Standard, die Uhrzeit die Wahl).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'schedule_type' => ScheduleType::Dynamic->value,
    ];

    /**
     * Gemeinsame Termine zu dieser Gewohnheit.
     *
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * progress-tracking.md: höchstens 5 gleichzeitig aktive Gewohnheiten,
     * damit die Liste schmal und der Fokus erhalten bleibt.
     */
    public const int MaxActivePerUser = 5;

    /**
     * Länge des Wochenstreifens in Tagen, heute eingeschlossen.
     *
     * Zugleich die Grenze fürs Nachtragen: Was der Streifen zeigt, lässt sich
     * abhaken, alles davor nicht.
     */
    public const int WeekOverviewDays = 7;

    /**
     * Ein ausgelassener vorgesehener Tag pro Serie bleibt folgenlos.
     *
     * Der Kulanztag ist die Auflösung des Streak-Konflikts aus
     * umfrage-auswertung.md §6: Die Umfrage widerlegt die Interview-Hypothese,
     * Streaks würden demotivieren (9 dafür · 5 dagegen · 11 offen), aber der
     * Schuldwert von ø 3,92 ist der höchste gemessene Problemwert überhaupt.
     * Die Empfehlung dort lautet wörtlich: Serie sichtbar machen, den Abbruch
     * vergebend gestalten.
     *
     * Lally et al. (2010) trägt das: Einzelne Aussetzer haben keine messbaren
     * Langzeitkosten. Genau deshalb darf ein einzelner Tag die Serie nicht
     * zunichtemachen — der zweite darf es, sonst misst die Zahl nichts mehr.
     */
    public const int StreakGraceDays = 1;

    /**
     * Ab wann eine Reihe erfüllter Termine eine Serie genannt wird.
     *
     * Unter drei Gliedern ist es keine Serie, sondern ein guter Tag. Sie
     * dennoch zu beziffern würde sie aufblasen — und die Karte erschiene schon
     * am Tag nach der ersten Erfüllung.
     */
    public const int StreakMinimum = 3;

    /**
     * Vorschläge für den Situations-Picker, mit ihrer ungefähren Tagesstunde.
     *
     * time-blocking.md: situative Cues statt Uhrzeiten. Eine Situation löst
     * Verhalten automatisch aus, eine Uhrzeit muss aktiv erinnert werden.
     * Die Liste ist nur ein Angebot — eigene Eingaben sind erlaubt.
     *
     * Die Stunde ist kein Termin, sondern eine Sortierhilfe: der Kalender muss
     * Situationen und feste Uhrzeiten auf derselben Achse einordnen können, und
     * dafür braucht auch „nach dem Frühstück" eine Stelle im Tag. Angezeigt
     * wird sie nie — die Achse trägt Anker, keine Uhr.
     *
     * @var array<string, int>
     */
    public const array TriggerSuggestions = [
        'nach dem Aufstehen' => 7,
        'nach dem Frühstück' => 8,
        'nach der Morgenvorlesung' => 11,
        'nach dem Mittagessen' => 13,
        'wenn ich nach Hause komme' => 17,
        'vor dem Schlafengehen' => 22,
    ];

    /**
     * Die Stunde, an der eine selbst getippte Situation einsortiert wird.
     *
     * Die Mitte des Tages ist die ehrlichste Annahme, solange die App nicht
     * weiß, wann „wenn ich aus der Bib komme" stattfindet.
     */
    public const int UnknownAnchorHour = 12;

    /**
     * Kurzformen der Wochentage, indiziert nach ISO-Nummer (1 = Montag).
     *
     * @var array<int, string>
     */
    public const array WeekdayAbbreviations = [
        1 => 'Mo',
        2 => 'Di',
        3 => 'Mi',
        4 => 'Do',
        5 => 'Fr',
        6 => 'Sa',
        7 => 'So',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ist die Gewohnheit an diesem Tag überhaupt vorgesehen?
     *
     * Dynamische Gewohnheiten hängen an einer Situation, die an jedem Tag
     * eintreten kann — sie gelten deshalb immer. Feste Gewohnheiten gelten
     * nur an ihren Wochentagen; an allen anderen Tagen sind sie nicht offen,
     * sondern schlicht nicht vorgesehen. Der Unterschied entscheidet darüber,
     * ob ein Tag in die Konsistenzrate zählt.
     *
     * Für Gewohnheiten ohne Platz im Tag lautet die Antwort **nie**: „Treppe
     * statt Aufzug" war an keinem Tag vorgesehen. Das ist keine Herabstufung,
     * sondern der Schutz vor einer Rechnung, die sie nur verlieren kann — wer
     * an keinem Aufzug vorbeikam, hat nichts ausgelassen. Abhaken lässt sie
     * sich trotzdem an jedem Tag; dafür fragt die Oberfläche
     * {@see isAvailableOn()}.
     */
    public function isScheduledOn(Carbon $date): bool
    {
        if (! $this->schedule_type->isPlanned()) {
            return false;
        }

        if (! $this->schedule_type->hasClockTime()) {
            return true;
        }

        return in_array($date->dayOfWeekIso, $this->scheduled_days ?? [], strict: true);
    }

    /**
     * Lässt sich die Gewohnheit an diesem Tag abhaken?
     *
     * Das Gegenstück zu {@see isScheduledOn()} und der Filter der Oberfläche.
     * Was sich ergibt, war nie vorgesehen — kann aber jeden Tag vorkommen und
     * muss deshalb jeden Tag anzutippen sein.
     */
    public function isAvailableOn(Carbon $date): bool
    {
        return $this->schedule_type->isPlanned()
            ? $this->isScheduledOn($date)
            : true;
    }

    /**
     * Wie oft die Gewohnheit im Rückblickfenster erfüllt wurde.
     *
     * Die ehrliche Zahl für alles, was sich ergibt: Eine Quote braucht einen
     * Nenner, und den gibt es hier nicht. „7× in 30 Tagen" behauptet nichts
     * über ein Soll — es zählt nur, was war.
     */
    public function completionsSince(int $days = 30, ?Carbon $until = null): int
    {
        $until ??= Carbon::today();

        return $this->completions()
            ->whereBetween('completed_on', [
                $until->copy()->subDays($days - 1)->startOfDay(),
                $until->copy()->endOfDay(),
            ])
            ->count();
    }

    /**
     * Die letzten Tage im Rückblick, heute als letzter Eintrag.
     *
     * Drei Zustände statt zwei: erfüllt, offen, oder gar nicht vorgesehen. Ein
     * Samstag ohne Mo–Fr-Gewohnheit ist keine Lücke, und der Streifen darf ihn
     * nicht wie eine aussehen lassen.
     *
     * Erwartet geladene `completions`, sonst fragt jeder Aufruf die Datenbank.
     *
     * Die Fensterlänge ist einstellbar, weil derselbe Rückblick zwei Aufgaben
     * hat: sieben Tage für den Streifen, ein längeres Fenster als Beleg für
     * einen Anpassungs-Vorschlag.
     *
     * @return list<array{date: string, label: string, scheduled: bool, completed: bool}>
     */
    public function weekOverview(?Carbon $until = null, ?int $days = null): array
    {
        $until ??= Carbon::today();
        $days ??= self::WeekOverviewDays;

        $completed = $this->completions
            ->map(fn (HabitCompletion $completion): string => $completion->completed_on->toDateString())
            ->all();

        /** @var list<array{date: string, label: string, scheduled: bool, completed: bool}> $overview */
        $overview = collect(range($days - 1, 0))
            ->map(function (int $offset) use ($until, $completed): array {
                $date = $until->copy()->subDays($offset);

                return [
                    'date' => $date->toDateString(),
                    'label' => self::WeekdayAbbreviations[$date->dayOfWeekIso],
                    'scheduled' => $this->isScheduledOn($date),
                    'completed' => in_array($date->toDateString(), $completed, strict: true),
                ];
            })
            ->values()
            ->all();

        return $overview;
    }

    /**
     * Die Tage im Rückblick, an denen die Gewohnheit anstand und nichts geschah.
     *
     * Das ist der Beleg, mit dem die KI ihren Vorschlag begründet — nicht das
     * Urteil über einen Nutzer. Tage vor dem Anlegen zählen nicht mit: was es
     * noch nicht gab, kann niemand versäumt haben.
     *
     * Erwartet geladene `completions` über mindestens dieses Fenster.
     *
     * @return list<array{date: string, label: string}>
     */
    public function recentMisses(int $days = 14, ?Carbon $until = null): array
    {
        $until ??= Carbon::today();
        $start = $this->created_at?->copy()->startOfDay();

        /** @var list<array{date: string, label: string}> $misses */
        $misses = collect($this->weekOverview($until, $days))
            ->filter(fn (array $day): bool => $day['scheduled'] && ! $day['completed'])
            ->filter(fn (array $day): bool => $start === null
                || Carbon::parse($day['date'])->greaterThanOrEqualTo($start))
            ->map(fn (array $day): array => [
                'date' => $day['date'],
                'label' => $day['label'],
            ])
            ->values()
            ->all();

        return $misses;
    }

    /**
     * Der nächste Tag, an dem die Gewohnheit ansteht — heute eingeschlossen.
     *
     * Situative Gewohnheiten gelten an jedem Tag, für sie ist es immer heute.
     * Bei festen Gewohnheiten liegt der nächste Termin spätestens in sieben
     * Tagen; ohne gewählten Wochentag gibt es keinen.
     */
    public function nextOccurrence(?Carbon $from = null): ?Carbon
    {
        $from ??= Carbon::today();

        foreach (range(0, 6) as $offset) {
            $candidate = $from->copy()->addDays($offset);

            if ($this->isScheduledOn($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Die Gewohnheit als Vorlage, mit der jemand anders sie übernehmen kann.
     *
     * Nur der Bauplan, nicht die Gewohnheit: Titel, Richtung und Anker reisen
     * mit, damit die Übernahme nicht bei null anfängt. Der Warum-Satz und der
     * kleinste Schritt bleiben zurück — sie gehören zu einer Person, nicht zu
     * einer Gewohnheit („damit ich den Kopf freikriege" ist niemandes Grund
     * außer dem eigenen). Der Verlauf ohnehin nicht: Die Übernahme beginnt bei
     * Tag eins, nicht bei der fremden Serie.
     *
     * Der Umfang reist mit: Er gehört zur Gewohnheit, nicht zur Person — „20
     * Minuten" beschreibt, was gemacht wird, nicht warum. Wem das zu viel ist,
     * stellt ihn nach dem Übernehmen um.
     *
     * @return array{title: string, behaviorType: string, targetAmount: float|null, targetUnit: string|null, measureLabel: string|null, scheduleType: string, triggerSituation: string|null, scheduledTime: string|null, scheduledDays: list<int>|null}
     */
    public function blueprint(): array
    {
        return [
            'title' => $this->title,
            'behaviorType' => $this->behavior_type->value,
            'targetAmount' => $this->target_amount,
            'targetUnit' => $this->target_unit?->value,
            // Fertig formatiert, damit das Übernahme-Sheet die Zeile zeigen
            // kann, ohne die Einheiten-Metadaten mitgereicht zu bekommen.
            'measureLabel' => $this->measureLabel(),
            'scheduleType' => $this->schedule_type->value,
            'triggerSituation' => $this->trigger_situation,
            'scheduledTime' => $this->scheduled_time?->format('H:i'),
            'scheduledDays' => $this->scheduled_days,
        ];
    }

    /**
     * Wie viele Tage bis zum nächsten Termin — 0 heißt heute.
     *
     * Die Sortiergröße der Gewohnheitsliste: Was heute ansteht, steht oben,
     * was in vier Tagen wieder dran ist, unten. Ohne gewählten Wochentag gibt
     * es keinen nächsten Termin und damit keine Stelle in der Reihe.
     */
    public function daysUntilNextOccurrence(?Carbon $from = null): ?int
    {
        $from ??= Carbon::today();
        $next = $this->nextOccurrence($from);

        return $next === null
            ? null
            : (int) $from->copy()->startOfDay()->diffInDays($next->copy()->startOfDay());
    }

    /**
     * Wann die Gewohnheit das nächste Mal ansteht, als Satzteil.
     *
     * „heute", „morgen", „am Freitag" — ab dem übernächsten Tag trägt der
     * Wochentag mehr als eine Zahl: „am Freitag" lässt sich einordnen, „in vier
     * Tagen" muss man nachrechnen.
     *
     * Ohne gewählten Wochentag gibt es keinen Termin und damit nichts zu
     * benennen; der Wann-Teil sagt dort ohnehin schon „kein Tag gewählt".
     */
    public function nextOccurrenceLabel(?Carbon $from = null): ?string
    {
        $from ??= Carbon::today();
        $days = $this->daysUntilNextOccurrence($from);

        if ($days === null) {
            return null;
        }

        return match ($days) {
            0 => 'heute',
            1 => 'morgen',
            // Die App-Locale ist nicht deutsch, die Oberfläche schon.
            default => 'am '.$from->copy()->addDays($days)->locale('de')->isoFormat('dddd'),
        };
    }

    /**
     * Wo im Tag die Gewohnheit sitzt, als Stunde — die Sortierung des Kalenders.
     *
     * Feste Uhrzeiten bringen ihre Stelle mit, bekannte Situationen bekommen
     * sie aus der Vorschlagsliste, und alles Selbstgetippte landet mittags.
     * Eine Näherung, die nur eine Aufgabe hat: den Tag von oben nach unten
     * lesbar zu machen.
     */
    public function dayAnchorHour(): ?int
    {
        if (! $this->schedule_type->isPlanned()) {
            return null;
        }

        return $this->schedule_type->hasClockTime()
            ? self::anchorHourFor(time: $this->scheduled_time?->format('H:i'))
            : self::anchorHourFor(situation: $this->trigger_situation);
    }

    /**
     * Dieselbe Rechnung für einen Anker, den es noch gar nicht gibt.
     *
     * Ein Vorschlag der KI muss sich einsortieren lassen, bevor er übernommen
     * wurde — sonst könnte die Oberfläche nicht zeigen, wohin der Block wandern
     * würde. Die Stunde wird deshalb hier bestimmt und nicht im Browser
     * nachgebaut.
     */
    public static function anchorHourFor(?string $situation = null, ?string $time = null): int
    {
        if ($time !== null && preg_match('/^(\d{1,2}):/', $time, $matches) === 1) {
            return (int) $matches[1];
        }

        return self::TriggerSuggestions[$situation] ?? self::UnknownAnchorHour;
    }

    /**
     * Nur feste Uhrzeiten lassen sich erinnern — ohne Zeitpunkt kein „vorher".
     */
    public function canRemind(): bool
    {
        return $this->schedule_type->hasClockTime()
            && $this->scheduled_time !== null;
    }

    /**
     * Der Umfang als fertige Zeile: „20 Min", „1,5 L" — oder nichts.
     *
     * Ein Umfang ist immer freiwillig. „Treppe statt Aufzug" hat keinen, und
     * eine erfundene Zahl wäre dort schlechter als gar keine.
     */
    public function measureLabel(): ?string
    {
        return $this->target_amount === null
            ? null
            : $this->target_unit?->format($this->target_amount);
    }

    /**
     * Titel und Umfang in einer Zeile — „Spazieren gehen · 20 Min".
     *
     * Für die Stellen, an denen nur ein einzelner String Platz hat: der Prompt
     * der KI und die Zusammenfassung. Wo die Oberfläche zwei Felder setzen
     * kann, nimmt sie lieber Titel und {@see measureLabel()} getrennt — dann
     * kann der Umfang leiser gesetzt werden als die Handlung.
     */
    public function titleWithMeasure(): string
    {
        $measure = $this->measureLabel();

        return $measure === null
            ? $this->title
            : $this->title.' · '.$measure;
    }

    /**
     * Wie lange die Gewohnheit dauert — in Minuten, oder gar nicht.
     *
     * **Nur Minuten sind eine Dauer.** „10 Seiten" und „2 Liter" sind ein
     * Umfang, aber keine Zeitspanne: Sie sagen, wie viel, nicht wie lange. Diese
     * Unterscheidung trägt alles Weitere — den belegten Platz im Tag und den
     * Beginn einer angehängten Gewohnheit.
     */
    public function durationMinutes(): ?int
    {
        return $this->target_unit === MeasureUnit::Minutes && $this->target_amount !== null
            ? (int) round($this->target_amount)
            : null;
    }

    /**
     * Wann die Gewohnheit anfängt, als Uhrzeit — sofern sie eine hat.
     *
     * Nur feste Uhrzeiten bringen einen Zeitpunkt mit. Eine Situation ist keine
     * Uhrzeit, und „wenn es sich ergibt" erst recht nicht.
     */
    public function startsAt(): ?CarbonInterface
    {
        return $this->schedule_type->hasClockTime()
            ? $this->scheduled_time?->copy()
            : null;
    }

    /**
     * Wann der Block wieder frei ist — Beginn plus Dauer.
     *
     * Ohne eines von beidem gibt es kein Ende: Eine Gewohnheit ohne Uhrzeit
     * belegt keinen Platz, und eine ohne Dauer ist ein Punkt, keine Spanne.
     */
    public function endsAt(): ?CarbonInterface
    {
        $start = $this->startsAt();
        $minutes = $this->durationMinutes();

        return $start === null || $minutes === null
            ? null
            : $start->copy()->addMinutes($minutes);
    }

    /**
     * Die belegte Spanne als fertige Zeile: „17:00 – 17:20".
     *
     * Steht im Kalender an der Stelle, an der sonst der Anker steht — sie sagt
     * dasselbe und dazu, wann der Platz wieder frei ist. Ohne Dauer bleibt es
     * beim Anker allein; eine erfundene Länge wäre schlechter als keine.
     */
    public function timeRangeLabel(): ?string
    {
        $end = $this->endsAt();

        return $end === null
            ? null
            : $this->startsAt()?->format('H:i').' – '.$end->format('H:i');
    }

    /**
     * Der Wann-Teil als fertige Zeile für die Oberfläche.
     *
     * Beispiele: „nach dem Aufstehen", „17:00 · Mo–Fr", „08:30 · täglich".
     *
     * Was sich ergibt, benennt sich selbst. Ohne diesen Zweig fiele es durch
     * {@see anchorLabel()} auf den **leeren String** — und der stünde dann
     * wortlos im Kalender, im Verzeichnis, im Bauplan und im Prompt der KI.
     */
    public function scheduleLabel(): string
    {
        if (! $this->schedule_type->isPlanned()) {
            return mb_lcfirst($this->schedule_type->label());
        }

        return $this->schedule_type->hasClockTime()
            ? self::anchorLabel(time: $this->scheduled_time?->format('H:i'), days: $this->scheduled_days)
            : self::anchorLabel(situation: $this->trigger_situation);
    }

    /**
     * Dieselbe Zeile für einen Anker, den es noch gar nicht gibt.
     *
     * Das Gegenstück zu {@see anchorHourFor()} und aus demselben Grund
     * statisch: Ein Vorschlag der KI muss sich benennen lassen, bevor er
     * übernommen wurde — im Gedächtnis steht er als Zeile, nicht als Modell.
     *
     * @param  list<int>|null  $days
     */
    public static function anchorLabel(?string $situation = null, ?string $time = null, ?array $days = null): string
    {
        if ($time === null) {
            return (string) $situation;
        }

        return $time.' · '.self::weekdayLabel($days ?? []);
    }

    /**
     * Wochentage als lesbare Aufzählung, zusammenhängende Läufe gerafft.
     *
     * „Mo, Di, Mi, Do, Fr" ist korrekt, aber schwer zu erfassen — „Mo–Fr"
     * ist dasselbe in einem Blick. Erst ab drei aufeinanderfolgenden Tagen
     * lohnt die Spanne; bei zweien ist die Aufzählung kürzer als der Strich.
     *
     * @param  list<int>  $scheduledDays
     */
    private static function weekdayLabel(array $scheduledDays): string
    {
        $days = collect($scheduledDays)->sort()->values();

        if ($days->isEmpty()) {
            return 'kein Tag gewählt';
        }

        if ($days->count() === 7) {
            return 'täglich';
        }

        /** @var list<list<int>> $runs */
        $runs = [];

        foreach ($days as $day) {
            $last = end($runs);

            if ($last !== false && $day === end($last) + 1) {
                $runs[array_key_last($runs)][] = $day;

                continue;
            }

            $runs[] = [$day];
        }

        return collect($runs)
            ->map(fn (array $run): string => count($run) >= 3
                ? self::WeekdayAbbreviations[$run[0]].'–'.self::WeekdayAbbreviations[end($run)]
                : implode(', ', array_map(fn (int $day): string => self::WeekdayAbbreviations[$day], $run)))
            ->implode(', ');
    }

    /**
     * @return HasMany<HabitCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(HabitCompletion::class);
    }

    /**
     * Was die KI zu dieser Gewohnheit schon vorgeschlagen hat.
     *
     * @return HasMany<AiSuggestion, $this>
     */
    public function aiSuggestions(): HasMany
    {
        return $this->hasMany(AiSuggestion::class);
    }

    /**
     * Dieselben Erfüllungen, aber nur die Datumsspalte — für die Serie.
     *
     * Eine eigene Relation, weil `completions` im DashboardController auf heute
     * eingegrenzt geladen wird und Eloquent dieselbe Relation nicht zweimal
     * verschieden laden kann. Die Serie braucht die volle Historie, aber von
     * jeder Zeile nur das Datum.
     *
     * @return HasMany<HabitCompletion, $this>
     */
    public function completionDates(): HasMany
    {
        return $this->hasMany(HabitCompletion::class)
            ->select(['habit_id', 'completed_on'])
            ->orderByDesc('completed_on');
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('graduated_at');
    }

    /**
     * Beendete Gewohnheiten — aus der Tagesliste heraus, aber nicht fort.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function graduated(Builder $query): void
    {
        $query->whereNotNull('graduated_at');
    }

    /**
     * Anteil der Tage mit Erfüllung im Rückblickfenster, in Prozent.
     *
     * Die ruhige Zweitansicht neben der Serie, nicht ihr Ersatz.
     * progress-tracking.md verwirft den Streak noch ganz, aber diese Position
     * stammt aus den Interviews und wurde von der Umfrage widerlegt
     * (umfrage-auswertung.md §6). Beides steht jetzt nebeneinander: die Serie
     * als Antrieb, die Rate als der ehrlichere Blick über dreißig Tage, der
     * bei einem Fehltag nicht springt. Vor dem ersten Tag existiert kein
     * Fenster — dann gibt es keine Rate.
     *
     * Gezählt werden nur Tage, an denen die Gewohnheit vorgesehen ist. Sonst
     * wäre eine Mo–Fr-Gewohnheit dauerhaft bei 71 % gedeckelt, obwohl sie
     * lückenlos erfüllt wurde — eine Bestrafung fürs Nichtstun an Tagen, an
     * denen nichts vorgesehen war.
     */
    public function consistencyRate(int $days = 30, ?Carbon $until = null): ?int
    {
        $until ??= Carbon::today();
        $start = $until->copy()->subDays($days - 1)->max($this->created_at->copy()->startOfDay());

        $window = $this->scheduledDaysBetween($start, $until);

        if ($window < 1) {
            return null;
        }

        // Carbon-Instanzen statt Datums-Strings: der `date`-Cast legt die Spalte
        // als "Y-m-d H:i:s" ab, ein String-Vergleich gegen "Y-m-d" würde den
        // letzten Tag lexikografisch aus dem Fenster schneiden.
        $completed = $this->completions()
            ->whereBetween('completed_on', [$start->copy()->startOfDay(), $until->copy()->endOfDay()])
            ->count();

        return (int) round($completed / $window * 100);
    }

    /**
     * Die laufende Serie: erfüllte Termine am Stück, rückwärts von heute.
     *
     * Gezählt werden **vorgesehene Termine**, keine Kalendertage. Ein Samstag
     * ohne Mo–Fr-Gewohnheit ist kein Glied und kein Bruch, er kommt in der
     * Kette schlicht nicht vor — sonst könnte eine Gewohnheit, die nie
     * ausgelassen wurde, jedes Wochenende auf null fallen.
     *
     * Vier Regeln, in dieser Reihenfolge:
     *
     * 1. Nicht vorgesehen → übersprungen.
     * 2. Vor dem Anlegen → Ende. Was es noch nicht gab, kann niemand versäumt
     *    haben (dieselbe Grenze wie in `recentMisses()`).
     * 3. Heute noch offen → bricht nichts und kostet keinen Kulanztag. Der Tag
     *    ist noch nicht vorbei; ihn als Aussetzer zu werten hieße, morgens um
     *    acht ein Urteil über den Abend zu fällen.
     * 4. Ausgelassen → Kulanztag verbrauchen, sonst Ende. Der ausgelassene Tag
     *    zählt nie als Glied, er unterbricht die Kette nur nicht.
     *
     * Bewusst kein gespeicherter Zähler: Nachtragen ist sieben Tage rückwirkend
     * erlaubt (HabitCompletionController), eine gespeicherte Zahl wäre danach
     * sofort falsch.
     *
     * Erwartet geladene `completionDates`, sonst fragt jeder Aufruf die
     * Datenbank.
     */
    public function currentStreak(?Carbon $until = null): int
    {
        $until ??= Carbon::today();

        $completed = $this->completionDates
            ->map(fn (HabitCompletion $completion): string => $completion->completed_on->toDateString())
            ->flip();

        $start = ($this->created_at ?? $until)->copy()->startOfDay();
        $cursor = $until->copy()->startOfDay();

        $streak = 0;
        $grace = self::StreakGraceDays;

        while ($cursor->greaterThanOrEqualTo($start)) {
            if ($this->isScheduledOn($cursor)) {
                if ($completed->has($cursor->toDateString())) {
                    $streak++;
                } elseif (! $cursor->isSameDay($until)) {
                    if ($grace < 1) {
                        break;
                    }

                    $grace--;
                }
            }

            $cursor->subDay();
        }

        return $streak;
    }

    /**
     * Die Einheit der Serie: Tage oder Male.
     *
     * „12 Tage in Folge" wäre bei einer Mo–Fr-Gewohnheit schlicht gelogen —
     * zwischen dem ersten und dem zwölften Termin liegen gut zwei Wochen.
     */
    public function streakUnit(): string
    {
        return $this->runsEveryDay() ? 'Tage' : 'Mal';
    }

    /**
     * Die Serie als fertige Zeile: „12 Tage in Folge" oder „12× in Folge".
     */
    public function streakLabel(int $streak): string
    {
        return $this->runsEveryDay()
            ? $streak.' Tage in Folge'
            : $streak.'× in Folge';
    }

    /**
     * Steht die Gewohnheit an jedem Wochentag an?
     *
     * Situative Gewohnheiten gelten immer; eine feste Gewohnheit tut es nur,
     * wenn alle sieben Tage gewählt sind.
     */
    private function runsEveryDay(): bool
    {
        return ! $this->schedule_type->hasClockTime()
            || count($this->scheduled_days ?? []) === 7;
    }

    /**
     * Anzahl der Tage im Zeitraum, an denen die Gewohnheit vorgesehen ist.
     *
     * Beide Grenzen zählen mit. Für dynamische Gewohnheiten ist das schlicht
     * die Länge des Zeitraums.
     *
     * Für Gewohnheiten ohne Platz im Tag ist es **null** — und damit gibt es
     * keinen Nenner und keine Quote. Genau die Begründung, mit der unten schon
     * die nicht gewählten Wochentage herausfallen: eine Rate darf nicht Tage
     * mitzählen, an denen nichts vorgesehen war.
     */
    public function scheduledDaysBetween(Carbon $start, Carbon $until): int
    {
        if (! $this->schedule_type->isPlanned()) {
            return 0;
        }

        $length = (int) $start->copy()->startOfDay()->diffInDays($until->copy()->startOfDay()) + 1;

        if (! $this->schedule_type->hasClockTime()) {
            return $length;
        }

        $weekdays = $this->scheduled_days ?? [];

        if ($weekdays === []) {
            return 0;
        }

        // Volle Wochen liefern jeden gewählten Tag genau einmal; nur der Rest
        // muss einzeln geprüft werden. Bei 30 Tagen wären das höchstens sechs
        // Schritte statt dreißig.
        $fullWeeks = intdiv($length, 7);
        $remainder = $length % 7;
        $count = $fullWeeks * count($weekdays);

        $cursor = $start->copy()->startOfDay()->addDays($fullWeeks * 7);

        for ($offset = 0; $offset < $remainder; $offset++) {
            if (in_array($cursor->dayOfWeekIso, $weekdays, strict: true)) {
                $count++;
            }

            $cursor->addDay();
        }

        return $count;
    }

    protected function casts(): array
    {
        return [
            'behavior_type' => BehaviorType::class,
            'target_amount' => 'float',
            'target_unit' => MeasureUnit::class,
            'schedule_type' => ScheduleType::class,
            'scheduled_time' => 'datetime:H:i',
            'scheduled_days' => 'array',
            'reminder_enabled' => 'boolean',
            'committed_at' => 'datetime',
            'graduated_at' => 'datetime',
        ];
    }
}
