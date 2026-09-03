import { router } from '@inertiajs/react';
import { CalendarOff, MapPin, Pencil, Trash2 } from 'lucide-react';
import { QUIET_BUTTON } from '@/lib/interaction';
import { destroy } from '@/routes/semester/courses';
import { destroy as undoException } from '@/routes/semester/courses/exceptions';
import type { CourseRow as Course } from '@/types';

/**
 * Ein Kurs im Semesterplan.
 *
 * Kein Haken und keine Serie: Ein Kurs ist nichts, was man sich vornimmt,
 * sondern etwas, um das herum geplant wird. Deshalb trägt die Zeile nur, was
 * man mit ihr tun kann — ändern, absagen, löschen.
 */
export function CourseRow({
    course,
    onEdit,
    onCancelDate,
}: {
    course: Course;
    onEdit: (course: Course) => void;
    /** Öffnet die Frage, an welchem Datum der Kurs ausfällt. */
    onCancelDate: (course: Course) => void;
}) {
    return (
        <li className="flex flex-col gap-2 rounded-xl border border-border bg-card px-4 py-3">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="type-eyebrow text-muted-foreground">
                        {course.timeRange} · {course.kindLabel}
                    </p>
                    <p className="mt-0.5 truncate text-[15px] font-semibold">
                        {course.title}
                    </p>
                    {course.location && (
                        <p className="mt-0.5 flex items-center gap-1 text-sm text-muted-foreground">
                            <MapPin
                                className="size-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            {course.location}
                        </p>
                    )}
                </div>

                <div className="flex shrink-0 items-center gap-1">
                    <IconButton
                        label={`${course.title} ändern`}
                        onClick={() => onEdit(course)}
                    >
                        <Pencil className="size-4" aria-hidden="true" />
                    </IconButton>
                    <IconButton
                        label={`${course.title} an einem Tag absagen`}
                        onClick={() => onCancelDate(course)}
                    >
                        <CalendarOff className="size-4" aria-hidden="true" />
                    </IconButton>
                    <IconButton
                        label={`${course.title} aus dem Plan nehmen`}
                        onClick={() =>
                            router.delete(destroy.url(course.id), {
                                preserveScroll: true,
                            })
                        }
                    >
                        <Trash2 className="size-4" aria-hidden="true" />
                    </IconButton>
                </div>
            </div>

            {course.exceptions.length > 0 && (
                <ul className="flex flex-col gap-1 border-t border-border pt-2">
                    {course.exceptions.map((exception) => (
                        <li
                            key={exception.onDate}
                            className="flex items-center justify-between gap-2 text-sm text-muted-foreground"
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
                                        undoException.url(course.id),
                                        {
                                            data: { on_date: exception.onDate },
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
            )}
        </li>
    );
}

/** Ein Knopf, der nur aus seinem Zeichen besteht — mit voller Trefferfläche. */
function IconButton({
    label,
    onClick,
    children,
}: {
    label: string;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={label}
            className="flex size-11 cursor-pointer items-center justify-center rounded-full text-muted-foreground transition-[background-color,color,scale] duration-[var(--duration-press)] ease-out hover:bg-accent hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.94]"
        >
            {children}
        </button>
    );
}
