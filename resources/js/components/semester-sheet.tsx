import { router, useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { PRIMARY_BUTTON, QUIET_BUTTON } from '@/lib/interaction';
import { destroy, store, update } from '@/routes/calendar/semester';
import type { SemesterPlan } from '@/types';

const FIELD =
    'h-12 w-full rounded-xl border border-input bg-card px-4 text-[15px] transition-colors duration-[var(--duration-press)] ease-out focus-visible:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';

const ERROR_PANEL =
    'rounded-xl border border-primary/25 bg-accent px-3 py-2 text-sm text-foreground';

/**
 * Der Zeitraum des Semesters — anlegen oder ändern.
 *
 * Ein Sheet und keine Seite: Der Zeitraum ist eine Angabe, keine Ansicht. Er
 * entscheidet nur, ab wann und bis wann die Kurse im Kalender gelten — und das
 * stellt man einmal im Semester ein, nicht jede Woche.
 */
export function SemesterSheet({
    open,
    semester,
    onOpenChange,
}: {
    open: boolean;
    /** Null heißt: noch keins — dann wird angelegt statt geändert. */
    semester: SemesterPlan | null;
    onOpenChange: (open: boolean) => void;
}) {
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

    function submit(event: React.FormEvent) {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (semester === null) {
            post(store.url(), options);

            return;
        }

        put(update.url(), options);
    }

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="mx-auto max-h-[85vh] max-w-lg gap-0 overflow-y-auto rounded-t-2xl px-5 pt-6 pb-8"
            >
                <SheetHeader className="gap-2 p-0">
                    <SheetTitle className="type-eyebrow text-left text-muted-foreground">
                        {semester === null ? 'Semester anlegen' : 'Semester'}
                    </SheetTitle>
                    <SheetDescription className="text-left text-sm text-muted-foreground">
                        Zwischen Anfang und Ende belegen deine Kurse ihre Zeit
                        im Kalender. Davor und danach ist die Woche frei.
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={submit} className="mt-6 flex flex-col gap-5">
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
                            onClick={() => onOpenChange(false)}
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
                                onOpenChange(false);
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
