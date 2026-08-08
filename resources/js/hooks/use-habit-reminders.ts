import { useEffect } from 'react';
import type { HabitReminder, Weekday } from '@/types';

/** Vorlaufzeit der Erinnerung in Minuten. */
const LEAD_MINUTES = 10;

/**
 * Prüfintervall. Bewusst ein kurzer Takt statt eines langen `setTimeout`:
 * Browser drosseln Zeitgeber in Hintergrund-Tabs stark, und ein schlafender
 * Rechner lässt lange Timer verfallen. Ein Tick alle 30 Sekunden übersteht
 * beides und trifft die Minute zuverlässig genug.
 */
const TICK_MS = 30_000;

/** Toleranz nach hinten: eine knapp verpasste Minute wird noch nachgeholt. */
const GRACE_MINUTES = 2;

const STORAGE_KEY = 'align:reminders-fired';

/**
 * Fordert die Benachrichtigungserlaubnis an.
 *
 * Gehört bewusst nicht in den Hook: der Systemdialog darf nur als Folge einer
 * bewussten Handlung erscheinen. Wird er beim bloßen Seitenaufruf gezeigt und
 * weggeklickt, ist die Erlaubnis dauerhaft verweigert und lässt sich nur noch
 * in den Browsereinstellungen zurückholen.
 */
export async function requestReminderPermission(): Promise<boolean> {
    if (!('Notification' in window)) {
        return false;
    }

    if (Notification.permission === 'granted') {
        return true;
    }

    if (Notification.permission === 'denied') {
        return false;
    }

    return (await Notification.requestPermission()) === 'granted';
}

function today(): string {
    return new Date().toLocaleDateString('sv-SE');
}

/**
 * Bereits ausgelöste Erinnerungen des heutigen Tages.
 *
 * Im `localStorage`, weil ein Seitenwechsel die Komponente neu aufbaut — ohne
 * dieses Gedächtnis würde jede Navigation die Erinnerung erneut auslösen.
 */
function readFired(): Set<number> {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return new Set();
        }

        const parsed: unknown = JSON.parse(raw);

        if (
            typeof parsed !== 'object' ||
            parsed === null ||
            (parsed as { date?: unknown }).date !== today()
        ) {
            return new Set();
        }

        return new Set((parsed as { ids?: number[] }).ids ?? []);
    } catch {
        // Ein unlesbarer Eintrag darf die Erinnerungen nicht lahmlegen.
        return new Set();
    }
}

function writeFired(ids: Set<number>): void {
    try {
        window.localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({ date: today(), ids: [...ids] }),
        );
    } catch {
        // Privater Modus oder volles Kontingent — die Erinnerung selbst
        // funktioniert weiter, sie kann sich nur nichts merken.
    }
}

/** Minuten seit Mitternacht für „HH:MM". */
function toMinutes(time: string): number {
    const [hours = 0, minutes = 0] = time.split(':').map(Number);

    return hours * 60 + minutes;
}

/**
 * Meldet sich zehn Minuten vor einer Gewohnheit mit fester Uhrzeit.
 *
 * Läuft nur, solange die App in einem Tab offen ist — ohne Service Worker
 * gibt es keine Zustellung im Hintergrund. Das ist die bewusste Grenze
 * dieser Umsetzung.
 */
export function useHabitReminders(reminders: HabitReminder[]): void {
    useEffect(() => {
        if (reminders.length === 0 || !('Notification' in window)) {
            return;
        }

        function check() {
            if (Notification.permission !== 'granted') {
                return;
            }

            const now = new Date();
            const weekday = (now.getDay() === 0 ? 7 : now.getDay()) as Weekday;
            const nowMinutes = now.getHours() * 60 + now.getMinutes();
            const fired = readFired();
            let changed = false;

            for (const reminder of reminders) {
                if (
                    reminder.completedToday ||
                    fired.has(reminder.id) ||
                    !reminder.scheduledDays.includes(weekday)
                ) {
                    continue;
                }

                const dueAt = toMinutes(reminder.scheduledTime) - LEAD_MINUTES;

                if (nowMinutes < dueAt || nowMinutes > dueAt + GRACE_MINUTES) {
                    continue;
                }

                // Ton nach §8: benennt, was ansteht, ohne etwas einzufordern.
                new Notification(`In ${LEAD_MINUTES} Minuten`, {
                    body: reminder.title,
                    tag: `align-habit-${reminder.id}`,
                });

                fired.add(reminder.id);
                changed = true;
            }

            if (changed) {
                writeFired(fired);
            }
        }

        check();
        const timer = window.setInterval(check, TICK_MS);

        return () => window.clearInterval(timer);
    }, [reminders]);
}
