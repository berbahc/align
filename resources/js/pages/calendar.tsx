import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { MonthGrid } from '@/components/month-grid';
import { Card, CardContent } from '@/components/ui/card';
import { QUIET_LINK } from '@/lib/interaction';
import { calendar } from '@/routes';
import { day as calendarDay } from '@/routes/calendar';
import { show as semesterShow } from '@/routes/semester';
import type { MonthDay } from '@/types';

interface CalendarProps {
    /** Der gezeigte Monat als „YYYY-MM". */
    month: string;
    /** Die Zeile im Kopf, fertig formatiert: „September 2026". */
    heading: string;
    previousMonth: string;
    nextMonth: string;
    isCurrentMonth: boolean;
    /** Heute als „YYYY-MM-DD" — das Ziel des Sprungs zurück. */
    today: string;
    /** Volle Wochen, Montag bis Sonntag — auch über die Monatskante hinaus. */
    days: MonthDay[];
    /** Der Semesterplan, wenn es einen gibt — sonst die Einladung dazu. */
    semester: { title: string; courseCount: number } | null;
}

const NAV_BUTTON =
    'flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full text-primary transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.94]';

/**
 * Der Monat — die Ebene, auf der man im Kalender ankommt.
 *
 * Bis hierher führte der Kalender direkt in einen einzelnen Tag. Man konnte
 * sich durch ihn blättern, aber nie sehen, wie die Wochen davor gelaufen sind.
 * Der Monat beantwortet die Frage, die ein einzelner Tag nicht beantworten
 * kann — „wie läuft das gerade eigentlich?" — und zwar ohne Zahl: Punkte, die
 * man von weitem als Muster liest.
 *
 * Von hier führt jeder Tag in seine Achse. Der Weg ist eine Adresse, kein
 * Zustand: `/calendar/2026-09-07` lässt sich neu laden und teilen.
 */
export default function Calendar({
    heading,
    previousMonth,
    nextMonth,
    isCurrentMonth,
    today,
    days,
    semester,
}: CalendarProps) {
    return (
        <>
            <Head title="Kalender" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
                <header className="flex items-center gap-2">
                    {/* Pfeile sind Links, kein Client-State: der Monat steht in
                        der URL und übersteht damit ein Neuladen. */}
                    <Link
                        href={calendar({ query: { month: previousMonth } })}
                        aria-label="Ein Monat zurück"
                        className={NAV_BUTTON}
                    >
                        <ChevronLeft className="size-5" aria-hidden="true" />
                    </Link>

                    <h1 className="flex-1 text-center text-[clamp(1.125rem,4vw,1.5rem)] leading-tight font-bold text-primary">
                        {heading}
                    </h1>

                    <Link
                        href={calendar({ query: { month: nextMonth } })}
                        aria-label="Ein Monat vor"
                        className={NAV_BUTTON}
                    >
                        <ChevronRight className="size-5" aria-hidden="true" />
                    </Link>
                </header>

                {!isCurrentMonth && (
                    <div className="flex justify-center">
                        <Link
                            href={calendar()}
                            className={`${QUIET_LINK} text-sm`}
                        >
                            Zurück zu diesem Monat
                        </Link>
                    </div>
                )}

                <Card className="gap-0 py-4">
                    <CardContent className="px-3 sm:px-5">
                        <MonthGrid days={days} today={today} />
                    </CardContent>
                </Card>

                {/* Der Sprung in den heutigen Tag steht unter dem Raster und
                    nicht als Kachel darin: Er führt eine Ebene tiefer, während
                    alles im Raster nur den Ausschnitt wechselt. */}
                <div className="flex justify-center">
                    <Link
                        href={calendarDay(today)}
                        className={`${QUIET_LINK} text-sm`}
                    >
                        Heutigen Tag öffnen
                    </Link>
                </div>

                {/* Der Semesterplan — kein Knopf, sondern eine Zeile, die den
                    Zustand kennt. Ohne Plan nennt sie den Grund, mit Plan
                    nennt sie ihn. Ein dauerhafter Aufruf zum Eintragen wäre
                    für alle da, die gar nicht studieren; die Markierung im
                    Raster darüber hat die Frage ohnehin schon gestellt. */}
                <div className="flex justify-center">
                    {semester === null ? (
                        <p className="text-center text-sm text-muted-foreground">
                            <Link href={semesterShow()} className={QUIET_LINK}>
                                Semesterplan anlegen
                            </Link>
                            {' — dann plant Align um deine Kurse herum.'}
                        </p>
                    ) : (
                        <Link
                            href={semesterShow()}
                            className={`${QUIET_LINK} text-sm`}
                        >
                            {`Semesterplan · ${semester.title} · ${semester.courseCount} ${semester.courseCount === 1 ? 'Kurs' : 'Kurse'}`}
                        </Link>
                    )}
                </div>
            </div>
        </>
    );
}

Calendar.layout = {
    breadcrumbs: [{ title: 'Kalender', href: calendar() }],
};
