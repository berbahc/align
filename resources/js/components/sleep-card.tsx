import { Link } from '@inertiajs/react';
import { AlarmClock, ChevronRight, Moon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { INTERACTIVE_CARD } from '@/lib/interaction';
import { show as sleepShow } from '@/routes/sleep';

export interface SleepCardData {
    /** Schlafenszeit heute Abend. */
    bedtime: string;
    /** Aufstehzeit morgen früh. */
    wakeTime: string;
    /** Klingelt morgen früh der Wecker? */
    alarmEnabled: boolean;
}

/**
 * Der Rahmen des Tages auf der Übersicht — und der Weg zum Schlafplan.
 *
 * Eine Zeile, keine Bühne: Die Karte sagt, wann der Tag endet und wann der
 * nächste beginnt. Sie trackt nichts und fordert nichts — der Schlaf ist der
 * Rahmen der Gewohnheiten, nicht selbst eine.
 */
export function SleepCard({ sleep }: { sleep: SleepCardData }) {
    return (
        <Link href={sleepShow()} className={INTERACTIVE_CARD}>
            <Card className="gap-0 py-4 shadow-none transition-[border-color] duration-[var(--duration-press)] ease-out hover:border-secondary">
                <CardContent className="flex items-center gap-3 px-5">
                    <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sand text-primary">
                        <Moon
                            className="size-5"
                            strokeWidth={1.5}
                            aria-hidden="true"
                        />
                    </span>

                    <span className="min-w-0 flex-1">
                        <span className={'type-eyebrow text-muted-foreground'}>
                            Dein Rahmen
                        </span>
                        <span className="mt-0.5 block text-[15px] leading-snug">
                            <span className="font-semibold">
                                {sleep.bedtime}
                            </span>{' '}
                            <span className="text-muted-foreground">
                                Schlafen ·
                            </span>{' '}
                            <span className="font-semibold">
                                {sleep.wakeTime}
                            </span>{' '}
                            <span className="text-muted-foreground">
                                Aufstehen
                            </span>
                        </span>
                        <span className="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                            <AlarmClock
                                className="size-3.5"
                                strokeWidth={1.5}
                                aria-hidden="true"
                            />
                            {sleep.alarmEnabled
                                ? 'Wecker für morgen früh an'
                                : 'Kein Wecker für morgen früh'}
                        </span>
                    </span>

                    <ChevronRight
                        className="size-5 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                </CardContent>
            </Card>
        </Link>
    );
}
