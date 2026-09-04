import { router, useForm } from '@inertiajs/react';
import { GraduationCap, List, Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import {
    OUTLINE_BUTTON,
    PRIMARY_BUTTON,
    QUIET_BUTTON,
} from '@/lib/interaction';
import { destroy, store, update } from '@/routes/calendar/semester';
import type { SemesterPlan } from '@/types';

const FIELD =
    'h-12 w-full rounded-xl border border-input bg-card px-4 text-[15px] transition-colors duration-[var(--duration-press)] ease-out focus-visible:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';

const ERROR_PANEL =
    'rounded-xl border border-primary/25 bg-accent px-3 py-2 text-sm text-foreground';

/**
 * Der Stundenplan hinter dem Knopf oben rechts im Monat.
 *
 * Ein Sheet und keine Seite: Der Zeitraum ist eine Angabe, keine Ansicht, und
 * die Kurse liegen im Tag, wo man sie anfasst. Hier steht, was gilt, ein Weg,
 * es zu ändern, und der Weg zu einem neuen Kurs — mehr nicht.
 */
export function SemesterSheet({
    open,
    semester,
    courseCount,
    maxCourses,
    onOpenChange,
    onAddCourse,
    onShowCourses,
}: {
    open: boolean;
    /** Null heißt: noch keins — dann wird angelegt statt geändert. */
    semester: SemesterPlan | null;
    courseCount: number;
    maxCourses: number;
    onOpenChange: (open: boolean) => void;
    /** Führt ins Kurs-Sheet — erst zu, dann auf. */
    onAddCourse: () => void;
    /** Führt in die Übersicht aller Kurse — erst zu, dann auf. */
    onShowCourses: () => void;
}) {
    const [editing, setEditing] = useState(false);
    const { data, setData, post, put, processing, errors, clearErrors } =
        useForm({
            title: semester?.title ?? '',
            starts_on: semester?.startsOn ?? '',
            ends_on: semester?.endsOn ?? '',
        });

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();
        setData({
            title: semester?.title ?? '',
            starts_on: semester?.startsOn ?? '',
            ends_on: semester?.endsOn ?? '',
        });
        // Die Setter sind bei jedem Rendern neu — siehe `course-sheet.tsx`.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, semester]);

    /** Zu heißt auch: nicht mehr im Ändern — das nächste Öffnen zeigt, was gilt. */
    function close(next: boolean) {
        if (!next) {
            setEditing(false);
        }

        onOpenChange(next);
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setEditing(false);

                if (semester === null) {
                    close(false);
                }
            },
        };

        if (semester === null) {
            post(store.url(), options);

            return;
        }

        put(update.url(), options);
    }

    return (
        <Sheet open={open} onOpenChange={close}>
            <SheetContent
                side="bottom"
                className="mx-auto max-h-[85vh] max-w-lg gap-0 overflow-y-auto rounded-t-2xl px-5 pt-6 pb-8"
            >
                <SheetHeader className="gap-2 p-0">
                    <SheetTitle className="type-eyebrow text-left text-muted-foreground">
                        {semester === null ? 'Semester anlegen' : 'Stundenplan'}
                    </SheetTitle>
                    <SheetDescription className="text-left text-sm text-muted-foreground">
                        {semester === null
                            ? 'Zwischen Anfang und Ende belegen deine Kurse ihre Zeit im Kalender — und Align plant deine Gewohnheiten darum herum.'
                            : 'Deine Kurse belegen ihre Zeit im Kalender; Align plant deine Gewohnheiten darum herum.'}
                    </SheetDescription>
                </SheetHeader>

                {semester !== null && !editing && (
                    <div className="mt-6 flex flex-col gap-4">
                        <div className="flex items-center gap-3 rounded-xl border border-border bg-card px-4 py-3">
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sand text-olive-mid">
                                <GraduationCap
                                    className="size-5"
                                    strokeWidth={1.5}
                                    aria-hidden="true"
                                />
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-[15px] font-semibold">
                                    {semester.title}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {semester.rangeLabel}
                                    {!semester.isCurrent &&
                                        (semester.startsInFuture
                                            ? ` · beginnt am ${semester.startsOnLabel}`
                                            : ' · vorbei')}
                                    {' · '}
                                    {courseCount === 1
                                        ? '1 Kurs'
                                        : `${courseCount} Kurse`}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setEditing(true)}
                                className={`${QUIET_BUTTON} shrink-0`}
                            >
                                Ändern
                            </button>
                        </div>

                        {/* Der eigentliche Weg: Die Kurse liegen im Tag, dort
                            werden sie angefasst. Hier kommt nur ein neuer dazu. */}
                        <button
                            type="button"
                            onClick={onAddCourse}
                            disabled={courseCount >= maxCourses}
                            className={PRIMARY_BUTTON}
                        >
                            <Plus className="size-4" aria-hidden="true" />
                            Kurs eintragen
                        </button>
                        {/* Die Übersicht: Wer sechs Kurse hat, will sie auch
                            einmal alle sehen — und ändern oder löschen, ohne
                            sechs Tage zu öffnen. */}
                        {courseCount > 0 && (
                            <button
                                type="button"
                                onClick={onShowCourses}
                                className={`${OUTLINE_BUTTON} w-full justify-center`}
                            >
                                <List className="size-4" aria-hidden="true" />
                                Alle Kurse ansehen
                            </button>
                        )}
                        <p className="text-center text-xs leading-relaxed text-muted-foreground">
                            {courseCount >= maxCourses
                                ? `${maxCourses} Kurse sind das Maximum — mehr wäre kein Plan mehr.`
                                : 'Eingetragene Kurse liegen an ihrem Tag im Kalender — antippen zum Ändern.'}
                        </p>
                    </div>
                )}

                <form
                    onSubmit={submit}
                    className={
                        semester === null || editing
                            ? 'mt-6 flex flex-col gap-5'
                            : 'hidden'
                    }
                >
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
                            className={FIELD}
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
                                className={FIELD}
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
                                className={FIELD}
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
                        <button
                            type="button"
                            onClick={() =>
                                semester === null
                                    ? close(false)
                                    : setEditing(false)
                            }
                            className={QUIET_BUTTON}
                        >
                            Abbrechen
                        </button>
                    </div>
                </form>

                {semester !== null && (
                    <div className="mt-5 flex justify-center border-t border-border pt-4">
                        {/* Leise, unten: Löschen nimmt alle Kurse mit. Was
                            sie verdrängt hatten, kommt zurück, wo es frei ist. */}
                        <button
                            type="button"
                            onClick={() => {
                                close(false);
                                router.delete(destroy.url(), {
                                    preserveScroll: true,
                                });
                            }}
                            className={QUIET_BUTTON}
                        >
                            Semester samt Kursen löschen
                        </button>
                    </div>
                )}
            </SheetContent>
        </Sheet>
    );
}
