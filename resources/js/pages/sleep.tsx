import { Head, useForm } from '@inertiajs/react';
import { AlarmClock } from 'lucide-react';
import { useState } from 'react';
import { FrameCarryList } from '@/components/frame-carry-list';
import InputError from '@/components/input-error';
import { SleepWeek } from '@/components/sleep-week';
import { TimeStepper } from '@/components/time-stepper';
import { Card, CardContent } from '@/components/ui/card';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { ToggleSwitch } from '@/components/ui/toggle-switch';
import { isQuiet, useFrameCarry } from '@/hooks/use-frame-carry';
import { BOTTOM_SHEET, PRIMARY_BUTTON, QUIET_LINK } from '@/lib/interaction';
import { sleepDurationLabel, sleepMinutes } from '@/lib/sleep';
import { dashboard } from '@/routes';
import { preview, update } from '@/routes/sleep';
import type { SleepWindow, Weekday } from '@/types';

const SHEET_ACTION =
    'inline-flex h-12 flex-1 cursor-pointer items-center justify-center rounded-xl px-4 text-center text-[15px] font-semibold transition-[background-color,scale] duration-[var(--duration-press)] ease-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:pointer-events-none disabled:opacity-50 motion-safe:active:scale-[0.97]';

/** Die Kurzform für die Zeilen im Nachtband, wo der volle Name nicht passt. */
const WEEKDAY_SHORT: Record<Weekday, string> = {
    1: 'Mo',
    2: 'Di',
    3: 'Mi',
    4: 'Do',
    5: 'Fr',
    6: 'Sa',
    7: 'So',
};

const WEEKDAY_NAMES: Record<Weekday, string> = {
    1: 'Montag',
    2: 'Dienstag',
    3: 'Mittwoch',
    4: 'Donnerstag',
    5: 'Freitag',
    6: 'Samstag',
    7: 'Sonntag',
};

interface SleepDayForm {
    weekday: Weekday;
    wake_time: string;
    bedtime: string;
    alarm_enabled: boolean;
}

interface SleepProps {
    windows: SleepWindow[];
    bedtimeReminderEnabled: boolean;
    reminderLeadMinutes: number;
    /** Der Tag, mit dem der Editor aufgeht — vom Kalender mitgegeben. */
    selectedWeekday: Weekday | null;
}

/**
 * Der Schlafplan — Aufsteh- und Schlafenszeit für jeden Wochentag.
 *
 * Kein Tracking, keine Serie, keine Quote: Der Schlaf ist der Rahmen, in dem
 * die Gewohnheiten stattfinden, nicht selbst eine. Was hier steht, begrenzt,
 * wann sich Gewohnheiten planen lassen; die Erinnerung vor der Schlafenszeit
 * beendet den Tag, der Wecker beginnt ihn.
 */
export default function Sleep({
    windows,
    bedtimeReminderEnabled,
    reminderLeadMinutes,
    selectedWeekday,
}: SleepProps) {
    const { data, setData, put, transform, processing, errors, isDirty } =
        useForm({
            days: windows.map((window): SleepDayForm => ({
                weekday: window.weekday,
                wake_time: window.wakeTime,
                bedtime: window.bedtime,
                alarm_enabled: window.alarmEnabled,
            })),
            bedtime_reminder_enabled: bedtimeReminderEnabled,
        });

    /**
     * Welcher Tag gerade im Editor steht.
     *
     * Nie keiner: Der Editor ist die eine Stelle, an der Zeiten geändert
     * werden, und ein leerer Platz daneben wäre eine Lücke ohne Grund. Montag
     * ist der Anfang der Woche und damit der Anfang des Plans.
     *
     * Es sei denn, jemand kommt mit einem Tag im Sinn: Die Aufsteh- und
     * Schlafensmarken im Kalender führen hierher und nennen den Tag, auf dem
     * man gerade stand. Ohne das landete man beim Montag und müsste den Tag
     * suchen, den man eben noch vor sich hatte.
     */
    const [selected, setSelected] = useState<Weekday>(selectedWeekday ?? 1);

    function updateDay(weekday: Weekday, patch: Partial<SleepDayForm>) {
        setData(
            'days',
            data.days.map((day) =>
                day.weekday === weekday ? { ...day, ...patch } : day,
            ),
        );
    }

    /**
     * Die Zeiten eines Tages auf die ganze Woche legen.
     *
     * Der häufigste Fall ist ein Rhythmus mit einer Ausnahme, nicht sieben
     * verschiedene Tage — wer Montag eingestellt hat, soll nicht sechsmal
     * dasselbe steppen müssen. Die Wecker-Schalter bleiben, wie sie sind:
     * Ob der Wecker sonntags klingelt, ist eine eigene Entscheidung.
     */
    function applyToAll(source: SleepDayForm) {
        setData(
            'days',
            data.days.map((day) => ({
                ...day,
                wake_time: source.wake_time,
                bedtime: source.bedtime,
            })),
        );
    }

    /**
     * Was der neue Rahmen mit den Gewohnheiten macht — gefragt, bevor
     * gespeichert wird.
     */
    const carry = useFrameCarry({});
    const [asking, setAsking] = useState(false);

    /**
     * Speichern, mit oder ohne die Gewohnheiten im Schlepptau.
     *
     * `transform` statt `setData`: Das Mitziehen ist eine Antwort auf die
     * Vorschau und kein Feld des Formulars — es steht in keinem Eingabefeld
     * und soll auch nirgends stehen bleiben.
     */
    function save(carryHabits: boolean) {
        setAsking(false);
        carry.forget();

        transform((current) => ({ ...current, carry_habits: carryHabits }));

        put(update.url(), { preserveScroll: true });
    }

    /**
     * Erst fragen, dann speichern.
     *
     * Hat der neue Rahmen keine Folgen — und das ist der Normalfall —, wird
     * direkt gespeichert: Ein Dialog, der „nichts passiert" meldet, ist ein
     * Klick ohne Auskunft. Erst wenn etwas mitziehen würde, kommt die Frage.
     *
     * Fällt die Vorschau aus, wird ebenfalls gespeichert. Der Schlafplan
     * gehört dem Nutzer, und ein Ausfall auf unserer Seite darf ihn nicht
     * aufhalten — dann bleiben die Gewohnheiten eben liegen, wo sie liegen.
     */
    function submit(event: React.FormEvent) {
        event.preventDefault();
        setAsking(true);

        void carry.ask(preview.url(), { ...data }, (result) => {
            if (result === null || isQuiet(result)) {
                save(false);
            }
        });
    }

    /** Steht etwas zur Entscheidung an? */
    const decision =
        asking && carry.preview !== null && !isQuiet(carry.preview)
            ? carry.preview
            : null;

    /**
     * Wie lange die Woche Schlaf lässt, als ein Satz.
     *
     * Die Seite heißt „Schlaf & Rhythmus" und nannte bisher nur Uhrzeiten. Wie
     * viel Schlaf dabei herauskommt, stand nirgends, obwohl es die eine Zahl
     * ist, um die es hier geht. Sind alle Tage gleich, ist es eine Zahl; sonst
     * eine Spanne, und die ist der Rhythmus, den der Titel verspricht.
     */
    const durations = data.days.map((day) =>
        sleepMinutes(day.wake_time, day.bedtime),
    );
    const shortest = data.days[durations.indexOf(Math.min(...durations))];
    const longest = data.days[durations.indexOf(Math.max(...durations))];
    const sameEveryDay = Math.min(...durations) === Math.max(...durations);

    // Der erste Fehler aus den verschachtelten Tages-Feldern — Inertia legt
    // sie unter Schlüsseln wie „days.2.bedtime" ab.
    const dayError = Object.entries(errors).find(([key]) =>
        key.startsWith('days.'),
    )?.[1];

    const selectedDay =
        data.days.find((day) => day.weekday === selected) ?? data.days[0];

    return (
        <>
            <Head title="Schlaf" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 sm:p-6">
                <header>
                    {/* Den Titel zeigt auf dem Telefon schon die Leiste oben.
                        Der Satz darunter ist neu und gilt überall. */}
                    <h1 className="type-title text-primary max-md:hidden">
                        Schlaf & Rhythmus
                    </h1>

                    {shortest !== undefined && longest !== undefined && (
                        <p className="text-sm text-muted-foreground md:mt-1">
                            {sameEveryDay ? (
                                <>
                                    Jeden Tag{' '}
                                    <span className="font-semibold text-foreground">
                                        {sleepDurationLabel(
                                            shortest.wake_time,
                                            shortest.bedtime,
                                        )}
                                    </span>{' '}
                                    Schlaf
                                </>
                            ) : (
                                <>
                                    <span className="font-semibold text-foreground">
                                        {sleepDurationLabel(
                                            shortest.wake_time,
                                            shortest.bedtime,
                                        )}
                                    </span>{' '}
                                    bis{' '}
                                    <span className="font-semibold text-foreground">
                                        {sleepDurationLabel(
                                            longest.wake_time,
                                            longest.bedtime,
                                        )}
                                    </span>{' '}
                                    Schlaf, je nach Tag
                                </>
                            )}
                        </p>
                    )}
                </header>

                {/* Links die Woche als Ganzes, rechts der eine Tag, den man
                    gerade ändert. Ein Schlafplan ist Woche mal Uhrzeit, also
                    zweidimensional; als Stapel gleicher Karten ging die zweite
                    Achse verloren, und sieben Zeilen mit „07:00" darin zeigten
                    nicht, dass das Wochenende später anfängt. Auf dem Handy
                    stehen beide untereinander, die Woche zuerst. */}
                <form
                    onSubmit={submit}
                    className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start"
                >
                    <div className="flex flex-col gap-4">
                        <Card className="gap-0 border-transparent py-5 shadow-[var(--shadow-lift)]">
                            <CardContent className="px-3 sm:px-4">
                                <SleepWeek
                                    days={data.days.map((day) => ({
                                        weekday: day.weekday,
                                        name: WEEKDAY_NAMES[day.weekday],
                                        short: WEEKDAY_SHORT[day.weekday],
                                        wakeTime: day.wake_time,
                                        bedtime: day.bedtime,
                                        alarmEnabled: day.alarm_enabled,
                                    }))}
                                    selected={selected}
                                    onSelect={setSelected}
                                    onToggleAlarm={(weekday, enabled) =>
                                        updateDay(weekday, {
                                            alarm_enabled: enabled,
                                        })
                                    }
                                />
                            </CardContent>
                        </Card>

                        {/* Nur der gefüllte Teil braucht ein Wort. Was nicht
                            Schlaf ist, ist wach, und das musste niemandem
                            gesagt werden. */}
                        <p className="flex flex-wrap items-center gap-x-4 gap-y-1 px-1 text-xs text-muted-foreground">
                            <span className="flex items-center gap-1.5">
                                <span className="h-2.5 w-5 shrink-0 rounded-full bg-primary" />
                                Schlaf
                            </span>
                            <span>
                                Der Wecker klingelt nur bei geöffneter App.
                            </span>
                        </p>
                    </div>

                    <aside className="flex flex-col gap-4">
                        {selectedDay !== undefined && (
                            <Card className="gap-0 py-5 shadow-none">
                                <CardContent className="flex flex-col gap-4 px-4">
                                    {/* Der Name und die Dauer: Auf dem Handy
                                        trägt die Zeile im Band keine Zahl
                                        mehr, damit der Balken Platz hat. Hier
                                        steht sie, für den Tag, den man gerade
                                        anfasst. */}
                                    <p className="flex flex-wrap items-baseline justify-between gap-x-3">
                                        <span className="type-eyebrow text-muted-foreground">
                                            {WEEKDAY_NAMES[selectedDay.weekday]}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            <span className="font-semibold text-foreground tabular-nums">
                                                {sleepDurationLabel(
                                                    selectedDay.wake_time,
                                                    selectedDay.bedtime,
                                                )}
                                            </span>{' '}
                                            Schlaf
                                        </span>
                                    </p>

                                    <div className="grid grid-cols-2 gap-3">
                                        <div className="flex flex-col items-center gap-2 rounded-xl bg-sand/50 p-3">
                                            <p className="type-eyebrow text-muted-foreground">
                                                Schlafen
                                            </p>
                                            <TimeStepper
                                                value={selectedDay.bedtime}
                                                onChange={(value) =>
                                                    updateDay(
                                                        selectedDay.weekday,
                                                        { bedtime: value },
                                                    )
                                                }
                                                label={`Schlafenszeit am ${WEEKDAY_NAMES[selectedDay.weekday]}`}
                                                size="compact"
                                            />
                                        </div>
                                        <div className="flex flex-col items-center gap-2 rounded-xl bg-sand/50 p-3">
                                            <p className="type-eyebrow text-muted-foreground">
                                                Aufstehen
                                            </p>
                                            <TimeStepper
                                                value={selectedDay.wake_time}
                                                onChange={(value) =>
                                                    updateDay(
                                                        selectedDay.weekday,
                                                        { wake_time: value },
                                                    )
                                                }
                                                label={`Aufstehzeit am ${WEEKDAY_NAMES[selectedDay.weekday]}`}
                                                size="compact"
                                            />
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-between gap-3 border-t border-border pt-4">
                                        <span className="flex min-w-0 items-center gap-2 text-sm text-muted-foreground">
                                            <AlarmClock
                                                className="size-4 shrink-0"
                                                strokeWidth={1.5}
                                                aria-hidden="true"
                                            />
                                            Wecker
                                        </span>
                                        <ToggleSwitch
                                            checked={selectedDay.alarm_enabled}
                                            onChange={(enabled) =>
                                                updateDay(selectedDay.weekday, {
                                                    alarm_enabled: enabled,
                                                })
                                            }
                                            label={`Wecker am ${WEEKDAY_NAMES[selectedDay.weekday]}`}
                                        />
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() => applyToAll(selectedDay)}
                                        className={`${QUIET_LINK} self-start text-xs`}
                                    >
                                        Diese Zeiten für alle Tage
                                    </button>
                                </CardContent>
                            </Card>
                        )}

                        <Card className="gap-0 py-4 shadow-none">
                            <CardContent className="flex items-center justify-between gap-3 px-4">
                                <div className="min-w-0">
                                    <p className="text-sm font-semibold">
                                        Erinnerung {reminderLeadMinutes} Min
                                        vorher
                                    </p>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        Nur bei geöffneter App.
                                    </p>
                                </div>
                                <ToggleSwitch
                                    checked={data.bedtime_reminder_enabled}
                                    onChange={(enabled) =>
                                        setData(
                                            'bedtime_reminder_enabled',
                                            enabled,
                                        )
                                    }
                                    label="Erinnerung vor der Schlafenszeit"
                                />
                            </CardContent>
                        </Card>

                        <InputError message={dayError} />
                        <InputError message={errors.bedtime_reminder_enabled} />

                        <button
                            type="submit"
                            disabled={processing || carry.asking || !isDirty}
                            className={PRIMARY_BUTTON}
                        >
                            {(processing || carry.asking) && (
                                <Spinner className="size-4" />
                            )}
                            Schlafplan speichern
                        </button>
                    </aside>
                </form>
            </div>

            {/* Erst zeigen, dann übernehmen: Der Rahmen ist der Rahmen von
                allem, und ihn zu verschieben, ohne zu sagen, was mitgeht,
                hieße den Tag hinter dem Rücken des Nutzers umzuräumen. */}
            <Sheet
                open={decision !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setAsking(false);
                        carry.forget();
                    }
                }}
            >
                <SheetContent side="bottom" className={BOTTOM_SHEET}>
                    <SheetHeader className="gap-2 p-0">
                        <SheetTitle className="type-eyebrow text-left text-primary">
                            Dein Tag beginnt jetzt anders
                        </SheetTitle>
                        <SheetDescription className="text-left text-[15px] leading-relaxed text-foreground">
                            {(decision?.moves.length ?? 0) > 0
                                ? 'Diese festen Uhrzeiten liegen sonst außerhalb deines Tages. Sie können um dieselbe Zeit mitrücken.'
                                : 'Diese festen Uhrzeiten liegen jetzt außerhalb deines Tages.'}
                        </SheetDescription>
                    </SheetHeader>

                    <div className="mt-5">
                        <FrameCarryList
                            moves={decision?.moves ?? []}
                            blocked={decision?.blocked ?? []}
                        />
                    </div>

                    <div className="mt-5 flex flex-col gap-3 sm:flex-row">
                        {(decision?.moves.length ?? 0) > 0 && (
                            <button
                                type="button"
                                onClick={() => save(true)}
                                className={`${SHEET_ACTION} bg-primary text-primary-foreground hover:bg-primary/90`}
                            >
                                Mitnehmen und speichern
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={() => save(false)}
                            className={`${SHEET_ACTION} border border-primary text-primary hover:bg-accent`}
                        >
                            Nur den Schlafplan speichern
                        </button>
                    </div>
                </SheetContent>
            </Sheet>
        </>
    );
}

Sleep.layout = {
    breadcrumbs: [
        { title: 'Übersicht', href: dashboard() },
        { title: 'Schlaf', href: '' },
    ],
};
