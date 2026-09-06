import { AlarmClock } from 'lucide-react';
import { useState } from 'react';
import { createRoot } from 'react-dom/client';
import { SleepWeek } from '@/components/sleep-week';
import { TimeStepper } from '@/components/time-stepper';
import { Card, CardContent } from '@/components/ui/card';
import { ToggleSwitch } from '@/components/ui/toggle-switch';
import { PRIMARY_BUTTON, QUIET_LINK } from '@/lib/interaction';
import { sleepDurationLabel } from '@/lib/sleep';
import type { Weekday } from '@/types';

const NAMES: Record<number, string> = {
    1: 'Montag', 2: 'Dienstag', 3: 'Mittwoch', 4: 'Donnerstag',
    5: 'Freitag', 6: 'Samstag', 7: 'Sonntag',
};
const SHORT: Record<number, string> = {
    1: 'Mo', 2: 'Di', 3: 'Mi', 4: 'Do', 5: 'Fr', 6: 'Sa', 7: 'So',
};

function Preview() {
    const [days, setDays] = useState(
        [1, 2, 3, 4, 5, 6, 7].map((weekday) => ({
            weekday: weekday as Weekday,
            name: NAMES[weekday]!,
            short: SHORT[weekday]!,
            wakeTime: weekday >= 6 ? '09:30' : '07:00',
            bedtime: weekday >= 5 ? '00:30' : '23:00',
            alarmEnabled: weekday <= 5,
        })),
    );
    const [selected, setSelected] = useState<Weekday>(6);
    const day = days.find((d) => d.weekday === selected)!;
    const patch = (weekday: Weekday, next: Partial<(typeof days)[number]>) =>
        setDays(days.map((d) => (d.weekday === weekday ? { ...d, ...next } : d)));

    return (
        <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 sm:p-6">
            <header>
                <h1 className="type-title text-primary max-md:hidden">Schlaf &amp; Rhythmus</h1>
                <p className="text-sm text-muted-foreground md:mt-1">
                    <span className="font-semibold text-foreground">6,5 Stunden</span> bis{' '}
                    <span className="font-semibold text-foreground">9 Stunden</span> Schlaf, je nach Tag
                </p>
            </header>

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
                <div className="flex flex-col gap-4">
                    <Card className="gap-0 border-transparent py-5 shadow-[var(--shadow-lift)]">
                        <CardContent className="px-3 sm:px-4">
                            <SleepWeek days={days} selected={selected} onSelect={setSelected}
                                onToggleAlarm={(w, e) => patch(w, { alarmEnabled: e })} />
                        </CardContent>
                    </Card>
                    <p className="flex flex-wrap items-center gap-x-4 gap-y-1 px-1 text-xs text-muted-foreground">
                        <span className="flex items-center gap-1.5">
                            <span className="h-2.5 w-5 shrink-0 rounded-full bg-primary" />Schlaf
                        </span>
                        <span>Der Wecker klingelt nur bei geöffneter App.</span>
                    </p>
                </div>

                <aside className="flex flex-col gap-4">
                    <Card className="gap-0 py-5 shadow-none">
                        <CardContent className="flex flex-col gap-4 px-4">
                            <p className="flex flex-wrap items-baseline justify-between gap-x-3">
                                <span className="type-eyebrow text-muted-foreground">{day.name}</span>
                                <span className="text-xs text-muted-foreground">
                                    <span className="font-semibold text-foreground tabular-nums">
                                        {sleepDurationLabel(day.wakeTime, day.bedtime)}
                                    </span>{' '}Schlaf
                                </span>
                            </p>
                            <div className="grid grid-cols-2 gap-3">
                                <div className="flex flex-col items-center gap-2 rounded-xl bg-sand/50 p-3">
                                    <p className="type-eyebrow text-muted-foreground">Schlafen</p>
                                    <TimeStepper value={day.bedtime} size="compact" label="Schlafen"
                                        onChange={(v) => patch(day.weekday, { bedtime: v })} />
                                </div>
                                <div className="flex flex-col items-center gap-2 rounded-xl bg-sand/50 p-3">
                                    <p className="type-eyebrow text-muted-foreground">Aufstehen</p>
                                    <TimeStepper value={day.wakeTime} size="compact" label="Aufstehen"
                                        onChange={(v) => patch(day.weekday, { wakeTime: v })} />
                                </div>
                            </div>
                            <div className="flex items-center justify-between gap-3 border-t border-border pt-4">
                                <span className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <AlarmClock className="size-4" strokeWidth={1.5} />Wecker
                                </span>
                                <ToggleSwitch checked={day.alarmEnabled} label="Wecker"
                                    onChange={(e) => patch(day.weekday, { alarmEnabled: e })} />
                            </div>
                            <span className={`${QUIET_LINK} self-start text-xs`}>Diese Zeiten für alle Tage</span>
                        </CardContent>
                    </Card>
                    <Card className="gap-0 py-4 shadow-none">
                        <CardContent className="flex items-center justify-between gap-3 px-4">
                            <div className="min-w-0">
                                <p className="text-sm font-semibold">Erinnerung 20 Min vorher</p>
                                <p className="mt-0.5 text-xs text-muted-foreground">Nur bei geöffneter App.</p>
                            </div>
                            <ToggleSwitch checked onChange={() => {}} label="Erinnerung" />
                        </CardContent>
                    </Card>
                    <button type="button" className={PRIMARY_BUTTON}>Schlafplan speichern</button>
                </aside>
            </div>
        </div>
    );
}

createRoot(document.getElementById('root')!).render(<Preview />);
