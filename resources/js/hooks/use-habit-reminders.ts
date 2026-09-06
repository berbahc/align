import { useEffect, useSyncExternalStore } from 'react';
import type { HabitReminder, Weekday } from '@/types';

/**
 * Vorlaufzeit der Erinnerung in Minuten.
 *
 * Exportiert, weil die Zahl auch beschriftet werden muss: Die Gewohnheiten-Liste
 * nennt sie im Menü („Erinnerung 10 Min vorher"). Stand sie dort ein zweites
 * Mal als Text, sagten Verhalten und Beschriftung irgendwann Verschiedenes.
 */
export const LEAD_MINUTES = 10;

/**
 * Wie lange eine überfällige Gewohnheit noch angezeigt wird.
 *
 * Nach einer Stunde ist es keine Erinnerung mehr, sondern eine Mahnung. Die
 * Gewohnheit bleibt auf der Übersicht offen, der Hinweis verschwindet.
 */
const OVERDUE_MINUTES = 60;

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

/** Eine Gewohnheit, die gerade ansteht, mit ihrem Abstand zur Uhrzeit. */
export interface DueReminder {
    reminder: HabitReminder;
    /** Minuten bis zur Uhrzeit. Negativ, wenn sie vorbei ist. */
    minutesUntil: number;
}

/**
 * Welche Gewohnheiten gerade anstehen.
 *
 * Ein Zustand, kein Ereignis: Die Auskunft gilt von zehn Minuten vorher bis
 * eine Stunde danach und lässt sich deshalb nicht verpassen. Der Vorgänger
 * feuerte in einem Fenster von drei Minuten — wer da nicht hinsah, bekam für
 * diesen Tag nichts mehr.
 *
 * Reine Funktion mit übergebener Zeit, damit sie prüfbar bleibt.
 */
export function dueReminders(
    reminders: HabitReminder[],
    now: Date,
): DueReminder[] {
    const weekday = (now.getDay() === 0 ? 7 : now.getDay()) as Weekday;
    const nowMinutes = now.getHours() * 60 + now.getMinutes();

    return reminders
        .filter(
            (reminder) =>
                !reminder.completedToday &&
                reminder.scheduledDays.includes(weekday),
        )
        .map((reminder) => ({
            reminder,
            minutesUntil: toMinutes(reminder.scheduledTime) - nowMinutes,
        }))
        .filter(
            (due) =>
                due.minutesUntil <= LEAD_MINUTES &&
                due.minutesUntil >= -OVERDUE_MINUTES,
        )
        .sort((a, b) => a.minutesUntil - b.minutesUntil);
}

function subscribeToTicks(onTick: () => void): () => void {
    const timer = window.setInterval(onTick, TICK_MS);

    return () => window.clearInterval(timer);
}

/**
 * Die laufende Nummer des aktuellen Takts.
 *
 * `useSyncExternalStore` ruft das bei jedem Render auf und vergleicht das
 * Ergebnis — deshalb eine Zahl, die sich nur alle TICK_MS ändert, und nicht
 * `new Date()`, das jedes Mal ungleich wäre und endlos neu rendern würde.
 */
function currentTick(): number {
    return Math.floor(Date.now() / TICK_MS);
}

/**
 * Wie `dueReminders`, aber mit eigenem Takt statt fester Zeit.
 *
 * Auf dem Server gibt es keinen Takt: SSR rendert mit der Serverzeit, der
 * Browser hydriert mit seiner eigenen, und beide lägen auseinander. Der
 * Server-Schnappschuss ist deshalb `null` und liefert eine leere Liste —
 * `useSyncExternalStore` ist genau für diesen Unterschied gebaut und rendert
 * nach dem Hydrieren ohne Warnung nach.
 */
export function useDueReminders(reminders: HabitReminder[]): DueReminder[] {
    const tick = useSyncExternalStore(
        subscribeToTicks,
        currentTick,
        (): null => null,
    );

    if (tick === null) {
        return [];
    }

    return dueReminders(reminders, new Date());
}

/**
 * Meldet sich zehn Minuten vor einer Gewohnheit mit fester Uhrzeit.
 *
 * Nur wenn der Tab gerade nicht sichtbar ist. Ist die App im Blick, trägt der
 * Hinweis in der Oberfläche dieselbe Auskunft und kann sogar das Abhaken
 * anbieten — eine Systemmeldung obendrauf wäre dieselbe Nachricht zweimal.
 *
 * Auch so bleibt die Grenze bestehen: ohne Service Worker läuft nichts, wenn
 * kein Tab offen ist.
 */
export function useHabitReminders(reminders: HabitReminder[]): void {
    useEffect(() => {
        if (reminders.length === 0 || !('Notification' in window)) {
            return;
        }

        function check() {
            if (Notification.permission !== 'granted' || !document.hidden) {
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
