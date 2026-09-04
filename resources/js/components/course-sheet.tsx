import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { TimeStepper } from '@/components/time-stepper';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import {
    CHOICE_TILE,
    CHOICE_TILE_OFF,
    CHOICE_TILE_ON,
    PRIMARY_BUTTON,
    QUIET_BUTTON,
} from '@/lib/interaction';
import { cn } from '@/lib/utils';
import { store, update } from '@/routes/calendar/semester/courses';
import type { CourseKind, CourseKindOption, CourseRow, Weekday } from '@/types';

/** Montag zuerst — dieselbe Zählung wie überall in der App. */
const WEEKDAYS: { value: Weekday; short: string; long: string }[] = [
    { value: 1, short: 'Mo', long: 'Montag' },
    { value: 2, short: 'Di', long: 'Dienstag' },
    { value: 3, short: 'Mi', long: 'Mittwoch' },
    { value: 4, short: 'Do', long: 'Donnerstag' },
    { value: 5, short: 'Fr', long: 'Freitag' },
    { value: 6, short: 'Sa', long: 'Samstag' },
    { value: 7, short: 'So', long: 'Sonntag' },
];

interface CourseForm {
    title: string;
    kind: CourseKind;
    weekday: Weekday;
    starts_at: string;
    ends_at: string;
    location: string;
}

function blank(): CourseForm {
    return {
        title: '',
        kind: 'vorlesung',
        weekday: 1,
        starts_at: '10:00',
        ends_at: '11:30',
        location: '',
    };
}

/**
 * Einen Kurs eintragen oder ändern.
 *
 * Dieselbe Schale wie das aufgeschlagene Block-Sheet im Kalender — ein
 * Formular, das von unten kommt, ist in dieser App immer dasselbe Formular.
 *
 * Die Zeiten stehen als Stepper und nicht als Zeitfeld: Der Browser zeigt sein
 * eigenes, je Gerät anderes, und die Fünferschritte sind hier so richtig wie
 * beim Schlafplan — eine Vorlesung fängt nicht um 10:03 an.
 */
export function CourseSheet({
    open,
    course,
    kinds,
    onOpenChange,
}: {
    open: boolean;
    /** Null heißt: ein neuer Kurs. */
    course: CourseRow | null;
    kinds: CourseKindOption[];
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm<CourseForm>(blank());

    // Beim Öffnen die Felder auf den Kurs setzen, den man angetippt hat — oder
    // leeren, wenn es ein neuer ist. Ohne das trüge das Sheet noch, was beim
    // letzten Mal darin stand.
    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();
        setData(
            course === null
                ? blank()
                : {
                      title: course.title,
                      kind: course.courseKind,
                      weekday: course.weekday,
                      starts_at: course.startsAt,
                      ends_at: course.endsAt,
                      location: course.location ?? '',
                  },
        );
        // `setData` und `clearErrors` sind bei jedem Rendern neu; sie in die
        // Abhängigkeiten zu nehmen hieße, das Formular bei jedem Tastendruck
        // zurückzusetzen.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, course]);

    function submit(event: React.FormEvent) {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        };

        if (course === null) {
            post(store.url(), options);

            return;
        }

        put(update.url(course.courseId), options);
    }

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="mx-auto max-h-[85vh] max-w-lg gap-0 overflow-y-auto rounded-t-2xl px-5 pt-6 pb-8"
            >
                <SheetHeader className="gap-2 p-0">
                    <SheetTitle className="type-eyebrow text-left text-muted-foreground">
                        {course === null ? 'Neuer Kurs' : 'Kurs ändern'}
                    </SheetTitle>
                    <SheetDescription className="text-left text-sm text-muted-foreground">
                        Was jede Woche zur selben Zeit läuft. Was einmal
                        ausfällt, trägst du später am Kurs ein.
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={submit} className="mt-6 flex flex-col gap-6">
                    <Field label="Titel" error={errors.title}>
                        <input
                            type="text"
                            value={data.title}
                            onChange={(event) =>
                                setData('title', event.target.value)
                            }
                            placeholder="Analysis I"
                            maxLength={120}
                            autoFocus
                            className="h-12 w-full rounded-xl border border-input bg-card px-4 text-[15px] transition-colors duration-[var(--duration-press)] ease-out focus-visible:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        />
                    </Field>

                    <Field label="Art" error={errors.kind}>
                        <div className="flex flex-wrap gap-2">
                            {kinds.map((kind) => (
                                <button
                                    key={kind.value}
                                    type="button"
                                    onClick={() => setData('kind', kind.value)}
                                    aria-pressed={data.kind === kind.value}
                                    className={cn(
                                        CHOICE_TILE,
                                        'px-4 py-2 text-sm font-semibold',
                                        data.kind === kind.value
                                            ? CHOICE_TILE_ON
                                            : CHOICE_TILE_OFF,
                                    )}
                                >
                                    {kind.label}
                                </button>
                            ))}
                        </div>
                    </Field>

                    <Field label="Wochentag" error={errors.weekday}>
                        <div className="flex flex-wrap gap-2">
                            {WEEKDAYS.map((day) => (
                                <button
                                    key={day.value}
                                    type="button"
                                    onClick={() =>
                                        setData('weekday', day.value)
                                    }
                                    aria-pressed={data.weekday === day.value}
                                    aria-label={day.long}
                                    className={cn(
                                        CHOICE_TILE,
                                        'flex size-11 items-center justify-center text-sm font-semibold',
                                        data.weekday === day.value
                                            ? CHOICE_TILE_ON
                                            : CHOICE_TILE_OFF,
                                    )}
                                >
                                    {day.short}
                                </button>
                            ))}
                        </div>
                    </Field>

                    <Field
                        label="Von – bis"
                        error={errors.starts_at ?? errors.ends_at}
                    >
                        <div className="flex items-center gap-4">
                            <TimeStepper
                                value={data.starts_at}
                                onChange={(value) =>
                                    setData('starts_at', value)
                                }
                                label="Anfang"
                                size="compact"
                            />
                            <span
                                className="text-muted-foreground"
                                aria-hidden="true"
                            >
                                –
                            </span>
                            <TimeStepper
                                value={data.ends_at}
                                onChange={(value) => setData('ends_at', value)}
                                label="Ende"
                                size="compact"
                            />
                        </div>
                    </Field>

                    <Field label="Raum (optional)" error={errors.location}>
                        <input
                            type="text"
                            value={data.location}
                            onChange={(event) =>
                                setData('location', event.target.value)
                            }
                            placeholder="HS 3"
                            maxLength={60}
                            className="h-12 w-full rounded-xl border border-input bg-card px-4 text-[15px] transition-colors duration-[var(--duration-press)] ease-out focus-visible:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        />
                    </Field>

                    <div className="flex flex-col items-center gap-4">
                        <button
                            type="submit"
                            disabled={processing || data.title.trim() === ''}
                            className={PRIMARY_BUTTON}
                        >
                            {processing && <Spinner className="size-4" />}
                            {course === null
                                ? 'In den Plan eintragen'
                                : 'Änderung speichern'}
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

/**
 * Eine Zeile des Formulars: Beschriftung, Feld, Fehler.
 *
 * Der Fehler steht unter dem Feld und trägt `role="alert"` — dieselbe ruhige
 * Form wie im Verschiebe-Sheet, ohne Rot.
 */
function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="flex flex-col gap-2">
            <span className="type-eyebrow text-muted-foreground">{label}</span>
            {children}
            {error && (
                <p
                    role="alert"
                    className="rounded-xl border border-primary/25 bg-accent px-3 py-2 text-sm text-foreground"
                >
                    {error}
                </p>
            )}
        </div>
    );
}
