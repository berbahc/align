<?php

namespace App\Models;

use App\Support\AppointmentFit;
use Carbon\CarbonInterface;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ein gemeinsamer Termin für genau einen Tag.
 *
 * @property int $id
 * @property int $habit_id
 * @property int $requester_id
 * @property int $invitee_id
 * @property Carbon $scheduled_for
 * @property Carbon|null $accepted_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['habit_id', 'requester_id', 'invitee_id', 'scheduled_for'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * Wie viele Tage Screen A1 zur Wahl stellt.
     *
     * Drei, wie das Mockup sie zeigt („heute · morgen · Do."). Mehr wäre der
     * Anfang einer Terminfindung — und die gehört laut §9 ausdrücklich nicht
     * in die App, sondern in WhatsApp.
     */
    public const int DayChoices = 3;

    /**
     * Wie weit im Voraus die drei Tage liegen dürfen.
     *
     * Die Tage kommen aus der Gewohnheit, nicht aus dem Kalender — und eine
     * wöchentliche Gewohnheit hätte sonst ihren dritten Termin erst in drei
     * Wochen. Eine Woche ist die Grenze, an der aus „das nächste Mal" ein
     * Vorausplanen würde.
     */
    public const int DayHorizon = 7;

    /**
     * Alle Tage, an denen sich diese Gewohnheit überhaupt verabreden ließe.
     *
     * Die eine Quelle für beide Seiten: Was die Oberfläche anbietet, ist immer
     * eine Teilmenge hiervon, und der Server prüft dagegen. Vorher rechneten
     * beide dieselbe Liste getrennt aus, und sobald der Anfang verschoben war
     * („Nochmal ausmachen?" beginnt am Tag danach), lag der dritte Vorschlag
     * jenseits der Prüfliste — die Oberfläche bot einen Tag an, der beim
     * Tippen durchfiel.
     *
     * Drei Regeln stecken darin:
     * - Nur Tage, an denen die Gewohnheit ansteht. Eine Mo–Fr-Gewohnheit
     *   lässt sich samstags nicht verabreden.
     * - Höchstens eine Woche weit ({@see DayHorizon}). Weiter wäre
     *   Vorausplanen statt „das nächste Mal".
     * - Ist die Uhrzeit von heute vorbei, fällt heute weg: Ein 17:00-Block um
     *   18:30 anzubieten wäre eine Verabredung für einen Moment, der vorüber
     *   ist.
     *
     * @return list<string> Datumszeilen im Format Y-m-d
     */
    public static function possibleDaysFor(Habit $habit): array
    {
        $from = self::slotHasPassedToday($habit) ? Carbon::tomorrow() : Carbon::today();
        $last = Carbon::today()->addDays(self::DayHorizon - 1);

        if ($from->greaterThan($last)) {
            return [];
        }

        $within = (int) round($from->copy()->startOfDay()->diffInDays($last)) + 1;
        $days = $habit->nextOccurrences(self::DayHorizon, $within, $from);

        // Was an keinem Tag vorgesehen ist, kann an jedem vorkommen: „Treppe
        // statt Aufzug" hat keinen nächsten Termin, aber jeden Tag eine
        // Gelegenheit. Für sie bleibt es beim schlichten Blick in den Kalender.
        if ($days === []) {
            return array_column(self::dayChoices($from), 'value');
        }

        return array_map(fn (Carbon $day): string => $day->toDateString(), $days);
    }

    /**
     * Die Tage, die Screen A1 zur Wahl stellt — höchstens drei.
     *
     * Nicht die nächsten drei Kalendertage, sondern die nächsten drei
     * **Termine der Gewohnheit**: Wer samstags eine Mo–Fr-Gewohnheit anlegt,
     * bekommt Montag, Dienstag, Mittwoch angeboten statt drei Tage, an denen
     * sie nicht stattfindet. Das ist dieselbe Logik, die §4 für die Uhrzeit
     * festhält — die Verabredung erfindet keine Zeit, sie nutzt die
     * vorhandene.
     *
     * `$after` verschiebt den Anfang: Der Weg „Nochmal ausmachen?" gibt den
     * Tag mit, an dem es gerade stattgefunden hat. Für den steht ja schon eine
     * Verabredung — ihn noch einmal anzubieten führte in die Abweisung und
     * wäre die Wiederholung von etwas, das gerade war.
     *
     * @param  Carbon|null  $after  Frühester Tag, der noch zur Wahl steht
     * @return list<array{value: string, label: string}>
     */
    public static function dayChoicesFor(Habit $habit, ?Carbon $after = null): array
    {
        $days = self::possibleDaysFor($habit);

        if ($after !== null) {
            $cut = $after->toDateString();
            $days = array_values(array_filter($days, fn (string $day): bool => $day >= $cut));
        }

        return array_map(fn (string $day): array => [
            'value' => $day,
            'label' => self::dayLabel(Carbon::parse($day)),
        ], array_slice($days, 0, self::DayChoices));
    }

    /**
     * Die nächsten drei Kalendertage — für alles ohne eigenen Termin.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function dayChoices(?Carbon $from = null): array
    {
        $from ??= Carbon::today();

        return array_map(function (int $offset) use ($from): array {
            $day = $from->copy()->addDays($offset);

            return [
                'value' => $day->toDateString(),
                'label' => self::dayLabel($day),
            ];
        }, range(0, self::DayChoices - 1));
    }

    /**
     * Ist der heutige Block dieser Gewohnheit schon vorbei?
     *
     * Nur feste Uhrzeiten können vorbei sein. Eine Situation ist kein
     * Zeitpunkt — „nach dem Aufstehen" lässt sich um 18:30 noch verabreden,
     * weil die Situation morgen wiederkommt und heute niemand widerlegen kann.
     */
    private static function slotHasPassedToday(Habit $habit): bool
    {
        $start = $habit->startsAt();

        return $start !== null && Carbon::today()->setTimeFrom($start)->isPast();
    }

    /**
     * „heute", „morgen", sonst der Wochentag.
     *
     * Der bloße Wochentag reicht nicht ganz: Die Auswahl reicht eine Woche
     * weit ({@see DayHorizon}), und der siebte Tag trägt denselben Namen wie
     * heute. Wer samstags gefragt wird, las dann „Samstag" und musste raten,
     * ob heute gemeint ist oder der in einer Woche — direkt daneben stand
     * eine zweite Anfrage mit „Heute". „Nächsten Samstag" beantwortet das.
     */
    public static function dayLabel(CarbonInterface $day): string
    {
        if ($day->isToday()) {
            return 'heute';
        }

        if ($day->isTomorrow()) {
            return 'morgen';
        }

        // Die App-Locale ist nicht deutsch, die Oberfläche schon.
        $weekday = $day->copy()->locale('de')->isoFormat('dddd');

        return $day->dayOfWeekIso === Carbon::today()->dayOfWeekIso
            ? 'nächsten '.$weekday
            : $weekday;
    }

    /**
     * @return BelongsTo<Habit, $this>
     */
    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    /**
     * @param  Builder<Appointment>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('accepted_at');
    }

    /**
     * @param  Builder<Appointment>  $query
     */
    #[Scope]
    protected function accepted(Builder $query): void
    {
        $query->whereNotNull('accepted_at');
    }

    /**
     * Verabredungen an einem bestimmten Tag.
     *
     * Heißt `onDate` und nicht `on`: `Model::on()` ist bei Eloquent bereits
     * vergeben — es wechselt die Datenbankverbindung. Ein Scope dieses Namens
     * lässt sich nicht einmal laden, PHP verweigert die Klasse.
     *
     * @param  Builder<Appointment>  $query
     */
    #[Scope]
    protected function onDate(Builder $query, Carbon $date): void
    {
        $query->whereDate('scheduled_for', $date);
    }

    /**
     * Alle Verabredungen, an denen jemand beteiligt ist — auf welcher Seite auch immer.
     *
     * @param  Builder<Appointment>  $query
     */
    #[Scope]
    protected function involving(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user) {
            $query->where('requester_id', $user->id)
                ->orWhere('invitee_id', $user->id);
        });
    }

    /**
     * Offene Anfragen an `$user`, fertig für die Oberfläche.
     *
     * Steht hier und nicht im Controller, weil zwei Seiten sie brauchen: die
     * Übersicht (Screen A2) und der Community-Bereich.
     *
     * Vergangenes verfällt still: Eine Anfrage für gestern ist keine Frage
     * mehr, und ein Hinweis darauf wäre ein Vorwurf.
     *
     * Sortiert nach Tag, das Nächste zuerst — auch innerhalb einer Person, die
     * mehrmals fragt. Die Oberfläche bündelt ihre Fragen unter einer
     * Kopfzeile, und dort oben soll die stehen, die zuerst beantwortet werden
     * muss.
     *
     * @return list<array{id: int, requesterId: int, name: string, initial: string, title: string, anchor: string, day: string, date: string, blueprint: array{title: string, templateKey: string|null, behaviorType: string, durationMinutes: int, measureLabel: string|null, scheduleType: string, triggerSituation: string|null, scheduledTime: string|null, scheduledDays: list<int>|null}, conflict: array{habitId: int, title: string, from: string, to: string, options: list<array{time: string, label: string}>}|null}>
     */
    public static function pendingFor(User $user): array
    {
        return self::query()
            ->pending()
            ->where('invitee_id', $user->id)
            ->whereDate('scheduled_for', '>=', Carbon::today())
            ->with(['requester', 'habit.chainedTo'])
            // Das Nächste zuerst. Ohne diese Zeile stand die Reihenfolge der
            // Anlage da: Wer heute gefragt wurde und vorher schon für nächste
            // Woche, las die ferne Frage oben und die heutige darunter — und
            // heute ist die einzige, die keinen Aufschub duldet. Die Kennung
            // entscheidet nur noch bei gleichem Tag, damit die Reihenfolge
            // zwischen zwei Aufrufen nicht springt.
            ->orderBy('scheduled_for')
            ->orderBy('id')
            ->get()
            ->map(fn (self $appointment): array => [
                'id' => $appointment->id,
                // Wer fragt — als Kennung und nicht nur als Name: Fragt
                // dieselbe Person zweimal, steht ihr Name künftig einmal über
                // beiden Fragen, und zwei Freunde dürfen gleich heißen.
                'requesterId' => $appointment->requester_id,
                ...$appointment->companion($user),
                'title' => $appointment->habit->title,
                'anchor' => $appointment->habit->momentLabel(),
                'day' => self::dayLabel($appointment->scheduled_for),
                // Das Datum roh dazu: Wer seinen Tag umstellen muss, um
                // zusagen zu können, braucht den Tag, nicht sein Wort.
                'date' => $appointment->scheduled_for->toDateString(),
                // Wer gefragt wird, sieht hier zum ersten Mal eine Gewohnheit,
                // die er selbst nicht führt. Manchmal ist die Antwort nicht ja
                // oder nein, sondern „das will ich auch" — dafür reist die
                // Vorlage mit.
                'blueprint' => $appointment->habit->blueprint(),
                // Und was dagegen steht: Wer zur selben Zeit schon etwas
                // vorhat, soll das sehen, bevor er zusagt — nicht danach.
                'conflict' => AppointmentFit::conflict($appointment, $user)?->present(),
            ])
            ->all();
    }

    /**
     * Alles, woran `$user` beteiligt ist und was noch bevorsteht.
     *
     * Das Fenster ist dasselbe, das die Wahl anbietet: eine Woche, heute
     * eingeschlossen. Weiter zu blicken wäre ein gemeinsamer Kalender
     * (Top-2 46 %, §9) — kürzer zu blicken hieße, dass eine zugesagte
     * Verabredung an einer wöchentlichen Gewohnheit nirgends stünde.
     *
     * Gibt die Einträge unaufbereitet zurück, weil die beiden Seiten
     * unterschiedlich aussortieren: Die Übersicht lässt weg, was schon in der
     * Habit-Zeile steht, der Community-Bereich hat keine solche Zeile.
     *
     * @return Collection<int, Appointment>
     */
    public static function upcomingFor(User $user, Carbon $today): Collection
    {
        return self::query()
            ->involving($user)
            ->whereDate('scheduled_for', '>=', $today)
            ->whereDate('scheduled_for', '<=', $today->copy()->addDays(self::DayHorizon - 1))
            ->with(['requester', 'invitee', 'habit.chainedTo'])
            ->orderBy('scheduled_for')
            ->get();
    }

    /**
     * Die zugesagten Verabredungen eines Tages — auf beiden Seiten.
     *
     * Der Kalender kannte Verabredungen bisher gar nicht: Er lädt die eigenen
     * Gewohnheiten, und wer zusagt, führt keine. Der gemeinsame Tag stand
     * damit in keinem der beiden Kalender — auf der gefragten Seite fehlte er
     * ganz, auf der fragenden fehlte, dass jemand mitmacht.
     *
     * Offene Anfragen bleiben draußen. Eine Frage ist kein Termin, und sie im
     * Raster zu zeigen hieße, einen Platz zu belegen, den niemand zugesagt hat.
     *
     * Die fragende Person wird als Nutzer der Gewohnheit gesetzt: Anker am
     * Tagesrand („nach dem Aufstehen") fragen den Schlafplan ihres Nutzers,
     * und das ist hier nicht der, der gerade schaut.
     *
     * @return Collection<int, Appointment>
     */
    public static function acceptedOn(User $user, Carbon $date): Collection
    {
        $appointments = self::query()
            ->involving($user)
            ->accepted()
            ->onDate($date)
            ->with(['habit.chainedTo', 'requester', 'invitee'])
            ->get();

        $appointments->each(
            fn (self $appointment) => $appointment->habit->setRelation('user', $appointment->requester),
        );

        return $appointments;
    }

    /**
     * An welchen Tagen eines Zeitraums etwas Gemeinsames ansteht.
     *
     * Dieselbe Form wie {@see Timetable::lectureDays()} — der Monat fragt
     * beide gleich, und beide antworten mit „ja an diesem Tag", nicht mit
     * „wie viel".
     *
     * @return array<string, true>
     */
    public static function acceptedDaysBetween(User $user, Carbon $from, Carbon $to): array
    {
        return self::query()
            ->involving($user)
            ->accepted()
            ->whereDate('scheduled_for', '>=', $from)
            ->whereDate('scheduled_for', '<=', $to)
            ->pluck('scheduled_for')
            ->mapWithKeys(fn (CarbonInterface $day): array => [$day->toDateString() => true])
            ->all();
    }

    /**
     * Darf `$user` diese Verabredung abhaken?
     *
     * Nur die gefragte Seite, und nur wenn zugesagt ist. Die fragende hakt
     * ihre eigene Gewohnheit ab wie immer — für sie wäre das hier ein zweiter
     * Haken für dieselbe Sache.
     *
     * Und nur am Tag selbst oder kurz danach: Dasselbe Nachtragefenster wie
     * bei einer Gewohnheit ({@see Habit::WeekOverviewDays}), aus demselben
     * Grund — weiter zurück wäre kein Nachtragen mehr.
     */
    public function isCompletableBy(User $user): bool
    {
        if ($this->accepted_at === null || $this->invitee_id !== $user->id) {
            return false;
        }

        $day = Carbon::parse($this->scheduled_for)->startOfDay();
        $today = Carbon::today();

        return $day->lessThanOrEqualTo($today)
            && $day->greaterThanOrEqualTo($today->copy()->subDays(Habit::WeekOverviewDays - 1));
    }

    /**
     * Hat `$user` seinen Anteil an dieser Verabredung erledigt?
     *
     * Zwei Wege zu derselben Aussage, weil die beiden Seiten verschiedene
     * Dinge abhaken: Die fragende Seite hakt **ihre Gewohnheit** ab, die
     * gefragte **ihre Zusage**. Gemeint ist beide Male „ich war dabei".
     */
    public function wasDoneBy(User $user): bool
    {
        if ($this->invitee_id === $user->id) {
            return $this->completed_at !== null;
        }

        return $this->habit->completions()
            ->whereDate('completed_on', $this->scheduled_for)
            ->exists();
    }

    /**
     * Auf welcher **eigenen** Gewohnheit ließe sich das wiederholen?
     *
     * Vorschlagen darf nur, wem die Gewohnheit gehört
     * ({@see ProposeAppointmentRequest::authorize()}). Für die fragende Seite
     * ist das die Gewohnheit dieser Verabredung; für die gefragte die eigene
     * mit derselben Katalog-Vorlage — also die, die sie beim Übernehmen
     * angelegt hat.
     *
     * Bewusst über `template_key` und nicht über ein gespeichertes Feld: Eine
     * dauerhafte Verbindung zwischen Gewohnheit und Person wäre der Anfang des
     * gemeinsamen Kalenders (community_feature3.md §9). Hier entsteht sie im
     * Moment der Frage und bleibt nirgends liegen.
     *
     * Null heißt: Es gibt nichts zu wiederholen. Wer gefragt wurde und nicht
     * übernommen hat, führt die Gewohnheit nicht — er wird gefragt, statt zu
     * fragen.
     */
    public function repeatableHabitFor(User $user): ?Habit
    {
        if ($this->requester_id === $user->id) {
            return $this->habit;
        }

        if ($this->habit->template_key === null) {
            return null;
        }

        return $user->habits()
            ->active()
            ->where('template_key', $this->habit->template_key)
            ->first();
    }

    /**
     * Wartet diese Verabredung noch auf die Antwort von `$user`?
     *
     * Solche Einträge gehören auf beiden Seiten in die Karte mit „Passt mir"
     * und „Lieber nicht" — und nirgends ein zweites Mal daneben.
     */
    public function awaitsAnswerFrom(User $user): bool
    {
        return $this->accepted_at === null && $this->invitee_id === $user->id;
    }

    /**
     * Ein Eintrag der Liste „Zusammen".
     *
     * @return array{id: int, name: string, initial: string, title: string, anchor: string, day: string, accepted: bool, iAsked: bool, completed: bool|null, canComplete: bool}
     */
    public function present(User $user): array
    {
        return [
            'id' => $this->id,
            ...$this->companion($user),
            'title' => $this->habit->title,
            'anchor' => $this->habit->momentLabel(),
            'day' => self::dayLabel($this->scheduled_for),
            'accepted' => $this->accepted_at !== null,
            'iAsked' => $this->requester_id === $user->id,
            // Nur der eigene Haken. Was die andere Seite getan hat, steht hier
            // bewusst nicht (community_feature3.md §6).
            'completed' => $this->requester_id === $user->id
                ? null
                : $this->completed_at !== null,
            'canComplete' => $this->isCompletableBy($user),
        ];
    }

    /**
     * Die jeweils andere Person aus Sicht von `$user`.
     */
    public function counterpart(User $user): User
    {
        return $this->requester_id === $user->id
            ? $this->invitee
            : $this->requester;
    }

    /**
     * Die andere Person, aufbereitet für die Oberfläche.
     *
     * @return array{name: string, initial: string}
     */
    public function companion(User $user): array
    {
        $person = $this->counterpart($user);

        return [
            'name' => $person->name,
            'initial' => mb_strtoupper(mb_substr($person->name, 0, 1)),
        ];
    }

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
