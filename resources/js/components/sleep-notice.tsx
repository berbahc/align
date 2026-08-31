import { usePage } from '@inertiajs/react';
import { Moon, X } from 'lucide-react';
import { useState } from 'react';
import { useBedtimeNotification, useSleepStatus } from '@/hooks/use-sleep';

/**
 * Der Wann-Teil der Schlafenszeit — §1.5: benannt wird, was gilt.
 */
function timing(minutesUntil: number, bedtime: string): string {
    if (minutesUntil > 1) {
        return `In ${minutesUntil} Minuten ist Schlafenszeit.`;
    }

    if (minutesUntil >= 0) {
        return 'Gleich ist Schlafenszeit.';
    }

    return `Schlafenszeit war um ${bedtime}.`;
}

/**
 * Der Hinweis vor der Schlafenszeit — in der App, auf jeder Seite.
 *
 * Dieselbe Bauart wie der Gewohnheits-Hinweis: kein Modal, keine Forderung.
 * Der Tag kündigt sein Ende an, mehr nicht — abhaken lässt sich hier nichts,
 * weil Schlafen keine Gewohnheit ist, sondern der Rahmen.
 */
export function SleepNotice() {
    const { sleep } = usePage().props;
    const { bedtime } = useSleepStatus(sleep ?? null);

    useBedtimeNotification(sleep ?? null);

    // Weggeklickt heißt weggeklickt — bis zur nächsten Schlafenszeit. Der
    // Zustand lebt nur im Speicher: Ein neuer Abend ist ein neuer Hinweis.
    const [hiddenFor, setHiddenFor] = useState<string | null>(null);

    if (bedtime === null || hiddenFor === bedtime.bedtime) {
        return null;
    }

    return (
        <div
            role="status"
            className="mx-auto mt-4 flex w-full max-w-3xl flex-col gap-2"
        >
            <div className="flex items-center gap-3 rounded-xl border border-primary/25 bg-sand px-4 py-3">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <Moon
                        className="size-5"
                        strokeWidth={1.5}
                        aria-hidden="true"
                    />
                </span>

                <span className="min-w-0 flex-1">
                    <span className="block text-[15px] leading-snug font-semibold">
                        {timing(bedtime.minutesUntil, bedtime.bedtime)}
                    </span>
                    <span className="mt-0.5 block text-xs text-muted-foreground">
                        Zeit, den Tag loszulassen.
                    </span>
                </span>

                <button
                    type="button"
                    onClick={() => setHiddenFor(bedtime.bedtime)}
                    className="-mr-2 flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full text-muted-foreground transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                >
                    <X className="size-4" aria-hidden="true" />
                    <span className="sr-only">
                        Hinweis zur Schlafenszeit ausblenden
                    </span>
                </button>
            </div>
        </div>
    );
}
