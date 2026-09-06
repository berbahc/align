import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * Der Tag als eigenständige Beschriftung, groß geschrieben.
 *
 * `Appointment::dayLabel()` liefert „heute", „morgen", „nächsten Samstag" —
 * klein, weil dieselbe Zeile auch mitten in einem Satz steht („… mit Test2 ·
 * morgen"). Wo sie dagegen für sich steht, am Satzanfang oder auf einem Knopf,
 * gehört sie groß. Die Entscheidung fällt deshalb an der Anzeigestelle und
 * nicht auf dem Server: Er weiß nicht, wo seine Zeile landet.
 */
export function capitaliseDay(day: string): string {
    return day.charAt(0).toUpperCase() + day.slice(1);
}
