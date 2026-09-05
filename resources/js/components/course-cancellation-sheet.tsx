import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { BOTTOM_SHEET, PRIMARY_BUTTON, QUIET_BUTTON } from '@/lib/interaction';
import { store } from '@/routes/calendar/semester/courses/exceptions';
import type { CourseRow, SemesterPlan } from '@/types';

/**
 * Ein Kurs fällt an einem einzelnen Tag aus.
 *
 * Nur der Ausfall, nicht der Ersatztermin: Der Ausfall ist der Fall, der jedes
 * Semester ein paar Mal vorkommt — Feiertag, kranker Dozent, Exkursionswoche.
 * Der Nachholtermin ist selten genug, dass er als zweiter Kurs eingetragen
 * werden kann, statt hier eine zweite Form zu rechtfertigen.
 *
 * Das Datumsfeld ist das native: Ein Kalender ist genau das, was der Browser
 * kann, und die Fünferschritte des Steppers helfen bei einem Tag nicht.
 */
export function CourseCancellationSheet({
    course,
    semester,
    onOpenChange,
}: {
    /** Null heißt zu. */
    course: CourseRow | null;
    semester: SemesterPlan;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm({ on_date: '' });

    useEffect(() => {
        if (course === null) {
            return;
        }

        clearErrors();
        setData('on_date', '');
        // Siehe `course-sheet.tsx`: Die Setter sind bei jedem Rendern neu.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [course]);

    function submit(event: React.FormEvent) {
        event.preventDefault();

        if (course === null) {
            return;
        }

        post(store.url(course.courseId), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    }

    return (
        <Sheet open={course !== null} onOpenChange={onOpenChange}>
            <SheetContent side="bottom" className={BOTTOM_SHEET}>
                <SheetHeader className="gap-2 p-0">
                    <SheetTitle className="type-eyebrow text-left text-muted-foreground">
                        Fällt aus
                    </SheetTitle>
                    <SheetDescription className="text-left text-sm text-muted-foreground">
                        An welchem Tag findet „{course?.title}" nicht statt? Nur
                        an diesem einen — danach läuft der Kurs wieder wie jede
                        Woche.
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={submit} className="mt-6 flex flex-col gap-6">
                    <div className="flex flex-col gap-2">
                        <label
                            htmlFor="cancellation-date"
                            className="type-eyebrow text-muted-foreground"
                        >
                            Datum
                        </label>
                        <input
                            id="cancellation-date"
                            type="date"
                            value={data.on_date}
                            min={semester.startsOn}
                            max={semester.endsOn}
                            onChange={(event) =>
                                setData('on_date', event.target.value)
                            }
                            className="h-12 w-full rounded-xl border border-input bg-card px-4 text-[15px] transition-colors duration-[var(--duration-press)] ease-out focus-visible:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        />
                        {errors.on_date && (
                            <p
                                role="alert"
                                className="rounded-xl border border-primary/25 bg-accent px-3 py-2 text-sm text-foreground"
                            >
                                {errors.on_date}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col items-center gap-4">
                        <button
                            type="submit"
                            disabled={processing || data.on_date === ''}
                            className={PRIMARY_BUTTON}
                        >
                            {processing && <Spinner className="size-4" />}
                            Als Ausfall vermerken
                        </button>
                        <button
                            type="button"
                            onClick={() => onOpenChange(false)}
                            className={QUIET_BUTTON}
                        >
                            Abbrechen
                        </button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>
    );
}
