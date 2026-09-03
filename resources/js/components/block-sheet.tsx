import { Check } from 'lucide-react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { BEHAVIOR_ICONS } from '@/lib/behavior-icons';
import { OUTLINE_BUTTON, PRIMARY_BUTTON, QUIET_LINK } from '@/lib/interaction';
import { cn } from '@/lib/utils';
import type { CalendarBlock as Block } from '@/types';

/** Der Weg zu einer KI-Funktion — ✦ steht nur hier (§8). */
const AI_LINK = `${QUIET_LINK} text-sm`;

/**
 * Der aufgeschlagene Block.
 *
 * Im Raster ist ein Block so hoch, wie die Gewohnheit dauert — bei zehn
 * Minuten sind das 44 Pixel, und dort ist kein Platz für zwei KI-Wege und
 * einen Warum-Satz. Statt die Blöcke künstlich aufzublasen (und damit das
 * Raster zur Lüge zu machen) zieht das Sheet die Handlungen heraus: Der Block
 * zeigt, **wann**, das Sheet zeigt **was man damit tun kann**.
 *
 * Der Haken ist bewusst nicht nur hier: Abhaken ist die häufigste Geste des
 * Tages und bleibt draußen am Block. Hier steht er ein zweites Mal, weil man
 * ihn nach dem Öffnen nicht suchen soll.
 */
export function BlockSheet({
    block,
    canComplete,
    onOpenChange,
    onToggle,
    onAdjust,
    onStuck,
    onUndoShift,
}: {
    block: Block | null;
    canComplete: boolean;
    onOpenChange: (open: boolean) => void;
    onToggle: (block: Block) => void;
    onAdjust: (block: Block) => void;
    onStuck: (block: Block) => void;
    /** Nimmt eine Verschiebung zurück, die nur für heute galt. */
    onUndoShift?: (block: Block) => void;
}) {
    const Icon = block ? BEHAVIOR_ICONS[block.behaviorType] : null;

    /** Ein Weg aus dem Sheet heraus in den nächsten — erst zu, dann auf. */
    function leaveFor(next: (block: Block) => void) {
        if (block === null) {
            return;
        }

        onOpenChange(false);
        next(block);
    }

    return (
        <Sheet open={block !== null} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="mx-auto max-h-[85vh] max-w-lg gap-0 overflow-y-auto rounded-t-2xl px-5 pt-6 pb-8"
            >
                <SheetHeader className="gap-2 p-0">
                    <SheetTitle className="type-eyebrow text-left text-muted-foreground">
                        {/* Bei fester Uhrzeit die belegte Spanne, sonst der
                            Anker: beide sagen, warum die Gewohnheit hier liegt. */}
                        {block?.timeRange ?? block?.anchor}
                    </SheetTitle>
                    <SheetDescription asChild>
                        <div className="flex items-center gap-3 text-left">
                            {Icon && (
                                <span
                                    className={cn(
                                        'flex size-11 shrink-0 items-center justify-center transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                        block?.completed
                                            ? 'rounded-full bg-primary text-primary-foreground'
                                            : 'rounded-xl bg-sand text-primary',
                                    )}
                                >
                                    <Icon
                                        className="size-5"
                                        strokeWidth={1.5}
                                        aria-hidden="true"
                                    />
                                </span>
                            )}
                            <span className="min-w-0 flex-1">
                                <span className="type-subheading block text-balance text-foreground">
                                    {block?.title}
                                </span>
                                {/* Die Dauer nur da, wo sie nicht schon in der
                                    Spanne oben steht. */}
                                {block?.measureLabel &&
                                    block.timeRange === null && (
                                        <span className="mt-0.5 block text-sm text-muted-foreground">
                                            {block.measureLabel}
                                        </span>
                                    )}
                            </span>
                        </div>
                    </SheetDescription>
                </SheetHeader>

                {/* Ein von Hand verschobener Block braucht einen Weg zurück.
                    Ohne ihn ließe sich die Ausnahme nur durch erneutes Ziehen
                    aufheben — und das wäre wieder eine Entscheidung, keine
                    Rücknahme. */}
                {block?.shifted && onUndoShift && (
                    <div className="mt-5 flex items-center justify-between gap-3 rounded-[14px] bg-sand/60 px-4 py-3">
                        <span className="text-sm leading-relaxed">
                            Heute ausnahmsweise hier.
                        </span>
                        <button
                            type="button"
                            onClick={() => onUndoShift(block)}
                            className={`${QUIET_LINK} shrink-0 text-sm`}
                        >
                            Zurücklegen
                        </button>
                    </div>
                )}

                {block?.smallestStep && (
                    <p className="mt-5 rounded-[14px] bg-sand/60 px-4 py-3 text-sm leading-relaxed">
                        → {block.smallestStep}
                    </p>
                )}

                {canComplete && block && (
                    <button
                        type="button"
                        onClick={() => {
                            onToggle(block);
                            onOpenChange(false);
                        }}
                        className={cn(
                            'mt-5',
                            block.completed
                                ? `${OUTLINE_BUTTON} h-12 w-full justify-center rounded-xl`
                                : PRIMARY_BUTTON,
                        )}
                    >
                        {block.completed ? (
                            'Doch noch offen'
                        ) : (
                            <>
                                <Check className="size-4" aria-hidden="true" />
                                Erledigt
                            </>
                        )}
                    </button>
                )}

                {/* Zwei Wege, zwei Fragen: „Wann" verschiebt den Block im Tag,
                    der kleine erste Schritt zerlegt die Gewohnheit selbst.
                    Beide stehen nur an lebenden Gewohnheiten — eine beendete
                    verschiebt man nicht mehr. */}
                {block && !block.graduated && (
                    <div className="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-border pt-4">
                        <button
                            type="button"
                            onClick={() => leaveFor(onAdjust)}
                            className={AI_LINK}
                        >
                            ✦ Anderer Zeitpunkt?
                        </button>
                        {!block.completed && (
                            <button
                                type="button"
                                onClick={() => leaveFor(onStuck)}
                                className={AI_LINK}
                            >
                                ✦ Kleinen ersten Schritt
                            </button>
                        )}
                    </div>
                )}

                {block?.graduated && (
                    <p className="mt-5 border-t border-border pt-4 text-sm leading-relaxed text-muted-foreground">
                        Diese Gewohnheit ist beendet. Der Tag, an dem sie lief,
                        bleibt stehen.
                    </p>
                )}
            </SheetContent>
        </Sheet>
    );
}
