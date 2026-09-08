<?php

namespace App\Http\Controllers;

use App\Actions\RestoreDisplacedHabits;
use App\Enums\CourseKind;
use App\Models\Appointment;
use App\Models\Course;
use App\Models\Habit;
use App\Models\HabitCompletion;
use App\Models\Semester;
use App\Models\SleepDayOverride;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\Timetable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Der Kalender in zwei Ebenen: der Monat als Überblick, der Tag als Achse.
 *
 * Time-Blocking knüpft an bestehendes Verhalten an: 20 von 25 Befragten planen
 * ohnehin mit Kalender oder Planer, das meistgenutzte Hilfsmittel überhaupt.
 * Bis hierher gab es nur den einen Tag — man konnte sich durch ihn blättern,
 * aber nie sehen, wie die Wochen davor gelaufen sind. Der Monat beantwortet
 * die Frage, die ein einzelner Tag nicht beantworten kann: „Wie läuft das
 * gerade eigentlich?"
 *
 * Der Tag trägt jetzt ein Stundenraster. Das war lange bewusst nicht so — der
 * Situationsanker schlägt in der Umfrage die feste Zeit (3,88 zu 3,50), und
 * ein Stundenlineal schien dagegen zu arbeiten. Es bleibt deshalb
 * **Hintergrund**: Die Linien geben Orientierung, die Überschrift eines Blocks
 * ist weiter sein Anker („nach dem Frühstück"), nicht eine Uhrzeit. Was keine
 * echte Uhrzeit hat, bekommt im Raster auch keine — es liegt dort ungefähr,
 * und die Zeichnung sagt das ({@see Habit::dayStartMinute()}).
 *
 * Vergangene Tage sind neutral. Kein Rot, keine Kreuze, keine markierte Lücke:
 * bei einem Schuldwert von ø 3,92 darf ein Rückblick kein Vorwurf sein. Der
 * Monat zeigt deshalb Punkte, keine Quoten — gefüllt, was lief; offen, was
 * nicht. Beides in derselben Farbe.
 */
class CalendarController extends Controller
{
    /**
     * Wie viele Punkte ein Tag im Monat höchstens zeigt.
     *
     * Fünf, weil mehr aktive Gewohnheiten nicht vorgesehen sind
     * ({@see Habit::MaxActivePerUser}). Beendete aus der Vergangenheit können
     * darüber hinausgehen; sie zählen mit, werden aber nicht mehr gezeichnet.
     */
    private const int MaxDots = 5;

    /**
     * Der Monat als Raster aus Wochen — die Ebene, auf der man ankommt.
     */
    public function index(Request $request, RestoreDisplacedHabits $restore): Response
    {
        // Ist der Stundenplan vorbei, kommt zurück, was er verdrängt hatte —
        // hier, weil hier das Band steht, das es sonst weiter meldete. Nur
        // ein Blick, wenn überhaupt etwas geparkt ist; geholt wird nur, was
        // wirklich frei ist.
        if ($request->user()->habits()->active()->displaced()->exists()) {
            $restore->handle($request->user());
        }

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $today = Carbon::today();
        $month = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : $today->copy()->startOfMonth();

        // Die Woche beginnt am Montag. Der ISO-Wochentag trägt die ganze App
        // (1 = Montag), und ein Raster, das sonntags anfängt, stünde quer zu
        // jeder anderen Wochendarstellung darin.
        $from = $month->copy()->startOfWeek(Carbon::MONDAY);
        $to = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        // Die App-Locale ist nicht deutsch, die Oberfläche schon — dasselbe
        // Muster wie in `heading()`, aus demselben Grund in zwei Zeilen.
        $localised = $month->copy();
        $localised->locale('de');

        $habits = $this->habitsForRange($request->user(), $from, $to);

        // Der Stundenplan einmal, die Vorlesungstage in einem Durchgang: Je
        // Zelle zu fragen wären zweiundvierzig Fragen an dieselbe Auskunft.
        $timetable = Timetable::for($request->user());
        $lectureDays = $timetable->lectureDays($from, $to);

        // Und dasselbe für das Gemeinsame: einmal gefragt statt zweiundvierzig
        // Mal, in derselben Form wie die Vorlesungstage.
        $appointmentDays = Appointment::acceptedDaysBetween($request->user(), $from, $to);

        $days = [];

        for ($day = $from->copy(); $day->lessThanOrEqualTo($to); $day->addDay()) {
            $days[] = $this->day($habits, $day, $month, $today, $lectureDays, $appointmentDays);
        }

        return Inertia::render('calendar', [
            'month' => $month->format('Y-m'),
            'heading' => $localised->isoFormat('MMMM YYYY'),
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'isCurrentMonth' => $month->isSameMonth($today),
            'today' => $today->toDateString(),
            'days' => $days,
            // Der Stundenplan hat keine eigene Ansicht — Kurse werden hier
            // eingetragen und im Tag angefasst. Der Monat trägt deshalb, was
            // das Sheet oben rechts braucht, und was der Plan verdrängt hat.
            'semester' => $this->semesterProps($timetable->semester(), $today),
            'kinds' => CourseKind::options(),
            'maxCourses' => Course::MaxPerSemester,
            'courseCount' => $timetable->courseCount(),
            // Alle Kurse für die Übersicht — Ändern und Löschen laufen über
            // dieselben Sheets wie im Tag.
            'courses' => $timetable->courseRows(),
            'displaced' => $this->displaced($request->user()),
        ]);
    }

    /**
     * Ein Tag als Achse von Ankern, hinterlegt mit den Stunden.
     */
    public function show(Request $request, string $date): Response
    {
        // Das Routenmuster lässt nur Ziffern durch, aber „2026-02-30" ist
        // ziffernrichtig und trotzdem kein Tag. PHP rollt so etwas stillschweigend
        // weiter (auf den 2. März), und ein Kalender, der auf einen anderen Tag
        // führt als der Link sagt, ist schlimmer als einer, der nichts findet.
        // Der Rückweg durch dasselbe Format deckt beides auf.
        $day = Carbon::createFromFormat('!Y-m-d', $date);

        abort_unless($day->format('Y-m-d') === $date, 404);

        $today = Carbon::today();

        $habits = $request->user()
            ->habits()
            ->with([
                'completions' => fn (Relation $query) => $query->whereDate('completed_on', $day),
                // Die Kette wird beim Sortieren und Benennen jedes Blocks
                // gefragt — ohne Vorladen wäre das eine Abfrage pro Glied.
                'chainedTo.chainedTo',
                // Was an genau diesem Tag woanders liegt — von Hand verschoben
                // oder für eine Verabredung freigeräumt.
                'dayShifts' => fn (Relation $query) => $query->whereDate('shifted_on', $day),
                'chainedTo.dayShifts' => fn (Relation $query) => $query->whereDate('shifted_on', $day),
                'chainedTo.chainedTo.dayShifts' => fn (Relation $query) => $query->whereDate('shifted_on', $day),
            ])
            ->orderBy('position')
            ->get();

        // Für die Anker-Stunden am Tagesrand („nach dem Aufstehen") fragt die
        // Gewohnheit den Schlafplan ihres Nutzers — die Beziehung wird hier
        // gesetzt, damit alle denselben geladenen Nutzer teilen.
        $habits->each(fn (Habit $habit) => $habit->setRelation('user', $request->user()));

        // Was heute mit jemandem ansteht — vor der Liste, weil eine zugesagte
        // Verabredung eine eigene Zeile ersetzen kann.
        $appointments = Appointment::acceptedOn($request->user(), $day);

        // Dieselbe Sache steht einmal im Tag, nicht zweimal: Wer zum
        // Frühstück zugesagt hat und selbst Frühstück im Plan hat, sieht
        // **seinen** Block — mit dem Doppel-Zeichen und an diesem Tag auf der
        // gemeinsamen Uhrzeit ({@see AppointmentController::update()} schreibt
        // dafür dieselbe Tagesausnahme wie das Platzmachen). Ein zweiter Block
        // daneben wäre derselbe Morgen zweimal.
        $replaced = Appointment::replacementsIn($appointments, $request->user(), $habits);

        $scheduled = $habits
            ->filter(fn (Habit $habit): bool => $this->existedOn($habit, $day))
            ->filter(fn (Habit $habit): bool => $habit->isScheduledOn($day))
            // „Nach der Vorlesung" ohne Vorlesung: An so einem Tag gibt es den
            // Auslöser nicht, also auch die Gewohnheit nicht. Der Parkvermerk
            // gehört hier ausdrücklich nicht dazu — was verdrängt wurde, soll
            // im Tag sichtbar bleiben, nur ohne Stelle.
            ->filter(fn (Habit $habit): bool => $habit->hasTriggerOn($day))
            ->values();

        // Der Rahmen des gezeigten Tages: Das Raster beginnt beim Aufstehen
        // und endet bei der Schlafenszeit. Die Stunden davor und danach sind
        // Nacht — sie zu zeichnen hieße, den Tag mit Platz zu füllen, in den
        // nichts geplant werden darf.
        $window = $request->user()->sleepWindowOn($day);
        // `frame()` kennt die Schlafenszeit nach Mitternacht und zählt sie als
        // Minute jenseits von 1440 weiter — sonst risse die Achse am Tagesrand.
        $timetable = Timetable::for($request->user());
        $courseBlocks = $timetable->blocksOn($day);

        // Dieselbe Zeile trägt zwei Fälle: Bei einer fremden Gewohnheit gibt
        // es nichts Eigenes, an das sich etwas hängen ließe — sie braucht
        // einen eigenen Block. Bei der eigenen steht der Block längst da und
        // es fehlt nur das Zeichen, dass jemand mitmacht.
        $companions = $this->companions($appointments, $request->user())
            // Und die Zusagen zu einer eigenen Sache: Sie hängen an einer
            // fremden Gewohnheit, gehören im Tag aber an die eigene Zeile.
            + array_map(
                fn (Appointment $appointment): array => $appointment->companion($request->user()),
                $replaced,
            );

        $appointmentBlocks = $this->appointmentBlocks(
            $appointments->reject(
                fn (Appointment $appointment): bool => in_array($appointment, $replaced, strict: true),
            ),
            $request->user(),
        );

        // Die Verabredung belegt den Tag wie ein Kurs: Ohne sie im Plan
        // rutschte eine eigene Situation genau dorthin, wo gleich gemeinsam
        // gelaufen wird. Die Form ist dieselbe, durch die schon der
        // Stundenplan kommt ({@see DayPlan::__construct()}).
        $plan = new DayPlan($scheduled, $window, $day, [
            ...$courseBlocks,
            ...$this->appointmentSpans($appointmentBlocks),
        ]);
        $frame = $plan->frame();

        // Der Tag wird von oben nach unten gelesen: Morgen zuerst, Abend
        // zuletzt. Gefragt wird der Tagesplan und nicht die Gewohnheit: Eine
        // Situation rutscht dort um das Feste herum, und das Raster muss sie
        // zeigen, wo die Rechnung sie hinlegt.
        $scheduled = $scheduled
            ->sortBy(fn (Habit $habit): array => [
                $plan->startOf($habit) ?? PHP_INT_MAX,
                $habit->position,
            ])
            ->values();

        return Inertia::render('calendar-day', [
            'date' => $day->toDateString(),
            'heading' => $this->heading($day, $today),
            'isToday' => $day->isSameDay($today),
            // Nachtragen darf nur, was der Wochenstreifen auch zeigt — dieselbe
            // Grenze, die HabitCompletionController serverseitig durchsetzt.
            // Die Zukunft ist ohnehin nicht abhakbar.
            'canComplete' => $this->withinBackdatingWindow($day, $today),
            // `canComplete` ist auch an einem künftigen Tag falsch — dort aber
            // aus einem anderen Grund. Ohne diese Unterscheidung stand unter
            // dem Morgen der Satz „Nachtragen geht für die letzten sieben
            // Tage", als wäre eine Frist verstrichen, die noch gar nicht läuft.
            'isPast' => $day->lessThan($today),
            'previousDate' => $this->previousDate($habits, $day),
            'nextDate' => $day->copy()->addDay()->toDateString(),
            // Damit der Weg zurück in den Monat führt, aus dem man kam.
            'month' => $day->format('Y-m'),
            'blocks' => $scheduled
                ->map(fn (Habit $habit): array => $this->block($habit, $day, $plan, $companions[$habit->id] ?? null, $timetable))
                ->all(),
            // Fremde Gewohnheiten, für heute zugesagt. Eigene Liste wie bei den
            // Kursen und aus demselben Grund: Sie lassen sich nicht abhaken und
            // nicht ziehen, und jede Stelle, die einen Block anfasst, müsste
            // sich sonst gegen eine Art verteidigen, die sie nicht behandelt.
            'appointmentBlocks' => $appointmentBlocks,
            // Kurse liegen auf derselben Achse, sind aber keine Gewohnheiten:
            // Sie werden nicht abgehakt, nicht gezogen und nicht angepasst.
            // Deshalb eine eigene Liste — zehn nullbare Felder an `blocks`
            // hätten jede Stelle, die einen Block anfasst, gegen eine Art
            // verteidigen müssen, die sie nicht behandeln kann.
            // Der Block trägt den ganzen Kurs: Hier liegt er, hier fasst man
            // ihn an — antippen öffnet das Sheet zum Ändern.
            'courseBlocks' => $timetable->coursesOn($day),
            'kinds' => CourseKind::options(),
            'semester' => $this->semesterProps($timetable->semester(), $today),
            // Nur was noch kommt, lässt sich verlegen: Ein vergangener Tag ist
            // vorbei, und ihn umzuräumen änderte nichts mehr an ihm.
            'canShift' => $day->greaterThanOrEqualTo($today),
            // Ein Vorschlag der KI, gestrichelt ins Raster gelegt — wenn die
            // Adresse einen nennt und er an diesem Wochentag gilt.
            'proposal' => $this->proposal($request, $habits, $day),
            'wakeTime' => $window['wakeTime'],
            'bedtime' => $window['bedtime'],
            'frameFrom' => $frame['from'],
            'frameTo' => $frame['to'],
            // Hat dieser Tag einen eigenen Rahmen statt den seines Wochentags?
            // Die Marke zeigt das, und nur dann gibt es einen Rückweg.
            'frameOverridden' => $request->user()->sleepDayOverrides
                ->contains(fn (SleepDayOverride $override): bool => $override->on_date->isSameDay($day)),
        ]);
    }

    /**
     * Der Zeitraum des Semesters für das Sheet — null ohne Semester.
     *
     * @return array{title: string, startsOn: string, endsOn: string, rangeLabel: string, isCurrent: bool, startsInFuture: bool, startsOnLabel: string}|null
     */
    private function semesterProps(?Semester $semester, Carbon $today): ?array
    {
        if ($semester === null) {
            return null;
        }

        return [
            'title' => $semester->title,
            'startsOn' => $semester->starts_on->toDateString(),
            'endsOn' => $semester->ends_on->toDateString(),
            'rangeLabel' => $semester->rangeLabel(),
            // Ein Semester, das nicht läuft, bleibt bearbeitbar, sagt aber,
            // dass es nichts blockiert — und ab wann wieder.
            'isCurrent' => $semester->covers($today),
            'startsInFuture' => $semester->starts_on->toDateString() > $today->toDateString(),
            'startsOnLabel' => $semester->starts_on->settings(['locale' => 'de'])->isoFormat('D. MMMM YYYY'),
        ];
    }

    /**
     * Was der Stundenplan verdrängt hat — die Liste, um die es beim
     * Semesterwechsel eigentlich geht.
     *
     * @return list<array{id: int, title: string, previousTime: string|null, previousLabel: string, from: string|null, fromLabel: string|null, conflictDate: string|null, conflictLabel: string|null}>
     */
    private function displaced(User $user): array
    {
        $timetable = Timetable::for($user);

        return array_values($user->habits()
            ->active()
            ->displaced()
            ->orderBy('position')
            ->get()
            ->map(function (Habit $habit) use ($user, $timetable): array {
                $habit->setRelation('user', $user);
                $tag = $this->firstConflictDate($habit, $timetable);

                return [
                    'id' => $habit->id,
                    'title' => $habit->title,
                    'previousTime' => $habit->scheduled_time?->format('H:i'),
                    'previousLabel' => $habit->scheduleLabel(),
                    // Ab wann der Platz weg ist — null, wenn schon jetzt. Ein
                    // Kurs im Oktober wird im September angekündigt, nicht
                    // verschwiegen: So kommt die Änderung nicht über Nacht.
                    'from' => $habit->isDisplaced() ? null : $habit->displaced_at?->toDateString(),
                    'fromLabel' => $habit->isDisplaced() ? null : $habit->displaced_at?->settings(['locale' => 'de'])->isoFormat('D. MMMM'),
                    // Der Tag, an dem der Kurs den Platz wirklich nimmt — nicht der
                    // Semesterbeginn. Liegt „Statistik" mittwochs und beginnt das
                    // Semester an einem Montag, zeigte der Montag einen freien Tag
                    // und keine Ursache. Ein Kurs rückt nicht; wer die Gewohnheit
                    // selbst umlegen will, muss genau dorthin.
                    'conflictDate' => $tag,
                    'conflictLabel' => $tag === null
                        ? null
                        : Carbon::parse($tag)->settings(['locale' => 'de'])->isoFormat('dddd, D. MMMM'),
                ];
            })
            ->all());
    }

    /**
     * Die Gewohnheiten eines Zeitraums samt der Haken, die darin liegen.
     *
     * Eine Abfrage für den ganzen Monat statt einer je Tag: Bei 42 Zellen wäre
     * das sonst der Unterschied zwischen zwei Abfragen und vierundachtzig.
     *
     * @return Collection<int, Habit>
     */
    private function habitsForRange(User $user, Carbon $from, Carbon $to): Collection
    {
        $habits = $user->habits()
            ->with([
                'completions' => fn (Relation $query) => $query
                    ->whereBetween('completed_on', [$from->toDateString(), $to->toDateString()]),
                'chainedTo.chainedTo',
            ])
            ->orderBy('position')
            ->get();

        $habits->each(fn (Habit $habit) => $habit->setRelation('user', $user));

        return $habits;
    }

    /**
     * Eine Zelle im Monatsraster.
     *
     * `planned` und `done` sind Zahlen, keine Quote: Der Monat zeigt Punkte,
     * und ein Prozentwert über einem einzelnen Tag wäre eine Bewertung.
     *
     * @param  Collection<int, Habit>  $habits
     * @param  array<string, true>  $lectureDays  Die Tage, an denen etwas an der Uni läuft
     * @param  array<string, true>  $appointmentDays  Die Tage, an denen etwas mit jemandem ansteht
     * @return array{date: string, dayOfMonth: int, inMonth: bool, isToday: bool, isFuture: bool, planned: int, done: int, hasLectures: bool, hasAppointment: bool}
     */
    private function day(Collection $habits, Carbon $day, Carbon $month, Carbon $today, array $lectureDays, array $appointmentDays): array
    {
        $scheduled = $habits
            ->filter(fn (Habit $habit): bool => $this->existedOn($habit, $day))
            ->filter(fn (Habit $habit): bool => $habit->isDueOn($day));

        $done = $scheduled
            ->filter(fn (Habit $habit): bool => $habit->completions
                ->contains(fn (HabitCompletion $completion): bool => $completion->completed_on->isSameDay($day)))
            ->count();

        return [
            'date' => $day->toDateString(),
            'dayOfMonth' => $day->day,
            'inMonth' => $day->isSameMonth($month),
            'isToday' => $day->isSameDay($today),
            // Ein künftiger Tag ist nicht „offen", er ist noch nicht dran —
            // die Oberfläche zeichnet ihn deshalb leiser als einen vergangenen.
            'isFuture' => $day->greaterThan($today),
            'planned' => min($scheduled->count(), self::MaxDots),
            'done' => min($done, self::MaxDots),
            // Nicht wie viel, nur ob: Der Monat sagt, dass dieser Tag an der
            // Uni stattfindet, nicht wie voll er ist. Wie voll, steht im Tag.
            'hasLectures' => isset($lectureDays[$day->toDateString()]),
            // Die Verabredung zählt bewusst **nicht** in `planned` mit: Wer
            // gefragt wurde, führt die Gewohnheit nicht und kann sie deshalb
            // nie abhaken — der Tag sähe für immer unerledigt aus. Das wäre
            // genau der Vorwurf, den ein Rückblick bei ø 3,92 Schuldgefühl
            // nicht erheben darf. Also wie beim Stundenplan: nicht wie viel,
            // nur ob.
            'hasAppointment' => isset($appointmentDays[$day->toDateString()]),
        ];
    }

    /**
     * Der Vorschlag aus `?suggestion=` als Ghost — oder null.
     *
     * Der Weg aus dem Sheet „Neue Plätze" in den Tag: Dort steht der Vorschlag
     * gestrichelt neben den Kursen, um die es geht, und lässt sich übernehmen
     * oder verwerfen. Nur eigene, noch nicht übernommene Vorschläge, und nur
     * an einem Tag, an dem sie überhaupt gälten — sonst zeigte der Ghost etwas,
     * das an diesem Datum nie läge.
     *
     * @param  Collection<int, Habit>  $habits
     * @return array{suggestionId: int, habitId: int, title: string, time: string, days: list<int>, label: string, reason: string, block: array<string, mixed>}|null
     */
    private function proposal(Request $request, Collection $habits, Carbon $day): ?array
    {
        $id = $request->integer('suggestion');

        if ($id < 1) {
            return null;
        }

        $suggestion = $request->user()->aiSuggestions()->notTaken()->find($id);
        $habit = $suggestion === null ? null : $habits->firstWhere('id', $suggestion->habit_id);
        $time = $suggestion?->payload['time'] ?? null;
        $days = $suggestion?->payload['days'] ?? null;

        if ($habit === null || ! is_string($time) || ! is_array($days) || ! in_array($day->dayOfWeekIso, $days, strict: true)) {
            return null;
        }

        $start = DayPlan::toMinutes($time);
        $minutes = $habit->durationMinutes() ?? DayPlan::AssumedMinutes;

        return [
            'suggestionId' => $suggestion->id,
            'habitId' => $habit->id,
            'title' => $habit->title,
            'time' => $time,
            'days' => array_values(array_map(intval(...), $days)),
            'label' => $suggestion->label,
            'reason' => (string) ($suggestion->payload['reason'] ?? ''),
            'block' => [
                ...$this->block($habit, $day),
                'anchor' => $suggestion->label,
                'anchorHour' => intdiv($start, 60),
                'startMinute' => $start,
                'exact' => true,
                'shifted' => false,
                'completed' => false,
                'timeRange' => $time.' – '.DayPlan::toTime($start + $minutes),
            ],
        ];
    }

    /**
     * Die Begleitung je eigener Gewohnheit — für das Doppel-Zeichen an der Zeile.
     *
     * Nur die Verabredungen, bei denen die Gewohnheit einem selbst gehört.
     * Bei den anderen gibt es keine eigene Zeile, an die sich etwas hängen
     * ließe; die werden zu einem Block ({@see appointmentBlocks()}).
     *
     * @param  Collection<int, Appointment>  $appointments
     * @return array<int, array{name: string, initial: string}>
     */
    private function companions(Collection $appointments, User $user): array
    {
        return $appointments
            ->filter(fn (Appointment $appointment): bool => $appointment->requester_id === $user->id)
            ->mapWithKeys(fn (Appointment $appointment): array => [
                $appointment->habit_id => $appointment->companion($user),
            ])
            ->all();
    }

    /**
     * Die fremden Gewohnheiten, die man für diesen Tag zugesagt hat.
     *
     * Sie liegen im Raster wie eine eigene, gehören aber jemand anderem: kein
     * Haken, kein Ziehen, kein Anpassen. Deshalb `kind`, wie schon bei den
     * Kursen — das Raster fragt danach, bevor es etwas anbietet.
     *
     * Die Stelle im Tag kommt aus der fremden Gewohnheit, nicht aus einer
     * eigenen Rechnung: Die Verabredung erfindet keine Zeit, sie teilt einen
     * Anker (community_feature3.md §4). Hat der Anker keine Uhr, liegt der
     * Block dort ungefähr — genau wie eine eigene Situation, und `exact` sagt
     * das.
     *
     * @param  Collection<int, Appointment>  $appointments
     * @return list<array{kind: string, id: int, habitId: int, title: string, anchor: string, name: string, initial: string, startMinute: int, durationMinutes: int|null, exact: bool, timeRange: string|null, behaviorType: string, completed: bool, canComplete: bool}>
     */
    private function appointmentBlocks(Collection $appointments, User $user): array
    {
        return $appointments
            ->filter(fn (Appointment $appointment): bool => $appointment->requester_id !== $user->id)
            ->map(function (Appointment $appointment) use ($user): array {
                $habit = $appointment->habit;

                return [
                    'kind' => 'appointment',
                    'id' => $appointment->id,
                    'habitId' => $habit->id,
                    'title' => $habit->title,
                    // Nur der Moment, nicht die Wiederholung: Die Wochentage
                    // gehören der Gewohnheit der anderen Person, und die
                    // Verabredung gilt für diesen einen Tag.
                    // Die festgehaltene Uhrzeit der Verabredung, nicht die
                    // Stelle, die diese Gewohnheit im eigenen Tag hätte: Der
                    // Anker der anderen Person („nach dem Aufstehen") rechnete
                    // sich hier aus dem **eigenen** Schlafplan aus, und
                    // derselbe Morgen stand in zwei Kalendern an zwei Stellen
                    // ({@see Appointment::startMinute()}).
                    'anchor' => $appointment->timeLabel(),
                    ...$appointment->companion($user),
                    'startMinute' => $appointment->startMinute(),
                    'durationMinutes' => $habit->durationMinutes(),
                    // Eine Verabredung hat immer eine Uhrzeit — seit sie eine
                    // eigene trägt, ist sie nie mehr eine Näherung.
                    'exact' => true,
                    'timeRange' => sprintf(
                        '%s – %s',
                        DayPlan::toTime($appointment->startMinute()),
                        DayPlan::toTime($appointment->startMinute()
                            + ($habit->durationMinutes() ?? DayPlan::AssumedMinutes)),
                    ),
                    'behaviorType' => $habit->behavior_type->value,
                    // Entscheidet das Zeichen: Die Vorlage weiß, worum es
                    // geht, die Verhaltensrichtung ordnet nur fachlich ein.
                    'templateKey' => $habit->template_key,
                    // Der eigene Haken an der Zusage. Er hängt an der
                    // Verabredung, nicht an der fremden Gewohnheit — die
                    // gehört der anderen Person.
                    'completed' => $appointment->completed_at !== null,
                    'canComplete' => $appointment->isCompletableBy($user),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Dieselben Blöcke, wie der Tagesplan sie liest.
     *
     * Die Kennung ist `0` und nicht die der Verabredung: Positive Zahlen sind
     * Gewohnheiten, negative sind Kurse ({@see Timetable::isCourseBlock()}).
     * Dieselbe Null benutzt {@see AppointmentFit::options()} schon, wenn sie
     * eine Verabredung in einen Plan legt.
     *
     * @param  list<array{title: string, startMinute: int, durationMinutes: int|null, ...}>  $blocks
     * @return list<array{id: int, title: string, from: int, to: int}>
     */
    private function appointmentSpans(array $blocks): array
    {
        return array_map(fn (array $block): array => [
            'id' => 0,
            'title' => $block['title'],
            'from' => $block['startMinute'],
            'to' => $block['startMinute'] + ($block['durationMinutes'] ?? DayPlan::AssumedMinutes),
        ], $blocks);
    }

    /**
     * Eine Gewohnheit als Block auf der Achse.
     *
     * @param  array{name: string, initial: string}|null  $companion  Wer heute mitmacht
     * @param  Timetable|null  $timetable  Für den ersten Tag, an dem ein Kurs den alten Platz nimmt
     * @return array{kind: 'habit', companion: array{name: string, initial: string}|null, id: int, title: string, anchor: string, anchorHour: int, scheduleType: string, conflictDate: string|null, startMinute: int|null, durationMinutes: int|null, exact: bool, shifted: bool, measureLabel: string|null, timeRange: string|null, behaviorType: string, templateKey: string|null, smallestStep: string|null, motivation: string|null, completed: bool, graduated: bool, chainedToId: int|null}
     */
    private function block(Habit $habit, Carbon $day, ?DayPlan $plan = null, ?array $companion = null, ?Timetable $timetable = null): array
    {
        return [
            // Wer heute mitmacht — dasselbe Feld, das die Übersicht liefert,
            // und derselbe Doppel-Kreis. Ohne es zeigte der Kalender an
            // demselben Tag weniger als die Zeile auf der Startseite.
            'companion' => $companion,
            // Sagt dem Raster, welcher Art dieser Block ist — daneben liegen
            // Kurse, und die lassen sich weder abhaken noch ziehen.
            'kind' => 'habit',
            'id' => $habit->id,
            'title' => $habit->title,
            // Mit dem Tag: An einem verschobenen Tag gilt die Ausnahme, und
            // sie sagt dazu, dass sie nur für ihn gilt.
            'anchor' => $habit->scheduleLabel($day),
            // Reist mit, damit ein Vorschlag der KI sich einsortieren kann,
            // bevor er übernommen wurde.
            'anchorHour' => $habit->dayAnchorHour($day) ?? Habit::UnknownAnchorHour,
            // Welche Planungsart der Zug antasten würde. Der Anker allein
            // verriete es nicht: Ein für heute verschobener Moment sieht aus
            // wie eine feste Uhrzeit.
            'scheduleType' => $habit->schedule_type->value,
            // Der erste Tag, an dem der Kurs den alten Platz wirklich
            // wegnimmt. Nur für Verdrängte, und nur als Weg dorthin: Wer
            // selbst umlegen will, soll das dort tun, wo er den Kurs und die
            // Lücken daneben sieht — nicht an einem beliebigen Tag.
            'conflictDate' => $timetable === null
                ? null
                : $this->firstConflictDate($habit, $timetable),
            // Wo der Block im Raster liegt und wie hoch er ist. Beides in
            // Minuten, damit der Browser nichts nachrechnen muss, was der
            // Server ohnehin schon weiß.
            'startMinute' => $plan?->startOf($habit) ?? $habit->dayStartMinute($day),
            'durationMinutes' => $habit->durationMinutes(),
            // Ob die Stelle eine Uhrzeit ist oder eine Näherung. Der Kalender
            // zeichnet beides verschieden: Was keine Uhr hat, bekommt auch
            // keine — es liegt dort ungefähr, und das darf man sehen.
            'exact' => $habit->startsAt($day) !== null,
            // Nur für diesen einen Tag von Hand hierher gelegt. Der Block sagt
            // das, und im Block-Sheet steht der Weg zurück.
            'shifted' => $habit->shiftedTimeOn($day) !== null,
            // Der Umfang und, wo er eine Dauer ist, die belegte Spanne.
            // „17:00 – 17:20" sagt zusätzlich, wann der Platz wieder frei
            // ist — die Größe, an der eine angehängte Gewohnheit beginnt.
            'measureLabel' => $habit->measureLabel(),
            'timeRange' => $habit->timeRangeLabel($day),
            'behaviorType' => $habit->behavior_type->value,
            // Entscheidet das Zeichen: Die Vorlage weiß, worum es
            // geht, die Verhaltensrichtung ordnet nur fachlich ein.
            'templateKey' => $habit->template_key,
            'smallestStep' => $habit->smallest_step,
            // Für die Starthilfe, die es jetzt auch im Kalender gibt.
            'motivation' => $habit->motivation,
            'completed' => $habit->completions->isNotEmpty(),
            'graduated' => $habit->graduated_at !== null,
            // Hängt der Block an dem darüber? Dann zieht die Oberfläche einen
            // Steg dazwischen, statt zwei zusammenhängende Blöcke wie zwei
            // unabhängige nebeneinanderzustellen.
            'chainedToId' => $habit->chained_to_habit_id,
        ];
    }

    /**
     * Der erste Tag, an dem diese Gewohnheit ihren Platz an einen Kurs verliert.
     *
     * Ziel eines Weges, kein Datum zum Anzeigen: Von der Liste „ohne festen
     * Platz" führt ein Knopf dorthin, weil man einen neuen Platz nur da
     * sinnvoll wählt, wo der Kurs steht, der den alten genommen hat.
     *
     * Gesucht wird die echte Überschneidung, nicht der nächste Tag, an dem die
     * Gewohnheit lief: Wer montags, mittwochs und freitags joggt und dessen
     * Statistik mittwochs liegt, hat am Montag keinen Konflikt — ihn dorthin
     * zu schicken zeigte ihm einen freien Tag und keine Ursache.
     *
     * Die alte Uhrzeit ist die Erinnerung daran, wo sie lag ({@see
     * DisplaceHabits}); ohne sie gibt es keine Spanne, die sich vergleichen
     * ließe. Zwei Wochen weit — ein wöchentlicher Kurs fällt in diese Spanne,
     * und was danach käme, wäre kein Konflikt mehr, sondern ein anderer Plan.
     */
    private function firstConflictDate(Habit $habit, Timetable $timetable): ?string
    {
        $start = $habit->scheduled_time;

        if ($habit->displaced_at === null || $start === null) {
            return null;
        }

        $from = $start->hour * 60 + $start->minute;
        $to = $from + ($habit->durationMinutes() ?? DayPlan::AssumedMinutes);
        $days = $habit->activeWeekdays() ?: Habit::EveryDay;

        // Gesucht wird ab dem Tag, an dem der Platz weg ist — nicht ab heute.
        // Ein Kurs, der mit der Vorlesungszeit beginnt, liegt Wochen entfernt;
        // von heute aus zwei Wochen weit zu suchen fand ihn nie und schickte
        // niemanden irgendwohin.
        $day = Carbon::parse($habit->displaced_at->toDateString())->max(Carbon::today());

        for ($step = 0; $step < 14; $step++, $day->addDay()) {
            if (! in_array($day->dayOfWeekIso, $days, strict: true)) {
                continue;
            }

            foreach ($timetable->blocksOn($day) as $block) {
                if ($from < $block['to'] && $to > $block['from']) {
                    return $day->toDateString();
                }
            }
        }

        return null;
    }

    /**
     * Gab es die Gewohnheit an diesem Tag überhaupt schon — und noch?
     *
     * Beendete Gewohnheiten bleiben in ihrer Vergangenheit stehen: Der Tag, an
     * dem sie lief, hat stattgefunden, und ihn nachträglich zu leeren wäre eine
     * Geschichtsfälschung. Vor dem Anlegen taucht sie dagegen nicht auf, sonst
     * entstünden rückwirkend Lücken, die niemand versäumt hat.
     */
    private function existedOn(Habit $habit, Carbon $date): bool
    {
        if ($habit->created_at?->startOfDay()->greaterThan($date)) {
            return false;
        }

        return $habit->graduated_at === null
            || $habit->graduated_at->startOfDay()->greaterThanOrEqualTo($date);
    }

    private function withinBackdatingWindow(Carbon $date, Carbon $today): bool
    {
        if ($date->greaterThan($today)) {
            return false;
        }

        return $date->greaterThanOrEqualTo(
            $today->copy()->subDays(Habit::WeekOverviewDays - 1),
        );
    }

    /**
     * Der Tag davor — aber nicht weiter zurück als bis zur ersten Gewohnheit.
     *
     * Davor gäbe es nichts zu sehen, und ein Pfeil, der in leere Tage führt,
     * verspricht etwas, das er nicht hält.
     *
     * @param  Collection<int, Habit>  $habits
     */
    private function previousDate(Collection $habits, Carbon $date): ?string
    {
        $first = $habits->min('created_at');

        if ($first === null) {
            return null;
        }

        $previous = $date->copy()->subDay();

        return $previous->greaterThanOrEqualTo(Carbon::parse($first)->startOfDay())
            ? $previous->toDateString()
            : null;
    }

    /**
     * Die Datumszeile im Kopf: „Heute · Dienstag, 21. Juli".
     *
     * Die App-Locale ist nicht deutsch, die Oberfläche schon — dasselbe Muster
     * wie im DashboardController.
     */
    private function heading(Carbon $date, Carbon $today): string
    {
        $localised = $date->copy();
        $localised->locale('de');

        $prefix = match (true) {
            $date->isSameDay($today) => 'Heute · ',
            $date->isSameDay($today->copy()->subDay()) => 'Gestern · ',
            $date->isSameDay($today->copy()->addDay()) => 'Morgen · ',
            default => '',
        };

        return $prefix.$localised->isoFormat('dddd, D. MMMM');
    }
}
