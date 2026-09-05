import { router } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useEffect } from 'react';
import { AiMascot } from '@/components/ai-mascot';
import type { MascotState } from '@/components/ai-mascot';
import { AiSuggestionFailure } from '@/components/ai-suggestion';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { useDayOrder } from '@/hooks/use-day-order';
import { spanLabel } from '@/lib/day-grid';
import { BOTTOM_SHEET } from '@/lib/interaction';
import { store, suggestions } from '@/routes/calendar/order';
import type { CalendarBlock } from '@/types';

const ACTION_BUTTON =
    'inline-flex h-12 flex-1 cursor-pointer items-center justify-center rounded-xl px-4 text-[15px] font-semibold transition-[background-color,scale] duration-[var(--duration-press)] ease-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:pointer-events-none disabled:opacity-50 motion-safe:active:scale-[0.97]';

/**
 * Der ganze Tag, neu geordnet.
 *
 * Die Einzelanpassung verschiebt eine Gewohnheit und lässt die übrigen stehen.
 * Sobald sich der Tag insgesamt verschoben hat — eine längere Dauer, ein
 * anderer Schlafrhythmus —, ist das die falsche Frage: Dann geht es nicht um
 * eine Gewohnheit, sondern um die Reihenfolge.
 *
 * Vorher und Nachher stehen nebeneinander, damit sichtbar ist, was sich
 * ändert. Übernommen wird nichts von selbst (ki-assistent-design.md §2).
 */
export function DayOrderSheet({
    open,
    date,
    blocks,
    onOpenChange,
}: {
    open: boolean;
    /** Der Tag, um den es geht — „YYYY-MM-DD". */
    date: string;
    /** Der Tag, wie er jetzt liegt — für den Vorher-Vergleich. */
    blocks: CalendarBlock[];
    onOpenChange: (open: boolean) => void;
}) {
    const suggestion = useDayOrder();
    /**
     * Was die Figur im Kopf gerade tut.
     *
     * Kein eigener Zustand — dieselben zwei Flags, die auch die Skelette und
     * die Absage steuern. Was die KI tut, steht damit an genau einer Stelle.
     */
    const mascotState: MascotState = suggestion.loading
        ? 'thinking'
        : suggestion.failed
          ? 'stumped'
          : 'speaking';

    useEffect(() => {
        if (open) {
            void suggestion.load(suggestions.url(), date);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, date]);

    /** Wo die Gewohnheit bisher stand — für die Zeile „vorher → nachher". */
    function previousAnchor(id: number): string | null {
        const block = blocks.find((candidate) => candidate.id === id);

        return block ? spanLabel(block) : null;
    }

    function apply() {
        router.post(
            store.url(),
            {
                date,
                order: suggestion.order.map((row) => ({
                    id: row.id,
                    time: row.time,
                })),
            },
            { preserveScroll: true },
        );

        onOpenChange(false);
    }

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="bottom" className={BOTTOM_SHEET}>
                <SheetHeader className="flex-row items-start gap-3 p-0">
                    {/* Die Figur steht neben dem Text, nicht davor:
                        Sie ist hier kein Aufzählungszeichen, sondern
                        der Gegenüber, der gerade überlegt oder
                        antwortet. Der Zustand kommt aus demselben
                        `loading`/`failed`, das die Skelette steuert. */}
                    <AiMascot
                        state={mascotState}
                        className="mt-0.5 size-9 shrink-0 text-primary"
                    />

                    <div className="flex min-w-0 flex-col gap-2">
                        <SheetTitle className="type-eyebrow text-primary">
                            Dein Tag, neu geordnet
                        </SheetTitle>
                        <SheetDescription className="text-left text-[15px] leading-relaxed text-foreground">
                            {suggestion.refusal ?? suggestion.reason ?? (
                                <Skeleton
                                    as="span"
                                    className="inline-block h-5 w-3/4 align-middle"
                                />
                            )}
                        </SheetDescription>
                    </div>
                </SheetHeader>

                <div className="mt-5 flex flex-col gap-2">
                    {suggestion.loading ? (
                        <>
                            <Skeleton className="h-14 rounded-[14px]" />
                            <Skeleton className="h-14 rounded-[14px]" />
                            <Skeleton className="h-14 rounded-[14px]" />
                        </>
                    ) : suggestion.failed ? (
                        <AiSuggestionFailure
                            onRetry={() =>
                                void suggestion.load(suggestions.url(), date)
                            }
                        />
                    ) : (
                        suggestion.order.map((row) => {
                            const before = previousAnchor(row.id);
                            const moved = before !== row.timeRange;

                            return (
                                <div
                                    key={row.id}
                                    className="rounded-[14px] bg-card px-4 py-3"
                                >
                                    <p className="text-[15px] leading-snug font-semibold">
                                        {row.title}
                                    </p>
                                    <p className="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                                        {/* Was bleibt, muss sich nicht als
                                            Veränderung ausgeben — nur was
                                            wandert, bekommt den Pfeil. */}
                                        {moved && before !== null && (
                                            <>
                                                <span className="line-through">
                                                    {before}
                                                </span>
                                                <ArrowRight
                                                    className="size-3"
                                                    aria-hidden="true"
                                                />
                                            </>
                                        )}
                                        <span
                                            className={
                                                moved
                                                    ? 'font-semibold text-foreground'
                                                    : undefined
                                            }
                                        >
                                            {row.timeRange}
                                        </span>
                                    </p>
                                </div>
                            );
                        })
                    )}
                </div>

                {/* Was die Umordnung kostet, steht vor der Entscheidung — nicht
                    danach: Aus Situationen werden feste Uhrzeiten, und das ist
                    der Preis einer ausgerechneten Reihenfolge. */}
                {suggestion.order.length > 0 && (
                    <p className="mt-4 text-xs leading-relaxed text-muted-foreground">
                        Alle bekommen dabei eine feste Uhrzeit.
                    </p>
                )}

                <div className="mt-5 flex gap-3">
                    <button
                        type="button"
                        onClick={() => onOpenChange(false)}
                        className={`${ACTION_BUTTON} border border-primary text-primary hover:bg-accent`}
                    >
                        Lass so
                    </button>
                    <button
                        type="button"
                        onClick={apply}
                        disabled={suggestion.order.length === 0}
                        className={`${ACTION_BUTTON} bg-primary text-primary-foreground hover:bg-primary/90`}
                    >
                        Übernehmen
                    </button>
                </div>
            </SheetContent>
        </Sheet>
    );
}
