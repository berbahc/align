<?php

namespace App\Models;

use App\Support\AppointmentFit;
use App\Support\DayPlan;
use App\Support\Timetable;
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
 * @property Carbon|null $starts_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['habit_id', 'requester_id', 'invitee_id', 'scheduled_for', 'starts_at'])]
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

        return array_map(function (string $day) use ($habit): array {
            $date = Carbon::parse($day);

            return [
                'value' => $day,
                'label' => self::dayLabel($date),
                // Die Uhrzeit **dieses** Tages, und zwar genau die, die die
                // Verabredung tragen wird ({@see startTimeFor()}). Das Sheet
                // zeigte bislang die Zeile von heute — und die trug an einem
                // Tag, an dem schon etwas verschoben war, „07:00 · nur an
                // diesem Tag" für einen Donnerstag, an dem beides nicht galt.
                // Eine Situation löst sich ohnehin je Tag anders auf.
                'time' => self::startTimeFor($habit, $date),
            ];
        }, array_slice($days, 0, self::DayChoices));
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
        $pending = self::query()
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
            ->get();

        // Einmal für alle Anfragen: Ob eine von ihnen eine eigene Zeile
        // ersetzt, hängt am eigenen Plan, und der ändert sich zwischen zwei
        // Anfragen nicht.
        $habits = $user->habits()->active()->get();
        $habits->each(fn (Habit $habit) => $habit->setRelation('user', $user));

        return $pending
            ->map(fn (self $appointment): array => [
                'id' => $appointment->id,
                // Wer fragt — als Kennung und nicht nur als Name: Fragt
                // dieselbe Person zweimal, steht ihr Name künftig einmal über
                // beiden Fragen, und zwei Freunde dürfen gleich heißen.
                'requesterId' => $appointment->requester_id,
                ...$appointment->companion($user),
                'title' => $appointment->habit->title,
                'anchor' => $appointment->timeLabel(),
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
                // Und was die Zusage aus dem eigenen Tag nimmt: Wer zum
                // Frühstück zusagt und selbst Frühstück im Plan hat,
                // frühstückt einmal. Das gehört vor die Zusage und nicht
                // hinterher in den Kalender.
                'replaces' => self::replacedRow($appointment->replaces($user, $habits)),
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
     * Die ersetzte Gewohnheit für die Karte — Titel und Zeitpunkt.
     *
     * Nicht die ganze Zeile: Verschwinden soll sie an diesem Tag, angefasst
     * wird sie hier nicht.
     *
     * @return array{title: string, moment: string}|null
     */
    private static function replacedRow(?Habit $habit): ?array
    {
        return $habit === null ? null : [
            'title' => $habit->title,
            'moment' => $habit->momentLabel(),
        ];
    }

    /**
     * Die eigene Gewohnheit, die diese Verabredung an diesem Tag ersetzt.
     *
     * Wer Aileen zum Frühstück zusagt und selbst Frühstück im Plan hat, will
     * an dem Tag einmal frühstücken und nicht zweimal. Dieselbe Sache am
     * selben Tag ist dieselbe Sache — die eigene Zeile fällt weg, der Eintrag
     * der Verabredung nimmt ihren Platz, und ein Haken zählt für beides
     * ({@see wasDoneBy()}).
     *
     * Woran „dieselbe Sache" hängt: an der Vorlage aus dem Katalog, nicht am
     * Titel. „Frühstücken" und „Frühstück" wären zwei Zeichenketten und
     * dasselbe Essen; wer seine Gewohnheit umbenennt, soll nicht plötzlich
     * zweimal radeln. Ohne Vorlage — alte Zeilen aus der Zeit der freien
     * Eingabe — gibt es nichts zu vergleichen, und dann bleibt es beim
     * gewöhnlichen Konflikt.
     *
     * Die Uhrzeit entscheidet ausdrücklich **nicht** mit: Ob gemeinsam um
     * neun statt allein um acht gefrühstückt wird, ändert nichts daran, dass
     * es ein Frühstück ist.
     *
     * @param  Collection<int, Habit>  $habits  Der eigene Plan, einmal geladen
     */
    public function replaces(User $user, Collection $habits): ?Habit
    {
        // Nur auf der gefragten Seite: Die fragende führt die Gewohnheit
        // selbst, dort ist die Verabredung ohnehin ihr eigener Block.
        if ($this->invitee_id !== $user->id) {
            return null;
        }

        $template = $this->habit->template_key;

        if ($template === null) {
            return null;
        }

        $date = Carbon::parse($this->scheduled_for)->startOfDay();

        return $habits->first(fn (Habit $habit): bool => $habit->template_key === $template
            && $habit->id !== $this->habit_id
            && $habit->isScheduledOn($date));
    }

    /**
     * Dieselbe Frage, wenn der eigene Plan nicht schon zur Hand ist.
     *
     * Für die einzelne Zusage — eine Liste fragt {@see replacementsIn()} und
     * lädt einmal statt je Zeile.
     */
    public function replacementFor(User $user): ?Habit
    {
        if ($this->invitee_id !== $user->id || $this->habit->template_key === null) {
            return null;
        }

        $habits = $user->habits()->active()->get();
        $habits->each(fn (Habit $habit) => $habit->setRelation('user', $user));

        return $this->replaces($user, $habits);
    }

    /**
     * Dieselbe Frage für einen ganzen Tag: Welche eigene Gewohnheit ist ersetzt?
     *
     * Nach der Kennung der ersetzten Gewohnheit abgelegt, weil die Aufrufer
     * genau so fragen — „steht diese Zeile heute noch?".
     *
     * @param  Collection<int, self>  $appointments
     * @param  Collection<int, Habit>  $habits
     * @return array<int, self>
     */
    public static function replacementsIn(Collection $appointments, User $user, Collection $habits): array
    {
        $replacements = [];

        foreach ($appointments as $appointment) {
            $replaced = $appointment->replaces($user, $habits);

            if ($replaced !== null) {
                $replacements[$replaced->id] = $appointment;
            }
        }

        return $replacements;
    }

    /**
     * Was zugesagte Verabredungen an diesen Tagen belegen.
     *
     * **Die eine Stelle, an der aus Zusagen die Belegung eines Tages wird** —
     * das Gegenstück zu {@see Timetable::blocksOn()} für Kurse, und aus
     * demselben Grund an einem Ort: {@see DayPlan} nimmt Fremdblöcke von
     * mehreren Aufrufern entgegen, und gäbe einer eine andere Belegung heraus
     * als die übrigen, säße dieselbe Minute auf zwei Wegen an zwei Stellen.
     *
     * Zwei Arten fallen dabei heraus, weil sie schon als Gewohnheit im Tag
     * stehen und sonst doppelt zählten:
     *
     * - **Die eigene Frage.** Sie hängt an der eigenen Gewohnheit; die ist
     *   ohnehin im Plan.
     * - **Die ersetzte Sache.** Wer zum Frühstück zusagt und selbst
     *   Frühstück führt, hat seine Zeile für den Tag mitgezogen
     *   ({@see replaces()}) — sie belegt die Zeit bereits.
     *
     * Die Kennung ist `0`: positive Zahlen sind Gewohnheiten, negative Kurse
     * ({@see Timetable::isCourseBlock()}).
     *
     * Eine Abfrage für alle gefragten Tage — die Kollisionsprüfung fragt bis
     * zu vierzehn davon.
     *
     * @param  list<Carbon>  $dates
     * @param  Collection<int, Habit>  $habits  Der eigene Plan, einmal geladen
     * @return array<string, list<array{id: int, title: string, from: int, to: int}>> Schlüssel „Y-m-d"
     */
    public static function blocksOnDates(User $user, array $dates, Collection $habits, ?self $except = null): array
    {
        if ($dates === []) {
            return [];
        }

        // Als Carbon um Mitternacht und nicht als Zeichenkette: Die Spalte ist
        // ein Datum, abgelegt wird „2026-09-08 00:00:00", und ein Vergleich
        // gegen „2026-09-08" fände keine Zeile. Dieselbe Falle wie in
        // {@see HabitDayShiftController::store()}.
        $days = array_map(fn (Carbon $date): Carbon => $date->copy()->startOfDay(), $dates);

        $appointments = self::query()
            ->accepted()
            ->where('invitee_id', $user->id)
            ->whereIn('scheduled_for', $days)
            ->with(['habit', 'requester'])
            ->get();

        $appointments->each(
            fn (self $appointment) => $appointment->habit->setRelation('user', $appointment->requester),
        );

        $blocks = [];

        foreach ($appointments as $appointment) {
            if ($except !== null && $appointment->is($except)) {
                continue;
            }

            if ($appointment->replaces($user, $habits) !== null) {
                continue;
            }

            $from = $appointment->startMinute();

            $blocks[$appointment->scheduled_for->toDateString()][] = [
                'id' => 0,
                // Mit Namen, weil der Satz danach ihn braucht: „Such eine
                // andere Zeit" hilft nicht, wenn unklar bleibt, wofür.
                'title' => sprintf('%s mit %s', $appointment->habit->title, $appointment->requester->name),
                'from' => $from,
                'to' => $from + ($appointment->habit->durationMinutes() ?? DayPlan::AssumedMinutes),
            ];
        }

        return $blocks;
    }

    /**
     * Gehört dieser Fremdblock zu einer Verabredung?
     *
     * Die eine Stelle, an der die Kennung gelesen wird — wie
     * {@see Timetable::isCourseBlock()} für Kurse.
     *
     * @param  array{id: int, title: string, from: int, to: int}  $block
     */
    public static function isAppointmentBlock(array $block): bool
    {
        return $block['id'] === 0;
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

        // Wer die Sache selbst im Plan hat, hakt seine Zeile ab und nicht die
        // Zusage. Zwei Häkchen für einen Morgen wären eines zu viel.
        if ($this->replacementFor($user) !== null) {
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
        // Steht die Verabredung an der Stelle einer eigenen Gewohnheit, hängt
        // der Haken an dieser Gewohnheit — genau wie auf der fragenden Seite.
        // Ein zweiter Haken an der Zusage wäre derselbe Morgen zweimal.
        $replaced = $this->replacementFor($user);

        if ($replaced !== null) {
            // Oder-Verknüpfung und nicht nur die Gewohnheit: Wer die Zusage
            // abgehakt hat und die Gewohnheit erst danach übernimmt, hat den
            // Morgen trotzdem hinter sich. Ein Haken, der beim Übernehmen
            // wieder verschwände, wäre eine Strafe für den dritten Weg.
            return $this->completed_at !== null
                || $replaced->completions()
                    ->whereDate('completed_on', $this->scheduled_for)
                    ->exists();
        }

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
            'anchor' => $this->timeLabel(),
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
     * Wann die Verabredung stattfindet, als Minute seit Mitternacht.
     *
     * **Die eine Uhrzeit für beide Seiten.** Sie wird beim Vorschlagen aus dem
     * Tag der fragenden Person genommen und dann festgehalten: Eine Situation
     * („nach dem Aufstehen") hat keine Uhrzeit, sondern eine Stelle im Tag —
     * und die rechnet jeder aus seinem eigenen Schlafplan aus. Berkay steht um
     * sieben auf, Aylin frühstückt um neun; ohne diese Zeile stand derselbe
     * Morgen in zwei Kalendern an zwei Stellen.
     *
     * Für Zeilen aus der Zeit davor fällt sie auf die alte Rechnung zurück.
     */
    public function startMinute(): int
    {
        if ($this->starts_at !== null) {
            return $this->starts_at->hour * 60 + $this->starts_at->minute;
        }

        return $this->habit->dayStartMinute(Carbon::parse($this->scheduled_for)->startOfDay())
            ?? Habit::UnknownAnchorHour * 60;
    }

    /**
     * Die Stelle im Tag der fragenden Person, einmal als Uhrzeit.
     *
     * Beim Anlegen gerufen — danach steht sie fest ({@see startMinute()}).
     */
    public static function startTimeFor(Habit $habit, Carbon $date): string
    {
        // Ohne geladenen Nutzer kennt eine Situation ihre Minute nicht:
        // `sleepBoundStartMinute()` steigt dann aus, und „nach dem Aufstehen"
        // fiele auf die Stunde aus der Vorschlagsliste zurück statt auf die
        // wirkliche Aufstehzeit. `loadMissing` und nicht `setRelation`, damit
        // jeder Aufrufer richtig liegt und keiner eine Abfrage zu viel macht.
        $habit->loadMissing('user');

        return DayPlan::toTime(
            $habit->dayStartMinute($date) ?? Habit::UnknownAnchorHour * 60,
        );
    }

    /**
     * Wie die Uhrzeit auf einer Karte steht: „um 07:00".
     *
     * Und nicht mehr der Anker der fragenden Gewohnheit: „nach dem Aufstehen"
     * las die gefragte Person als **ihr** Aufstehen, und genau daraus entstand
     * das Missverständnis, das diese Uhrzeit beendet.
     */
    public function timeLabel(): string
    {
        return 'um '.DayPlan::toTime($this->startMinute());
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
            'starts_at' => 'datetime',
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
