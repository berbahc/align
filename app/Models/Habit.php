<?php

namespace App\Models;

use App\Enums\BehaviorType;
use App\Enums\ScheduleType;
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
 * @property BehaviorType $behavior_type
 * @property int|null $focus_minutes
 * @property int $position
 * @property Carbon|null $committed_at
 * @property Carbon|null $graduated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'schedule_type', 'trigger_situation', 'scheduled_time', 'scheduled_days', 'reminder_enabled', 'motivation', 'behavior_type', 'focus_minutes', 'position', 'committed_at'])]
class Habit extends Model
{
    /** @use HasFactory<HabitFactory> */
    use HasFactory;

    /**
     * progress-tracking.md: höchstens 5 gleichzeitig aktive Gewohnheiten,
     * damit die Liste schmal und der Fokus erhalten bleibt.
     */
    public const int MaxActivePerUser = 5;

    /**
     * Vorschläge für den Situations-Picker.
     *
     * time-blocking.md: situative Cues statt Uhrzeiten. Eine Situation löst
     * Verhalten automatisch aus, eine Uhrzeit muss aktiv erinnert werden.
     * Die Liste ist nur ein Angebot — eigene Eingaben sind erlaubt.
     *
     * @var list<string>
     */
    public const array TriggerSuggestions = [
        'nach dem Aufstehen',
        'nach dem Frühstück',
        'nach der Morgenvorlesung',
        'nach dem Mittagessen',
        'wenn ich nach Hause komme',
        'vor dem Schlafengehen',
    ];

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
     */
    public function isScheduledOn(Carbon $date): bool
    {
        if ($this->schedule_type !== ScheduleType::Fixed) {
            return true;
        }

        return in_array($date->dayOfWeekIso, $this->scheduled_days ?? [], strict: true);
    }

    /**
     * Nur feste Uhrzeiten lassen sich erinnern — ohne Zeitpunkt kein „vorher".
     */
    public function canRemind(): bool
    {
        return $this->schedule_type === ScheduleType::Fixed
            && $this->scheduled_time !== null;
    }

    /**
     * Der Wann-Teil als fertige Zeile für die Oberfläche.
     *
     * Beispiele: „nach dem Aufstehen", „17:00 · Mo–Fr", „08:30 · täglich".
     */
    public function scheduleLabel(): string
    {
        if ($this->schedule_type !== ScheduleType::Fixed) {
            return (string) $this->trigger_situation;
        }

        return $this->scheduled_time?->format('H:i').' · '.$this->weekdayLabel();
    }

    /**
     * Wochentage als lesbare Aufzählung, zusammenhängende Läufe gerafft.
     *
     * „Mo, Di, Mi, Do, Fr" ist korrekt, aber schwer zu erfassen — „Mo–Fr"
     * ist dasselbe in einem Blick. Erst ab drei aufeinanderfolgenden Tagen
     * lohnt die Spanne; bei zweien ist die Aufzählung kürzer als der Strich.
     */
    private function weekdayLabel(): string
    {
        $days = collect($this->scheduled_days ?? [])->sort()->values();

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
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('graduated_at');
    }

    /**
     * Anteil der Tage mit Erfüllung im Rückblickfenster, in Prozent.
     *
     * progress-tracking.md: bewusst eine Konsistenzrate statt eines Streaks.
     * Ein Streak bricht bei einem einzigen Fehltag komplett zusammen, obwohl
     * einzelne Aussetzer laut Lally et al. keine messbaren Langzeitkosten haben.
     * Vor dem ersten Tag existiert kein Fenster — dann gibt es keine Rate.
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
     * Anzahl der Tage im Zeitraum, an denen die Gewohnheit vorgesehen ist.
     *
     * Beide Grenzen zählen mit. Für dynamische Gewohnheiten ist das schlicht
     * die Länge des Zeitraums.
     */
    public function scheduledDaysBetween(Carbon $start, Carbon $until): int
    {
        $length = (int) $start->copy()->startOfDay()->diffInDays($until->copy()->startOfDay()) + 1;

        if ($this->schedule_type !== ScheduleType::Fixed) {
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
            'schedule_type' => ScheduleType::class,
            'scheduled_time' => 'datetime:H:i',
            'scheduled_days' => 'array',
            'reminder_enabled' => 'boolean',
            'committed_at' => 'datetime',
            'graduated_at' => 'datetime',
        ];
    }
}
