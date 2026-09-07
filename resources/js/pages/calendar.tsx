import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, GraduationCap } from 'lucide-react';
import { useState } from 'react';
import { AiMascot } from '@/components/ai-mascot';
import { CourseCancellationSheet } from '@/components/course-cancellation-sheet';
import { CourseDetailSheet } from '@/components/course-detail-sheet';
import { CourseSheet } from '@/components/course-sheet';
import { CoursesSheet } from '@/components/courses-sheet';
import { MonthGrid, MonthLegend } from '@/components/month-grid';
import { NewPlacesSheet } from '@/components/new-places-sheet';
import { SemesterSheet } from '@/components/semester-sheet';
import { Card, CardContent } from '@/components/ui/card';
import { OUTLINE_BUTTON, QUIET_LINK } from '@/lib/interaction';
import { calendar } from '@/routes';
import { day as calendarDay } from '@/routes/calendar';
import type {
    CourseKindOption,
    CourseRow,
    DisplacedHabit,
    MonthDay,
    SemesterPlan,
} from '@/types';

interface CalendarProps {
    /** Der gezeigte Monat als „YYYY-MM". */
    month: string;
    /** Die Zeile im Kopf, fertig formatiert: „September 2026". */
    heading: string;
    previousMonth: string;
    nextMonth: string;
    isCurrentMonth: boolean;
    /** Heute als „YYYY-MM-DD" — das Ziel des Sprungs zurück. */
    today: string;
    /** Volle Wochen, Montag bis Sonntag — auch über die Monatskante hinaus. */
    days: MonthDay[];
    /** Der Zeitraum des Semesters — null, solange keiner eingetragen ist. */
    semester: SemesterPlan | null;
    kinds: CourseKindOption[];
    maxCourses: number;
    courseCount: number;
    /** Alle Kurse des Semesters — für die Übersicht hinter dem Knopf. */
    courses: CourseRow[];
    /** Was der Stundenplan verdrängt hat — leer, solange nichts wartet. */
    displaced: DisplacedHabit[];
}

const NAV_BUTTON =
    'flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full text-primary transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.94]';

/**
 * Der Monat — die Ebene, auf der man ankommt.
 *
 * Zwei Ebenen, nicht drei: Der Monat zeigt, ob ein Tag voll war, der Tag
 * zeigt, was darin liegt — Gewohnheiten und Kurse auf einer Achse. Der
 * Stundenplan hat keine eigene Ansicht: Kurse werden hier oben rechts
 * eingetragen und im Tag angefasst, dort, wo sie liegen. Eine Wochen- oder
 * Semesterseite daneben zeigte dasselbe noch einmal, nur weniger.
 *
 * Die Zelle ist der Weg in den Tag.
 */
export default function Calendar({
    heading,
    previousMonth,
    nextMonth,
    isCurrentMonth,
    today,
    days,
    semester,
    kinds,
    maxCourses,
    courseCount,
    courses,
    displaced,
}: CalendarProps) {
    const [semesterOpen, setSemesterOpen] = useState(false);
    const [coursesOpen, setCoursesOpen] = useState(false);
    const [courseOpen, setCourseOpen] = useState(false);
    const [placesOpen, setPlacesOpen] = useState(false);
    /** Der Kurs aus der Übersicht: aufgeschlagen, im Formular, im Ausfall. */
    const [openedCourse, setOpenedCourse] = useState<CourseRow | null>(null);
    const [editingCourse, setEditingCourse] = useState<CourseRow | null>(null);
    const [cancellingCourse, setCancellingCourse] = useState<CourseRow | null>(
        null,
    );

    // Steht schon etwas ohne Platz da — oder kündigt sich das erst an? Beides
    // steht im Band, aber nicht mit demselben Satz: Was kommt, ist eine
    // Ankündigung, keine Bitte um eine Entscheidung.
    const upcoming = displaced.filter((habit) => habit.from !== null);
    const onlyUpcoming =
        displaced.length > 0 && upcoming.length === displaced.length;
    const firstFrom = upcoming[0]?.fromLabel ?? null;

    /**
     * Wie der Monat bisher gelaufen ist, in Tagen.
     *
     * Nur Tage dieses Monats und nur bis heute. Was noch kommt, ist nicht
     * offen, sondern nicht dran; künftige Tage mitzuzählen machte aus jedem
     * Monatsanfang einen Rückstand.
     */
    const monthDone = days.reduce(
        (sum, day) =>
            day.inMonth && !day.isFuture
                ? {
                      done: sum.done + day.done,
                      planned: sum.planned + day.planned,
                  }
                : sum,
        { done: 0, planned: 0 },
    );

    return (
        <>
            <Head title="Kalender" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
                {/* Mittig und symmetrisch: Pfeil, Monat, Pfeil. Der
                    Stundenplan saß hier als drittes Zeichen rechts und zog die
                    Zeile aus der Mitte. Er steht jetzt unter dem Raster, wo er
                    ein Wort tragen kann, statt geraten zu werden. */}
                <header className="flex items-center justify-center gap-2">
                    {/* Pfeile sind Links, kein Client-State: der Monat steht in
                        der URL und übersteht damit ein Neuladen. */}
                    <Link
                        href={calendar({ query: { month: previousMonth } })}
                        aria-label="Ein Monat zurück"
                        className={NAV_BUTTON}
                    >
                        <ChevronLeft className="size-5" aria-hidden="true" />
                    </Link>

                    <div className="min-w-0 flex-1 text-center">
                        <h1 className="text-[clamp(1.125rem,4vw,1.75rem)] leading-tight font-bold text-primary">
                            {heading}
                        </h1>

                        {/* Der Monat sagte bisher nicht, wie er gelaufen ist.
                            Die Punkte zeigen es je Tag, aber niemand zählt
                            fünfunddreißig Zellen zusammen. Dieselbe Sprache
                            wie auf der Übersicht und der Gewohnheiten-Seite:
                            Tage, keine Prozentzahl. */}
                        {monthDone.planned > 0 && (
                            <p className="mt-1 text-xs text-muted-foreground">
                                <span className="font-semibold text-foreground tabular-nums">
                                    {monthDone.done} von {monthDone.planned}
                                </span>{' '}
                                erledigt
                            </p>
                        )}
                    </div>

                    <Link
                        href={calendar({ query: { month: nextMonth } })}
                        aria-label="Ein Monat vor"
                        className={NAV_BUTTON}
                    >
                        <ChevronRight className="size-5" aria-hidden="true" />
                    </Link>
                </header>

                {!isCurrentMonth && (
                    <div className="flex justify-center">
                        <Link
                            href={calendar()}
                            className={`${QUIET_LINK} text-sm`}
                        >
                            Zurück zu diesem Monat
                        </Link>
                    </div>
                )}

                {/* Was der Stundenplan verdrängt hat. Steht über dem Raster,
                    weil es eine Entscheidung braucht. Kein Warnton: Nichts ist
                    verloren, es wartet nur. */}
                {displaced.length > 0 && (
                    <div
                        role="status"
                        className="flex flex-col gap-2 rounded-xl border border-primary/25 bg-accent px-4 py-3"
                    >
                        <p className="text-sm text-foreground">
                            {onlyUpcoming
                                ? `${displaced.length === 1 ? 'Eine Gewohnheit verliert' : `${displaced.length} Gewohnheiten verlieren`} ab dem ${firstFrom} durch deinen Stundenplan ihren Platz. Bis dahin läuft alles wie bisher. Ein neuer Platz lässt sich schon jetzt finden.`
                                : `${displaced.length === 1 ? 'Eine Gewohnheit hat' : `${displaced.length} Gewohnheiten haben`} durch deinen Stundenplan ihren Platz verloren. Sie bleiben, bis sie einen neuen haben.`}
                        </p>
                        <ul className="flex flex-col gap-1">
                            {displaced.map((habit) => (
                                <li
                                    key={habit.id}
                                    className="flex flex-wrap items-baseline justify-between gap-x-3 text-sm"
                                >
                                    <span className="font-semibold">
                                        {habit.title}
                                    </span>
                                    {habit.previousTime !== null && (
                                        <span className="shrink-0 text-muted-foreground">
                                            {habit.from === null
                                                ? `lief bisher ${habit.previousTime}`
                                                : `${habit.previousTime} · bis ${habit.fromLabel}`}
                                        </span>
                                    )}
                                    {/* Der Weg zum Tag, an dem der Kurs liegt.
                                        Er steht neben der Gewohnheit, weil jede
                                        an einem anderen Tag klemmen kann — und
                                        er führt dorthin, nicht zum
                                        Semesterbeginn: Liegt „Statistik"
                                        mittwochs, zeigte der Montag davor einen
                                        freien Tag und keine Ursache.

                                        Neben der KI und nicht statt ihr: Ein
                                        Kurs rückt nicht, also braucht die
                                        Gewohnheit eine andere Zeit — vorschlagen
                                        lassen oder selbst hinlegen sind zwei
                                        gleich gute Wege dahin. */}
                                    {habit.conflictDate !== null && (
                                        <Link
                                            href={calendarDay(
                                                habit.conflictDate,
                                            )}
                                            className={`${QUIET_LINK} basis-full text-xs`}
                                        >
                                            Zum {habit.conflictLabel} springen
                                        </Link>
                                    )}
                                </li>
                            ))}
                        </ul>
                        {/* Der Weg zur KI, die Figur steht nur hier (§8). */}
                        <button
                            type="button"
                            onClick={() => setPlacesOpen(true)}
                            className={`${OUTLINE_BUTTON} self-start`}
                        >
                            <AiMascot
                                variant="mark"
                                className="size-4 shrink-0"
                            />
                            Neue Zeiten vorschlagen
                        </button>
                    </div>
                )}

                {/* Die zwei Wege aus dem Monat heraus, mittig als Paar. Sie
                    stehen über dem Raster: Wer den Kalender öffnet, will
                    meistens in den heutigen Tag, und dieser Weg soll nicht
                    unter fünf Wochen Raster liegen. */}
                <div className="flex flex-wrap items-center justify-center gap-x-6 gap-y-3">
                    <Link
                        href={calendarDay(today)}
                        className={`${QUIET_LINK} text-sm`}
                    >
                        Heutigen Tag öffnen
                    </Link>

                    <button
                        type="button"
                        onClick={() => setSemesterOpen(true)}
                        className={`${QUIET_LINK} inline-flex items-center gap-1.5 text-sm`}
                    >
                        <GraduationCap
                            className="size-4 shrink-0"
                            strokeWidth={1.75}
                            aria-hidden="true"
                        />
                        {semester === null
                            ? 'Semester eintragen'
                            : courseCount === 1
                              ? 'Stundenplan, 1 Kurs'
                              : `Stundenplan, ${courseCount} Kurse`}
                    </button>
                </div>

                {/* Die eine tragende Fläche der Seite (§12/§16): Das Raster ist
                    der Gegenstand, alles andere begleitet es. Vorher lag es so
                    flach wie jede Notiz daneben. */}
                <Card className="gap-0 border-transparent py-5 shadow-[var(--shadow-lift)]">
                    <CardContent className="px-3 sm:px-5">
                        <MonthGrid days={days} today={today} />
                    </CardContent>
                </Card>

                {/* Die Legende erklärt, was direkt darüber steht. */}
                <MonthLegend className="justify-center" />

                <SemesterSheet
                    open={semesterOpen}
                    semester={semester}
                    courseCount={courseCount}
                    maxCourses={maxCourses}
                    onOpenChange={setSemesterOpen}
                    onAddCourse={() => {
                        setSemesterOpen(false);
                        setEditingCourse(null);
                        setCourseOpen(true);
                    }}
                    onShowCourses={() => {
                        setSemesterOpen(false);
                        setCoursesOpen(true);
                    }}
                />

                {semester !== null && (
                    <>
                        <CoursesSheet
                            open={coursesOpen}
                            courses={courses}
                            onOpenChange={setCoursesOpen}
                            onOpen={setOpenedCourse}
                        />

                        {/* Dieselben Sheets wie im Tag — ein Kurs wird hier
                            nicht anders geändert als dort. */}
                        <CourseDetailSheet
                            course={openedCourse}
                            onOpenChange={() => setOpenedCourse(null)}
                            onEdit={(course) => {
                                setEditingCourse(course);
                                setCourseOpen(true);
                            }}
                            onCancelDate={setCancellingCourse}
                        />

                        <CourseSheet
                            open={courseOpen}
                            course={editingCourse}
                            kinds={kinds}
                            onOpenChange={setCourseOpen}
                        />

                        <CourseCancellationSheet
                            course={cancellingCourse}
                            semester={semester}
                            onOpenChange={() => setCancellingCourse(null)}
                        />

                        <NewPlacesSheet
                            open={placesOpen}
                            onOpenChange={setPlacesOpen}
                        />
                    </>
                )}
            </div>
        </>
    );
}

Calendar.layout = {
    breadcrumbs: [{ title: 'Kalender', href: calendar() }],
};
