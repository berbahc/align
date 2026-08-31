import type { SleepWindow, Weekday } from '@/types';

/**
 * Liegt die Uhrzeit im wachen Teil des Tages?
 *
 * Spiegelt `SleepSchedule::containsTime()` im Backend. Eine Schlafenszeit vor
 * der Aufstehzeit meint „nach Mitternacht": 07:00 bis 00:30 ist ein Rahmen,
 * der über den Tagesrand hinausreicht — dann ist wach, was **nach** dem
 * Aufstehen oder **vor** der Schlafenszeit liegt, statt dazwischen.
 */
export function isAwakeAt(window: SleepWindow, time: string): boolean {
    if (window.bedtime > window.wakeTime) {
        return time >= window.wakeTime && time <= window.bedtime;
    }

    return time >= window.wakeTime || time <= window.bedtime;
}

/**
 * Der erste gewählte Wochentag, an dem die Uhrzeit außerhalb des Rahmens läge.
 *
 * Der Server weist so eine Uhrzeit ohnehin ab — die Oberfläche soll es vorher
 * sagen können, mit demselben Ergebnis. Null heißt: alles im Rahmen.
 */
export function outsideSleepWindow(
    time: string,
    days: Weekday[],
    windows: SleepWindow[],
): SleepWindow | null {
    for (const day of days) {
        const window = windows.find((candidate) => candidate.weekday === day);

        if (window !== undefined && !isAwakeAt(window, time)) {
            return window;
        }
    }

    return null;
}

/**
 * Der Rahmen als eine Zeile — „07:00 bis 23:00".
 */
export function formatWindow(window: SleepWindow): string {
    return `${window.wakeTime} bis ${window.bedtime}`;
}
