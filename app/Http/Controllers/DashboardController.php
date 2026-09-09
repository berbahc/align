<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentNotice;
use App\Models\Friendship;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Wie viele laufende Serien die Übersicht zeigt.
     *
     * Drei, nicht fünf: Die Karten stehen nebeneinander, und drei ist die
     * Reihe, die auf jeder Breite noch ohne Umbruch trägt. Wer mehr Serien
     * hat, sieht sie auf der Gewohnheiten-Seite je Zeile.
     */
    public const int StreaksShown = 3;

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
        // Dieselbe Sache steht einmal im Tag, nicht zweimal: Wer zum Frühstück
        // zugesagt hat und selbst Frühstück im Plan hat, sieht **seine** Zeile
        // — mit dem Doppel-Zeichen, genau wie die fragende Seite. Sie zu
        // entfernen wäre falsch: Frühstücken ist eine tägliche Gewohnheit, an
        // ihr hängen Ketten („nach dem Frühstück"), und sie zählt in die
        // Serie. Weg fällt nur die zweite Zeile darunter
        // ({@see upcomingAppointments()}).
        $replaced = Appointment::replacementsIn(
            Appointment::acceptedOn($request->user(), $today),
            $request->user(),
            $habits,
        );

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
            'greeting' => $this->greeting(Carbon::now()),
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
            'consistency' => $this->consistency($habits, $today),
            'streaks' => $this->streaks($habits, $today),
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
                // Entscheidet das Zeichen: Die Vorlage weiß, worum es
                // geht, die Verhaltensrichtung ordnet nur fachlich ein.
                'templateKey' => $habit->template_key,
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
                'companion' => $this->companion($habit, $request->user(), $replaced[$habit->id] ?? null),
                'appointmentId' => ($replaced[$habit->id] ?? $habit->appointments->first())?->id,
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
                $appointment->scheduled_for->isToday()
                && (
                    // Die eigene Gewohnheit, für die ich gefragt habe …
                    ($appointment->requester_id === $user->id
                        && in_array($appointment->habit_id, $shownHabitIds, true))
                    // … und die eigene, die eine Zusage ersetzt: Beide Male
                    // trägt die Zeile in der Tagesliste das Doppel-Zeichen
                    // schon, und die Karte sagte nur noch einmal, was zwei
                    // Zentimeter darüber steht.
                    || in_array($appointment->replacementFor($user)?->id, $shownHabitIds, true)
                )
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
    private function companion(Habit $habit, User $user, ?Appointment $replacement = null): ?array
    {
        // Die eigene Anfrage hängt an der eigenen Gewohnheit; die Zusage zu
        // derselben Sache hängt an der fremden und kommt deshalb von außen
        // ({@see Appointment::replaces()}). Zwei Wege, ein Doppel-Zeichen.
        $appointment = $replacement ?? $habit->appointments->first();

        if ($appointment === null) {
            return null;
        }

        return [
            ...$appointment->companion($user),
            'pending' => $appointment->accepted_at === null,
            // Die eigene Uhrzeit, wenn heute eine andere gilt: „mit Berkay ·
            // statt 09:00". Ohne sie sähe die Zeile aus, als hätte sich die
            // Gewohnheit dauerhaft verschoben.
            'insteadOf' => $replacement === null ? null : $habit->scheduled_time?->format('H:i'),
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
        $tonight = $user->sleepWindowOn($today);
        $tomorrow = $user->sleepWindowOn($today->copy()->addDay());

        return [
            'bedtime' => $tonight['bedtime'],
            'wakeTime' => $tomorrow['wakeTime'],
            'alarmEnabled' => $tomorrow['alarmEnabled'],
        ];
    }

    /**
     * Die Anrede über der Übersicht, passend zur Tageszeit.
     *
     * Sie braucht die **Uhrzeit**, nicht das Datum. Vorher stand hier
     * `Carbon::today()` — Mitternacht, jeden Aufruf —, und damit hat die Seite
     * rund um die Uhr „Guten Morgen" gesagt.
     *
     * Fünf Stufen statt drei: Eine App, die den Tag einteilt, darf ihn auch
     * benennen. Die Nacht bekommt ihre eigene Anrede und keine Ermahnung —
     * wer um drei die Übersicht öffnet, hat dafür seinen Grund
     * (Designsprache §1.5).
     *
     * Die Zeitzone ist die der App (`Europe/Berlin`); einen eigenen Zeitraum
     * je Nutzer kennt das Modell nicht.
     */
    private function greeting(Carbon $now): string
    {
        return match (true) {
            $now->hour < 5 => 'Gute Nacht',
            $now->hour < 11 => 'Guten Morgen',
            $now->hour < 14 => 'Guten Mittag',
            $now->hour < 18 => 'Guten Nachmittag',
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
     * Die laufenden Serien — bis zu drei, in der Reihenfolge der Gewohnheiten.
     *
     * Gerechnet wird über **alle** aktiven Gewohnheiten, nicht nur die heute
     * vorgesehenen: Die Serie einer Mo–Fr-Gewohnheit würde sonst jeden Samstag
     * von der Übersicht verschwinden, obwohl sie ungebrochen weiterläuft.
     *
     * **Drei statt einer.** Vorher stand hier die stärkste allein, weil die
     * Karte vollflächig `primary` war und Designsprache §5.4 genau eine solche
     * Fläche zulässt: „ihre Wirkung hängt davon ab, dass sie allein bleibt".
     * Die Karten sind heute Milchglas und keine Farbfläche mehr — die Regel
     * ist damit auf anderem Weg gewahrt, und wer drei Gewohnheiten trägt,
     * sieht auch drei.
     *
     * Weggeklickte Serien fehlen: Das × auf der Karte ist keine Verneinung des
     * Fortschritts, nur seiner Anzeige ({@see HabitStreakCardController}).
     *
     * Nicht nach Länge sortiert: Die Reihenfolge ist die der Liste
     * (`position`), damit dieselbe Gewohnheit nicht heute vorn und morgen
     * hinten steht, nur weil eine andere einen Tag aufgeholt hat. Eine
     * Rangliste wäre außerdem der Vergleich, den `progress-tracking.md` für
     * den Fortschritt ausschließt.
     *
     * @param  Collection<int, Habit>  $habits
     * @return list<array{id: int, count: int, unit: string, title: string}>
     */
    private function streaks(Collection $habits, Carbon $today): array
    {
        return $habits
            // Was jemand weggeklickt hat, kommt nicht von selbst zurück — der
            // Weg zurück steht im ⋯-Menü der Gewohnheit.
            ->filter(fn (Habit $habit): bool => $habit->streak_hidden_at === null)
            ->map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'count' => $habit->currentStreak($today),
                'unit' => $habit->streakUnit(),
                'title' => $habit->title,
            ])
            ->filter(fn (array $streak): bool => $streak['count'] >= Habit::StreakMinimum)
            ->take(self::StreaksShown)
            ->values()
            ->all();
    }

    /**
     * Erledigte und geplante Tage über alle aktiven Gewohnheiten, 30 Tage weit.
     *
     * Die ruhige Zweitansicht neben der Serie: Sie springt nicht bei einem
     * einzelnen Fehltag und bleibt damit der ehrlichere Blick über dreißig
     * Tage. Dass sie den Streak ersetzt, stand nur in progress-tracking.md und
     * stammt aus den Interviews; die Umfrage hat das widerlegt
     * (umfrage-auswertung.md §6). Ohne aktive Gewohnheiten gibt es keine Zahl,
     * dann zeigt die Oberfläche den leeren Zustand statt „0 von 0".
     *
     * **Zwei Zahlen statt einer Prozentzahl.** „62 %" über alle Gewohnheiten
     * lädt zu einer Fehllesung ein: Wer täglich meditiert und das
     * Wochenend-Radfahren auslässt, liest 79 %, obwohl eine seiner beiden
     * Gewohnheiten bei null steht. Die Zahl ist nach Häufigkeit gewichtet, und
     * niemand liest sie so. „30 von 38 geplanten Tagen" behauptet dagegen gar
     * nicht, ein Durchschnitt zu sein, und sagt genau das, was gerechnet wurde.
     * Nebenbei trägt die Karte „Heute" damit nur noch eine Prozentzahl, ihre
     * eigene.
     *
     * Es ist dieselbe Form wie auf der Gewohnheiten-Seite, nur über alle statt
     * über eine. Wer die Zahl dort lesen kann, kann sie auch hier lesen.
     *
     * Die geplanten Tage werden pro Gewohnheit ermittelt, nicht pauschal mit 30
     * multipliziert: Eine Mo–Fr-Gewohnheit hat in dreißig Tagen rund
     * zweiundzwanzig. Über alle Kalendertage zu rechnen hielte sie dauerhaft
     * unter 72 %, obwohl sie lückenlos erfüllt wurde.
     *
     * Der Nenner kommt aus {@see Habit::consistencyWindow()} und schneidet am
     * Anlegedatum ab: Lally et al. 2010 nennt Konsistenz den Anteil genutzter
     * Gelegenheiten, und ein Tag vor dem Anlegen war keine.
     *
     * **Der Zähler stand einmal als `withCount` in der Hauptabfrage** und zählte
     * damit jeden Haken der letzten dreißig Tage. Das ging so lange gut, wie
     * niemand seinen Plan änderte: Danach rechnete der Nenner mit den neuen
     * Wochentagen und der Zähler mit allen — „20 von 9". Er kommt jetzt aus
     * {@see Habit::consistencyDone()}, das dieselbe Bedingung anlegt wie der
     * Nenner. Eine Abfrage kostet das nicht: Die Termine liegen für die Serie
     * ohnehin schon geladen bereit.
     *
     * @param  Collection<int, Habit>  $habits
     * @return array{done: int, scheduled: int}|null
     */
    private function consistency(Collection $habits, Carbon $today): ?array
    {
        if ($habits->isEmpty()) {
            return null;
        }

        $possible = $habits->sum(
            fn (Habit $habit): int => $habit->consistencyWindow($today)['scheduled'] ?? 0,
        );

        if ($possible < 1) {
            return null;
        }

        return [
            'done' => (int) $habits->sum(
                fn (Habit $habit): int => $habit->consistencyDone($today),
            ),
            'scheduled' => (int) $possible,
        ];
    }
}
