import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { day as calendarDay } from '@/routes/calendar';
import type { CourseRow, WeekHabitBlock, Weekday } from '@/types';

/**
 * Wie hoch eine Stunde ist.
 *
 * Halb so hoch wie im Tagesraster: Dort steht ein Tag, hier eine Woche, und
 * ein Block muss in fünf Spalten nebeneinander noch einen Titel tragen. 44
 * Pixel je Stunde geben neunzig Minuten 66 — genug für zwei Zeilen.
 */
const HOUR_HEIGHT = 44;

/** Der kleinste Ausschnitt, den das Raster zeigt — auch bei leerer Woche. */
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
 * Die Woche als Raster — Wochentage als Spalten, Stunden als Zeilen, und
 * darin beides: Kurse und Gewohnheiten.
 *
 * Die mittlere Ebene des Kalenders. Der Monat zeigt, ob ein Tag voll war; der
 * Tag zeigt, was jetzt dran ist. Die Woche zeigt, wie das Semester um die
 * Gewohnheiten herum liegt — und ist deshalb auch der Ort, an dem Kurse
 * eingetragen werden: dort, wo man sie sieht, nicht auf einer Seite daneben.
 *
 * Dieselbe Grammatik wie im Tag. Ein Kurs: sandfarben, links Oliv, antippen
 * öffnet seine Handlungen. Eine Gewohnheit: heller Grund, links die
 * Primärfarbe — durchgezogen bei einer Uhrzeit, gestrichelt bei einer
 * Situation —, antippen führt in den Tag, denn dort wird gehandelt. Hier wird
 * nur geschaut und der Rahmen gesetzt.
 *
 * Das Wochenende erscheint nur, wenn dort etwas liegt. Fünf Spalten sind auf
 * einem Telefon schon eng; zwei leere dazu nähmen den anderen die Breite.
 */
export function WeekGrid({
    courses,
    habits,
    dates,
    onOpenCourse,
}: {
    courses: CourseRow[];
    /** Je ISO-Wochentag die Gewohnheiten, die dort eine Stelle haben. */
    habits: Record<Weekday, WeekHabitBlock[]>;
    /** Je ISO-Wochentag das nächste Datum — das Ziel beim Antippen einer Gewohnheit. */
    dates: Record<Weekday, string>;
    onOpenCourse: (course: CourseRow) => void;
}) {
    const columns = WEEKDAYS.filter(
        (day) =>
            day.value <= 5 ||
            courses.some((course) => course.weekday === day.value) ||
            habits[day.value].length > 0,
    );

    const starts = [
        ...courses.map((course) => toMinutes(course.startsAt)),
        ...columns.flatMap((day) =>
            habits[day.value].map((habit) => habit.startMinute),
        ),
    ];
    const ends = [
        ...courses.map((course) => toMinutes(course.endsAt)),
        ...columns.flatMap((day) =>
            habits[day.value].map(
                (habit) => habit.startMinute + habit.durationMinutes,
            ),
        ),
    ];

    const from = Math.floor(Math.min(EARLIEST_HOUR * 60, ...starts) / 60) * 60;
    const to = Math.ceil(Math.max(LATEST_HOUR * 60, ...ends) / 60) * 60;
    const height = ((to - from) / 60) * HOUR_HEIGHT;

    const hours: number[] = [];

    for (let minute = from; minute < to; minute += 60) {
        hours.push(minute);
    }

    const offset = (minute: number) => ((minute - from) / 60) * HOUR_HEIGHT;

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
                        style={{ top: offset(minute) }}
                    >
                        {String(minute / 60).padStart(2, '0')}
                    </span>
                ))}
            </div>

            {columns.map((day) => (
                <div key={day.value} className="min-w-0 flex-1">
                    <p className="type-eyebrow mb-2 h-5 text-center text-muted-foreground">
                        <span className="sm:hidden">{day.short}</span>
                        <span className="hidden sm:inline">{day.long}</span>
                    </p>

                    <div className="relative" style={{ height }}>
                        <div aria-hidden="true">
                            {hours.map((minute) => (
                                <span
                                    key={minute}
                                    className="absolute inset-x-0 h-px bg-border"
                                    style={{ top: offset(minute) }}
                                />
                            ))}
                        </div>

                        <ul>
                            {courses
                                .filter(
                                    (course) => course.weekday === day.value,
                                )
                                .map((course) => {
                                    const start = toMinutes(course.startsAt);
                                    const end = toMinutes(course.endsAt);
                                    const blockHeight =
                                        ((end - start) / 60) * HOUR_HEIGHT - 2;
                                    const spacious = blockHeight >= 44;

                                    return (
                                        <li
                                            key={`course-${course.id}`}
                                            className="absolute inset-x-0"
                                            style={{
                                                top: offset(start),
                                                height: blockHeight,
                                            }}
                                        >
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    onOpenCourse(course)
                                                }
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

                            {habits[day.value].map((habit) => {
                                const blockHeight = Math.max(
                                    (habit.durationMinutes / 60) * HOUR_HEIGHT -
                                        2,
                                    22,
                                );

                                return (
                                    <li
                                        key={`habit-${habit.id}`}
                                        className="absolute inset-x-0"
                                        style={{
                                            top: offset(habit.startMinute),
                                            height: blockHeight,
                                        }}
                                    >
                                        <Link
                                            href={calendarDay(dates[day.value])}
                                            aria-label={`${habit.title}, ${day.long} — im Tag öffnen`}
                                            className={cn(
                                                'flex h-full w-full cursor-pointer items-center overflow-hidden rounded-lg border-l-[3px] bg-track px-1.5 text-left transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.97]',
                                                habit.exact
                                                    ? 'border-l-primary'
                                                    : 'border-dashed border-l-olive-mid',
                                            )}
                                        >
                                            <span className="block truncate text-[11px] leading-tight font-semibold text-foreground">
                                                {habit.title}
                                            </span>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                </div>
            ))}
        </div>
    );
}
