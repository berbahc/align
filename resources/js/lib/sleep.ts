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
 * Welche der gewählten Tage zu dieser Uhrzeit schlafen.
 *
 * {@see outsideSleepWindow} nennt den ersten — genug für den Satz, zu wenig
 * für den Ausweg: Wer eine 07:30-Gewohnheit täglich plant, stolpert über
 * Samstag *und* Sonntag und müsste sonst zweimal suchen, was ihn aufhält.
 */
export function asleepWeekdays(
    time: string,
    days: Weekday[],
    windows: SleepWindow[],
): Weekday[] {
    return days.filter((day) => {
        const window = windows.find((candidate) => candidate.weekday === day);

        return window !== undefined && !isAwakeAt(window, time);
    });
}

/**
 * Der Rahmen als eine Zeile — „07:00 bis 23:00".
 */
export function formatWindow(window: SleepWindow): string {
    return `${window.wakeTime} bis ${window.bedtime}`;
}

/** Minuten seit Mitternacht für „HH:MM". */
function toMinutes(time: string): number {
    const [hours = 0, minutes = 0] = time.split(':').map(Number);

    return hours * 60 + minutes;
}

/**
 * Wie lange zwischen Schlafenszeit und Aufstehen liegt, in Minuten.
 *
 * Der Rahmen läuft über Mitternacht: 23:00 bis 07:00 sind acht Stunden, nicht
 * minus sechzehn. Der Rest gegen 1440 fängt das ab und gilt auch für eine
 * Schlafenszeit nach Mitternacht (00:30 bis 07:00 sind sechseinhalb Stunden).
 */
export function sleepMinutes(wakeTime: string, bedtime: string): number {
    return (toMinutes(wakeTime) - toMinutes(bedtime) + 1440) % 1440;
}

/**
 * Dieselbe Dauer als fertige Zeile: „8 Stunden", „7,5 Stunden".
 *
 * Der Schlafplan nannte bisher nur Uhrzeiten. Wie lange jemand dabei schläft,
 * stand nirgends, obwohl es die eine Zahl ist, um die es auf dieser Seite
 * geht. Halbe Stunden mit Komma, weil Deutsch so schreibt; Viertelstunden
 * bekommen ihre Minuten, statt auf eine krumme Kommazahl gerundet zu werden.
 */
export function sleepDurationLabel(
    wakeTime: string,
    bedtime: string,
    short = false,
): string {
    const minutes = sleepMinutes(wakeTime, bedtime);
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;
    const unit = short ? 'Std' : 'Stunden';

    if (rest === 0) {
        return `${hours} ${unit}`;
    }

    if (rest === 30) {
        return `${hours},5 ${unit}`;
    }

    return `${hours} Std ${rest} Min`;
}
