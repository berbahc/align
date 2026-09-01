import { Head, useForm } from '@inertiajs/react';
import { AlarmClock, ChevronDown } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { TimeStepper } from '@/components/time-stepper';
import { Card, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { ToggleSwitch } from '@/components/ui/toggle-switch';
import { PRIMARY_BUTTON, QUIET_LINK } from '@/lib/interaction';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { update } from '@/routes/sleep';
import type { SleepWindow, Weekday } from '@/types';

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
}: SleepProps) {
    const { data, setData, put, processing, errors, isDirty } = useForm({
        days: windows.map((window): SleepDayForm => ({
            weekday: window.weekday,
            wake_time: window.wakeTime,
            bedtime: window.bedtime,
            alarm_enabled: window.alarmEnabled,
        })),
        bedtime_reminder_enabled: bedtimeReminderEnabled,
    });

    /** Welcher Wochentag gerade aufgeklappt ist; null heißt keiner. */
    const [expanded, setExpanded] = useState<Weekday | null>(null);

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

    function submit(event: React.FormEvent) {
        event.preventDefault();
        put(update.url(), { preserveScroll: true });
    }

    // Der erste Fehler aus den verschachtelten Tages-Feldern — Inertia legt
    // sie unter Schlüsseln wie „days.2.bedtime" ab.
    const dayError = Object.entries(errors).find(([key]) =>
        key.startsWith('days.'),
    )?.[1];

    return (
        <>
            <Head title="Schlaf" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
                <header>
                    <h1 className="type-title text-primary">
                        Schlaf & Rhythmus
                    </h1>
                </header>

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <Card>
                        <CardContent className="flex items-center justify-between gap-4">
                            <div className="min-w-0">
                                <p className="text-[15px] font-semibold">
                                    Erinnerung {reminderLeadMinutes} Min vor der
                                    Schlafenszeit
                                </p>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    Nur bei geöffneter App.
                                </p>
                            </div>
                            <ToggleSwitch
                                checked={data.bedtime_reminder_enabled}
                                onChange={(enabled) =>
                                    setData('bedtime_reminder_enabled', enabled)
                                }
                                label="Erinnerung vor der Schlafenszeit"
                            />
                        </CardContent>
                    </Card>

                    <section aria-label="Zeiten je Wochentag">
                        {/* Die eine Grenze, die man kennen muss — dort, wo die
                            Wecker stehen, nicht als Fußnote am Seitenende. */}
                        <p className="mb-2 text-xs text-muted-foreground">
                            Der Wecker klingelt nur bei geöffneter App.
                        </p>
                        <ul className="flex flex-col gap-2">
                            {data.days.map((day) => {
                                const isOpen = expanded === day.weekday;

                                return (
                                    <li key={day.weekday}>
                                        <Card
                                            className={cn(
                                                'gap-0 py-0 transition-[box-shadow,border-color] duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                                // Der offene Tag liegt höher als
                                                // die geschlossenen: Höhe trägt
                                                // die Hierarchie, nicht Farbe
                                                // (Apple §12).
                                                isOpen
                                                    ? 'shadow-[var(--shadow-lift)]'
                                                    : 'shadow-none',
                                            )}
                                        >
                                            <CardContent className="px-0">
                                                <div className="flex items-center gap-3 px-4 py-3">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setExpanded(
                                                                isOpen
                                                                    ? null
                                                                    : day.weekday,
                                                            )
                                                        }
                                                        aria-expanded={isOpen}
                                                        className="flex min-w-0 flex-1 cursor-pointer items-center gap-3 rounded-lg text-left transition-[scale] duration-[var(--duration-press)] ease-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.99]"
                                                    >
                                                        <span className="w-24 shrink-0 text-[15px] font-semibold">
                                                            {
                                                                WEEKDAY_NAMES[
                                                                    day.weekday
                                                                ]
                                                            }
                                                        </span>
                                                        <span className="text-sm text-muted-foreground tabular-nums">
                                                            {day.wake_time} –{' '}
                                                            {day.bedtime}
                                                        </span>
                                                        <ChevronDown
                                                            className={cn(
                                                                'ml-auto size-4 shrink-0 text-muted-foreground transition-transform duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                                                isOpen &&
                                                                    'rotate-180',
                                                            )}
                                                            aria-hidden="true"
                                                        />
                                                    </button>

                                                    {/* Der Wecker sitzt an der
                                                        Zeile, nicht am Konto:
                                                        Montag ist eine andere
                                                        Entscheidung als
                                                        Sonntag. */}
                                                    <span className="flex shrink-0 items-center gap-1.5">
                                                        <AlarmClock
                                                            className={cn(
                                                                'size-4',
                                                                day.alarm_enabled
                                                                    ? 'text-primary'
                                                                    : 'text-muted-foreground/50',
                                                            )}
                                                            strokeWidth={1.5}
                                                            aria-hidden="true"
                                                        />
                                                        <ToggleSwitch
                                                            checked={
                                                                day.alarm_enabled
                                                            }
                                                            onChange={(
                                                                enabled,
                                                            ) =>
                                                                updateDay(
                                                                    day.weekday,
                                                                    {
                                                                        alarm_enabled:
                                                                            enabled,
                                                                    },
                                                                )
                                                            }
                                                            label={`Wecker am ${WEEKDAY_NAMES[day.weekday]}`}
                                                        />
                                                    </span>
                                                </div>

                                                {isOpen && (
                                                    <div className="flex flex-col gap-4 border-t border-border px-4 py-4 motion-safe:animate-in motion-safe:duration-[var(--duration-fluid)] motion-safe:ease-[var(--ease-fluid)] motion-safe:fade-in motion-safe:slide-in-from-top-1">
                                                        <div className="grid grid-cols-2 gap-3">
                                                            <div className="flex flex-col items-center gap-2 rounded-xl bg-sand/50 p-3">
                                                                <p
                                                                    className={
                                                                        'type-eyebrow text-muted-foreground'
                                                                    }
                                                                >
                                                                    Aufstehen
                                                                </p>
                                                                <TimeStepper
                                                                    value={
                                                                        day.wake_time
                                                                    }
                                                                    onChange={(
                                                                        value,
                                                                    ) =>
                                                                        updateDay(
                                                                            day.weekday,
                                                                            {
                                                                                wake_time:
                                                                                    value,
                                                                            },
                                                                        )
                                                                    }
                                                                    label={`Aufstehzeit am ${WEEKDAY_NAMES[day.weekday]}`}
                                                                    size="compact"
                                                                />
                                                            </div>
                                                            <div className="flex flex-col items-center gap-2 rounded-xl bg-sand/50 p-3">
                                                                <p
                                                                    className={
                                                                        'type-eyebrow text-muted-foreground'
                                                                    }
                                                                >
                                                                    Schlafen
                                                                </p>
                                                                <TimeStepper
                                                                    value={
                                                                        day.bedtime
                                                                    }
                                                                    onChange={(
                                                                        value,
                                                                    ) =>
                                                                        updateDay(
                                                                            day.weekday,
                                                                            {
                                                                                bedtime:
                                                                                    value,
                                                                            },
                                                                        )
                                                                    }
                                                                    label={`Schlafenszeit am ${WEEKDAY_NAMES[day.weekday]}`}
                                                                    size="compact"
                                                                />
                                                            </div>
                                                        </div>

                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                applyToAll(day)
                                                            }
                                                            className={`${QUIET_LINK} self-start text-xs`}
                                                        >
                                                            Für alle Tage
                                                            übernehmen
                                                        </button>
                                                    </div>
                                                )}
                                            </CardContent>
                                        </Card>
                                    </li>
                                );
                            })}
                        </ul>
                    </section>

                    <InputError message={dayError} />
                    <InputError message={errors.bedtime_reminder_enabled} />

                    <button
                        type="submit"
                        disabled={processing || !isDirty}
                        className={PRIMARY_BUTTON}
                    >
                        {processing && <Spinner className="size-4" />}
                        Schlafplan speichern
                    </button>
                </form>
            </div>
        </>
    );
}

Sleep.layout = {
    breadcrumbs: [
        { title: 'Übersicht', href: dashboard() },
        { title: 'Schlaf', href: '' },
    ],
};
