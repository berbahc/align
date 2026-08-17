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
 * dienstags 17:00 und mittwochs 17:00 sind kein Konflikt. Ein Fenster ohne
 * Dauer ist ein Punkt: Es kollidiert nur mit einem Start zur selben Minute,
 * denn wie lange es dauert, weiß niemand.
 *
 * Das Ergebnis ist ein Hinweis, keine Sperre. Die App weist nichts ab, was
 * jemand bewusst so will — sie sagt nur, was sie sieht.
 */
export function findConflict(
    time: string,
    days: Weekday[],
    slots: BusySlot[],
): BusySlot | null {
    const start = toMinutes(time);

    if (Number.isNaN(start)) {
        return null;
    }

    return (
        slots.find((slot) => {
            if (!slot.days.some((day) => days.includes(day))) {
                return false;
            }

            const from = toMinutes(slot.from);
            const to = toMinutes(slot.to);

            return from === to ? start === from : start >= from && start < to;
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
): string | null {
    let candidate = time;

    for (let step = 0; step < slots.length + 1; step++) {
        const conflict = findConflict(candidate, days, slots);

        if (conflict === null) {
            return candidate === time ? null : candidate;
        }

        const next = toMinutes(conflict.to);

        // Ein Punkt-Fenster hat kein Ende, hinter das sich rücken ließe.
        if (next === toMinutes(conflict.from)) {
            return null;
        }

        candidate = toTime(next);
    }

    return null;
}
