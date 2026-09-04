import { Check } from 'lucide-react';
import { BEHAVIOR_ICONS } from '@/lib/behavior-icons';
import { MIN_BLOCK_HEIGHT, spanLabel } from '@/lib/day-grid';
import type { PlacedBlock } from '@/lib/day-grid';
import { cn } from '@/lib/utils';
import type { CalendarBlock as Block } from '@/types';

/**
 * Ab welcher Höhe auch die Icon-Kachel und der kleine erste Schritt Platz haben.
 *
 * Der Anker steht dagegen immer da, auch im kleinsten Block: Er ist der Grund,
 * aus dem die Gewohnheit an dieser Stelle liegt, und ein Block ohne ihn wäre
 * ein Titel, der irgendwo im Tag schwebt. Zwei Zeilen passen in 44 Pixel; die
 * dritte nicht.
 */
const SPACIOUS = 64;

/** Der Spalt zwischen zwei nebeneinanderliegenden Blöcken. */
const LANE_GAP = 4;

/**
 * Ein Block im Stundenraster.
 *
 * Er liegt an der Stelle, an der die Gewohnheit stattfindet — und zeichnet
 * dabei einen Unterschied mit, den die App sonst nur weiß: Eine feste Uhrzeit
 * bekommt eine durchgezogene Kante und ihre Spanne als Zeile, eine Situation
 * eine gestrichelte und ihren Anker. „Nach dem Frühstück" liegt im Raster
 * ungefähr dort, wo es hingehört, und behauptet keine Minute — der
 * Situationsanker schlägt in der Umfrage die feste Zeit (3,88 zu 3,50), und
 * ihn nachträglich in eine Uhrzeit umzudeuten wäre die falsche Richtung.
 *
 * Der Block führt in seine Handlungen; der Haken bleibt außen. Abhaken ist die
 * häufigste Geste des Tages und darf keinen Umweg über ein Sheet nehmen.
 */
export function CalendarBlock({
    placed,
    canComplete,
    onToggle,
    onOpen,
    ghost = false,
    faded = false,
    dragging = false,
    lifted = false,
    dragHandlers,
}: {
    placed: PlacedBlock<Block>;
    canComplete: boolean;
    onToggle: (block: Block) => void;
    /** Öffnet den Block — dort stehen Anpassung, Starthilfe und der Rest. */
    onOpen?: (block: Block) => void;
    /** Der Vorschlag der KI an seiner möglichen neuen Stelle. */
    ghost?: boolean;
    /** Der bisherige Platz, während der Ghost woanders liegt. */
    faded?: boolean;
    /** Wird dieser Block gerade getragen? */
    dragging?: boolean;
    /** Rutscht er gerade mit — an der Kette des Getragenen? Dann liegt er mit obenauf. */
    lifted?: boolean;
    /** Die Geste — sie hängt an der Fläche, die auch ins Sheet führt. */
    dragHandlers?: {
        onPointerDown: (event: React.PointerEvent, block: Block) => void;
        onPointerMove: (event: React.PointerEvent) => void;
        onPointerUp: () => void;
        onPointerCancel: () => void;
    };
}) {
    const { block, top, height, lane, lanes } = placed;
    const Icon = BEHAVIOR_ICONS[block.behaviorType];

    const drawn = Math.max(height, MIN_BLOCK_HEIGHT);
    const spacious = drawn >= SPACIOUS;
    const width = `calc((100% - ${(lanes - 1) * LANE_GAP}px) / ${lanes})`;

    return (
        <li
            className={cn(
                'absolute',
                // Getragen liegt der Block über allem anderen — sonst
                // verschwände er unter dem Block, an dem er vorbeiwandert.
                dragging
                    ? 'z-30 transition-none'
                    : 'transition-[top] duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                lifted && !dragging && 'z-20',
            )}
            style={{
                top,
                height: drawn,
                width,
                left: `calc((${width} + ${LANE_GAP}px) * ${lane})`,
            }}
        >
            <div
                className={cn(
                    'flex h-full overflow-hidden rounded-[10px] transition-opacity duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                    // Die Kante trägt die Auskunft: durchgezogen heißt Uhrzeit,
                    // gestrichelt heißt ungefähr hier.
                    block.exact
                        ? 'border-l-[3px] border-primary'
                        : 'border-l-[3px] border-dashed border-olive-mid',
                    ghost && 'border-2 border-dashed border-primary bg-sand/40',
                    faded && 'opacity-40',
                    // Aufgenommen: Der Block hebt sich sichtbar von der Fläche
                    // ab, damit die Geste eine Rückmeldung hat (Apple §1 —
                    // Rückmeldung gehört auf das Drücken).
                    dragging && 'shadow-lift ring-2 ring-primary',
                    // Die Karte, auf der das Raster liegt, ist weiß — ein
                    // weißer Block darauf wäre nur seine Kante. `track` ist der
                    // leiseste Ton der Designsprache, der noch eine Fläche ist;
                    // erledigt füllt sich der Block auf `sand`.
                    !ghost && (block.completed ? 'bg-accent' : 'bg-track'),
                )}
            >
                {/* Die ganze Fläche ist der Weg hinein — ein eigener Knopf
                    darin wäre bei 44 Pixeln Höhe nicht mehr zu treffen. */}
                {onOpen && !ghost ? (
                    <button
                        type="button"
                        onClick={() => onOpen(block)}
                        onPointerDown={(event) =>
                            dragHandlers?.onPointerDown(event, block)
                        }
                        onPointerMove={dragHandlers?.onPointerMove}
                        onPointerUp={dragHandlers?.onPointerUp}
                        onPointerCancel={dragHandlers?.onPointerCancel}
                        aria-label={`${block.title} öffnen`}
                        // Vor dem Langdruck gehört die senkrechte Bewegung dem
                        // Scrollen; erst danach dem Block. Im Zweifel gewinnt
                        // die Liste.
                        style={{ touchAction: dragging ? 'none' : 'pan-y' }}
                        className="min-w-0 flex-1 cursor-pointer px-2.5 py-1 text-left focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
                    >
                        <BlockBody
                            block={block}
                            Icon={Icon}
                            spacious={spacious}
                        />
                    </button>
                ) : (
                    <div className="min-w-0 flex-1 px-2.5 py-1">
                        <BlockBody
                            block={block}
                            Icon={Icon}
                            spacious={spacious}
                        />
                    </div>
                )}

                {/* Ohne Nachtrag-Recht bleibt der Zustand sichtbar, aber
                    unantastbar: ein toter Knopf wäre eine Einladung, die
                    zurückgewiesen wird. */}
                {canComplete && !ghost ? (
                    <button
                        type="button"
                        onClick={() => onToggle(block)}
                        aria-pressed={block.completed}
                        aria-label={
                            block.completed
                                ? `${block.title} als noch offen markieren`
                                : `${block.title} als erledigt markieren`
                        }
                        className="flex w-11 shrink-0 cursor-pointer items-center justify-center self-stretch transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
                    >
                        <Tick completed={block.completed} />
                    </button>
                ) : (
                    <span
                        aria-hidden="true"
                        className="flex w-11 shrink-0 items-center justify-center self-stretch"
                    >
                        <Tick completed={block.completed} />
                    </span>
                )}
            </div>
        </li>
    );
}

/**
 * Was im Block steht — je nach Höhe mehr oder weniger.
 *
 * Die Zeile über dem Titel ist bei fester Uhrzeit die belegte Spanne und sonst
 * der Anker. Beide sagen dasselbe: warum die Gewohnheit hier liegt.
 */
function BlockBody({
    block,
    Icon,
    spacious,
}: {
    block: Block;
    Icon: (typeof BEHAVIOR_ICONS)[keyof typeof BEHAVIOR_ICONS];
    spacious: boolean;
}) {
    return (
        <span className="flex h-full items-start gap-2">
            {spacious && (
                <span
                    className={cn(
                        'mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-lg',
                        block.completed
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-sand text-primary',
                    )}
                >
                    <Icon
                        className="size-3.5"
                        strokeWidth={1.5}
                        aria-hidden="true"
                    />
                </span>
            )}

            <span className="min-w-0 flex-1">
                <span className="type-eyebrow block truncate text-muted-foreground">
                    {spanLabel(block)}
                    {/* Nur heute hierher gelegt — die Marke sagt, dass morgen
                        wieder der reguläre Zeitpunkt gilt. */}
                    {block.shifted && (
                        <span className="ml-1.5 text-primary">· nur heute</span>
                    )}
                </span>
                <span
                    className={cn(
                        'block truncate text-[13px] leading-tight font-semibold',
                        block.completed ? 'text-olive-mid' : 'text-foreground',
                    )}
                >
                    {block.title}
                </span>
                {/* Der kleine erste Schritt steht nur da, wo er auch hinpasst
                    — sonst bliebe von ihm ein Wort und drei Punkte. */}
                {spacious && block.smallestStep && !block.completed && (
                    <span className="mt-0.5 block truncate text-[11px] text-muted-foreground">
                        → {block.smallestStep}
                    </span>
                )}
            </span>
        </span>
    );
}

function Tick({ completed }: { completed: boolean }) {
    return (
        <span
            className={cn(
                'flex size-6 items-center justify-center rounded-full transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                completed ? 'bg-primary' : 'border-2 border-dashed border-sand',
            )}
        >
            {completed && (
                <Check
                    className="size-3.5 text-primary-foreground"
                    strokeWidth={2.5}
                    aria-hidden="true"
                />
            )}
        </span>
    );
}
