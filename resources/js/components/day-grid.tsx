import { Link } from '@inertiajs/react';
import { Moon, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';
import { CalendarBlock } from '@/components/calendar-block';
import { useBlockDrag } from '@/hooks/use-block-drag';
import type { BlockDrag } from '@/hooks/use-block-drag';
import {
    gridBounds,
    hourMarks,
    offsetOf,
    placeBlocks,
    timeLabel,
    withDrag,
} from '@/lib/day-grid';
import { show as sleepShow } from '@/routes/sleep';
import type { CalendarBlock as Block } from '@/types';

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
    frameFrom,
    frameTo,
    wakeTime,
    bedtime,
    canComplete,
    canShift,
    isToday,
    onToggle,
    onOpen,
    onDrop,
    ghost,
}: {
    blocks: Block[];
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
    /** Ein Block wurde losgelassen — jetzt kommt die Frage nach der Reichweite. */
    onDrop: (drag: BlockDrag) => void;
    /** Der Vorschlag der KI: sein Block, und wessen Platz er vorwegnimmt. */
    ghost: { block: Block; replaces: number } | null;
}) {
    const now = useNowMinute(isToday);

    // Der Ghost darf über den Rahmen hinausragen — ein Vorschlag um 23:30 muss
    // sichtbar sein, damit man ihn ablehnen kann.
    const bounds = gridBounds(
        Math.min(frameFrom, ghost?.block.startMinute ?? frameFrom),
        Math.max(
            frameTo,
            ghost?.block.startMinute !== null &&
                ghost?.block.startMinute !== undefined
                ? ghost.block.startMinute + (ghost.block.durationMinutes ?? 0)
                : frameTo,
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

    const placed = placeBlocks(ghost ? [...shown, ghost.block] : shown, bounds);

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
                    {placed.map((entry) => (
                        <CalendarBlock
                            key={
                                entry.block.id === ghost?.block.id &&
                                entry.block === ghost.block
                                    ? `${entry.block.id}-ghost`
                                    : entry.block.id
                            }
                            placed={entry}
                            canComplete={canComplete}
                            onToggle={onToggle}
                            // Der Klick nach dem Loslassen ist der Nachhall der
                            // Geste, nicht ihre eigene Absicht.
                            onOpen={(block) =>
                                drag.swallowsClick() || onOpen(block)
                            }
                            dragging={drag.drag?.id === entry.block.id}
                            dragHandlers={canShift ? drag.handlers : undefined}
                            ghost={entry.block === ghost?.block}
                            faded={
                                ghost !== null &&
                                entry.block !== ghost.block &&
                                entry.block.id === ghost.replaces
                            }
                        />
                    ))}
                </ul>

                {/* Die Zielzeit, solange der Block wandert — in derselben
                    Spalte wie die Stunden, damit man sie im Blick hat, ohne
                    den Finger zu heben. */}
                {drag.drag !== null && (
                    <div
                        className="pointer-events-none absolute inset-x-0 z-40 flex items-center gap-2"
                        style={{ top: offsetOf(drag.drag.minute, bounds) }}
                    >
                        <span
                            className="shrink-0 -translate-y-1/2 rounded-full bg-primary px-1.5 py-0.5 text-center text-[11px] leading-none font-semibold text-primary-foreground tabular-nums"
                            style={{ width: GUTTER }}
                        >
                            {timeLabel(drag.drag.minute)}
                        </span>
                        <span className="h-px flex-1 bg-primary/40" />
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

                {blocks.length === 0 && (
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

            {homeless.length > 0 && (
                <ul className="mt-4 flex flex-col gap-2 border-t border-border pt-4">
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
