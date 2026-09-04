import { Head, Link } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    GraduationCap,
    Sparkles,
} from 'lucide-react';
import { useState } from 'react';
import { CourseSheet } from '@/components/course-sheet';
import { MonthGrid } from '@/components/month-grid';
import { NewPlacesSheet } from '@/components/new-places-sheet';
import { SemesterSheet } from '@/components/semester-sheet';
import { Card, CardContent } from '@/components/ui/card';
import { OUTLINE_BUTTON, QUIET_LINK } from '@/lib/interaction';
import { calendar } from '@/routes';
import { day as calendarDay } from '@/routes/calendar';
import type {
    CourseKindOption,
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
    displaced,
}: CalendarProps) {
    const [semesterOpen, setSemesterOpen] = useState(false);
    const [courseOpen, setCourseOpen] = useState(false);
    const [placesOpen, setPlacesOpen] = useState(false);

    // Steht schon etwas ohne Platz da — oder kündigt sich das erst an? Beides
    // steht im Band, aber nicht mit demselben Satz: Was kommt, ist eine
    // Ankündigung, keine Bitte um eine Entscheidung.
    const upcoming = displaced.filter((habit) => habit.from !== null);
    const onlyUpcoming =
        displaced.length > 0 && upcoming.length === displaced.length;
    const firstFrom = upcoming[0]?.fromLabel ?? null;

    return (
        <>
            <Head title="Kalender" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
                <header className="flex items-center gap-2">
                    {/* Pfeile sind Links, kein Client-State: der Monat steht in
                        der URL und übersteht damit ein Neuladen. */}
                    <Link
                        href={calendar({ query: { month: previousMonth } })}
                        aria-label="Ein Monat zurück"
                        className={NAV_BUTTON}
                    >
                        <ChevronLeft className="size-5" aria-hidden="true" />
                    </Link>

                    <h1 className="flex-1 text-center text-[clamp(1.125rem,4vw,1.5rem)] leading-tight font-bold text-primary">
                        {heading}
                    </h1>

                    <Link
                        href={calendar({ query: { month: nextMonth } })}
                        aria-label="Ein Monat vor"
                        className={NAV_BUTTON}
                    >
                        <ChevronRight className="size-5" aria-hidden="true" />
                    </Link>

                    {/* Der Stundenplan, oben rechts: ein Knopf, kein Tab. Wer
                        ihn drückt, trägt Kurse ein oder setzt den Zeitraum —
                        beides im Sheet, beides ohne die Seite zu verlassen. */}
                    <button
                        type="button"
                        onClick={() => setSemesterOpen(true)}
                        aria-label={
                            semester === null
                                ? 'Semester und Kurse eintragen'
                                : `Kurse verwalten — ${courseCount} eingetragen`
                        }
                        className={NAV_BUTTON}
                    >
                        <GraduationCap
                            className="size-5"
                            strokeWidth={1.75}
                            aria-hidden="true"
                        />
                    </button>
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
                                ? `${displaced.length === 1 ? 'Eine Gewohnheit verliert' : `${displaced.length} Gewohnheiten verlieren`} ab dem ${firstFrom} durch deinen Stundenplan ihren Platz. Bis dahin läuft alles wie bisher — ein neuer Platz lässt sich schon jetzt finden.`
                                : `${displaced.length === 1 ? 'Eine Gewohnheit hat' : `${displaced.length} Gewohnheiten haben`} durch deinen Stundenplan ihren Platz verloren. Sie bleiben, bis sie einen neuen haben.`}
                        </p>
                        <ul className="flex flex-col gap-1">
                            {displaced.map((habit) => (
                                <li
                                    key={habit.id}
                                    className="flex items-baseline justify-between gap-3 text-sm"
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
                                </li>
                            ))}
                        </ul>
                        {/* Der Weg zur KI — ✦ steht nur hier (§8). */}
                        <button
                            type="button"
                            onClick={() => setPlacesOpen(true)}
                            className={`${OUTLINE_BUTTON} self-start`}
                        >
                            <Sparkles className="size-4" aria-hidden="true" />
                            Neue Zeiten vorschlagen
                        </button>
                    </div>
                )}

                <Card className="gap-0 py-4">
                    <CardContent className="px-3 sm:px-5">
                        <MonthGrid days={days} today={today} />
                    </CardContent>
                </Card>

                {/* Der Sprung in den heutigen Tag steht unter dem Raster und
                    nicht als Kachel darin: Er führt eine Ebene tiefer, während
                    alles im Raster nur den Ausschnitt wechselt. */}
                <div className="flex justify-center">
                    <Link
                        href={calendarDay(today)}
                        className={`${QUIET_LINK} text-sm`}
                    >
                        Heutigen Tag öffnen
                    </Link>
                </div>

                <SemesterSheet
                    open={semesterOpen}
                    semester={semester}
                    courseCount={courseCount}
                    maxCourses={maxCourses}
                    onOpenChange={setSemesterOpen}
                    onAddCourse={() => {
                        setSemesterOpen(false);
                        setCourseOpen(true);
                    }}
                />

                {semester !== null && (
                    <>
                        <CourseSheet
                            open={courseOpen}
                            course={null}
                            kinds={kinds}
                            onOpenChange={setCourseOpen}
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
