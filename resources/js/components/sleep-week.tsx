import { AlarmClock } from 'lucide-react';
import { sleepDurationLabel, sleepMinutes } from '@/lib/sleep';
import { cn } from '@/lib/utils';
import type { Weekday } from '@/types';

/** Wo die Nachtachse anfängt: 18 Uhr, davor geht niemand ins Bett. */
const NIGHT_START = 18 * 60;

/** Eine Stunde Luft an beiden Enden, damit kein Balken an der Kante klebt. */
const PADDING = 60;

export interface SleepDay {
    weekday: Weekday;
    /** Der Name, wie er in der Zeile steht. */
    name: string;
    /** Die Kurzform für schmale Bildschirme. */
    short: string;
    wakeTime: string;
    bedtime: string;
    alarmEnabled: boolean;
}

/** Minuten seit 18 Uhr, über Mitternacht hinweg gezählt. */
function nightMinute(time: string): number {
    const [hours = 0, minutes = 0] = time.split(':').map(Number);

    return (hours * 60 + minutes - NIGHT_START + 1440) % 1440;
}

/** „23:00" für eine Minute auf der Nachtachse. */
function timeLabel(minute: number): string {
    const total = (minute + NIGHT_START) % 1440;
    const hours = Math.floor(total / 60);

    return `${String(hours).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
}

/**
 * Die Woche als Nachtband.
 *
 * Ein Schlafplan ist Woche **mal** Uhrzeit, also zweidimensional. Als Liste
 * aus sieben Zeilen mit Zahlen darin geht die zweite Achse verloren: Man liest
 * „07:00" siebenmal und sieht trotzdem nicht, dass das Wochenende zwei Stunden
 * später anfängt. Hier liegen die sieben Nächte übereinander auf derselben
 * Achse, und die Verschiebung ist die Form, die dabei entsteht.
 *
 * Die Achse beginnt um 18 Uhr statt um Mitternacht. Sonst zerfiele jeder
 * Balken in zwei Stücke an den beiden Enden des Tages, und genau das, worum es
 * geht — die zusammenhängende Nacht — wäre nicht mehr zu sehen.
 *
 * Ihre Grenzen kommen aus den Daten, nicht aus einer festen Zahl: Wer um zehn
 * ins Bett geht, bekommt eine engere Achse als jemand mit Mitternacht, und die
 * Balken füllen in beiden Fällen die Breite. Dieselbe Rechnung wie in
 * {@see boundsFor()} im Tagesraster.
 */
export function SleepWeek({
    days,
    selected,
    onSelect,
    onToggleAlarm,
}: {
    days: SleepDay[];
    selected: Weekday;
    onSelect: (weekday: Weekday) => void;
    onToggleAlarm: (weekday: Weekday, enabled: boolean) => void;
}) {
    const starts = days.map((day) => nightMinute(day.bedtime));
    const ends = days.map(
        (day, index) =>
            starts[index]! + sleepMinutes(day.wakeTime, day.bedtime),
    );

    // Auf volle Stunden gerundet, damit die Beschriftung auf der Achse sitzt.
    const from = Math.max(
        0,
        Math.floor((Math.min(...starts) - PADDING) / 60) * 60,
    );
    const to = Math.min(
        1440,
        Math.ceil((Math.max(...ends) + PADDING) / 60) * 60,
    );
    const span = Math.max(to - from, 60);

    /** Alle drei Stunden eine Marke, damit die Achse lesbar bleibt. */
    const ticks: number[] = [];

    for (
        let minute = Math.ceil(from / 180) * 180;
        minute <= to;
        minute += 180
    ) {
        ticks.push(minute);
    }

    return (
        <div>
            {/* Die Achse steht oben, damit die sieben Nächte darunter alle
                dieselbe Bezugslinie haben. Sie trägt dieselben Spalten wie
                eine Zeile, nur leer — ein geschätzter Randabstand säße bei
                jeder Bildschirmbreite ein paar Pixel daneben, und eine Achse,
                die nicht über ihren Balken liegt, ist schlimmer als keine. */}
            <div
                aria-hidden="true"
                className="mb-1 flex items-end gap-2 sm:gap-3"
            >
                <span className="w-9 shrink-0 pl-2 sm:w-24" />
                <div className="relative h-4 min-w-0 flex-1">
                    {ticks.map((minute) => (
                        <span
                            key={minute}
                            className="absolute top-0 -translate-x-1/2 text-[10px] leading-none text-faintest tabular-nums"
                            style={{
                                left: `${((minute - from) / span) * 100}%`,
                            }}
                        >
                            {timeLabel(minute)}
                        </span>
                    ))}
                </div>
                <span className="hidden w-20 shrink-0 sm:block" />
                <span className="size-11 shrink-0" />
            </div>

            <ul className="flex flex-col gap-1">
                {days.map((day, index) => {
                    const isSelected = day.weekday === selected;
                    const left = ((starts[index]! - from) / span) * 100;
                    const width =
                        (sleepMinutes(day.wakeTime, day.bedtime) / span) * 100;
                    const duration = sleepDurationLabel(
                        day.wakeTime,
                        day.bedtime,
                    );

                    return (
                        <li
                            key={day.weekday}
                            className={cn(
                                'flex items-center gap-2 rounded-xl transition-colors duration-[var(--duration-press)] ease-out sm:gap-3',
                                isSelected && 'bg-accent',
                            )}
                        >
                            {/* Die Zeile wählt den Tag aus. Sie ist der
                                eigentliche Weg, deshalb trägt sie die ganze
                                Breite und nicht nur der Name. */}
                            <button
                                type="button"
                                onClick={() => onSelect(day.weekday)}
                                aria-pressed={isSelected}
                                className={cn(
                                    'flex min-w-0 flex-1 cursor-pointer items-center gap-2 rounded-xl py-2 transition-colors duration-[var(--duration-press)] ease-out focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring sm:gap-3',
                                    !isSelected && 'hover:bg-accent/50',
                                )}
                            >
                                <span
                                    className={cn(
                                        'w-9 shrink-0 pl-2 text-left text-xs font-semibold sm:w-24 sm:text-sm',
                                        isSelected
                                            ? 'text-foreground'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    <span className="sm:hidden">
                                        {day.short}
                                    </span>
                                    <span className="max-sm:hidden">
                                        {day.name}
                                    </span>
                                </span>

                                {/* Die Spur ist der Ausschnitt der Nacht, der
                                    Balken die Zeit darin. Voll gerundet wie
                                    der Fortschrittsbalken (§5.3). */}
                                <span className="relative h-5 min-w-0 flex-1 overflow-hidden rounded-full bg-track sm:h-6">
                                    <span
                                        className="absolute inset-y-0 rounded-full bg-primary motion-safe:transition-[left,width] motion-safe:duration-[var(--duration-fluid)] motion-safe:ease-[var(--ease-fluid)]"
                                        style={{
                                            left: `${left}%`,
                                            width: `${width}%`,
                                        }}
                                    />
                                </span>

                                {/* Auf dem Handy bleibt sie weg. Sie sagt in
                                    Zahlen, was der Balken in seiner Länge
                                    schon zeigt, und nahm ihm dafür siebenmal
                                    ein Fünftel der Breite. Die genaue Dauer
                                    des gewählten Tages steht im Editor. */}
                                <span
                                    className={cn(
                                        'hidden w-20 shrink-0 text-right text-xs tabular-nums sm:block',
                                        isSelected
                                            ? 'font-semibold text-foreground'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {duration}
                                </span>
                            </button>

                            {/* Der Wecker bleibt an der Zeile erreichbar, ohne
                                dass man den Tag erst auswählen muss. Gefüllt
                                heißt an, Kontur heißt aus: Form und Farbe
                                zugleich, damit es auch ohne Farbwahrnehmung
                                unterscheidbar bleibt. */}
                            <button
                                type="button"
                                onClick={() =>
                                    onToggleAlarm(
                                        day.weekday,
                                        !day.alarmEnabled,
                                    )
                                }
                                aria-pressed={day.alarmEnabled}
                                className="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            >
                                <span
                                    className={cn(
                                        'flex size-7 items-center justify-center rounded-full transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                        day.alarmEnabled
                                            ? 'bg-primary text-primary-foreground'
                                            : 'border border-sand text-muted-foreground',
                                    )}
                                >
                                    <AlarmClock
                                        className="size-3.5"
                                        strokeWidth={1.75}
                                        aria-hidden="true"
                                    />
                                </span>
                                <span className="sr-only">
                                    {day.alarmEnabled
                                        ? `Wecker am ${day.name} ausschalten`
                                        : `Wecker am ${day.name} einschalten`}
                                </span>
                            </button>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
