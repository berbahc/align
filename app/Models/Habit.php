<?php

namespace App\Models;

use App\Enums\BehaviorType;
use App\Enums\HabitCategory;
use App\Enums\HabitTemplate;
use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use App\Http\Requests\Concerns\ChecksSituation;
use App\Support\DayPlan;
use App\Support\Timetable;
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
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $template_key
 * @property ScheduleType $schedule_type
 * @property string|null $trigger_situation
 * @property Carbon|null $scheduled_time
 * @property list<int>|null $scheduled_days
 * @property int|null $chained_to_habit_id
 * @property Habit|null $chainedTo
 * @property bool $reminder_enabled
 * @property string|null $motivation
 * @property string|null $smallest_step
 * @property BehaviorType $behavior_type
 * @property float|null $target_amount
 * @property MeasureUnit|null $target_unit
 * @property int $position
 * @property Carbon|null $committed_at
 * @property Carbon|null $graduated_at
 * @property Carbon|null $displaced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'template_key', 'schedule_type', 'trigger_situation', 'scheduled_time', 'scheduled_days', 'chained_to_habit_id', 'reminder_enabled', 'motivation', 'smallest_step', 'behavior_type', 'target_amount', 'target_unit', 'position', 'committed_at'])]
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
     * Die Tage, an denen diese Gewohnheit ausnahmsweise woanders liegt.
     *
     * @return HasMany<HabitDayShift, $this>
     */
    public function dayShifts(): HasMany
    {
        return $this->hasMany(HabitDayShift::class);
    }

    /**
     * Die Ausnahme-Uhrzeit für ein Datum — oder nichts.
     *
     * Nimmt die geladene Beziehung, wenn es sie gibt: Der Kalender fragt für
     * jeden Block, und eine Abfrage pro Zeile wäre der Preis für eine
     * Ausnahme, die die meisten Tage gar nicht haben.
     */
    public function shiftedTimeOn(?Carbon $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $shift = $this->relationLoaded('dayShifts')
            ? $this->dayShifts->first(
                fn (HabitDayShift $shift): bool => $shift->shifted_on->isSameDay($date),
            )
            : $this->dayShifts()->whereDate('shifted_on', $date)->first();

        return $shift?->scheduled_time->format('H:i');
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
     * Wie viele Glieder eine Kette haben darf.
     *
     * Bei höchstens fünf gleichzeitigen Gewohnheiten kann keine Kette länger
     * sein — die Zahl ist zugleich die Abbruchbedingung der Rekursion, die den
     * Beginn einer gekoppelten Gewohnheit ausrechnet.
     */
    public const int MaxChainDepth = 5;

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
        self::AfterLecture => 11,
        'vor dem Schlafengehen' => 22,
    ];

    /**
     * Wo die Vorlesungs-Situation liegt, wenn der Stundenplan gerade fehlt.
     *
     * Angeboten wird sie nur, solange es Kurse gibt
     * ({@see situationChoicesFor()}). Wer seinen Stundenplan danach loescht,
     * behaelt aber die Gewohnheit, und die braucht eine Stelle. Elf Uhr ist
     * die Annahme dafuer und die einzige, die in diesem Modell noch steht.
     */
    private const int LectureFallbackStart = 660;

    /**
     * Wie weit „nach dem Aufstehen" nach hinten darf: drei Stunden.
     *
     * Am Schlafplan statt an einer festen Uhrzeit: Wer um elf aufsteht,
     * frühstückt nicht um sieben.
     */
    private const int SituationWindowMinutes = 120;

    /**
     * Und „vor dem Schlafengehen" nach vorn: eine Stunde.
     *
     * Enger als am Morgen, weil das Wort es enger meint. Drei Stunden vor dem
     * Schlafengehen ist früher Abend und nichts, was jemand als „vor dem
     * Schlafengehen" plant.
     */
    private const int EveningWindowMinutes = 60;

    /** Die Situation, die am Stundenplan hängt statt an der Uhr. */
    public const string AfterLecture = 'nach der Vorlesung';

    /** Einmal je Gewohnheit geladen — nur die eine Situation fragt danach. */
    private ?Timetable $timetable = null;

    /**
     * Die Situationen samt der Tage, an denen sie schon vergeben sind.
     *
     * Eine Situation trägt **pro Tag** genau eine Gewohnheit. „Nach dem
     * Aufstehen" zweimal am selben Montag zu vergeben hieße, zwei Dinge im
     * selben Moment zu tun — der Kalender zeigte sie untereinander, als gäbe
     * es eine Reihenfolge, die niemand festgelegt hat. Das unterläuft das
     * Time-Blocking, dem die ganze Planung dient.
     *
     * An **verschiedenen** Tagen ist es dagegen kein Konflikt: „Lesen" montags
     * und „Dehnen" dienstags nach dem Aufstehen überschneiden sich nirgends.
     * Bei nur drei Situationen wäre die Sperre über die ganze Woche sonst eine
     * Grenze von drei situativen Gewohnheiten — eine, die aus der Rechnung
     * nicht folgt.
     *
     * Diese Methode ist die eine Quelle dafür: Die Oberfläche sperrt daraus
     * die belegten Tage, {@see ChecksSituation} weist sie ab. Liefen beide
     * auseinander, böte der Picker etwas an, das beim Speichern scheitert.
     *
     * `$except` ist die gerade bearbeitete Gewohnheit — ohne sie wären ihre
     * eigenen Tage für sie selbst gesperrt.
     *
     * @return list<array{situation: string, takenBy: string|null, takenDays: list<int>}>
     */
    public static function situationChoicesFor(User $user, ?self $except = null): array
    {
        $holders = $user->habits()
            ->active()
            ->whereNotNull('trigger_situation')
            // Eine gekettete Gewohnheit belegt keinen Moment: Ihr Anker ist die
            // Gewohnheit davor, und `trigger_situation` liest bei ihr niemand.
            // Ohne diese Zeile sperrte sie einen Moment, den sie gar nicht hat.
            ->whereIn('schedule_type', ScheduleType::withOwnAnchor())
            ->when($except?->exists, fn (Builder $query) => $query->whereKeyNot($except))
            ->get(['id', 'title', 'schedule_type', 'trigger_situation', 'scheduled_days'])
            ->groupBy('trigger_situation');

        return array_map(function (string $situation) use ($holders): array {
            /** @var Collection<int, self> $taken */
            $taken = $holders->get($situation) ?? collect();

            /** @var list<int> $days */
            $days = $taken
                ->flatMap(fn (self $habit): array => $habit->activeWeekdays())
                ->unique()
                ->sort()
                ->values()
                ->all();

            return [
                'situation' => $situation,
                // Mehrere Namen kommagetrennt: Der Satz darunter sagt, wem die
                // gesperrten Kreise gehören — gesperrt wird über die Tage.
                'takenBy' => $taken->isEmpty()
                    ? null
                    : $taken->pluck('title')->implode(', '),
                'takenDays' => $days,
            ];
        }, self::offeredSituations($user));
    }

    /**
     * Welche Situationen ueberhaupt zur Wahl stehen.
     *
     * Die Regel dahinter ist der Grund, warum es nur noch drei sind: Eine
     * Situation wird angeboten, wenn die App ausrechnen kann, wann sie
     * stattfindet — und zwar aus etwas, das der Nutzer ihr gesagt hat.
     *
     * Der Schlafplan sagt, wann jemand aufsteht und ins Bett geht; daraus
     * folgen zwei. Der Stundenplan sagt, wann die Vorlesungen enden; daraus
     * folgt die dritte — und deshalb steht sie nur da, wenn es ihn gibt.
     *
     * Was frueher hier stand, ruhte auf Annahmen: Fruehstueck 45 Minuten nach
     * dem Aufstehen, Mittagessen um eins, zu Hause um fuenf. Das ist fuer
     * niemanden richtig, und ein Kalender, der eine Gewohnheit an eine
     * erfundene Uhrzeit legt, ist unzuverlaessig an genau der Stelle, an der er
     * verlaesslich sein muesste. Wer „nach dem Fruehstueck" plant, haengt seine
     * Gewohnheit jetzt an die Gewohnheit „Fruehstuecken" — eine Kette weiss die
     * Uhrzeit, eine Annahme raet sie.
     *
     * @return list<string>
     */
    public static function offeredSituations(User $user): array
    {
        return array_values(array_filter(
            array_keys(self::TriggerSuggestions),
            fn (string $situation): bool => $situation !== self::AfterLecture
                || Timetable::for($user)->courseCount() > 0,
        ));
    }

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
     * Alle sieben Tage — „täglich" als Liste.
     *
     * Stand vorher siebenmal ausgeschrieben im Code, überall als Rückfall für
     * eine Gewohnheit ohne eigene Tage. Jetzt an einer Stelle, damit die
     * Bedeutung eine bleibt.
     *
     * @var list<int>
     */
    public const array EveryDay = [1, 2, 3, 4, 5, 6, 7];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Die Vorlage, aus der die Gewohnheit entstanden ist — falls es sie gibt.
     *
     * `null` bei Gewohnheiten aus der Zeit der freien Eingabe und bei einem
     * Schlüssel, den der Katalog nicht mehr kennt. Beides ist derselbe Fall:
     * eine Gewohnheit ohne Vorlage, die trotzdem weiterläuft.
     */
    public function template(): ?HabitTemplate
    {
        return $this->template_key === null
            ? null
            : HabitTemplate::tryFrom($this->template_key);
    }

    /**
     * Die Katalog-Kategorie — der Bereich, unter dem die Gewohnheit steht.
     */
    public function category(): ?HabitCategory
    {
        return $this->template()?->category();
    }

    /**
     * Die Gewohnheit, an der diese hängt — der Vorgänger in der Kette.
     *
     * @return BelongsTo<Habit, $this>
     */
    public function chainedTo(): BelongsTo
    {
        return $this->belongsTo(Habit::class, 'chained_to_habit_id');
    }

    /**
     * Die Gewohnheiten, die an dieser hängen.
     *
     * @return HasMany<Habit, $this>
     */
    public function chainedHabits(): HasMany
    {
        return $this->hasMany(Habit::class, 'chained_to_habit_id');
    }

    /**
     * Steht diese Gewohnheit ohne Platz da?
     *
     * Verdrängt wird der Anker, nicht die Kette: Eine gekoppelte Gewohnheit hat
     * keine eigene Stelle im Tag, sondern erbt die des Vorgängers — fällt der
     * aus dem Tag, fällt sie mit. Deshalb fragt sie hier ihren Anker, statt
     * selbst eine Spalte zu tragen; beim Zurücklegen kommt sie so in einem
     * einzigen Schreibvorgang mit.
     *
     * Sie bleibt aktiv — zählt gegen die Fünfergrenze, steht in der Liste,
     * behält ihre Uhrzeit als Erinnerung. Nur im Tag liegt sie nirgends, bis
     * sie einen neuen Platz bekommt.
     *
     * Mit Datum: Der Vermerk gilt ab einem Tag, nicht seit einem Klick. Ein
     * Kurs im Oktober nimmt im September noch nichts weg — bis zum
     * Semesterbeginn läuft die Gewohnheit weiter, wo sie lief.
     */
    public function isDisplaced(?Carbon $on = null): bool
    {
        return $this->anchorHabit()?->displacedOn($on) ?? false;
    }

    /**
     * Gilt der eigene Vermerk an diesem Tag schon?
     *
     * `displaced_at` ist der Tag, ab dem der Platz weg ist — sofort, wenn der
     * Kurs schon läuft, sonst der Semesterbeginn ({@see DisplaceHabits}).
     */
    private function displacedOn(?Carbon $on = null): bool
    {
        return $this->displaced_at !== null
            && $this->displaced_at->toDateString() <= ($on ?? Carbon::today())->toDateString();
    }

    /**
     * Die Gewohnheit hat wieder einen Platz — der Vermerk fällt weg.
     *
     * Ein Leerlauf, wenn sie nie verdrängt war: Jeder Weg, der eine Uhrzeit
     * setzt, darf das ohne Nachfrage aufrufen.
     */
    /**
     * Die Tageszeit, zu der diese Gewohnheit gehört — falls die Vorlage eine kennt.
     *
     * @return array{from: int, to: int}|null
     */
    public function dayBand(): ?array
    {
        return $this->template()?->dayBand();
    }

    public function takeAPlace(): void
    {
        if ($this->displaced_at === null) {
            return;
        }

        $this->forceFill(['displaced_at' => null])->save();
    }

    /**
     * Was diese Gewohnheit und alles, was an ihr hängt, ab `$start` belegen.
     *
     * Eine Kette heißt „danach": Rückt das erste Glied, rücken die übrigen
     * mit. Wer prüfen will, ob eine Uhrzeit frei ist, muss deshalb nicht eine
     * Spanne prüfen, sondern alle — sonst landet der Nachfolger in etwas, das
     * er beim Ziehen nie hätte betreten dürfen.
     *
     * Steht hier und nicht im Aufrufer, weil zwei Wege dieselbe Antwort
     * brauchen: das Ziehen im Raster und das Formular. Zwei Rechnungen wären
     * zwei Wahrheiten über denselben Tag.
     *
     * @return list<array{id: int, title: string, from: int, to: int}>
     */
    public function spansFrom(int $start): array
    {
        $spans = [];
        $current = $this;
        $cursor = $start;

        for ($depth = 0; $depth < self::MaxChainDepth; $depth++) {
            $minutes = $current->durationMinutes() ?? DayPlan::AssumedMinutes;

            $spans[] = [
                'id' => $current->id,
                'title' => $current->title,
                'from' => $cursor,
                'to' => $cursor + $minutes,
            ];
            // Das nächste Glied fängt nach der Luft an, nicht am Ende.
            $cursor += $minutes + DayPlan::ChainBreatherMinutes;

            $next = $current->chainedHabits()
                ->whereNull('graduated_at')
                ->orderBy('position')
                ->first();

            if ($next === null) {
                break;
            }

            $current = $next;
        }

        return $spans;
    }

    /**
     * Die Gewohnheit, von der diese ihre Stelle im Tag hat.
     *
     * Für alles mit eigenem Anker ist das sie selbst. Eine gekoppelte
     * Gewohnheit fragt ihren Vorgänger, und der notfalls seinen — bis jemand
     * einen eigenen Anker hat.
     *
     * Die Tiefengrenze ist eine Notbremse, keine Regel: Zyklen weist die
     * Validierung ab. Sie schützt die Rekursion vor Daten, die nie durch ein
     * Formular gegangen sind.
     */
    public function anchorHabit(): ?Habit
    {
        $habit = $this;

        for ($depth = 0; $depth < self::MaxChainDepth; $depth++) {
            if ($habit->schedule_type->hasOwnAnchor()) {
                return $habit;
            }

            if ($habit->schedule_type !== ScheduleType::Chained) {
                return null;
            }

            $next = $habit->chainedTo;

            if ($next === null) {
                return null;
            }

            $habit = $next;
        }

        return null;
    }

    /**
     * An welchen Wochentagen die Gewohnheit läuft.
     *
     * Die eine Quelle dafür, und sie beantwortet drei verschiedene Fragen:
     *
     * - Eine **gekoppelte** Gewohnheit läuft, wenn die läuft, an der sie hängt
     *   — nicht öfter und nicht seltener. Ohne Vorgänger läuft sie nirgends.
     * - Eine **feste** Uhrzeit ohne Tage ist kein Zeitpunkt: Das Formular
     *   verlangt die Tage, und eine leere Liste heißt hier deshalb „an keinem
     *   Tag", nicht „an allen".
     * - Eine **Situation** ohne Tage heißt täglich. Das ist die Altlast und
     *   ihre einzige Stelle: Bis die Wochentage auch für Situationen wählbar
     *   waren, hatte keine von ihnen eine Spalte — sie liefen jeden Tag, und
     *   genau das tun sie weiter, bis jemand Tage abwählt.
     *
     * @return list<int>
     */
    public function activeWeekdays(): array
    {
        if (! $this->schedule_type->hasOwnAnchor()) {
            return $this->anchorHabit()?->activeWeekdays() ?? [];
        }

        /** @var list<int> $days */
        $days = $this->scheduled_days ?? ($this->schedule_type->hasClockTime() ? [] : self::EveryDay);

        return $days;
    }

    /**
     * Ist die Gewohnheit an diesem Tag überhaupt vorgesehen?
     *
     * Eine Gewohnheit gilt nur an ihren Wochentagen; an allen anderen Tagen ist
     * sie nicht offen, sondern schlicht nicht vorgesehen. Der Unterschied
     * entscheidet darüber, ob ein Tag in die Konsistenzrate zählt.
     *
     * Das galt lange nur für feste Uhrzeiten — eine Situation gab hier `true`
     * zurück, für jeden Tag. „Nach dem Aufstehen lesen" hieß damit zwangsläufig
     * auch sonntags, ohne dass es je jemand so gewählt hätte.
     */
    public function isScheduledOn(Carbon $date): bool
    {
        return in_array($date->dayOfWeekIso, $this->activeWeekdays(), strict: true);
    }

    /**
     * Steht sie an diesem Tag wirklich an?
     *
     * Der Unterschied zu {@see isScheduledOn()}: Eine Gewohnheit, deren Platz
     * gerade ein Kurs hat, steht an ihren Wochentagen zwar weiter im Plan —
     * anstehen tut sie nicht. Wer beides verwechselt, hält ihr einen Tag
     * vor, den die App ihr selbst genommen hat.
     *
     * Für alles, was aus dem Tag eine Bilanz zieht: die Serie, der
     * Rückblick, die Übersicht. Die Kollisionsprüfung fragt weiter
     * {@see isScheduledOn()} — sie will alle Tage sehen, auch die geparkten.
     */
    public function isDueOn(Carbon $date): bool
    {
        return $this->isScheduledOn($date)
            && $this->hasTriggerOn($date)
            && ! $this->isDisplaced($date);
    }

    /**
     * Gibt es an diesem Tag überhaupt den Auslöser, an dem sie hängt?
     *
     * Nur eine Situation kann fehlen, und nur eine: „Nach der Vorlesung" ohne
     * Vorlesung ist kein Auslöser. Ohne Trigger keine Gewohnheit
     * (time-blocking.md) — sie steht an so einem Tag nicht an, statt an einer
     * erfundenen Uhrzeit zu liegen.
     *
     * Wer kein Semester eingetragen hat, behält die Situation: Dann weiß die
     * App nichts über Vorlesungen, und Schweigen ist kein Nein.
     */
    public function hasTriggerOn(Carbon $date): bool
    {
        if ($this->trigger_situation !== self::AfterLecture
            || $this->schedule_type !== ScheduleType::Dynamic
            || ! $this->relationLoaded('user')) {
            return true;
        }

        return $this->situationStart($date) !== null;
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
     * **Vor dem Anlegen ist nichts vorgesehen.** Ohne diese Grenze zeigte eine
     * heute angelegte Mo–Fr-Gewohnheit fünf offene Tage für die Woche davor,
     * als hätte jemand fünf Mal ausgelassen. `progress-tracking.md` schließt
     * das aus: „fehlende Tage dürfen nie bestraft oder prominent angezeigt
     * werden." {@see recentMisses()} wandte die Regel bisher hinterher an;
     * seit der Streifen diese Reihe unmittelbar zeichnet, gehört sie hierher.
     *
     * @return list<array{date: string, label: string, scheduled: bool, completed: bool}>
     */
    public function weekOverview(?Carbon $until = null, ?int $days = null): array
    {
        $until ??= Carbon::today();
        $days ??= self::WeekOverviewDays;

        $created = $this->created_at?->copy()->startOfDay();

        $completed = $this->completions
            ->map(fn (HabitCompletion $completion): string => $completion->completed_on->toDateString())
            ->all();

        /** @var list<array{date: string, label: string, scheduled: bool, completed: bool}> $overview */
        $overview = collect(range($days - 1, 0))
            ->map(function (int $offset) use ($until, $completed, $created): array {
                $date = $until->copy()->subDays($offset);
                $existed = $created === null || $date->greaterThanOrEqualTo($created);

                return [
                    'date' => $date->toDateString(),
                    'label' => self::WeekdayAbbreviations[$date->dayOfWeekIso],
                    'scheduled' => $existed && $this->isDueOn($date),
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
        return $this->nextOccurrences(1, self::WeekOverviewDays, $from)[0] ?? null;
    }

    /**
     * Die nächsten Tage, an denen die Gewohnheit ansteht — heute eingeschlossen.
     *
     * Die Mehrzahl von {@see nextOccurrence()} und die Grundlage der
     * Verabredungs-Tage: Gefragt wird nicht „welche drei Tage kommen als
     * Nächstes", sondern „wann steht *diese* Gewohnheit als Nächstes an". Für
     * eine Mo–Fr-Gewohnheit am Samstag sind das Montag, Dienstag, Mittwoch —
     * und nicht heute, morgen, übermorgen, an denen sie gar nicht stattfindet.
     *
     * @param  int  $limit  Höchstzahl der Tage
     * @param  int  $withinDays  Wie weit gesucht wird, `$from` eingeschlossen
     * @return list<Carbon>
     */
    public function nextOccurrences(int $limit, int $withinDays, ?Carbon $from = null): array
    {
        $from ??= Carbon::today();
        $days = [];

        foreach (range(0, $withinDays - 1) as $offset) {
            if (count($days) === $limit) {
                break;
            }

            $candidate = $from->copy()->addDays($offset);

            if ($this->isScheduledOn($candidate)) {
                $days[] = $candidate;
            }
        }

        return $days;
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
     * Der Vorlagen-Schlüssel reist mit: Eine Übernahme soll dieselbe
     * Katalog-Vorlage treffen, nicht nur denselben Text. Bei Gewohnheiten aus
     * der Zeit der freien Eingabe ist er `null` — sie lassen sich nicht mehr
     * übernehmen, weil es außerhalb des Katalogs kein Anlegen mehr gibt.
     *
     * @return array{title: string, templateKey: string|null, behaviorType: string, durationMinutes: int, measureLabel: string|null, scheduleType: string, triggerSituation: string|null, scheduledTime: string|null, scheduledDays: list<int>|null}
     */
    public function blueprint(): array
    {
        // Eine Kette lässt sich nicht verschenken: „nach dem Spaziergang" meint
        // *meinen* Spaziergang, und den hat die andere Person nicht. Die Vorlage
        // trägt deshalb den Anker, an dem die Kette hängt, statt der Kopplung —
        // wer sie übernimmt, bekommt den Zeitpunkt, nicht die fremde Reihe.
        $anchor = $this->schedule_type->hasOwnAnchor()
            ? $this
            : $this->anchorHabit();

        // Eine Kette ohne Anker ist kaputt — dann bekommt die Vorlage die
        // Voreinstellung des Systems und die übernehmende Person wählt selbst
        // eine Situation, statt einen Anschluss zu erben, den es nicht gibt.
        $type = $anchor === null ? ScheduleType::Dynamic : $anchor->schedule_type;

        return [
            'title' => $this->title,
            'templateKey' => $this->template()?->value,
            'behaviorType' => $this->behavior_type->value,
            // Die Dauer, mit der die Übernahme startet. Eine alte Gewohnheit
            // kann statt einer Dauer einen Umfang tragen („10 Seiten") — dann
            // beginnt die Übernahme beim Startwert ihrer Vorlage, notfalls
            // beim kleinsten erlaubten Wert.
            'durationMinutes' => $this->durationMinutes()
                ?? $this->template()?->defaultMinutes()
                ?? (int) MeasureUnit::Minutes->min(),
            // Fertig formatiert, damit das Übernahme-Sheet die Zeile zeigen
            // kann, ohne die Einheiten-Metadaten mitgereicht zu bekommen.
            'measureLabel' => $this->measureLabel(),
            'scheduleType' => $type->value,
            'triggerSituation' => $anchor?->trigger_situation,
            'scheduledTime' => $anchor?->scheduled_time?->format('H:i'),
            'scheduledDays' => $anchor?->scheduled_days,
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
     *
     * Die beiden Situationen am Tagesrand fragen den Schlafplan: „nach dem
     * Aufstehen" sitzt zur eigenen Aufstehzeit, „vor dem Schlafengehen" eine
     * Stunde vor der eigenen Schlafenszeit — der gemittelte Wert aus der
     * Vorschlagsliste wäre für Frühaufsteher wie Nachteulen gleich falsch.
     * Dafür muss die `user`-Beziehung geladen sein; ohne sie gilt weiter der
     * Mittelwert, statt je Gewohnheit eine eigene Abfrage loszutreten.
     */
    public function dayAnchorHour(?Carbon $on = null): ?int
    {
        // Auch keine ungefähre Stelle: Die Stunde ist eine Sortierhilfe für
        // Blöcke, die im Tag stehen — und diese steht dort gerade nicht. Eine
        // gekoppelte fragt weiter unten ihren Anker und bekommt dort dieselbe
        // Antwort. Ein Umzug für genau diesen Tag geht vor: Er sagt, dass sie
        // heute doch eine Stelle hat.
        if ($this->shiftedTimeOn($on) === null && $this->displacedOn($on)) {
            return null;
        }

        return $this->plannedAnchorHour($on);
    }

    /**
     * Die Stunde, zu der sie gehört — ohne die Frage, ob sie gerade Platz hat.
     *
     * Für den Rückweg: Wo läge sie, wenn der Kurs weg wäre? Eine Gewohnheit
     * an einer Situation hat keine Uhrzeit, mit der sich das beantworten
     * ließe — nur diese Stunde, mit der sie im Tag auch belegt wurde.
     */
    public function plannedAnchorHour(?Carbon $on = null): ?int
    {
        $shifted = $this->shiftedTimeOn($on);

        if ($shifted !== null) {
            return self::anchorHourFor(time: $shifted);
        }

        // Die gekoppelte Gewohnheit sortiert sich zur Stunde ihres Vorgängers
        // und landet damit direkt unter ihm — die Reihenfolge innerhalb der
        // Stunde entscheidet dann `position`.
        if (! $this->schedule_type->hasOwnAnchor()) {
            return $this->anchorHabit()?->dayAnchorHour($on);
        }

        if ($this->schedule_type->hasClockTime()) {
            return self::anchorHourFor(time: $this->scheduled_time?->format('H:i'));
        }

        return $this->sleepBoundAnchorHour($on)
            ?? self::anchorHourFor(situation: $this->trigger_situation);
    }

    /**
     * Die Stunde aus dem Schlafplan — für die Situationen am Tagesrand.
     *
     * Nur die grobe Stunde, fürs Einsortieren. Wo der Block wirklich liegt,
     * sagt {@see sleepBoundStartMinute()}.
     */
    private function sleepBoundAnchorHour(?Carbon $on = null): ?int
    {
        $minute = $this->sleepBoundStartMinute($on);

        return $minute === null ? null : intdiv($minute, 60);
    }

    /**
     * Wo „nach dem Aufstehen" und „vor dem Schlafengehen" wirklich sitzen.
     *
     * Beide hängen am Rahmen des **gezeigten** Tages, nicht an einer festen
     * Stunde und nicht an heute: Wer am Wochenende später aufsteht, hat seine
     * Morgengewohnheit auch später — und wer donnerstags erst um Mitternacht
     * ins Bett geht, hat abends eine Stunde mehr. Der Schlafplan ist je
     * Wochentag einstellbar, und diese Rechnung ist die Stelle, an der sich
     * das auszahlen muss.
     *
     * Auf die Minute, nicht auf die Stunde gerundet. „Nach dem Aufstehen"
     * beginnt beim Aufstehen — bei 07:40 also um 07:40 und nicht um 07:00, was
     * noch vor dem Aufstehen läge. „Vor dem Schlafengehen" **endet** an der
     * Schlafenszeit: Der Block wird so weit nach vorn gelegt, dass er gerade
     * noch hineinpasst. Eine Stunde davor wäre bei zehn Minuten Meditation
     * fünfzig Minuten zu früh und bei einer Stunde Lesen zu spät.
     *
     * `null`, wenn die Situation nicht am Rahmen hängt oder der Nutzer nicht
     * geladen ist — dann bleibt es beim Mittelwert aus der Vorschlagsliste,
     * statt je Gewohnheit eine eigene Abfrage loszutreten.
     */
    public function sleepBoundStartMinute(?Carbon $on = null): ?int
    {
        if (! $this->relationLoaded('user')) {
            return null;
        }

        $window = $this->user->sleepWindowFor(($on ?? Carbon::today())->dayOfWeekIso);

        $wake = DayPlan::toMinutes($window['wakeTime']);
        $bed = DayPlan::toMinutes($window['bedtime']);

        // Eine Schlafenszeit nach Mitternacht liegt jenseits des Tagesrands —
        // dieselbe Rechnung wie in {@see DayPlan::frame()}.
        if ($bed <= $wake) {
            $bed += 1440;
        }

        return match ($this->trigger_situation) {
            'nach dem Aufstehen' => $wake,
            'vor dem Schlafengehen' => max(
                $wake,
                $bed - ($this->durationMinutes() ?? DayPlan::AssumedMinutes),
            ),
            default => null,
        };
    }

    /**
     * Die Spanne, in der eine Situations-Gewohnheit ausweichen darf.
     *
     * Der Anfang ist die Stelle, an die der Kalender sie zuerst legt; das Ende
     * sagt, wie weit sie rutschen kann, wenn dort schon etwas liegt. Für die
     * beiden Situationen am Tagesrand hängt beides am Schlafplan, für die
     * übrigen steht es in {@see SituationWindows}.
     *
     * `null` heißt: keine Spanne, also auch kein Ausweichen — eine feste
     * Uhrzeit, eine Kette, oder eine selbst getippte Situation, über die
     * niemand etwas weiß.
     *
     * @return array{from: int, to: int}|null
     */
    public function situationWindow(?Carbon $on = null): ?array
    {
        if ($this->schedule_type !== ScheduleType::Dynamic || $this->trigger_situation === null) {
            return null;
        }

        $minutes = $this->durationMinutes() ?? DayPlan::AssumedMinutes;
        $start = $this->situationStart($on);

        if ($start === null) {
            return null;
        }

        // „Vor dem Schlafengehen" ist die einzige, die rückwärts zählt: Sie
        // endet an der Schlafenszeit, statt an einem Anlass zu beginnen.
        $window = $this->trigger_situation === 'vor dem Schlafengehen'
            ? ['from' => max(0, $start - self::EveningWindowMinutes), 'to' => $start + $minutes]
            : ['from' => $start, 'to' => $start + self::SituationWindowMinutes];

        // Das Fenster fasst mindestens, was hineinsoll: Eine vierstündige
        // Gewohnheit „nach dem Mittagessen" liegt eben von 13:00 bis 17:00.
        // Sonst gäbe es Dauern, zu denen keine Situation mehr passt.
        $window['to'] = max($window['to'], $window['from'] + $minutes);

        return $window;
    }

    /**
     * Wo eine Situation anfängt — an ihrem Anlass, nicht an einer Uhr.
     *
     * Jede der sechs hängt an etwas, das die App kennt: der Schlafplan sagt,
     * wann jemand aufsteht und ins Bett geht, der Stundenplan, wann die
     * Vorlesungen enden. Eine feste Uhrzeit wäre für Spätaufsteher und volle
     * Uni-Tage gleich falsch — wer um zehn aufsteht, frühstückt nicht um acht.
     *
     * Was die App **nicht** wissen muss: wann jemand isst. Wer sein Frühstück
     * als Gewohnheit führt, dessen Block liegt ohnehin im Weg, und die
     * Situation weicht ihm aus. Die Annahme reicht als Startpunkt.
     *
     * `null` heißt: An diesem Tag gibt es den Anlass nicht.
     */
    private function situationStart(?Carbon $on): ?int
    {
        $bound = $this->sleepBoundStartMinute($on);

        if ($bound !== null) {
            return $bound;
        }

        if ($this->trigger_situation !== self::AfterLecture) {
            return null;
        }

        $lastLecture = $this->lastLectureEnd($on);

        return $lastLecture === null
            ? ($this->knowsTimetable() ? null : self::LectureFallbackStart)
            : $lastLecture + DayPlan::BreatherMinutes;
    }

    /** Weiß die App überhaupt etwas über Vorlesungen? */
    private function knowsTimetable(): bool
    {
        return $this->relationLoaded('user')
            && ($this->timetable ??= Timetable::for($this->user))->semester() !== null;
    }

    /**
     * Wann an diesem Tag die letzte Vorlesung endet — null, wenn keine läuft.
     */
    private function lastLectureEnd(?Carbon $on): ?int
    {
        if (! $this->knowsTimetable()) {
            return null;
        }

        $lectures = ($this->timetable ??= Timetable::for($this->user))
            ->blocksOn($on ?? Carbon::today());

        return $lectures === [] ? null : max(array_column($lectures, 'to'));
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
            && $this->scheduled_time !== null
            // Eine Uhrzeit, die nur noch Erinnerung ist, weckt niemanden.
            && ! $this->displacedOn();
    }

    /**
     * Der Umfang als fertige Zeile: „20 Min" — oder nichts.
     *
     * Neue Gewohnheiten haben immer eine Dauer; `null` gibt es nur noch bei
     * Einträgen aus der Zeit der freien Eingabe, und „1,5 L" nur bei alten
     * Zeilen mit einer Einheit, die der Katalog nicht mehr vergibt.
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
    public function startsAt(?Carbon $on = null): ?CarbonInterface
    {
        return $this->resolveStart(0, $on);
    }

    /**
     * Wo im Tag die Gewohnheit beginnt, als Minute seit Mitternacht.
     *
     * Die echte Uhrzeit, wo es eine gibt — sonst die Stunde, auf die der
     * Kalender die Situation ohnehin sortiert. Das Zweite ist eine Näherung
     * und keine Behauptung; ob es eine war, sagt {@see startsAt()}.
     *
     * Die Rechnung steht hier und nicht in {@see DayPlan}, weil drei Stellen
     * sie brauchen: das Time-Blocking für freie Fenster, das Stundenraster zum
     * Hinlegen und die Erinnerung für ihren Wecker. Liefen sie auseinander,
     * stünde ein Block woanders, als der Server ihn glaubt.
     */
    public function dayStartMinute(?Carbon $on = null): ?int
    {
        $exact = $this->startsAt($on);

        if ($exact !== null) {
            return $exact->hour * 60 + $exact->minute;
        }

        // Erst die Frage, ob sie an diesem Tag überhaupt eine Stelle hat:
        // Eine geparkte Gewohnheit hat keine, und die Minute vom Tagesrand
        // legte sie sonst zurück an genau den Platz, den jetzt ein Kurs hat.
        $hour = $this->dayAnchorHour($on);

        if ($hour === null) {
            return null;
        }

        // Die beiden Situationen am Tagesrand kennen ihre Minute genau; für
        // alle anderen bleibt es bei der Stunde aus der Vorschlagsliste.
        return $this->sleepBoundStartMinute($on) ?? $hour * 60;
    }

    /**
     * Hier zahlt sich die Dauer aus: Eine gekoppelte Gewohnheit beginnt, wo die
     * vorige aufhört. Ein 20-Minuten-Block um 17:00 setzt die nächste auf 17:20,
     * und über mehrere Glieder rechnet sich das durch.
     *
     * Ohne bekannte Dauer beim Vorgänger bleibt dessen eigener Beginn stehen —
     * gleichzeitig anzufangen ist falsch, aber weniger falsch als eine
     * erfundene Länge.
     */
    private function resolveStart(int $depth, ?Carbon $on = null): ?CarbonInterface
    {
        if ($depth >= self::MaxChainDepth) {
            return null;
        }

        // Die Ausnahme für einen Tag schlägt jede Regel — auch die Kette und
        // den Parkvermerk. Wer sein Lesen einmal nach hinten schiebt,
        // verschiebt damit den Block, nicht seinen Plan; und fällt die
        // Vorlesung an einem Tag aus, liegt die verdrängte Gewohnheit an
        // genau diesem Tag wieder da. „Für heute hierhin gelegt" ist die
        // speziellere Aussage als „hat gerade keinen Platz".
        $shifted = $this->shiftedTimeOn($on);

        if ($shifted !== null) {
            return Carbon::createFromFormat('H:i', $shifted);
        }

        // Eine geparkte Gewohnheit hat sonst keine Stelle im Tag — sie läge
        // weiter dort, wo jetzt der Kurs ist. Hier und nicht bei den
        // Aufrufern, damit Raster, Rechnung und Erinnerung dasselbe sehen.
        // Die Kette fällt mit: Ein Nachfolger fragt seinen Vorgänger, und der
        // antwortet mit nichts. Deshalb reicht hier die eigene Spalte.
        if ($this->displacedOn($on)) {
            return null;
        }

        if ($this->schedule_type->hasClockTime()) {
            return $this->scheduled_time?->copy();
        }

        if ($this->schedule_type !== ScheduleType::Chained) {
            return null;
        }

        $previous = $this->chainedTo;

        if ($previous === null) {
            return null;
        }

        // „Danach" heißt nicht „in derselben Minute": Nach dem Ende bleibt
        // die Viertelstunde Luft, die zwischen zwei Gewohnheiten überall gilt.
        return $previous->resolveStart($depth + 1, $on)
            ?->copy()
            ->addMinutes(($previous->durationMinutes() ?? DayPlan::AssumedMinutes) + DayPlan::ChainBreatherMinutes);
    }

    /**
     * Wann eine angehängte Gewohnheit anfinge — nach dem Ende, plus Luft.
     *
     * Dieselbe Rechnung wie in {@see resolveStart()}, nur von außen
     * gefragt: für die Zeile „danach ab 17:35" bei der Wahl der Vorgängerin.
     */
    public function followerStartsAt(?Carbon $on = null): ?CarbonInterface
    {
        return $this->startsAt($on)
            ?->copy()
            ->addMinutes(($this->durationMinutes() ?? DayPlan::AssumedMinutes) + DayPlan::ChainBreatherMinutes);
    }

    /**
     * Wann der Block wieder frei ist — Beginn plus Dauer.
     *
     * Ohne eines von beidem gibt es kein Ende: Eine Gewohnheit ohne Uhrzeit
     * belegt keinen Platz, und eine ohne Dauer ist ein Punkt, keine Spanne.
     */
    public function endsAt(?Carbon $on = null): ?CarbonInterface
    {
        $start = $this->startsAt($on);
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
    public function timeRangeLabel(?Carbon $on = null): ?string
    {
        $start = $this->startsAt($on);

        if ($start === null) {
            return null;
        }

        $end = $this->endsAt($on);

        if ($end !== null) {
            return $start->format('H:i').' – '.$end->format('H:i');
        }

        // Ohne Dauer bleibt nur der Beginn — und der lohnt die Zeile nur da, wo
        // er sonst nirgends steht. Bei fester Uhrzeit nennt ihn der Anker schon;
        // bei „nach dem Spaziergang" wüsste man ihn sonst nicht.
        return $this->schedule_type->hasOwnAnchor()
            ? null
            : 'ab '.$start->format('H:i');
    }

    /**
     * Der Wann-Teil als fertige Zeile für die Oberfläche.
     *
     * Beispiele: „nach dem Aufstehen", „17:00 · Mo–Fr", „08:30 · täglich".
     *
     * Die Zeile ist nur die Zusammensetzung von {@see schedulePieces()} — wer
     * die beiden Hälften einzeln braucht, holt sie dort. So kann die kurze
     * Form nicht von der langen abweichen.
     */
    public function scheduleLabel(?Carbon $on = null): string
    {
        return collect($this->schedulePieces($on))
            ->filter()
            ->implode(' · ');
    }

    /**
     * Derselbe Wann-Teil in seinen zwei Hälften: die Uhr und die Wiederholung.
     *
     * Die Übersicht liest den Tag als Plan von früh nach spät und stellt die
     * Uhrzeit dafür in eine eigene Spalte. Als Teil einer Kette aus Punkten
     * ging sie unter: „mit Test2 · 09:00 · nur an diesem Tag · 90 Min" sind
     * vier verschiedene Auskünfte in einem Gewicht, und zwei gleichnamige
     * Gewohnheiten am selben Tag ließen sich darin nicht auseinanderhalten.
     *
     * `time` steht nur da, wo es wirklich eine Uhr gibt. „nach dem Aufstehen"
     * ist ein Zeitpunkt, aber keine Uhrzeit — die abgeleitete Stunde in die
     * Spalte zu schreiben wäre eine Festlegung, die niemand getroffen hat.
     *
     * @return array{timeLabel: string|null, repeatLabel: string|null}
     */
    public function schedulePieces(?Carbon $on = null): array
    {
        // An einem verschobenen Tag gilt die Ausnahme, nicht der Plan. Sie sagt
        // dazu, dass sie nur für diesen Tag gilt — sonst sähe die Zeile aus,
        // als hätte sich die Gewohnheit dauerhaft geändert.
        $shifted = $this->shiftedTimeOn($on);

        if ($shifted !== null) {
            return ['timeLabel' => $shifted, 'repeatLabel' => 'nur an diesem Tag'];
        }

        // Verdrängt, aber nicht vergessen: Die Zeile nennt, wann die Gewohnheit
        // lief — das ist der Anhaltspunkt für den neuen Platz, für die Person
        // wie für die KI. In die Uhrspalte gehört das nicht: Die alte Zeit gilt
        // ja gerade nicht mehr.
        if ($this->isDisplaced($on)) {
            $previous = $this->anchorHabit()?->scheduled_time?->format('H:i');

            return [
                'timeLabel' => null,
                'repeatLabel' => $previous === null
                    ? 'braucht einen neuen Platz'
                    : 'braucht einen neuen Platz · lief bisher '.$previous,
            ];
        }

        // Der Vorgänger ist der Auslöser, also heißt er auch so: „nach dem
        // Spaziergang". Genau die Form, die time-blocking.md für Ketten
        // vorsieht — eine bestehende Gewohnheit ist der zuverlässigste Auslöser,
        // den es gibt.
        if (! $this->schedule_type->hasOwnAnchor()) {
            $previous = $this->chainedTo;

            return [
                'timeLabel' => null,
                'repeatLabel' => $previous === null
                    ? 'noch ohne Anschluss'
                    : 'nach „'.$previous->title.'"',
            ];
        }

        // Die Situation trägt ihre Tage mit, sobald es nicht alle sieben sind:
        // „nach dem Aufstehen" allein läse sich wie jeden Tag, und genau das
        // war es einmal.
        if (! $this->schedule_type->hasClockTime()) {
            $days = $this->activeWeekdays();

            return [
                'timeLabel' => null,
                'repeatLabel' => count($days) === 7
                    ? $this->trigger_situation
                    : $this->trigger_situation.' · '.self::weekdayLabel($days),
            ];
        }

        $time = $this->scheduled_time?->format('H:i');

        // Ohne Uhrzeit steht auch die Wochentag-Aufzählung nicht: Sie beschriebe
        // die Wiederholung eines Zeitpunkts, den es nicht gibt.
        return $time === null
            ? ['timeLabel' => null, 'repeatLabel' => null]
            : ['timeLabel' => $time, 'repeatLabel' => self::weekdayLabel($this->scheduled_days ?? [])];
    }

    /**
     * Wann im Tag — ohne die Wiederholung.
     *
     * Der Unterschied zu {@see scheduleLabel()} sind die Wochentage. Sie
     * gehören zur Gewohnheit ihres Besitzers, und in einer Verabredung hat
     * niemand etwas von ihnen: Die gilt für **einen** Tag, und der steht schon
     * daneben. Wer gefragt wurde, ob er heute mitmacht, las bisher „17:00 · Mo,
     * Mi" und musste annehmen, er verpflichte sich für Montag und Mittwoch.
     *
     * Der eigene Rhythmus entsteht erst beim Übernehmen, und dort wählt ihn
     * der Übernehmende selbst im Assistenten.
     */
    public function momentLabel(): string
    {
        if (! $this->schedule_type->hasOwnAnchor()) {
            $previous = $this->chainedTo;

            return $previous === null
                ? 'noch ohne Anschluss'
                : 'nach „'.$previous->title.'"';
        }

        return $this->schedule_type->hasClockTime()
            ? (string) $this->scheduled_time?->format('H:i')
            : (string) $this->trigger_situation;
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
    public static function weekdayLabel(array $scheduledDays): string
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
     * Alles, was im Tag noch eine Stelle hat.
     *
     * Für Abfragen, die `scheduled_time` roh lesen, statt über `startsAt()`
     * zu gehen — dort greift der Vermerk nicht von selbst.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function placed(Builder $query): void
    {
        // Ein Vermerk, der erst ab Semesterbeginn gilt, nimmt heute nichts weg.
        $query->where(fn (Builder $inner) => $inner
            ->whereNull('displaced_at')
            ->orWhere('displaced_at', '>', now()));
    }

    /**
     * Was einen Vermerk trägt — heute platzlos oder ab Semesterbeginn.
     *
     * Beides gehört gesagt: Wer im September einträgt, dass im Oktober ein
     * Kurs auf dem Spaziergang liegt, soll das nicht erst am 1. Oktober
     * erfahren. Ob der Vermerk *heute* schon gilt, sagt {@see isDisplaced()}.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function displaced(Builder $query): void
    {
        $query->whereNotNull('displaced_at');
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
        $consistency = $this->consistency($days, $until);

        if ($consistency === null) {
            return null;
        }

        return (int) round($consistency['done'] / $consistency['scheduled'] * 100);
    }

    /**
     * Dieselbe Konsistenz als die zwei Zahlen, aus denen sie besteht.
     *
     * „18 von 21 Tagen" statt „84 %". Der Prozentwert ist der Vergleichswert
     * zwischen Gewohnheiten, die beiden Zahlen sind der ehrlichere Blick auf
     * eine einzelne — vor allem bei seltenen.
     *
     * **Warum das nicht dasselbe ist:** Eine Gewohnheit für Samstag und
     * Sonntag hat im Fenster acht vorgesehene Tage. Ein ausgelassenes
     * Wochenende macht daraus 75 %, zwei machen 50 % — eine Zahl, die nach
     * Note klingt, obwohl Lally et al. 2010 für einzelne Aussetzer keine
     * messbaren Langzeitkosten finden (`progress-tracking.md`). „6 von 8
     * Tagen" sagt dasselbe, ohne zu urteilen. Die Interviewauswertung führt
     * genau diesen Fall als Gegenbeispiel: Eine App, die anzeigte, wie weit
     * jemand zurückgefallen war, wirkte demotivierend, obwohl Fortschritt da
     * war — „Fortschritt braucht einen ehrlichen, nicht strafenden Kontext."
     *
     * `sinceStart` sagt, ob das Anlegedatum das Fenster beschnitten hat, die
     * Gewohnheit also jünger als `$days` ist. Ohne diese Auskunft könnte die
     * Oberfläche „1 von 1" nicht erklären: Sie verspräche dreißig Tage und
     * zeigte einen. Die Zahl ist dann richtig gerechnet und trotzdem nicht zu
     * lesen.
     *
     * `null`, solange es kein Fenster gibt: Vor dem ersten vorgesehenen Tag
     * gäbe es nichts zu teilen und nichts zu zählen.
     *
     * @return array{done: int, scheduled: int, sinceStart: bool}|null
     */
    public function consistency(int $days = 30, ?Carbon $until = null): ?array
    {
        $until ??= Carbon::today();
        $window = $this->consistencyWindow($until, $days);

        if ($window === null) {
            return null;
        }

        return ['done' => $this->consistencyDone($until, $days), ...$window];
    }

    /**
     * Nur der Zähler: genutzte Gelegenheiten im Fenster.
     *
     * **Gezählt wird nur, was auf einen vorgesehenen Wochentag fällt** — nach
     * derselben Regel, nach der {@see consistencyWindow()} den Nenner bildet.
     * Ohne diese Bedingung war die Rate zerlegbar: Wer täglich abhakte und
     * seinen Plan danach auf Sa+So stellte, las „20 von 9 Tagen". Der Nenner
     * rechnet mit dem heutigen Plan, der Zähler zählte jeden Haken im Fenster,
     * auch die von Tagen, an denen die Gewohnheit nach diesem Plan nie anstand.
     *
     * Neu ist die Regel nicht, nur ihre zweite Hälfte:
     * {@see HabitCompletionController::completionDate()} weist ein Nachtragen
     * an einem nicht vorgesehenen Tag längst ab, wörtlich weil „eine Erfüllung
     * dort sie über 100 % treiben" würde. Was beim Schreiben gilt, muss beim
     * Lesen auch gelten — sonst reicht eine Planänderung, um die Zahl kaputt
     * zu machen.
     *
     * `isScheduledOn()` und nicht `isDueOn()`: Der Nenner fragt allein nach dem
     * Wochentag. Prüfte der Zähler zusätzlich Auslöser und Vermerk, entstünde
     * dieselbe Schieflage nur andersherum.
     *
     * Die geladene Beziehung wird bevorzugt — auf beiden Seiten, die diese Zahl
     * zeigen, liegen die Termine ohnehin schon für die Serie im Speicher.
     */
    public function consistencyDone(?Carbon $until = null, int $days = 30): int
    {
        $until ??= Carbon::today();
        $from = $until->copy()->subDays($days - 1)->startOfDay();
        $to = $until->copy()->endOfDay();

        if ($this->activeWeekdays() === []) {
            return 0;
        }

        // Carbon-Instanzen statt Datums-Strings: der `date`-Cast legt die Spalte
        // als "Y-m-d H:i:s" ab, ein String-Vergleich gegen "Y-m-d" würde den
        // letzten Tag lexikografisch aus dem Fenster schneiden.
        $dates = $this->relationLoaded('completionDates')
            ? $this->completionDates->pluck('completed_on')
            : $this->completions()->whereBetween('completed_on', [$from, $to])->pluck('completed_on');

        return $dates
            // Die geladene Beziehung trägt die ganze Historie, die eigene
            // Abfrage nur das Fenster — geprüft wird deshalb hier, für beide.
            //
            // `CarbonInterface` und nicht `Carbon`: Je nachdem, woher die Zeile
            // kommt, liegt im Cast eine unveränderliche Instanz.
            ->filter(fn (CarbonInterface $date): bool => $date->betweenIncluded($from, $to)
                && $this->isScheduledOn(Carbon::instance($date)))
            ->count();
    }

    /**
     * Nur der Nenner: wie viele Gelegenheiten das Fenster überhaupt enthielt.
     *
     * Reine Rechnung, keine Datenbank. Das ist der Grund, aus dem er getrennt
     * steht: Der **Zähler** braucht die Grenze am Anlegedatum gar nicht, denn
     * Erfüllungen vor dem Anlegen kann es nicht geben. Er lässt sich deshalb
     * beim Laden der Gewohnheiten in einem `withCount` mitnehmen und kostet
     * nichts extra. Nur der Nenner braucht die Grenze, und die steht hier.
     *
     * Wer beides zusammen will und die Abfrage nicht scheut, nimmt
     * {@see consistency()}.
     *
     * `sinceStart` sagt, ob das Anlegedatum das Fenster beschnitten hat. Ohne
     * diese Auskunft könnte die Oberfläche „1 von 1" nicht erklären: Sie
     * verspräche dreißig Tage und zeigte einen.
     *
     * @return array{scheduled: int, sinceStart: bool}|null
     */
    public function consistencyWindow(?Carbon $until = null, int $days = 30): ?array
    {
        $until ??= Carbon::today();
        $created = $this->created_at->copy()->startOfDay();
        $full = $until->copy()->subDays($days - 1);

        $scheduled = $this->scheduledDaysBetween($full->copy()->max($created), $until);

        if ($scheduled < 1) {
            return null;
        }

        return ['scheduled' => $scheduled, 'sinceStart' => $created->greaterThan($full)];
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
            if ($this->isDueOn($cursor)) {
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
        // Die Kette ist schon in `activeWeekdays()` mitgedacht: Sie läuft so
        // oft wie die, an der sie hängt — hinter einer Mo–Fr-Gewohnheit wären
        // „12 Tage in Folge" gelogen.
        return count($this->activeWeekdays()) === 7;
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

        // Die gekoppelte Gewohnheit steht an denselben Tagen an wie die, an der
        // sie hängt — das löst `activeWeekdays()` bereits auf. Sonst rechnete
        // sie mit einem Soll, das ihr Vorgänger gar nicht hat.
        $weekdays = $this->activeWeekdays();

        if ($weekdays === []) {
            return 0;
        }

        if (count($weekdays) === 7) {
            return $length;
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
            'displaced_at' => 'datetime',
        ];
    }
}
