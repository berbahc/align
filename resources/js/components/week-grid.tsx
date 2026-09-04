import { cn } from '@/lib/utils';
import type { CourseRow, Weekday } from '@/types';

/**
 * Wie hoch eine Stunde ist.
 *
 * Halb so hoch wie im Tagesraster: Dort steht ein Tag, hier eine Woche, und
 * eine Vorlesung von neunzig Minuten muss in fünf Spalten nebeneinander noch
 * einen Titel tragen. 44 Pixel je Stunde geben einem Block von neunzig
 * Minuten 66 — genug für zwei Zeilen.
 */
const HOUR_HEIGHT = 44;

/** Der kleinste Ausschnitt, den das Raster zeigt — auch bei einem Kurs. */
const EARLIEST_HOUR = 8;
const LATEST_HOUR = 18;

const WEEKDAYS: { value: Weekday; short: string; long: string }[] = [
    { value: 1, short: 'Mo', long: 'Montag' },
    { value: 2, short: 'Di', long: 'Dienstag' },
    { value: 3, short: 'Mi', long: 'Mittwoch' },
    { value: 4, short: 'Do', long: 'Donnerstag' },
    { value: 5, short: 'Fr', long: 'Freitag' },
    { value: 6, short: 'Sa', long: 'Samstag' },
    { value: 7, short: 'So', long: 'Sonntag' },
];

function toMinutes(time: string): number {
    const [hours = '0', minutes = '0'] = time.split(':');

    return Number(hours) * 60 + Number(minutes);
}

/**
 * Die Woche als Raster — Wochentage als Spalten, Stunden als Zeilen.
 *
 * So zeigt jedes Uni-Portal den Stundenplan, und so kennt ihn jeder
 * Studierende: Zwölf Kurse passen auf einen Bildschirm, Lücken und volle Tage
 * sieht man, ohne zu lesen. Eine Liste sagte dasselbe in zwölf Karten und
 * verlangte dafür Scrollen.
 *
 * Das Wochenende erscheint nur, wenn dort etwas liegt. Fünf Spalten sind auf
 * einem Telefon schon eng; zwei leere dazu nähmen den anderen die Breite.
 *
 * Die Blöcke tragen dieselbe Grammatik wie im Tagesraster: sandfarben gefüllt,
 * links eine Kante in Oliv — ein Kurs ist kein Vorsatz, sondern eine Tatsache.
 * Antippen öffnet das Sheet mit den Handlungen; im Block selbst steht nur, was
 * er ist.
 */
export function WeekGrid({
    courses,
    onOpen,
}: {
    courses: CourseRow[];
    onOpen: (course: CourseRow) => void;
}) {
    const columns = WEEKDAYS.filter(
        (day) =>
            day.value <= 5 ||
            courses.some((course) => course.weekday === day.value),
    );

    const starts = courses.map((course) => toMinutes(course.startsAt));
    const ends = courses.map((course) => toMinutes(course.endsAt));

    const from = Math.floor(Math.min(EARLIEST_HOUR * 60, ...starts) / 60) * 60;
    const to = Math.ceil(Math.max(LATEST_HOUR * 60, ...ends) / 60) * 60;
    const height = ((to - from) / 60) * HOUR_HEIGHT;

    const hours: number[] = [];

    for (let minute = from; minute < to; minute += 60) {
        hours.push(minute);
    }

    return (
        <div className="flex gap-1">
            {/* Die Stundenspalte — schmal, weil sie nur Orientierung ist. */}
            <div
                className="relative w-8 shrink-0"
                style={{ height, marginTop: 28 }}
                aria-hidden="true"
            >
                {hours.map((minute) => (
                    <span
                        key={minute}
                        className="absolute right-1 -translate-y-1/2 text-[11px] leading-none font-medium text-faintest tabular-nums"
                        style={{ top: ((minute - from) / 60) * HOUR_HEIGHT }}
                    >
                        {String(minute / 60).padStart(2, '0')}
                    </span>
                ))}
            </div>

            {columns.map((day) => {
                const own = courses.filter(
                    (course) => course.weekday === day.value,
                );

                return (
                    <div key={day.value} className="min-w-0 flex-1">
                        <p className="type-eyebrow mb-2 h-5 text-center text-muted-foreground">
                            <span className="sm:hidden">{day.short}</span>
                            <span className="hidden sm:inline">{day.long}</span>
                        </p>

                        <div className="relative" style={{ height }}>
                            {/* Die Stundenlinien — hinter allem, in der
                                leisesten Farbe. */}
                            <div aria-hidden="true">
                                {hours.map((minute) => (
                                    <span
                                        key={minute}
                                        className="absolute inset-x-0 h-px bg-border"
                                        style={{
                                            top:
                                                ((minute - from) / 60) *
                                                HOUR_HEIGHT,
                                        }}
                                    />
                                ))}
                            </div>

                            <ul>
                                {own.map((course) => {
                                    const start = toMinutes(course.startsAt);
                                    const end = toMinutes(course.endsAt);
                                    const top =
                                        ((start - from) / 60) * HOUR_HEIGHT;
                                    const blockHeight =
                                        ((end - start) / 60) * HOUR_HEIGHT - 2;
                                    const spacious = blockHeight >= 44;

                                    return (
                                        <li
                                            key={course.id}
                                            className="absolute inset-x-0"
                                            style={{
                                                top,
                                                height: blockHeight,
                                            }}
                                        >
                                            <button
                                                type="button"
                                                onClick={() => onOpen(course)}
                                                aria-label={`${course.kindLabel} ${course.title}, ${day.long} ${course.timeRange.replace('–', 'bis')}`}
                                                className={cn(
                                                    'flex h-full w-full cursor-pointer flex-col overflow-hidden rounded-lg border-l-[3px] border-l-olive-mid bg-sand px-1.5 text-left transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-sand/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.97]',
                                                    spacious
                                                        ? 'justify-start py-1.5'
                                                        : 'justify-center py-0.5',
                                                )}
                                            >
                                                {spacious && (
                                                    <span className="block truncate text-[10px] leading-tight text-olive-mid tabular-nums">
                                                        {course.startsAt}
                                                    </span>
                                                )}
                                                <span className="block truncate text-[11px] leading-tight font-semibold text-foreground">
                                                    {course.title}
                                                </span>
                                                {spacious &&
                                                    course.exceptions.length >
                                                        0 && (
                                                        <span className="mt-auto block truncate text-[10px] leading-tight text-olive-mid">
                                                            {
                                                                course
                                                                    .exceptions
                                                                    .length
                                                            }{' '}
                                                            {course.exceptions
                                                                .length === 1
                                                                ? 'Ausnahme'
                                                                : 'Ausnahmen'}
                                                        </span>
                                                    )}
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
