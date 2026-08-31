import { useEffect, useSyncExternalStore } from 'react';
import type { SleepShared, SleepWindow, Weekday } from '@/types';

/**
 * Wie lange der Hinweis nach der Schlafenszeit noch stehen bleibt.
 *
 * Danach ist es keine Erinnerung mehr, sondern eine Mahnung — und wer um
 * Mitternacht noch wach ist, hat sich entschieden.
 */
const BEDTIME_OVERDUE_MINUTES = 30;

/** Wie lange der Wecker klingelt, ehe er von selbst aufgibt. */
const ALARM_RING_MINUTES = 10;

/** Derselbe Takt wie bei den Gewohnheits-Erinnerungen, aus denselben Gründen. */
const TICK_MS = 30_000;

/** Toleranz der Systembenachrichtigung nach hinten. */
const GRACE_MINUTES = 2;

const BEDTIME_FIRED_KEY = 'align:bedtime-fired';
const ALARM_DISMISSED_KEY = 'align:alarm-dismissed';

/** Minuten seit Mitternacht für „HH:MM". */
function toMinutes(time: string): number {
    const [hours = 0, minutes = 0] = time.split(':').map(Number);

    return hours * 60 + minutes;
}

function isoWeekday(date: Date): Weekday {
    return (date.getDay() === 0 ? 7 : date.getDay()) as Weekday;
}

/**
 * Der Rahmen, der zu einem Wochentag gehört — aus den drei geteilten Tagen.
 *
 * Die geteilte Eigenschaft trägt gestern, heute und morgen aus Sicht des
 * Servers. Gesucht wird über den Wochentag statt über die Position, damit die
 * Antwort auch dann stimmt, wenn der Tab über Mitternacht offen blieb und die
 * Props noch vom Vortag stammen.
 */
function windowForWeekday(
    sleep: SleepShared,
    weekday: Weekday,
): SleepWindow | null {
    return (
        [sleep.yesterday, sleep.today, sleep.tomorrow].find(
            (candidate) => candidate.weekday === weekday,
        ) ?? null
    );
}

export interface BedtimeStatus {
    /** Die Schlafenszeit, um die es geht — „23:00". */
    bedtime: string;
    /** Minuten bis dahin. Negativ, wenn sie vorbei ist. */
    minutesUntil: number;
}

/**
 * Steht die Schlafenszeit an?
 *
 * Ein Zustand, kein Ereignis — dieselbe Bauart wie bei den
 * Gewohnheits-Erinnerungen: Die Auskunft gilt vom Vorlauf bis kurz danach
 * und lässt sich deshalb nicht verpassen.
 *
 * Zwei Kandidaten, weil eine Schlafenszeit nach Mitternacht zum **gestrigen**
 * Wochentag gehört: Wer dienstags bis 00:30 plant, bekommt den Hinweis in der
 * Nacht auf Mittwoch — aus Dienstags Zeile.
 */
export function bedtimeStatus(
    sleep: SleepShared,
    now: Date,
): BedtimeStatus | null {
    const nowMinutes = now.getHours() * 60 + now.getMinutes();
    const weekday = isoWeekday(now);
    const previous = ((weekday + 5) % 7) + 1;

    const candidates: BedtimeStatus[] = [];

    const today = windowForWeekday(sleep, weekday);

    if (today !== null && today.bedtime > today.wakeTime) {
        candidates.push({
            bedtime: today.bedtime,
            minutesUntil: toMinutes(today.bedtime) - nowMinutes,
        });
    }

    const yesterday = windowForWeekday(sleep, previous as Weekday);

    if (yesterday !== null && yesterday.bedtime <= yesterday.wakeTime) {
        candidates.push({
            bedtime: yesterday.bedtime,
            minutesUntil: toMinutes(yesterday.bedtime) - nowMinutes,
        });
    }

    return (
        candidates.find(
            (candidate) =>
                candidate.minutesUntil <= sleep.leadMinutes &&
                candidate.minutesUntil >= -BEDTIME_OVERDUE_MINUTES,
        ) ?? null
    );
}

export interface AlarmStatus {
    /** Die Aufstehzeit, zu der geklingelt wird — „07:00". */
    wakeTime: string;
}

/**
 * Klingelt gerade der Wecker?
 *
 * Nur, wenn der heutige Morgen einen aktiven Wecker hat und die Aufstehzeit
 * höchstens {@see ALARM_RING_MINUTES} zurückliegt. Ausgeschaltet wird er über
 * {@see dismissAlarm} — das Gedächtnis dafür liegt im `localStorage`, damit
 * ein Seitenwechsel ihn nicht erneut auslöst.
 */
export function alarmStatus(sleep: SleepShared, now: Date): AlarmStatus | null {
    const window = windowForWeekday(sleep, isoWeekday(now));

    if (window === null || !window.alarmEnabled) {
        return null;
    }

    const nowMinutes = now.getHours() * 60 + now.getMinutes();
    const wakeMinutes = toMinutes(window.wakeTime);

    if (
        nowMinutes < wakeMinutes ||
        nowMinutes > wakeMinutes + ALARM_RING_MINUTES
    ) {
        return null;
    }

    if (readDate(ALARM_DISMISSED_KEY) === localDate(now)) {
        return null;
    }

    return { wakeTime: window.wakeTime };
}

/** Der heutige Tag als „YYYY-MM-DD" in lokaler Zeit. */
function localDate(now: Date = new Date()): string {
    return now.toLocaleDateString('sv-SE');
}

function readDate(key: string): string | null {
    try {
        return window.localStorage.getItem(key);
    } catch {
        return null;
    }
}

function writeDate(key: string): void {
    try {
        window.localStorage.setItem(key, localDate());
    } catch {
        // Privater Modus oder volles Kontingent — dann klingelt der Wecker
        // nach einem Seitenwechsel eben noch einmal.
    }
}

/** Der Wecker ist aus — für diesen Morgen. */
export function dismissAlarm(): void {
    writeDate(ALARM_DISMISSED_KEY);
}

function subscribeToTicks(onTick: () => void): () => void {
    const timer = window.setInterval(onTick, TICK_MS);

    return () => window.clearInterval(timer);
}

function currentTick(): number {
    return Math.floor(Date.now() / TICK_MS);
}

/**
 * Der aktuelle Schlaf-Zustand im Takt der Uhr.
 *
 * SSR bekommt `null` und rendert nichts — derselbe Handgriff wie bei den
 * Gewohnheits-Erinnerungen: Serverzeit und Browserzeit lägen auseinander.
 */
export function useSleepStatus(sleep: SleepShared | null): {
    bedtime: BedtimeStatus | null;
    alarm: AlarmStatus | null;
} {
    const tick = useSyncExternalStore(
        subscribeToTicks,
        currentTick,
        (): null => null,
    );

    if (tick === null || sleep === null) {
        return { bedtime: null, alarm: null };
    }

    const now = new Date();

    return {
        bedtime: sleep.reminderEnabled ? bedtimeStatus(sleep, now) : null,
        alarm: alarmStatus(sleep, now),
    };
}

/**
 * Meldet die Schlafenszeit als Systembenachrichtigung — einmal pro Abend,
 * und nur, wenn der Tab gerade nicht sichtbar ist.
 *
 * Ist die App im Blick, trägt der Hinweis in der Oberfläche dieselbe
 * Auskunft — eine Systemmeldung obendrauf wäre dieselbe Nachricht zweimal.
 */
export function useBedtimeNotification(sleep: SleepShared | null): void {
    useEffect(() => {
        if (
            sleep === null ||
            !sleep.reminderEnabled ||
            !('Notification' in window)
        ) {
            return;
        }

        function check() {
            if (
                sleep === null ||
                Notification.permission !== 'granted' ||
                !document.hidden
            ) {
                return;
            }

            const status = bedtimeStatus(sleep, new Date());

            if (
                status === null ||
                status.minutesUntil > sleep.leadMinutes ||
                status.minutesUntil < sleep.leadMinutes - GRACE_MINUTES
            ) {
                return;
            }

            if (readDate(BEDTIME_FIRED_KEY) === localDate()) {
                return;
            }

            // Ton nach §8: benennt, was ansteht, ohne etwas einzufordern.
            new Notification(
                `In ${sleep.leadMinutes} Minuten ist Schlafenszeit`,
                {
                    body: `Um ${status.bedtime} beginnt deine Nacht.`,
                    tag: 'align-bedtime',
                },
            );

            writeDate(BEDTIME_FIRED_KEY);
        }

        check();
        const timer = window.setInterval(check, TICK_MS);

        return () => window.clearInterval(timer);
    }, [sleep]);
}
