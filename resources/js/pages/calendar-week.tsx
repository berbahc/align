import { Head } from '@inertiajs/react';
import { GraduationCap, Plus, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { CalendarViews } from '@/components/calendar-views';
import { CourseCancellationSheet } from '@/components/course-cancellation-sheet';
import { CourseDetailSheet } from '@/components/course-detail-sheet';
import { CourseSheet } from '@/components/course-sheet';
import { NewPlacesSheet } from '@/components/new-places-sheet';
import { SemesterSheet } from '@/components/semester-sheet';
import { Card, CardContent } from '@/components/ui/card';
import { WeekGrid } from '@/components/week-grid';
import { OUTLINE_BUTTON, QUIET_BUTTON, QUIET_LINK } from '@/lib/interaction';
import { calendar } from '@/routes';
import type {
    CourseKindOption,
    CourseRow as Course,
    DisplacedHabit,
    SemesterPlan,
    WeekHabitBlock,
    Weekday,
} from '@/types';

interface CalendarWeekProps {
    /** Null, solange niemand einen Zeitraum eingetragen hat. */
    semester: SemesterPlan | null;
    courses: Course[];
    kinds: CourseKindOption[];
    maxCourses: number;
    /** Was der Plan verdrängt hat — leer, solange nichts wartet. */
    displaced: DisplacedHabit[];
    /** Je ISO-Wochentag die Gewohnheiten mit ihrer Stelle. */
    habitBlocks: Record<Weekday, WeekHabitBlock[]>;
    /** Je ISO-Wochentag das nächste Datum — das Ziel beim Antippen. */
    weekdayDates: Record<Weekday, string>;
}

/**
 * Die Woche — die mittlere Ebene des Kalenders.
 *
 * Der Monat sagt, ob ein Tag voll war; der Tag sagt, was jetzt dran ist. Die
 * Woche zeigt, wie das Semester um die Gewohnheiten herum liegt: Kurse und
 * Gewohnheiten auf einem Raster. Deshalb werden Kurse auch hier eingetragen —
 * dort, wo man sie sieht. Eine eigene Seite dafür wäre ein zweiter Kalender,
 * der dasselbe zeigt, nur weniger.
 *
 * Der Zeitraum des Semesters ist eine Angabe, keine Ansicht: eine Zeile im
 * Kopf und ein Sheet dahinter.
 */
export default function CalendarWeek({
    semester,
    courses,
    kinds,
    maxCourses,
    displaced,
    habitBlocks,
    weekdayDates,
}: CalendarWeekProps) {
    const [editing, setEditing] = useState<Course | null>(null);
    const [sheetOpen, setSheetOpen] = useState(false);
    const [cancelling, setCancelling] = useState<Course | null>(null);
    const [opened, setOpened] = useState<Course | null>(null);
    const [placesOpen, setPlacesOpen] = useState(false);
    const [semesterOpen, setSemesterOpen] = useState(false);

    function openNew() {
        setEditing(null);
        setSheetOpen(true);
    }

    function openEdit(course: Course) {
        setEditing(course);
        setSheetOpen(true);
    }

    return (
        <>
            <Head title="Woche" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
                <header className="flex flex-col gap-3">
                    <h1 className="type-title text-primary">Deine Woche</h1>

                    {/* Der Rahmen des Semesters als eine Zeile — was gilt,
                        und ein Weg, es zu ändern. Ohne Semester die Einladung
                        samt Grund, denn die Woche allein sagt nicht, wozu ein
                        Stundenplan gut wäre. */}
                    {semester === null ? (
                        <p className="text-sm text-muted-foreground">
                            <button
                                type="button"
                                onClick={() => setSemesterOpen(true)}
                                className={QUIET_LINK}
                            >
                                Semester anlegen
                            </button>
                            {' — dann plant Align um deine Kurse herum.'}
                        </p>
                    ) : (
                        <p className="flex flex-wrap items-baseline gap-x-2 text-sm text-muted-foreground">
                            <span className="font-semibold text-foreground">
                                {semester.title}
                            </span>
                            <span>{semester.rangeLabel}</span>
                            {!semester.isCurrent && (
                                <span>
                                    ·{' '}
                                    {semester.startsInFuture
                                        ? `beginnt am ${semester.startsOnLabel}`
                                        : 'vorbei'}
                                </span>
                            )}
                            <button
                                type="button"
                                onClick={() => setSemesterOpen(true)}
                                className={QUIET_BUTTON}
                            >
                                Ändern
                            </button>
                        </p>
                    )}
                </header>

                <CalendarViews active="week" />

                {/* Was der Plan verdrängt hat. Steht über dem Raster, weil es
                    eine Entscheidung braucht und die Kurse nur Tatsachen sind.
                    Kein Warnton: Nichts ist verloren, es wartet nur. */}
                {displaced.length > 0 && (
                    <div
                        role="status"
                        className="flex flex-col gap-2 rounded-xl border border-primary/25 bg-accent px-4 py-3"
                    >
                        <p className="text-sm text-foreground">
                            {displaced.length === 1
                                ? 'Eine Gewohnheit hat durch deinen Plan ihren Platz verloren.'
                                : `${displaced.length} Gewohnheiten haben durch deinen Plan ihren Platz verloren.`}{' '}
                            Sie bleiben, bis sie einen neuen haben.
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
                                            lief bisher {habit.previousTime}
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
                        <WeekGrid
                            courses={courses}
                            habits={habitBlocks}
                            dates={weekdayDates}
                            onOpenCourse={setOpened}
                        />
                    </CardContent>
                </Card>

                {semester !== null && (
                    <div className="flex flex-col items-center gap-2">
                        {courses.length === 0 && (
                            <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                <GraduationCap
                                    className="size-4"
                                    strokeWidth={1.5}
                                    aria-hidden="true"
                                />
                                Noch kein Kurs — sobald einer drinsteht, belegt
                                er hier seine Zeit.
                            </p>
                        )}
                        <button
                            type="button"
                            onClick={openNew}
                            disabled={courses.length >= maxCourses}
                            className={OUTLINE_BUTTON}
                        >
                            <Plus className="size-4" aria-hidden="true" />
                            Kurs eintragen
                        </button>
                        {courses.length >= maxCourses && (
                            <p className="text-sm text-muted-foreground">
                                {maxCourses} Kurse sind das Maximum — mehr wäre
                                kein Plan mehr.
                            </p>
                        )}
                    </div>
                )}

                <SemesterSheet
                    open={semesterOpen}
                    semester={semester}
                    onOpenChange={setSemesterOpen}
                />

                {semester !== null && (
                    <>
                        <CourseSheet
                            open={sheetOpen}
                            course={editing}
                            kinds={kinds}
                            onOpenChange={setSheetOpen}
                        />

                        <CourseCancellationSheet
                            course={cancelling}
                            semester={semester}
                            onOpenChange={() => setCancelling(null)}
                        />

                        <CourseDetailSheet
                            course={opened}
                            onOpenChange={() => setOpened(null)}
                            onEdit={openEdit}
                            onCancelDate={setCancelling}
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

CalendarWeek.layout = {
    breadcrumbs: [
        { title: 'Kalender', href: calendar() },
        { title: 'Woche', href: '' },
    ],
};
