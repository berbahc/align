import { Link } from '@inertiajs/react';
import { Moon, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';
import { AppointmentBlock } from '@/components/appointment-block';
import { CalendarBlock } from '@/components/calendar-block';
import { CourseBlock } from '@/components/course-block';
import { useBlockDrag } from '@/hooks/use-block-drag';
import type { BlockDrag } from '@/hooks/use-block-drag';
import type { GridBlock } from '@/lib/day-grid';
import {
    boundsFor,
    collisionOf,
    hourMarks,
    offsetOf,
    placeBlocks,
    timeLabel,
    withDrag,
} from '@/lib/day-grid';
import { cn } from '@/lib/utils';
import { show as sleepShow } from '@/routes/sleep';
import type {
    AppointmentBlock as Appointment,
    CalendarBlock as Block,
    CourseBlock as Course,
} from '@/types';

/** Die Breite der Stundenspalte links — „07:00" plus Luft. */
const GUTTER = 'calc(var(--spacing) * 13)';

/**
 * Der Tag als Stundenraster.
 *
 * Das Raster reicht vom Aufstehen bis zur Schlafenszeit — und darüber hinaus
 * nur so weit, wie ein Block es erzwingt. Der Schlafplan ist die Grenze der
 * Planung, und die beiden Marken liegen deshalb **im** Raster an ihrer echten
 * Minute: Wer um 07:40 aufsteht, hat seine Linie um 07:40 und nicht um sieben.
 * Was jenseits davon liegt, steht in der Nachtzone — sichtbar draußen, aber
 * nicht aus dem Raster gefallen.
 *
 * Die Linien liegen bewusst hinter allem und in der leisesten Farbe, die die
 * Designsprache kennt. Sie sind Orientierung, nicht Taktung: Was ein Block
 * über sich sagt, steht im Block.
 */
export function DayGrid({
    blocks,
    courseBlocks,
    appointmentBlocks,
    selfInitial,
    frameFrom,
    frameTo,
    wakeTime,
    bedtime,
    canComplete,
    canShift,
    isToday,
    onToggle,
    onOpen,
    onOpenCourse,
    onToggleAppointment,
    onDrop,
    onAdjustWake,
    frameOverridden = false,
    ghost,
}: {
    blocks: Block[];
    /** Was der Stundenplan an diesem Tag belegt — liegt fest, reagiert nicht. */
    courseBlocks: Course[];
    /**
     * Fremde Gewohnheiten, für diesen Tag zugesagt.
     *
     * Wie ein Kurs: Sie liegen fest, lassen sich nicht abhaken und nicht
     * ziehen. Die Gewohnheit gehört jemand anderem.
     */
    appointmentBlocks: Appointment[];
    /** Die eigene Initiale — für das Doppel-Zeichen der Verabredung. */
    selfInitial: string;
    frameFrom: number;
    frameTo: number;
    wakeTime: string;
    bedtime: string;
    canComplete: boolean;
    /** Lässt sich an diesem Tag überhaupt noch etwas verlegen? */
    canShift: boolean;
    isToday: boolean;
    onToggle: (block: Block) => void;
    onOpen: (block: Block) => void;
    /** Ein Kurs wurde angetippt — seine Handlungen liegen im Sheet dahinter. */
    onOpenCourse: (block: Course) => void;
    /** Den eigenen Haken an einer zugesagten Verabredung umlegen. */
    onToggleAppointment: (block: Appointment) => void;
    /** Ein Block wurde losgelassen — jetzt kommt die Frage nach der Reichweite. */
    onDrop: (drag: BlockDrag) => void;
    /**
     * Die Aufsteh-Marke antippen — „heute war das anders".
     *
     * Fehlt an vergangenen Tagen: Sie liegen hinter uns, und ihren Anfang
     * nachträglich zu verschieben wäre eine Korrektur der eigenen Geschichte.
     * Dann bleibt die Marke der Link zum Schlafplan, der sie immer war.
     */
    onAdjustWake?: () => void;
    /** Gilt an diesem Tag schon ein eigener Rahmen? */
    frameOverridden?: boolean;
    /** Der Vorschlag der KI: sein Block, und wessen Platz er vorwegnimmt. */
    ghost: { block: Block; replaces: number } | null;
}) {
    const now = useNowMinute(isToday);

    // Gezeigt wird alles: der Rahmen, jeder Block und der Vorschlag der KI —
    // auch wenn einer davon außerhalb des Rahmens liegt. Die Kurse gehören
    // dazu: Eine Abendvorlesung unter einer frühen Schlafenszeit belegt auf
    // dem Server Zeit, und ein Raster, das sie nicht zeichnet, wäre genau der
    // Widerspruch zwischen Rechnung und Bild, den dieser Kalender vermeidet.
    const bounds = boundsFor(frameFrom, frameTo, [
        ...blocks,
        ...courseBlocks,
        ...appointmentBlocks,
        ...(ghost ? [ghost.block] : []),
    ]);

    // Geschoben werden darf trotzdem nur in den Rahmen: Der Server weist alles
    // andere ab, und ein Zug, der in eine Fehlermeldung führt, ist keiner.
    const reach = { from: frameFrom, to: frameTo, height: 0 };

    // Während ein Block getragen wird, liegt der Tag so da, wie er nach dem
    // Loslassen aussähe — samt der Gewohnheiten, die an ihm hängen. Man soll
    // sehen, was man anrichtet, bevor man loslässt.
    const drag = useBlockDrag({
        bounds: reach,
        enabled: canShift && ghost === null,
        onDrop,
    });

    const shown = drag.drag
        ? withDrag(blocks, drag.drag.id, drag.drag.minute)
        : blocks;

    // Was gerade wandert — der gezogene Block und alles, was an ihm hängt.
    // Es wird getrennt vom Rest gelegt: Teilte es sich unterwegs die Spalte
    // mit dem, worüber es gerade schwebt, spränge es beim Streifen eines
    // Kurses auf halbe Breite nach rechts. Es liegt stattdessen obenauf, in
    // voller Breite, und der Rest bleibt, wo er ist.
    const before = new Map(
        blocks.map((block) => [block.id, block.startMinute]),
    );
    const moving = drag.drag
        ? shown.filter(
              (block) =>
                  block.id === drag.drag?.id ||
                  block.startMinute !== before.get(block.id),
          )
        : [];
    const resting = shown.filter(
        (block) => !moving.some((other) => other.id === block.id),
    );

    // Beide Arten in einem Durchgang: Läge eine Gewohnheit auf einer
    // Vorlesung, müssten sie sich die Breite teilen wie zwei Gewohnheiten
    // auch. Zwei getrennte Aufrufe zeichneten sie übereinander.
    // `frameTo` als Grenze: Ein kurzer Block, der auf die Mindesthöhe wächst,
    // soll nicht über die Schlafenszeit ragen, an der er enden sollte.
    const placed = placeBlocks<GridBlock>(
        [
            ...(ghost ? [...resting, ghost.block] : resting),
            ...courseBlocks,
            ...appointmentBlocks,
        ],
        bounds,
        frameTo,
    );
    // Ein Durchgang, eine Liste: Der getragene Block behält sein Element —
    // wanderte er beim Anheben in eine zweite Liste, verlöre der Browser
    // den Griff (`setPointerCapture` hängt am Element, nicht an der Kennung).
    const lifted = new Set(moving.map((block) => block.id));
    const entries = [
        ...placed,
        ...placeBlocks<GridBlock>(moving, bounds, frameTo),
    ];

    // Ob die Stelle unter dem Finger überhaupt geht — steht am Zeitschild,
    // bevor man loslässt. Ein Kurs rückt nicht; das soll man sehen, nicht
    // erst im Pop-up lesen.
    const blockedBy = drag.drag
        ? collisionOf(
              blocks,
              courseBlocks,
              appointmentBlocks,
              drag.drag.id,
              drag.drag.minute,
          )
        : null;

    const showNow =
        now !== null && now >= bounds.from && now <= bounds.to && isToday;

    return (
        <div>
            <div className="relative" style={{ height: bounds.height }}>
                {/* Die Nacht: außerhalb des Rahmens wird nicht geplant. Sie
                    erscheint nur, wenn dort auch etwas liegt — sonst endet das
                    Raster am Rahmen, wie es soll. */}
                {frameFrom > bounds.from && (
                    <div
                        aria-hidden="true"
                        className="absolute inset-x-0 top-0 rounded-t-lg bg-sand/25"
                        style={{ height: offsetOf(frameFrom, bounds) }}
                    />
                )}
                {frameTo < bounds.to && (
                    <div
                        aria-hidden="true"
                        className="absolute inset-x-0 bottom-0 rounded-b-lg bg-sand/25"
                        style={{ top: offsetOf(frameTo, bounds) }}
                    />
                )}

                {/* Die Stunden. `aria-hidden`, weil eine Vorlesesoftware mit
                    sechzehn Uhrzeiten hintereinander nichts anfangen kann —
                    was gilt, sagt jeder Block selbst. */}
                <div aria-hidden="true">
                    {hourMarks(bounds, [frameFrom, frameTo]).map((minute) => (
                        <div
                            key={minute}
                            className="absolute inset-x-0 flex h-0 items-center gap-2"
                            style={{ top: offsetOf(minute, bounds) }}
                        >
                            <span
                                className="shrink-0 pr-2 text-right text-[11px] leading-none font-medium text-faintest tabular-nums"
                                style={{ width: GUTTER }}
                            >
                                {timeLabel(minute)}
                            </span>
                            <span className="h-px flex-1 bg-border" />
                        </div>
                    ))}
                </div>

                {/* Die beiden Ränder des Tages, an ihrer echten Minute. Sie
                    führen zum Schlafplan, weil sie dort herkommen — und sie
                    sind der Grund, aus dem „nach dem Aufstehen" genau hier
                    liegt und nicht eine Stunde daneben. */}
                <FrameMarker
                    icon={Sun}
                    label="Aufstehen"
                    time={wakeTime}
                    top={offsetOf(frameFrom, bounds)}
                    onAdjust={onAdjustWake}
                    adjusted={frameOverridden}
                />
                <FrameMarker
                    icon={Moon}
                    label="Schlafenszeit"
                    time={bedtime}
                    top={offsetOf(frameTo, bounds)}
                />

                <ul
                    className="absolute inset-y-0 right-0"
                    style={{ left: GUTTER }}
                >
                    {/* Der Schlüssel trägt die Art mit: Eine Verabredung
                        führt ihre eigene Kennung, und die kann dieselbe Zahl
                        sein wie die einer Gewohnheit. */}
                    {entries.map((entry) =>
                        entry.block.kind === 'course' ? (
                            <CourseBlock
                                key={`course-${entry.block.id}`}
                                placed={{ ...entry, block: entry.block }}
                                onOpen={onOpenCourse}
                            />
                        ) : entry.block.kind === 'appointment' ? (
                            <AppointmentBlock
                                key={`appointment-${entry.block.id}`}
                                placed={{ ...entry, block: entry.block }}
                                selfInitial={selfInitial}
                                onToggle={onToggleAppointment}
                            />
                        ) : (
                            <CalendarBlock
                                placed={{ ...entry, block: entry.block }}
                                selfInitial={selfInitial}
                                key={
                                    entry.block.id === ghost?.block.id &&
                                    entry.block === ghost.block
                                        ? `habit-${entry.block.id}-ghost`
                                        : `habit-${entry.block.id}`
                                }
                                canComplete={canComplete}
                                onToggle={onToggle}
                                // Der Klick nach dem Loslassen ist der Nachhall der
                                // Geste, nicht ihre eigene Absicht.
                                onOpen={(block) =>
                                    drag.swallowsClick() || onOpen(block)
                                }
                                dragging={drag.drag?.id === entry.block.id}
                                dragHandlers={
                                    canShift ? drag.handlers : undefined
                                }
                                ghost={entry.block === ghost?.block}
                                faded={
                                    ghost !== null &&
                                    entry.block !== ghost.block &&
                                    entry.block.id === ghost.replaces
                                }
                                lifted={lifted.has(entry.block.id)}
                            />
                        ),
                    )}
                </ul>

                {/* Die Zielzeit, solange der Block wandert — in derselben
                    Spalte wie die Stunden, damit man sie im Blick hat, ohne
                    den Finger zu heben. Liegt die Stelle in einem Kurs, sagt
                    das Schild es gleich: Loslassen ginge hier nicht. */}
                {drag.drag !== null && (
                    <div
                        className="pointer-events-none absolute inset-x-0 z-40 flex h-0 items-center gap-2"
                        style={{ top: offsetOf(drag.drag.minute, bounds) }}
                    >
                        <span
                            className={cn(
                                'shrink-0 rounded-full px-1.5 py-0.5 text-center text-[11px] leading-none font-semibold tabular-nums',
                                blockedBy === null
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-foreground text-background line-through',
                            )}
                            style={{ width: GUTTER }}
                        >
                            {timeLabel(drag.drag.minute)}
                        </span>
                        <span
                            className={cn(
                                'h-px flex-1',
                                blockedBy === null
                                    ? 'bg-primary/40'
                                    : 'bg-foreground/40',
                            )}
                        />
                        {blockedBy !== null && (
                            <span className="shrink-0 rounded-full bg-foreground px-2 py-0.5 text-[11px] leading-none font-semibold text-background">
                                {blockedBy.kind === 'habit'
                                    ? `dort liegt „${blockedBy.title}"`
                                    : `nicht während „${blockedBy.title}"`}
                            </span>
                        )}
                    </div>
                )}

                {/* Wo der Tag gerade steht. Nur heute, nur im Rahmen — eine
                    Linie auf einem vergangenen Tag wäre eine Behauptung über
                    einen Moment, den es dort nicht mehr gibt. */}
                {showNow && (
                    <div
                        className="pointer-events-none absolute inset-x-0 z-10 flex h-0 items-center gap-2"
                        style={{ top: offsetOf(now, bounds) }}
                    >
                        <span
                            className="shrink-0 pr-2 text-right text-[11px] leading-none font-semibold text-primary tabular-nums"
                            style={{ width: GUTTER }}
                        >
                            {timeLabel(now)}
                        </span>
                        <span className="relative h-0.5 flex-1 rounded-full bg-primary">
                            <span className="absolute -top-[3px] -left-1 size-2 rounded-full bg-primary" />
                        </span>
                    </div>
                )}

                {blocks.length === 0 &&
                    courseBlocks.length === 0 &&
                    appointmentBlocks.length === 0 && (
                        /* §1.5 — benannt wird, was gilt, nicht was fehlt. */
                        <p
                            className="absolute inset-x-0 top-6 text-center text-sm leading-relaxed text-muted-foreground"
                            style={{ paddingLeft: GUTTER }}
                        >
                            Für diesen Tag war nichts vorgesehen.
                        </p>
                    )}
            </div>
        </div>
    );
}

/**
 * Ein Rand des Tages — Aufstehen oben, Schlafenszeit unten.
 *
 * Kein Block, sondern eine Grenze: Der Marker hat keinen Haken und keine
 * Dauer, er sagt nur, wo das Raster anfängt und aufhört.
 *
 * Wohin er führt, hängt an der Frage, die man hier stellen kann. An einem
 * vergangenen Tag gibt es nur eine — „wo kommt das her?" —, und die Antwort
 * ist der Schlafplan. An einem Tag, der noch kommt, gibt es eine zweite:
 * „heute war das anders". Sie ist die häufigere, und deshalb liegt sie auf
 * der Marke selbst statt hinter einem Umweg über die Wocheneinstellung.
 */
function FrameMarker({
    icon: Icon,
    label,
    time,
    top,
    onAdjust,
    adjusted = false,
}: {
    icon: typeof Sun;
    label: string;
    time: string;
    /** Die Pixelhöhe im Raster — die Marke sitzt auf ihrer eigenen Minute. */
    top: number;
    /** Lässt sich dieser Rand für diesen einen Tag verschieben? */
    onAdjust?: () => void;
    /** Gilt hier schon eine Ausnahme? Dann ist die Uhrzeit eine eigene. */
    adjusted?: boolean;
}) {
    const shell =
        'group absolute inset-x-0 z-20 flex -translate-y-1/2 items-center gap-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';

    /* Zeichen und Uhrzeit bleiben in der Stundenspalte: Dort kommt kein Block
       hin, und die Grenze des Tages darf nicht dadurch unsichtbar werden, dass
       eine Gewohnheit genau an ihr endet — was seit „vor dem Schlafengehen"
       der Regelfall ist. */
    const inner = (
        <>
            <span
                className={cn(
                    'flex shrink-0 items-center justify-end gap-1 pr-1.5 text-[11px] leading-none font-semibold tabular-nums transition-colors duration-[var(--duration-press)] ease-out group-hover:text-primary',
                    // Ein eigener Rahmen für diesen Tag ist eine Abweichung
                    // und sieht auch so aus — sonst wäre nicht zu erkennen,
                    // dass hier etwas anderes gilt als in der Woche.
                    adjusted ? 'text-primary' : 'text-foreground',
                )}
                style={{ width: GUTTER }}
            >
                <Icon className="size-3" strokeWidth={2} aria-hidden="true" />
                {time}
            </span>
            {/* Die Grenze selbst, kräftiger als eine Stundenlinie. */}
            <span
                className={cn(
                    'h-px flex-1',
                    adjusted ? 'bg-primary/45' : 'bg-olive-mid/45',
                )}
                aria-hidden="true"
            />
        </>
    );

    if (onAdjust !== undefined) {
        return (
            <button
                type="button"
                onClick={onAdjust}
                aria-label={`${label} um ${time}, für diesen Tag ändern`}
                style={{ top }}
                className={`${shell} cursor-pointer`}
            >
                {inner}
            </button>
        );
    }

    return (
        <Link
            href={sleepShow()}
            aria-label={`${label} um ${time}, zum Schlafplan`}
            style={{ top }}
            className={shell}
        >
            {inner}
        </Link>
    );
}

/**
 * Die aktuelle Minute — oder null, solange keine feststeht.
 *
 * Der erste Wert kommt aus einem Timeout und nicht aus dem Effektkörper: Ein
 * `setState` direkt im Effekt löst eine zweite Renderrunde aus, bevor der
 * Browser überhaupt gezeichnet hat.
 */
function useNowMinute(active: boolean): number | null {
    const [minute, setMinute] = useState<number | null>(null);

    useEffect(() => {
        if (!active) {
            return;
        }

        const tick = () => {
            const now = new Date();
            setMinute(now.getHours() * 60 + now.getMinutes());
        };

        const first = window.setTimeout(tick, 0);
        const timer = window.setInterval(tick, 60_000);

        return () => {
            window.clearTimeout(first);
            window.clearInterval(timer);
        };
    }, [active]);

    return minute;
}
