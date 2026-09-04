<?php

namespace App\Http\Controllers;

use App\Actions\RestoreDisplacedHabits;
use App\Enums\CourseKind;
use App\Models\Course;
use App\Models\Habit;
use App\Models\HabitCompletion;
use App\Models\Semester;
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

        $days = [];

        for ($day = $from->copy(); $day->lessThanOrEqualTo($to); $day->addDay()) {
            $days[] = $this->day($habits, $day, $month, $today, $lectureDays);
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

        $scheduled = $habits
            ->filter(fn (Habit $habit): bool => $this->existedOn($habit, $day))
            ->filter(fn (Habit $habit): bool => $habit->isScheduledOn($day))
            // Der Tag wird von oben nach unten gelesen: Morgen zuerst, Abend
            // zuletzt. Bei gleicher Stunde entscheidet die eigene Reihenfolge
            // aus der Gewohnheitsliste.
            ->sortBy(fn (Habit $habit): array => [
                $habit->dayStartMinute($day) ?? PHP_INT_MAX,
                $habit->position,
            ])
            ->values();

        // Der Rahmen des gezeigten Tages: Das Raster beginnt beim Aufstehen
        // und endet bei der Schlafenszeit. Die Stunden davor und danach sind
        // Nacht — sie zu zeichnen hieße, den Tag mit Platz zu füllen, in den
        // nichts geplant werden darf.
        $window = $request->user()->sleepWindowFor($day->dayOfWeekIso);
        // `frame()` kennt die Schlafenszeit nach Mitternacht und zählt sie als
        // Minute jenseits von 1440 weiter — sonst risse die Achse am Tagesrand.
        $timetable = Timetable::for($request->user());
        $courseBlocks = $timetable->blocksOn($day);
        $frame = (new DayPlan($scheduled, $window, $day, $courseBlocks))->frame();

        return Inertia::render('calendar-day', [
            'date' => $day->toDateString(),
            'heading' => $this->heading($day, $today),
            'isToday' => $day->isSameDay($today),
            // Nachtragen darf nur, was der Wochenstreifen auch zeigt — dieselbe
            // Grenze, die HabitCompletionController serverseitig durchsetzt.
            // Die Zukunft ist ohnehin nicht abhakbar.
            'canComplete' => $this->withinBackdatingWindow($day, $today),
            'previousDate' => $this->previousDate($habits, $day),
            'nextDate' => $day->copy()->addDay()->toDateString(),
            // Damit der Weg zurück in den Monat führt, aus dem man kam.
            'month' => $day->format('Y-m'),
            'blocks' => $scheduled->map(fn (Habit $habit): array => $this->block($habit, $day))->all(),
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
            'wakeTime' => $window['wakeTime'],
            'bedtime' => $window['bedtime'],
            'frameFrom' => $frame['from'],
            'frameTo' => $frame['to'],
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
     * @return list<array{id: int, title: string, previousTime: string|null, previousLabel: string, from: string|null, fromLabel: string|null}>
     */
    private function displaced(User $user): array
    {
        return array_values($user->habits()
            ->active()
            ->displaced()
            ->orderBy('position')
            ->get()
            ->map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'title' => $habit->title,
                'previousTime' => $habit->scheduled_time?->format('H:i'),
                'previousLabel' => $habit->scheduleLabel(),
                // Ab wann der Platz weg ist — null, wenn schon jetzt. Ein
                // Kurs im Oktober wird im September angekündigt, nicht
                // verschwiegen: So kommt die Änderung nicht über Nacht.
                'from' => $habit->isDisplaced() ? null : $habit->displaced_at?->toDateString(),
                'fromLabel' => $habit->isDisplaced() ? null : $habit->displaced_at?->settings(['locale' => 'de'])->isoFormat('D. MMMM'),
            ])
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
     * @return array{date: string, dayOfMonth: int, inMonth: bool, isToday: bool, isFuture: bool, planned: int, done: int, hasLectures: bool}
     */
    private function day(Collection $habits, Carbon $day, Carbon $month, Carbon $today, array $lectureDays): array
    {
        $scheduled = $habits
            ->filter(fn (Habit $habit): bool => $this->existedOn($habit, $day))
            ->filter(fn (Habit $habit): bool => $habit->isScheduledOn($day));

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
        ];
    }

    /**
     * Eine Gewohnheit als Block auf der Achse.
     *
     * @return array{kind: 'habit', id: int, title: string, anchor: string, anchorHour: int, scheduleType: string, startMinute: int|null, durationMinutes: int|null, exact: bool, shifted: bool, measureLabel: string|null, timeRange: string|null, behaviorType: string, smallestStep: string|null, motivation: string|null, completed: bool, graduated: bool, chainedToId: int|null}
     */
    private function block(Habit $habit, Carbon $day): array
    {
        return [
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
            // Wo der Block im Raster liegt und wie hoch er ist. Beides in
            // Minuten, damit der Browser nichts nachrechnen muss, was der
            // Server ohnehin schon weiß.
            'startMinute' => $habit->dayStartMinute($day),
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
