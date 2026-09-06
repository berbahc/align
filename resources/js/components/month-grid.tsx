import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { day as calendarDay } from '@/routes/calendar';
import type { MonthDay } from '@/types';

/**
 * Die Wochentage über dem Raster.
 *
 * Montag zuerst: Der ISO-Wochentag trägt die ganze App (1 = Montag), und ein
 * Raster, das sonntags anfängt, stünde quer zu jeder anderen Wochenansicht
 * darin.
 */
const WEEKDAYS = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

/**
 * Der Monat als Raster aus Wochen.
 *
 * Jeder Tag trägt einen Punkt je Gewohnheit, die anstand — gefüllt, was lief.
 * Bewusst keine Quote und kein Balken: Eine Zahl über einem einzelnen Tag wäre
 * eine Note, und bei einem Schuldwert von ø 3,92 darf ein Rückblick kein
 * Vorwurf sein. Ein offener Punkt sieht deshalb gestern genauso aus wie
 * morgen; nur die Ziffer wird für kommende Tage leiser.
 *
 * Die Zelle ist der Weg in den Tag. Sie ist ein Link und kein Knopf: Der Tag
 * hat eine eigene Adresse, übersteht ein Neuladen und lässt sich teilen.
 */
export function MonthGrid({
    days,
    today,
}: {
    days: MonthDay[];
    today: string;
}) {
    return (
        <div>
            <div
                className="grid grid-cols-7"
                role="presentation"
                aria-hidden="true"
            >
                {WEEKDAYS.map((weekday) => (
                    <span
                        key={weekday}
                        className="type-eyebrow py-2 text-center text-muted-foreground"
                    >
                        {weekday}
                    </span>
                ))}
            </div>

            <ul className="grid grid-cols-7 gap-y-1">
                {days.map((day) => (
                    <li key={day.date}>
                        <Link
                            href={calendarDay(day.date)}
                            aria-label={dayLabel(day, today)}
                            aria-current={day.isToday ? 'date' : undefined}
                            className={cn(
                                'flex h-14 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.94]',
                                // Tage aus dem Nachbarmonat füllen nur die
                                // Woche auf. Sie bleiben erreichbar — nur eben
                                // leiser, damit der Monat seine Kante behält.
                                !day.inMonth && 'opacity-40',
                            )}
                        >
                            <span
                                className={cn(
                                    'flex size-7 items-center justify-center rounded-full text-[13px] leading-none font-semibold tabular-nums',
                                    day.isToday &&
                                        'bg-primary text-primary-foreground',
                                    !day.isToday &&
                                        (day.isFuture
                                            ? 'text-faintest'
                                            : 'text-foreground'),
                                )}
                            >
                                {day.dayOfMonth}
                            </span>

                            {/* Die Höhe steht auch ohne Punkte, damit die
                                Ziffern aller Zellen auf einer Linie bleiben. */}
                            <span className="flex h-1.5 items-center gap-[3px]">
                                {Array.from({ length: day.planned }).map(
                                    (_, index) => (
                                        <span
                                            key={index}
                                            className={cn(
                                                'size-1.5 rounded-full',
                                                index < day.done
                                                    ? 'bg-primary'
                                                    : 'bg-sand',
                                            )}
                                        />
                                    ),
                                )}
                            </span>

                            {/* Ein Vorlesungstag. Eine Linie und kein Punkt,
                                damit sie nicht mitgezählt wird; leiser als der
                                offene Punkt, damit sie den Inhalt des Monats
                                nie überstimmt. Sie wird auch für die Zukunft
                                nicht gedämpft: Ein künftiger Tag hat noch kein
                                Ergebnis, eine Vorlesung in drei Wochen ist
                                aber genauso Tatsache wie eine von gestern. So
                                zeigt der Monat, was er sonst nicht kann — wo
                                die Vorlesungszeit aufhört.

                                Das Band steht auch leer, damit die Punktreihe
                                in allen Zellen auf einer Linie bleibt.

                                Daneben, wenn etwas mit jemandem ansteht: ein
                                offener Ring. Kein Punkt, weil er sonst
                                mitgezählt würde, und keine zweite Linie, weil
                                die beiden sich dann nur in der Farbe
                                unterschieden — Linie gegen Ring ist ein
                                Unterschied in der Form. */}
                            <span
                                aria-hidden="true"
                                className="flex h-1.5 items-center justify-center gap-1"
                            >
                                <span
                                    className={cn(
                                        'h-px w-4 rounded-full',
                                        day.hasLectures && 'bg-olive-mid/40',
                                    )}
                                />
                                {day.hasAppointment && (
                                    <span className="size-1.5 rounded-full border border-primary/70" />
                                )}
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    );
}

/**
 * Was eine Vorlesesoftware statt „14" hört.
 *
 * Die Punkte sind für sie unsichtbar; ohne diesen Satz bliebe von der Zelle
 * eine Zahl ohne Auskunft.
 */
function dayLabel(day: MonthDay, today: string): string {
    const date = new Date(`${day.date}T00:00:00`).toLocaleDateString('de-DE', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });

    const prefix = day.date === today ? 'Heute, ' : '';

    const lectures = day.hasLectures ? ' · Vorlesungstag' : '';
    const together = day.hasAppointment ? ' · zusammen verabredet' : '';

    if (day.planned === 0) {
        return `${prefix}${date}, nichts vorgesehen${lectures}${together}`;
    }

    if (day.isFuture) {
        return `${prefix}${date}, ${day.planned} vorgesehen${lectures}${together}`;
    }

    return `${prefix}${date}, ${day.done} von ${day.planned} erledigt${lectures}${together}`;
}
