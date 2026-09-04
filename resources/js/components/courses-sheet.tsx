import { GraduationCap } from 'lucide-react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { BOTTOM_SHEET } from '@/lib/interaction';
import type { CourseRow, Weekday } from '@/types';

const WEEKDAYS: Record<Weekday, string> = {
    1: 'Montag',
    2: 'Dienstag',
    3: 'Mittwoch',
    4: 'Donnerstag',
    5: 'Freitag',
    6: 'Samstag',
    7: 'Sonntag',
};

/**
 * Alle Kurse auf einen Blick — Wochentag für Wochentag.
 *
 * Der Tag zeigt, was an ihm liegt; wer sechs Kurse hat, will sie trotzdem
 * einmal alle sehen und geradewegs ändern oder löschen, ohne sechs Tage zu
 * öffnen. Keine eigene Ansicht, sondern ein Sheet hinter dem Stundenplan —
 * und antippen führt in dieselben Sheets wie im Tag.
 */
export function CoursesSheet({
    open,
    courses,
    onOpenChange,
    onOpen,
}: {
    open: boolean;
    courses: CourseRow[];
    onOpenChange: (open: boolean) => void;
    /** Ein Kurs wurde angetippt — erst zu, dann auf. */
    onOpen: (course: CourseRow) => void;
}) {
    const weekdays = (Object.keys(WEEKDAYS) as unknown as Weekday[])
        .map(Number)
        .filter((weekday) =>
            courses.some((course) => course.weekday === weekday),
        ) as Weekday[];

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="bottom" className={BOTTOM_SHEET}>
                <SheetHeader className="gap-2 p-0">
                    <SheetTitle className="type-eyebrow text-left text-muted-foreground">
                        Alle Kurse
                    </SheetTitle>
                    <SheetDescription className="text-left text-sm text-muted-foreground">
                        {courses.length === 1
                            ? 'Ein Kurs in der Woche — antippen zum Ändern, Ausfallen oder Löschen.'
                            : `${courses.length} Kurse in der Woche — antippen zum Ändern, Ausfallen oder Löschen.`}
                    </SheetDescription>
                </SheetHeader>

                <div className="mt-5 flex flex-col gap-5">
                    {weekdays.map((weekday) => (
                        <section key={weekday}>
                            <h3 className="type-eyebrow text-muted-foreground">
                                {WEEKDAYS[weekday]}
                            </h3>
                            <ul className="mt-2 flex flex-col gap-2">
                                {courses
                                    .filter(
                                        (course) => course.weekday === weekday,
                                    )
                                    .map((course) => (
                                        <li key={course.courseId}>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    onOpenChange(false);
                                                    onOpen(course);
                                                }}
                                                className="flex w-full cursor-pointer items-center gap-3 rounded-xl border-l-[3px] border-l-olive-mid bg-sand px-3 py-2.5 text-left transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-sand/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.98]"
                                            >
                                                <GraduationCap
                                                    className="size-5 shrink-0 text-olive-mid"
                                                    strokeWidth={1.5}
                                                    aria-hidden="true"
                                                />
                                                <span className="min-w-0 flex-1">
                                                    <span className="type-eyebrow block truncate text-olive-mid">
                                                        {course.timeRange}
                                                        {course.exceptions
                                                            .length > 0 &&
                                                            ` · ${course.exceptions.length === 1 ? '1 Ausnahme' : `${course.exceptions.length} Ausnahmen`}`}
                                                    </span>
                                                    <span className="block truncate text-sm font-semibold text-foreground">
                                                        {course.title}
                                                    </span>
                                                    <span className="block truncate text-xs text-olive-mid">
                                                        {course.kindLabel}
                                                        {course.location &&
                                                            ` · ${course.location}`}
                                                    </span>
                                                </span>
                                            </button>
                                        </li>
                                    ))}
                            </ul>
                        </section>
                    ))}
                </div>
            </SheetContent>
        </Sheet>
    );
}
