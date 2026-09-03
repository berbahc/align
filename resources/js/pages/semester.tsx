import { Head, useForm } from '@inertiajs/react';
import { GraduationCap, Plus } from 'lucide-react';
import { useState } from 'react';
import { CourseCancellationSheet } from '@/components/course-cancellation-sheet';
import { CourseRow } from '@/components/course-row';
import { CourseSheet } from '@/components/course-sheet';
import { Card, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import {
    OUTLINE_BUTTON,
    PRIMARY_BUTTON,
    QUIET_BUTTON,
} from '@/lib/interaction';
import { dashboard } from '@/routes';
import {
    store as storeSemester,
    update as updateSemester,
} from '@/routes/semester';
import type {
    CourseKindOption,
    CourseRow as Course,
    SemesterPlan,
    Weekday,
} from '@/types';

const WEEKDAY_NAMES: Record<Weekday, string> = {
    1: 'Montag',
    2: 'Dienstag',
    3: 'Mittwoch',
    4: 'Donnerstag',
    5: 'Freitag',
    6: 'Samstag',
    7: 'Sonntag',
};

interface SemesterProps {
    /** Null, solange niemand einen Zeitraum eingetragen hat. */
    semester: SemesterPlan | null;
    courses: Course[];
    kinds: CourseKindOption[];
    maxCourses: number;
}

/**
 * Der Semesterplan — der zweite Rahmen neben dem Schlafplan.
 *
 * Der Schlafplan sagt, wann der Tag anfängt und aufhört. Dieser sagt, wann
 * darin nichts geht. Beide sagen nicht, was zu tun ist: Ein Kurs wird nicht
 * abgehakt und hat keine Serie — er ist eine Tatsache, um die herum geplant
 * wird.
 *
 * Erst der Zeitraum, dann die Kurse. Ohne Anfang und Ende wüsste niemand, ab
 * wann die Vorlesungen im Kalender stehen und ab wann nicht mehr.
 */
export default function Semester({
    semester,
    courses,
    kinds,
    maxCourses,
}: SemesterProps) {
    const [editing, setEditing] = useState<Course | null>(null);
    const [sheetOpen, setSheetOpen] = useState(false);
    const [cancelling, setCancelling] = useState<Course | null>(null);

    const byWeekday = (Object.keys(WEEKDAY_NAMES) as unknown as string[])
        .map((key) => Number(key) as Weekday)
        .map((weekday) => ({
            weekday,
            courses: courses.filter((course) => course.weekday === weekday),
        }))
        .filter((day) => day.courses.length > 0);

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
            <Head title="Semester" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
                <header>
                    <h1 className="type-title text-primary">Semesterplan</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Was jede Woche feststeht. Align plant deine Gewohnheiten
                        darum herum.
                    </p>
                </header>

                <SemesterFrame semester={semester} />

                {semester !== null && (
                    <>
                        {courses.length === 0 ? (
                            <Card>
                                <CardContent className="flex flex-col items-center gap-4 py-8 text-center">
                                    <span className="flex size-11 items-center justify-center rounded-xl bg-track text-olive-mid">
                                        <GraduationCap
                                            className="size-5"
                                            strokeWidth={1.5}
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <p className="text-sm text-muted-foreground">
                                        Noch kein Kurs eingetragen. Sobald einer
                                        drinsteht, belegt er im Kalender seine
                                        Zeit — und keine Gewohnheit landet mehr
                                        darauf.
                                    </p>
                                    <button
                                        type="button"
                                        onClick={openNew}
                                        className={OUTLINE_BUTTON}
                                    >
                                        <Plus
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                        Ersten Kurs eintragen
                                    </button>
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="flex flex-col gap-5">
                                {byWeekday.map((day) => (
                                    <section
                                        key={day.weekday}
                                        className="flex flex-col gap-2"
                                    >
                                        <h2 className="type-eyebrow text-muted-foreground">
                                            {WEEKDAY_NAMES[day.weekday]}
                                        </h2>
                                        <ul className="flex flex-col gap-2">
                                            {day.courses.map((course) => (
                                                <CourseRow
                                                    key={course.id}
                                                    course={course}
                                                    onEdit={openEdit}
                                                    onCancelDate={setCancelling}
                                                />
                                            ))}
                                        </ul>
                                    </section>
                                ))}
                            </div>
                        )}

                        {courses.length > 0 && (
                            <div className="flex flex-col items-center gap-2">
                                <button
                                    type="button"
                                    onClick={openNew}
                                    disabled={courses.length >= maxCourses}
                                    className={OUTLINE_BUTTON}
                                >
                                    <Plus
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Kurs eintragen
                                </button>
                                {courses.length >= maxCourses && (
                                    <p className="text-sm text-muted-foreground">
                                        {maxCourses} Kurse sind das Maximum —
                                        mehr wäre kein Plan mehr.
                                    </p>
                                )}
                            </div>
                        )}

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
                    </>
                )}
            </div>
        </>
    );
}

/**
 * Der Zeitraum — anlegen, solange es keinen gibt, sonst aufklappbar ändern.
 *
 * Er steht ganz oben, weil ohne ihn nichts anderes eine Wirkung hätte: Kurse
 * außerhalb der Vorlesungszeit belegen keine Zeit.
 */
function SemesterFrame({ semester }: { semester: SemesterPlan | null }) {
    const [open, setOpen] = useState(semester === null);

    const { data, setData, post, put, processing, errors } = useForm({
        title: semester?.title ?? '',
        starts_on: semester?.startsOn ?? '',
        ends_on: semester?.endsOn ?? '',
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        };

        if (semester === null) {
            post(storeSemester.url(), options);

            return;
        }

        put(updateSemester.url(), options);
    }

    if (semester !== null && !open) {
        return (
            <Card>
                <CardContent className="flex items-center justify-between gap-4">
                    <div className="min-w-0">
                        <p className="text-[15px] font-semibold">
                            {semester.title}
                        </p>
                        <p className="mt-0.5 text-sm text-muted-foreground">
                            {semester.rangeLabel}
                            {!semester.isCurrent &&
                                ' · vorbei, blockiert nichts mehr'}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() => setOpen(true)}
                        className={`${QUIET_BUTTON} shrink-0`}
                    >
                        Ändern
                    </button>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardContent>
                <form onSubmit={submit} className="flex flex-col gap-5">
                    <div className="flex flex-col gap-2">
                        <label
                            htmlFor="semester-title"
                            className="type-eyebrow text-muted-foreground"
                        >
                            Semester
                        </label>
                        <input
                            id="semester-title"
                            type="text"
                            value={data.title}
                            onChange={(event) =>
                                setData('title', event.target.value)
                            }
                            placeholder="Wintersemester 25/26"
                            maxLength={60}
                            className="h-12 w-full rounded-xl border border-input bg-card px-4 text-[15px] transition-colors duration-[var(--duration-press)] ease-out focus-visible:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        />
                        {errors.title && (
                            <p role="alert" className={ERROR_PANEL}>
                                {errors.title}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-2 sm:flex-row sm:gap-4">
                        <div className="flex flex-1 flex-col gap-2">
                            <label
                                htmlFor="semester-start"
                                className="type-eyebrow text-muted-foreground"
                            >
                                Vorlesungszeit ab
                            </label>
                            <input
                                id="semester-start"
                                type="date"
                                value={data.starts_on}
                                onChange={(event) =>
                                    setData('starts_on', event.target.value)
                                }
                                className="h-12 w-full rounded-xl border border-input bg-card px-4 text-[15px] transition-colors duration-[var(--duration-press)] ease-out focus-visible:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            />
                        </div>
                        <div className="flex flex-1 flex-col gap-2">
                            <label
                                htmlFor="semester-end"
                                className="type-eyebrow text-muted-foreground"
                            >
                                bis
                            </label>
                            <input
                                id="semester-end"
                                type="date"
                                value={data.ends_on}
                                onChange={(event) =>
                                    setData('ends_on', event.target.value)
                                }
                                className="h-12 w-full rounded-xl border border-input bg-card px-4 text-[15px] transition-colors duration-[var(--duration-press)] ease-out focus-visible:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            />
                        </div>
                    </div>

                    {(errors.starts_on || errors.ends_on) && (
                        <p role="alert" className={ERROR_PANEL}>
                            {errors.starts_on ?? errors.ends_on}
                        </p>
                    )}

                    <div className="flex flex-col items-center gap-3">
                        <button
                            type="submit"
                            disabled={
                                processing ||
                                data.title.trim() === '' ||
                                data.starts_on === '' ||
                                data.ends_on === ''
                            }
                            className={PRIMARY_BUTTON}
                        >
                            {processing && <Spinner className="size-4" />}
                            {semester === null
                                ? 'Semester anlegen'
                                : 'Zeitraum speichern'}
                        </button>
                        {semester !== null && (
                            <button
                                type="button"
                                onClick={() => setOpen(false)}
                                className={QUIET_BUTTON}
                            >
                                Abbrechen
                            </button>
                        )}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

/** Die ruhige Fehlerfläche — kein Rot, wie überall in dieser App. */
const ERROR_PANEL =
    'rounded-xl border border-primary/25 bg-accent px-3 py-2 text-sm text-foreground';

Semester.layout = {
    breadcrumbs: [
        { title: 'Übersicht', href: dashboard() },
        { title: 'Semester', href: '' },
    ],
};
