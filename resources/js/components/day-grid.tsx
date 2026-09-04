import { Link } from '@inertiajs/react';
import { Moon, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';
import { CalendarBlock } from '@/components/calendar-block';
import { CourseBlock } from '@/components/course-block';
import { useBlockDrag } from '@/hooks/use-block-drag';
import type { BlockDrag } from '@/hooks/use-block-drag';
import type { GridBlock } from '@/lib/day-grid';
import {
    collisionOf,
    gridBounds,
    hourMarks,
    offsetOf,
    placeBlocks,
    timeLabel,
    withDrag,
} from '@/lib/day-grid';
import { cn } from '@/lib/utils';
import { show as sleepShow } from '@/routes/sleep';
import type { CalendarBlock as Block, CourseBlock as Course } from '@/types';

/** Die Breite der Stundenspalte links — „07:00" plus Luft. */
const GUTTER = 'calc(var(--spacing) * 13)';

/**
 * Der Tag als Stundenraster.
 *
 * Das Raster reicht vom Aufstehen bis zur Schlafenszeit und keine Minute
 * weiter. Die Stunden davor und danach zu zeichnen hieße, den Tag mit Platz zu
 * füllen, in den nichts geplant werden darf — der Schlafplan ist die Grenze
 * der Planung, nicht eine Empfehlung darin.
 *
 * Die Linien liegen bewusst hinter allem und in der leisesten Farbe, die die
 * Designsprache kennt. Sie sind Orientierung, nicht Taktung: Was ein Block
 * über sich sagt, steht im Block.
 */
export function DayGrid({
    blocks,
    courseBlocks,
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
    onDrop,
    ghost,
}: {
    blocks: Block[];
    /** Was der Stundenplan an diesem Tag belegt — liegt fest, reagiert nicht. */
    courseBlocks: Course[];
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
    /** Ein Block wurde losgelassen — jetzt kommt die Frage nach der Reichweite. */
    onDrop: (drag: BlockDrag) => void;
    /** Der Vorschlag der KI: sein Block, und wessen Platz er vorwegnimmt. */
    ghost: { block: Block; replaces: number } | null;
}) {
    const now = useNowMinute(isToday);

    // Der Ghost darf über den Rahmen hinausragen — ein Vorschlag um 23:30 muss
    // sichtbar sein, damit man ihn ablehnen kann.
    //
    // Und die Kurse ebenso: Eine Abendvorlesung unter einer frühen
    // Schlafenszeit belegt auf dem Server Zeit. Zeichnete das Raster sie
    // nicht, wäre das genau der Widerspruch zwischen Rechnung und Bild, den
    // dieser Kalender vermeiden soll.
    const bounds = gridBounds(
        Math.min(
            frameFrom,
            ghost?.block.startMinute ?? frameFrom,
            ...courseBlocks.map((course) => course.startMinute),
        ),
        Math.max(
            frameTo,
            ghost?.block.startMinute !== null &&
                ghost?.block.startMinute !== undefined
                ? ghost.block.startMinute + (ghost.block.durationMinutes ?? 0)
                : frameTo,
            ...courseBlocks.map(
                (course) => course.startMinute + course.durationMinutes,
            ),
        ),
    );

    // Während ein Block getragen wird, liegt der Tag so da, wie er nach dem
    // Loslassen aussähe — samt der Gewohnheiten, die an ihm hängen. Man soll
    // sehen, was man anrichtet, bevor man loslässt.
    const drag = useBlockDrag({
        bounds,
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
    const placed = placeBlocks<GridBlock>(
        [...(ghost ? [...resting, ghost.block] : resting), ...courseBlocks],
        bounds,
    );
    // Ein Durchgang, eine Liste: Der getragene Block behält sein Element —
    // wanderte er beim Anheben in eine zweite Liste, verlöre der Browser
    // den Griff (`setPointerCapture` hängt am Element, nicht an der Kennung).
    const lifted = new Set(moving.map((block) => block.id));
    const entries = [...placed, ...placeBlocks<GridBlock>(moving, bounds)];

    // Ob die Stelle unter dem Finger überhaupt geht — steht am Zeitschild,
    // bevor man loslässt. Ein Kurs rückt nicht; das soll man sehen, nicht
    // erst im Pop-up lesen.
    const blockedBy = drag.drag
        ? collisionOf(blocks, courseBlocks, drag.drag.id, drag.drag.minute)
        : null;

    // Was gar keine Stelle im Tag hat, verschwindet nicht — es steht unter dem
    // Raster. Eine Gewohnheit, deren Kette gerissen ist, wäre sonst weg.
    const homeless = blocks.filter((block) => block.startMinute === null);

    const showNow =
        now !== null && now >= bounds.from && now <= bounds.to && isToday;

    return (
        <div>
            <FrameMarker icon={Sun} label="Aufstehen" time={wakeTime} />

            <div className="relative mt-1" style={{ height: bounds.height }}>
                {/* Die Stunden. `aria-hidden`, weil eine Vorlesesoftware mit
                    sechzehn Uhrzeiten hintereinander nichts anfangen kann —
                    was gilt, sagt jeder Block selbst. */}
                <div aria-hidden="true">
                    {hourMarks(bounds).map((minute) => (
                        <div
                            key={minute}
                            className="absolute inset-x-0 flex items-center gap-2"
                            style={{ top: offsetOf(minute, bounds) }}
                        >
                            <span
                                className="shrink-0 -translate-y-1/2 pr-2 text-right text-[11px] leading-none font-medium text-faintest tabular-nums"
                                style={{ width: GUTTER }}
                            >
                                {timeLabel(minute)}
                            </span>
                            <span className="h-px flex-1 bg-border" />
                        </div>
                    ))}
                </div>

                <ul
                    className="absolute inset-y-0 right-0"
                    style={{ left: GUTTER }}
                >
                    {entries.map((entry) =>
                        entry.block.kind === 'course' ? (
                            <CourseBlock
                                key={entry.block.id}
                                placed={{ ...entry, block: entry.block }}
                                onOpen={onOpenCourse}
                            />
                        ) : (
                            <CalendarBlock
                                placed={{ ...entry, block: entry.block }}
                                key={
                                    entry.block.id === ghost?.block.id &&
                                    entry.block === ghost.block
                                        ? `${entry.block.id}-ghost`
                                        : entry.block.id
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
                        className="pointer-events-none absolute inset-x-0 z-40 flex items-center gap-2"
                        style={{ top: offsetOf(drag.drag.minute, bounds) }}
                    >
                        <span
                            className={cn(
                                'shrink-0 -translate-y-1/2 rounded-full px-1.5 py-0.5 text-center text-[11px] leading-none font-semibold tabular-nums',
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
                            <span className="shrink-0 -translate-y-1/2 rounded-full bg-foreground px-2 py-0.5 text-[11px] leading-none font-semibold text-background">
                                {blockedBy.kind === 'course'
                                    ? `nicht während „${blockedBy.title}"`
                                    : `dort liegt „${blockedBy.title}"`}
                            </span>
                        )}
                    </div>
                )}

                {/* Wo der Tag gerade steht. Nur heute, nur im Rahmen — eine
                    Linie auf einem vergangenen Tag wäre eine Behauptung über
                    einen Moment, den es dort nicht mehr gibt. */}
                {showNow && (
                    <div
                        className="pointer-events-none absolute inset-x-0 z-10 flex items-center gap-2"
                        style={{ top: offsetOf(now, bounds) }}
                    >
                        <span
                            className="shrink-0 -translate-y-1/2 pr-2 text-right text-[11px] leading-none font-semibold text-primary tabular-nums"
                            style={{ width: GUTTER }}
                        >
                            {timeLabel(now)}
                        </span>
                        <span className="relative h-0.5 flex-1 rounded-full bg-primary">
                            <span className="absolute -top-[3px] -left-1 size-2 rounded-full bg-primary" />
                        </span>
                    </div>
                )}

                {blocks.length === 0 && courseBlocks.length === 0 && (
                    /* §1.5 — benannt wird, was gilt, nicht was fehlt. */
                    <p
                        className="absolute inset-x-0 top-6 text-center text-sm leading-relaxed text-muted-foreground"
                        style={{ paddingLeft: GUTTER }}
                    >
                        Für diesen Tag war nichts vorgesehen.
                    </p>
                )}
            </div>

            <FrameMarker icon={Moon} label="Schlafenszeit" time={bedtime} />

            {/* Was im Tag keine Stelle hat: die verdrängten, und die seltene
                gerissene Kette. Die Überschrift sagt, was diese Zone ist —
                sonst sähe sie aus wie ein Rest, der nicht ins Raster passte. */}
            {homeless.length > 0 && (
                <p className="type-eyebrow mt-4 border-t border-border pt-4 text-muted-foreground">
                    Ohne festen Platz
                </p>
            )}
            {homeless.length > 0 && (
                <ul className="mt-2 flex flex-col gap-2">
                    {homeless.map((block) => (
                        <li key={block.id}>
                            <button
                                type="button"
                                onClick={() => onOpen(block)}
                                className="w-full cursor-pointer rounded-[10px] bg-card px-3 py-2 text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            >
                                <span className="type-eyebrow block text-muted-foreground">
                                    {block.anchor}
                                </span>
                                <span className="block text-[13px] leading-tight font-semibold">
                                    {block.title}
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

/**
 * Ein Rand des Tages — Aufstehen oben, Schlafenszeit unten.
 *
 * Kein Block, sondern eine Grenze: Der Marker hat keinen Haken und keine
 * Dauer, er sagt nur, wo das Raster anfängt und aufhört. Er führt zum
 * Schlafplan, weil er dort herkommt.
 */
function FrameMarker({
    icon: Icon,
    label,
    time,
}: {
    icon: typeof Sun;
    label: string;
    time: string;
}) {
    return (
        <Link
            href={sleepShow()}
            className="flex items-center gap-2 rounded-xl py-1 text-muted-foreground transition-colors duration-[var(--duration-press)] ease-out hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
        >
            <span
                className="flex shrink-0 justify-end pr-2"
                style={{ width: GUTTER }}
            >
                <Icon className="size-4" strokeWidth={1.5} aria-hidden="true" />
            </span>
            <span className="text-xs font-semibold tabular-nums">{time}</span>
            <span className="text-xs">{label}</span>
            <span className="ml-1 h-px flex-1 bg-border" aria-hidden="true" />
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
