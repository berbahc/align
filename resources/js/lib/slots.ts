import { BREATHER_MINUTES } from '@/lib/day-grid';
import type { BusySlot, Weekday } from '@/types';

/** „17:20" → 1040. Ungültiges ergibt NaN und fällt damit aus jedem Vergleich. */
function toMinutes(time: string): number {
    const [hours = '0', minutes = '0'] = time.split(':');

    return Number(hours) * 60 + Number(minutes);
}

function toTime(minutes: number): string {
    const clamped = ((minutes % 1440) + 1440) % 1440;

    return `${String(Math.floor(clamped / 60)).padStart(2, '0')}:${String(clamped % 60).padStart(2, '0')}`;
}

/**
 * Belegt eine andere Gewohnheit den gewünschten Zeitpunkt?
 *
 * Zwei Fenster stoßen nur zusammen, wenn sie sich einen Wochentag teilen —
 * dienstags 17:00 und mittwochs 17:00 sind kein Konflikt. Gerechnet wird mit
 * derselben Viertelstunde Luft wie auf dem Server: Ein Start direkt am Ende
 * des anderen Blocks ist zu eng, und ein Ende direkt vor seinem Anfang auch.
 * Ein Fenster ohne Dauer ist ein Punkt, der nur seine Luft um sich braucht.
 *
 * Der Server weist dasselbe ab — die Oberfläche sagt es nur vorher.
 */
export function findConflict(
    time: string,
    days: Weekday[],
    slots: BusySlot[],
    durationMinutes = 0,
): BusySlot | null {
    const start = toMinutes(time);

    if (Number.isNaN(start)) {
        return null;
    }

    const end = start + durationMinutes;

    return (
        slots.find((slot) => {
            if (!slot.days.some((day) => days.includes(day))) {
                return false;
            }

            const from = toMinutes(slot.from);
            const to = toMinutes(slot.to);

            return (
                start < to + BREATHER_MINUTES && end + BREATHER_MINUTES > from
            );
        }) ?? null
    );
}

/**
 * Der nächste Zeitpunkt, an dem nichts anderes läuft.
 *
 * Springt von Fenster zu Fenster weiter, weil hinter dem einen gleich das
 * nächste liegen kann. Findet sich innerhalb eines Tages nichts, gibt es
 * nichts vorzuschlagen.
 */
export function nextFreeTime(
    time: string,
    days: Weekday[],
    slots: BusySlot[],
    durationMinutes = 0,
): string | null {
    let candidate = time;

    for (let step = 0; step < slots.length + 1; step++) {
        const conflict = findConflict(candidate, days, slots, durationMinutes);

        if (conflict === null) {
            return candidate === time ? null : candidate;
        }

        // Hinter dem Block — und hinter seiner Luft.
        candidate = toTime(toMinutes(conflict.to) + BREATHER_MINUTES);
    }

    return null;
}
