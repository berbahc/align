import { Link } from '@inertiajs/react';
import { CalendarClock, Check } from 'lucide-react';
import { AiMascot } from '@/components/ai-mascot';
import { HabitGlyph } from '@/components/habit-glyph';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { ASSUMED_MINUTES, spanLabel } from '@/lib/day-grid';
import {
    AI_LINK,
    BOTTOM_SHEET,
    OUTLINE_BUTTON,
    PRIMARY_BUTTON,
    QUIET_LINK,
} from '@/lib/interaction';
import { cn } from '@/lib/utils';
import { day as calendarDay } from '@/routes/calendar';
import type { CalendarBlock as Block } from '@/types';

/** „Montag, 8. September" — der Tag, an den der Weg führt. */
function dayLabel(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('de-DE', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
}

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
    date,
}: {
    block: Block | null;
    canComplete: boolean;
    onOpenChange: (open: boolean) => void;
    onToggle: (block: Block) => void;
    onAdjust: (block: Block) => void;
    onStuck: (block: Block) => void;
    /** Nimmt eine Verschiebung zurück, die nur für heute galt. */
    onUndoShift?: (block: Block) => void;
    /** Der gezeigte Tag — der Weg zum Konflikttag entfällt, wenn er es ist. */
    date: string;
}) {
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
            <SheetContent side="bottom" className={BOTTOM_SHEET}>
                <SheetHeader className="gap-2 p-0">
                    <SheetTitle className="type-eyebrow text-left text-muted-foreground">
                        {/* Bei fester Uhrzeit die belegte Spanne, sonst der
                            Anker: beide sagen, warum die Gewohnheit hier liegt. */}
                        {block === null ? null : spanLabel(block)}
                    </SheetTitle>
                    <SheetDescription asChild>
                        <div className="flex items-center gap-3 text-left">
                            {block !== null && (
                                <span
                                    className={cn(
                                        'flex size-11 shrink-0 items-center justify-center transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                        block?.completed
                                            ? 'rounded-full bg-primary text-primary-foreground'
                                            : 'rounded-xl bg-sand text-primary',
                                    )}
                                >
                                    <HabitGlyph
                                        habit={block}
                                        className="size-5"
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
                                {/* Die Annahme beim Namen nennen: Wer sie
                                    nicht kennt, wundert sich, warum direkt
                                    danach schon etwas liegen darf. */}
                                {block?.durationMinutes === null &&
                                    block.startMinute !== null && (
                                        <span className="mt-0.5 block text-sm text-muted-foreground">
                                            Ohne festgelegte Dauer rechnet Align
                                            mit {ASSUMED_MINUTES} Minuten. Die
                                            Dauer legst du beim Anpassen fest.
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

                {/* Selbst umlegen statt fragen.
                    Der Weg führt an den ersten Tag, an dem der Kurs den alten
                    Platz wirklich nimmt — dort steht er im Raster, und man
                    sieht die Lücken daneben. Einen neuen Platz wählt man
                    sinnvoll nur da, wo man sieht, wogegen man ihn wählt.
                    Steht man schon auf diesem Tag, fehlt der Knopf: Er führte
                    dorthin, wo man ist. */}
                {block?.conflictDate && block.conflictDate !== date && (
                    <Link
                        href={calendarDay(block.conflictDate)}
                        onClick={() => onOpenChange(false)}
                        className={`${OUTLINE_BUTTON} mt-5 h-12 w-full justify-center rounded-xl`}
                    >
                        <CalendarClock className="size-4" aria-hidden="true" />
                        Selbst umlegen am {dayLabel(block.conflictDate)}
                    </Link>
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
                            className={`${AI_LINK} text-sm`}
                        >
                            <AiMascot
                                variant="mark"
                                className="size-4 shrink-0"
                            />
                            Anderer Zeitpunkt?
                        </button>
                        {!block.completed && (
                            <button
                                type="button"
                                onClick={() => leaveFor(onStuck)}
                                className={`${AI_LINK} text-sm`}
                            >
                                <AiMascot
                                    variant="mark"
                                    className="size-4 shrink-0"
                                />
                                Kleinen ersten Schritt
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
