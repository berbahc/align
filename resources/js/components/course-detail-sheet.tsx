import { router } from '@inertiajs/react';
import { GraduationCap, MapPin } from 'lucide-react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { OUTLINE_BUTTON, QUIET_BUTTON } from '@/lib/interaction';
import { destroy } from '@/routes/calendar/semester/courses';
import { destroy as undoException } from '@/routes/calendar/semester/courses/exceptions';
import type { CourseBlock, Weekday } from '@/types';

const WEEKDAY_NAMES: Record<Weekday, string> = {
    1: 'Montag',
    2: 'Dienstag',
    3: 'Mittwoch',
    4: 'Donnerstag',
    5: 'Freitag',
    6: 'Samstag',
    7: 'Sonntag',
};

/**
 * Der aufgeschlagene Kurs — das Gegenstück zum Block-Sheet der Gewohnheiten.
 *
 * Im Wochenraster ist ein Block so groß wie der Kurs lang, und dort ist kein
 * Platz für drei Handlungen und eine Liste von Ausnahmen. Das Sheet zieht sie
 * heraus: Der Block zeigt, **wann**, das Sheet zeigt **was man damit tun kann**.
 *
 * Dieselbe Schale wie überall. Kein Haken — ein Kurs wird nicht abgehakt.
 */
export function CourseDetailSheet({
    course,
    onOpenChange,
    onEdit,
    onCancelDate,
}: {
    /** Null heißt zu. */
    course: CourseBlock | null;
    onOpenChange: (open: boolean) => void;
    onEdit: (course: CourseBlock) => void;
    onCancelDate: (course: CourseBlock) => void;
}) {
    /** Ein Weg aus dem Sheet heraus in das nächste — erst zu, dann auf. */
    function leaveFor(next: (course: CourseBlock) => void) {
        if (course === null) {
            return;
        }

        onOpenChange(false);
        next(course);
    }

    return (
        <Sheet open={course !== null} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="mx-auto max-h-[85vh] max-w-lg gap-0 overflow-y-auto rounded-t-2xl px-5 pt-6 pb-8"
            >
                <SheetHeader className="gap-2 p-0">
                    <SheetTitle className="type-eyebrow text-left text-muted-foreground">
                        {course &&
                            `${WEEKDAY_NAMES[course.weekday]} · ${course.timeRange} · ${course.kindLabel}`}
                    </SheetTitle>
                    <SheetDescription asChild>
                        <div className="flex items-center gap-3 text-left">
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sand text-olive-mid">
                                <GraduationCap
                                    className="size-5"
                                    strokeWidth={1.5}
                                    aria-hidden="true"
                                />
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="type-subheading block truncate text-foreground">
                                    {course?.title}
                                </span>
                                {course?.location && (
                                    <span className="mt-0.5 flex items-center gap-1 text-sm text-muted-foreground">
                                        <MapPin
                                            className="size-3.5 shrink-0"
                                            aria-hidden="true"
                                        />
                                        {course.location}
                                    </span>
                                )}
                            </span>
                        </div>
                    </SheetDescription>
                </SheetHeader>

                {course && course.exceptions.length > 0 && (
                    <section className="mt-5 border-t border-border pt-4">
                        <h3 className="type-eyebrow text-muted-foreground">
                            Ausnahmen
                        </h3>
                        <ul className="mt-2 flex flex-col gap-1.5">
                            {course.exceptions.map((exception) => (
                                <li
                                    key={exception.onDate}
                                    className="flex items-center justify-between gap-2 text-sm"
                                >
                                    <span className="min-w-0 truncate">
                                        {exception.dateLabel} ·{' '}
                                        {exception.cancelled
                                            ? 'fällt aus'
                                            : exception.timeRange}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.delete(
                                                undoException.url(
                                                    course.courseId,
                                                ),
                                                {
                                                    data: {
                                                        on_date:
                                                            exception.onDate,
                                                    },
                                                    preserveScroll: true,
                                                },
                                            )
                                        }
                                        className={`${QUIET_BUTTON} shrink-0`}
                                    >
                                        zurücknehmen
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <div className="mt-6 flex flex-wrap gap-2">
                    <button
                        type="button"
                        onClick={() => leaveFor(onEdit)}
                        className={OUTLINE_BUTTON}
                    >
                        Ändern
                    </button>
                    <button
                        type="button"
                        onClick={() => leaveFor(onCancelDate)}
                        className={OUTLINE_BUTTON}
                    >
                        Fällt einmal aus
                    </button>
                </div>

                {/* Der Weg, den man nicht suchen soll, aber finden können muss:
                    leise, unten, ohne Rahmen. */}
                <div className="mt-5 flex justify-center border-t border-border pt-4">
                    <button
                        type="button"
                        onClick={() => {
                            if (course === null) {
                                return;
                            }

                            onOpenChange(false);
                            router.delete(destroy.url(course.courseId), {
                                preserveScroll: true,
                            });
                        }}
                        className={QUIET_BUTTON}
                    >
                        Aus dem Plan nehmen
                    </button>
                </div>
            </SheetContent>
        </Sheet>
    );
}
