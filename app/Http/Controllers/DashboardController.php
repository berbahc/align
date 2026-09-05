<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentNotice;
use App\Models\Friendship;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $today = Carbon::today();

        // Die Oberfläche ist durchgängig deutsch; die App-Locale ist es nicht,
        // deshalb wird sie hier gezielt für die Datumsausgabe gesetzt.
        $localisedToday = $today->copy();
        $localisedToday->locale('de');

        $habits = $request->user()
            ->habits()
            ->active()
            ->with(['completions' => fn (Relation $query) => $query->whereDate('completed_on', $today)])
            // Screen A3: die Verabredung sitzt in der Habit-Zeile, als
            // Doppel-Zeichen an der Stelle der Icon-Kachel.
            //
            // Auch die noch offene: Sie steht sonst zweimal auf der Seite —
            // einmal als Gewohnheit und einmal als Karte unter „Zusammen" —,
            // und die Karte sagt nichts, was die Zeile nicht schon sagt. Nur
            // eigene Gewohnheiten werden hier geladen, die Anfrage ist also
            // immer die eigene; was andere fragen, steht weiter oben.
            ->with(['appointments' => fn (Relation $query) => $query->onDate($today)->with('invitee')])
            // Die Serie braucht die ganze Historie, `completions` ist oben aber
            // auf heute eingegrenzt — deshalb die zweite, schmale Relation.
            ->with('completionDates')
            // Was heute ausnahmsweise woanders liegt: Ohne diese Zeile stünde
            // eine verschobene Gewohnheit weiter an ihrer alten Stelle, und
            // wer für eine Verabredung Platz gemacht hat, sähe davon nichts.
            ->with(['dayShifts' => fn (Relation $query) => $query->whereDate('shifted_on', $today)])
            ->withCount(['completions as completions_last_30_days' => fn (Builder $query) => $query
                ->where('completed_on', '>=', $today->copy()->subDays(29)->startOfDay()),
            ])
            ->orderBy('position')
            ->get();

        // Für die Anker-Stunden am Tagesrand („nach dem Aufstehen") fragt die
        // Gewohnheit den Schlafplan ihres Nutzers — die Beziehung wird hier
        // gesetzt, damit alle denselben geladenen Nutzer teilen, statt ihn je
        // einzeln nachzuladen.
        $habits->each(fn (Habit $habit) => $habit->setRelation('user', $request->user()));

        // Die Tagesliste zeigt nur, was heute ansteht. Eine Mo–Fr-Gewohnheit
        // ist am Samstag nicht offen, sondern nicht vorgesehen — sie dennoch
        // als unerledigt zu zeigen wäre eine Forderung, die niemand erhoben hat.
        // Der Tag wird von oben nach unten gelesen: Morgen zuerst, Abend
        // zuletzt — dieselbe Achse wie im Kalender. Die Anlege-Reihenfolge
        // entscheidet nur noch bei gleicher Stunde; als alleinige Sortierung
        // stellte sie das Abendritual über die Gewohnheit nach dem Aufstehen.
        $todaysHabits = $habits
            // Was gerade keinen Platz hat, steht nicht an — es wartet auf
            // einen neuen. Unter „heute" wäre es eine Aufgabe, die niemand
            // erfüllen kann.
            ->filter(fn (Habit $habit): bool => $habit->isDueOn($today))
            ->sortBy(fn (Habit $habit): array => [
                $habit->dayAnchorHour($today) ?? PHP_INT_MAX,
                $habit->position,
            ])
            ->values();

        return Inertia::render('dashboard', [
            'greeting' => $this->greeting($today),
            'today' => $localisedToday->isoFormat('dddd, D. MMMM'),
            // Der Rahmen des heutigen Tages: Schlafenszeit heute Abend,
            // Aufstehen morgen früh, Weckerstand für morgen. Die Karte führt
            // zum Schlafplan — sie ist sein Ort auf der Übersicht.
            //
            // `sleepCard`, nicht `sleep`: Den Namen trägt schon die geteilte
            // Eigenschaft mit dem Rahmen der umliegenden Tage — dieselbe
            // Bezeichnung würde sie auf dieser Seite überdecken.
            'sleepCard' => $this->sleepCard($request->user(), $today),
            // Mockup A2 setzt die offene Anfrage über die Gewohnheiten. Es ist
            // der einzige Weg, auf dem jemand von ihr erfährt — es gibt keine
            // Mail und kein Nachfassen (community_feature3.md §5).
            'friendRequests' => Friendship::pendingFor($request->user()),
            // Screen A2 für die Verabredung: dieselbe Stelle, anderer Inhalt.
            'appointmentRequests' => Appointment::pendingFor($request->user()),
            // Was jemand abgesagt hat — einmal, bis es weggeklickt ist (§5).
            'appointmentNotices' => AppointmentNotice::forUser($request->user()),
            // Alles Verabredete, was noch bevorsteht — für beide Seiten.
            'upcomingAppointments' => $this->upcomingAppointments(
                $request->user(),
                $today,
                $todaysHabits->pluck('id')->all(),
            ),
            'friends' => $request->user()->friends()->map(fn (User $friend): array => [
                'id' => $friend->id,
                'name' => $friend->name,
                'initial' => mb_strtoupper(mb_substr($friend->name, 0, 1)),
            ])->all(),
            'appointmentsEnabled' => $request->user()->appointments_enabled,
            'todayProgress' => $this->todayProgress($todaysHabits),
            'consistency' => $this->consistencyRate($habits, $today),
            'streak' => $this->streak($habits, $today),
            // Eine leere Tagesliste heißt nicht, dass es keine Gewohnheiten
            // gibt — eine Mo–Fr-Gewohnheit ist am Samstag schlicht nicht
            // vorgesehen. Ohne diese Zahl könnte die Oberfläche die beiden
            // Fälle nicht auseinanderhalten und würde am Wochenende zum
            // Anlegen auffordern, obwohl längst fünf Gewohnheiten laufen.
            'activeCount' => $habits->count(),
            'maxActive' => Habit::MaxActivePerUser,
            'habits' => $todaysHabits->map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'title' => $habit->title,
                'scheduleLabel' => $habit->scheduleLabel($today),
                // Dieselbe Auskunft in ihren zwei Hälften: Die Übersicht liest
                // den Tag als Plan und stellt die Uhr in eine eigene Spalte,
                // die Wiederholung bleibt in der Nebenzeile. Die ganze Zeile
                // steht daneben, weil das Verabredungs-Sheet sie braucht.
                ...$habit->schedulePieces($today),
                'behaviorType' => $habit->behavior_type->value,
                'measureLabel' => $habit->measureLabel(),
                'smallestStep' => $habit->smallest_step,
                // Der Warum-Satz wird seit dem Anlegen gespeichert und stand
                // bislang nirgends. Er erscheint jetzt im Starthilfe-Sheet —
                // dort, wo er trägt, und nicht dauerhaft in der Liste, wo er
                // abstumpfen würde.
                'motivation' => $habit->motivation,
                'completedAt' => $habit->completions->first()?->completed_at->format('H:i'),
                // §6: Zwei Häkchen? Nein — eines. Der Fortschritt der anderen
                // Person steht hier bewusst nicht, sonst wäre die Verabredung
                // durch die Hintertür doch ein Dauerstatus.
                'companion' => $this->companion($habit, $request->user()),
                'appointmentId' => $habit->appointments->first()?->id,
                // Die Tage, an denen sich genau diese Gewohnheit zu zweit
                // angehen lässt. Sie stehen an der Zeile und nicht einmal für
                // die ganze Seite: Eine Mo–Fr-Gewohnheit lässt sich freitags
                // nicht für morgen verabreden, eine tägliche schon.
                'appointmentDays' => Appointment::dayChoicesFor($habit),
            ])->all(),
        ]);
    }

    /**
     * Was mit jemandem ansteht — zugesagt oder von mir gefragt.
     *
     * Eine Verabredung, die erst morgen gilt, war bis eben unsichtbar: Sie
     * erschien am Tag selbst und davor nirgends. Wer für morgen zusagte, sah
     * danach nichts mehr und musste annehmen, es sei schiefgegangen.
     *
     * Das ist **kein** gemeinsamer Kalender (Top-2 46 %, abgelehnt): Es steht
     * hier nur, was ohnehin schon vereinbart ist, höchstens drei Tage weit,
     * und nach dem Tag verschwindet es spurlos (§7).
     *
     * Zwei Fälle fehlen bewusst, weil sie anderswo schon stehen: offene
     * Anfragen an mich (die Karte mit den Knöpfen darüber) und was heute an
     * einer Gewohnheit hängt, die in der Tagesliste steht — dort trägt es das
     * Doppel-Zeichen in der Zeile. Das gilt für die Zusage wie für die noch
     * offene Frage: Beide Male sagte die Karte nur noch einmal, was zwei
     * Zentimeter darüber schon steht.
     *
     * Entscheidend ist, dass die Zeile wirklich da ist. Eine verdrängte oder
     * für heute nicht vorgesehene Gewohnheit steht in keiner Tagesliste — ihre
     * Verabredung verschwände sonst ersatzlos. Deshalb kommen die Kennungen
     * der gezeigten Zeilen herein und nicht nur das Datum.
     *
     * Der Community-Bereich lässt nur den ersten Fall weg — dort gibt es keine
     * Habit-Zeile, die den zweiten tragen könnte.
     *
     * @param  list<int>  $shownHabitIds  Die Gewohnheiten, die heute in der Liste stehen
     * @return list<array{id: int, name: string, initial: string, title: string, anchor: string, day: string, accepted: bool, iAsked: bool, completed: bool|null, canComplete: bool, repeatHabitId: int|null, repeatDays: list<array{value: string, label: string}>}>
     */
    private function upcomingAppointments(User $user, Carbon $today, array $shownHabitIds): array
    {
        return Appointment::upcomingFor($user, $today)
            ->reject(fn (Appointment $appointment): bool => $appointment->awaitsAnswerFrom($user) || (
                $appointment->requester_id === $user->id
                && $appointment->scheduled_for->isToday()
                && in_array($appointment->habit_id, $shownHabitIds, true)
            ))
            ->map(fn (Appointment $appointment): array => [
                ...$appointment->present($user),
                ...$this->repeat($appointment, $user),
            ])
            ->values()
            ->all();
    }

    /**
     * Der Weg zur nächsten Verabredung — „Nochmal ausmachen?".
     *
     * `community_feature3.md` §7: der ganze Wiederholungs-Mechanismus, „immer
     * als neue Einzelentscheidung, nie als Abo". Felix' gemeinsamer Sport
     * scheiterte an der losen Absicht; jede Wiederholung wird deshalb frisch
     * hergestellt, statt einmal vereinbart und dann zu erodieren.
     *
     * **Nur nach dem eigenen Anteil.** Erschiene der Weg erst, wenn beide
     * abgehakt haben, verriete allein seine Anwesenheit, dass die andere
     * Person fertig ist — genau der Fremdfortschritt, den §6 ausschließt.
     *
     * @return array{repeatHabitId: int|null, repeatDays: list<array{value: string, label: string}>}
     */
    private function repeat(Appointment $appointment, User $user): array
    {
        $habit = $appointment->wasDoneBy($user)
            ? $appointment->repeatableHabitFor($user)
            : null;

        return [
            'repeatHabitId' => $habit?->id,
            // Die Tage kommen aus der Gewohnheit, nicht aus dem Kalender —
            // dieselbe Wahl wie beim ersten Mal, nur ab dem Tag **nach** dem
            // gemeinsamen. Für den gemeinsamen selbst steht schon eine
            // Verabredung; ihn noch einmal anzubieten führte in die Abweisung
            // und wäre ohnehin die Wiederholung von etwas, das gerade war.
            'repeatDays' => $habit === null ? [] : Appointment::dayChoicesFor(
                $habit,
                Carbon::parse($appointment->scheduled_for)->startOfDay()->addDay(),
            ),
        ];
    }

    /**
     * Wer heute mitmacht — zugesagt oder erst gefragt.
     *
     * Geladen werden nur eigene Gewohnheiten, die Verabredung hängt also immer
     * an einer eigenen Frage. `pending` unterscheidet die beiden Fälle, und die
     * Zeile zeichnet daraus einen durchgezogenen oder einen gestrichelten
     * zweiten Kreis — gestrichelt heißt im ganzen System „noch nicht
     * festgelegt" (designsprache.md §7.3).
     *
     * Bewusst ohne Fortschritt der anderen Person: Das wäre durch die Hintertür
     * doch ein Dauerstatus (community_feature3.md §6).
     *
     * @return array{name: string, initial: string, pending: bool, repeatHabitId: int|null, repeatDays: list<array{value: string, label: string}>}|null
     */
    private function companion(Habit $habit, User $user): ?array
    {
        $appointment = $habit->appointments->first();

        if ($appointment === null) {
            return null;
        }

        return [
            ...$appointment->companion($user),
            'pending' => $appointment->accepted_at === null,
            // Für „Nochmal ausmachen?" im erledigten Zustand der Zeile. Steht
            // erst da, wenn der eigene Anteil erledigt ist — siehe repeat().
            ...$this->repeat($appointment, $user),
        ];
    }

    /**
     * Der Rahmen, wie er von heute aus aussieht.
     *
     * Die Schlafenszeit gehört zum heutigen Abend, die Aufstehzeit zum
     * morgigen Morgen — zwei verschiedene Wochentage, wenn der Plan je Tag
     * verschieden ist. Der Wecker gilt für morgen früh: Das ist der nächste
     * Moment, in dem er klingeln könnte.
     *
     * @return array{bedtime: string, wakeTime: string, alarmEnabled: bool}
     */
    private function sleepCard(User $user, Carbon $today): array
    {
        $windows = $user->sleepWindows();
        $tonight = $windows[$today->dayOfWeekIso];
        $tomorrow = $windows[$today->copy()->addDay()->dayOfWeekIso];

        return [
            'bedtime' => $tonight['bedtime'],
            'wakeTime' => $tomorrow['wakeTime'],
            'alarmEnabled' => $tomorrow['alarmEnabled'],
        ];
    }

    private function greeting(Carbon $now): string
    {
        return match (true) {
            $now->hour < 11 => 'Guten Morgen',
            $now->hour < 18 => 'Schönen Tag',
            default => 'Guten Abend',
        };
    }

    /**
     * Fortschritt des heutigen Tages.
     *
     * Das Tagesziel ist schlicht die Anzahl der aktiven Gewohnheiten — es gibt
     * keine separate Zielgröße, die man verfehlen könnte. Formuliert wird
     * immer, was erledigt ist, nie was fehlt (Designsprache §1.5).
     *
     * @param  Collection<int, Habit>  $habits
     * @return array{completed: int, total: int, percentage: int}
     */
    private function todayProgress(Collection $habits): array
    {
        // Nur eigene Gewohnheiten. Eine zugesagte Verabredung zählt bewusst
        // nicht mit — sie ist die Gewohnheit einer anderen Person, und der
        // Nenner der Quote ist „was ich mir vorgenommen habe". Wer sie
        // dauerhaft will, übernimmt sie; dann ist sie eine eigene und zählt
        // wie jede andere. Festgehalten in `AppointmentCompletionTest`.

        $total = $habits->count();
        $completed = $habits->filter(
            fn (Habit $habit): bool => $habit->completions->isNotEmpty(),
        )->count();

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total === 0 ? 0 : (int) round($completed / $total * 100),
        ];
    }

    /**
     * Die stärkste laufende Serie — der Inhalt der Streak-Karte.
     *
     * Gerechnet wird über **alle** aktiven Gewohnheiten, nicht nur die heute
     * vorgesehenen: Die Serie einer Mo–Fr-Gewohnheit würde sonst jeden Samstag
     * von der Übersicht verschwinden, obwohl sie ungebrochen weiterläuft.
     *
     * Genau eine Serie, nicht fünf. Designsprache §5.4: die Streak-Karte ist
     * die einzige vollflächig farbige Fläche im mobilen Layout, „ihre Wirkung
     * hängt davon ab, dass sie allein bleibt".
     *
     * @param  Collection<int, Habit>  $habits
     * @return array{count: int, unit: string, title: string}|null
     */
    private function streak(Collection $habits, Carbon $today): ?array
    {
        $strongest = $habits
            ->map(fn (Habit $habit): array => [
                'count' => $habit->currentStreak($today),
                'unit' => $habit->streakUnit(),
                'title' => $habit->title,
            ])
            ->sortByDesc('count')
            ->first();

        if ($strongest === null || $strongest['count'] < Habit::StreakMinimum) {
            return null;
        }

        return $strongest;
    }

    /**
     * Gemeinsame Konsistenzrate über alle aktiven Gewohnheiten der letzten 30 Tage.
     *
     * Die ruhige Zweitansicht neben der Serie: Sie springt nicht bei einem
     * einzelnen Fehltag und bleibt damit der ehrlichere Blick über dreißig
     * Tage. Dass sie den Streak ersetzt, stand nur in progress-tracking.md und
     * stammt aus den Interviews — die Umfrage hat das widerlegt
     * (umfrage-auswertung.md §6). Ohne aktive Gewohnheiten gibt es keine Rate —
     * dann zeigt die Oberfläche den leeren Zustand statt „0 %".
     *
     * Die Zahl der möglichen Tage wird pro Gewohnheit ermittelt, nicht pauschal
     * mit 30 multipliziert: eine Mo–Fr-Gewohnheit hat in dreißig Tagen rund
     * zweiundzwanzig vorgesehene Tage. Über alle Tage zu rechnen würde sie
     * dauerhaft unter 72 % halten, obwohl sie lückenlos erfüllt wurde.
     *
     * @param  Collection<int, Habit>  $habits
     */
    private function consistencyRate(Collection $habits, Carbon $today): ?int
    {
        if ($habits->isEmpty()) {
            return null;
        }

        $start = $today->copy()->subDays(29);

        $possible = $habits->sum(
            fn (Habit $habit): int => $habit->scheduledDaysBetween($start, $today),
        );

        if ($possible < 1) {
            return null;
        }

        $completed = $habits->sum('completions_last_30_days');

        return (int) round($completed / $possible * 100);
    }
}
