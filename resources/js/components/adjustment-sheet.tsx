import { router } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { useEffect, useState } from 'react';
import { AiSuggestionFailure } from '@/components/ai-suggestion';
import { formatWeekdays } from '@/components/schedule-picker';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { useAnchorSuggestions } from '@/hooks/use-anchor-suggestions';
import { cn } from '@/lib/utils';
import { store, suggestions } from '@/routes/habits/adjustment';
import type { AnchorAlternative, CalendarBlock } from '@/types';

const ACTION_BUTTON =
    'inline-flex h-12 flex-1 cursor-pointer items-center justify-center rounded-xl px-4 text-[15px] font-semibold transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:opacity-50';

/** Wie eine Alternative auf der Achse heißt — dasselbe Format wie `scheduleLabel()`. */
export function alternativeLabel(alternative: AnchorAlternative): string {
    if (alternative.situation) {
        return alternative.situation;
    }

    return `${alternative.time} · ${formatWeekdays(alternative.days ?? [])}`;
}

/**
 * Die Anpassungs-Karte: ein anderer Zeitpunkt, vorgeschlagen und ablehnbar.
 *
 * Die Beobachtung steht **vor** dem Vorschlag — erst was aufgefallen ist, dann
 * was daraus folgt. Ohne diesen Satz wäre die Anpassung eine Ansage aus dem
 * Nichts statt einer nachvollziehbaren Empfehlung (Transparenz-light).
 *
 * „Lass so" und „Übernehmen" sind gleich breit. Ein kleinerer Ablehn-Knopf wäre
 * eine Empfehlung, keine Wahl — und träfe bei einem Schuldwert von ø 3,92 genau
 * die falsche Stelle.
 */
export function AdjustmentSheet({
    block,
    onOpenChange,
    onPreview,
}: {
    block: CalendarBlock | null;
    onOpenChange: (open: boolean) => void;
    /** Meldet die gewählte Alternative nach oben, damit der Ghost wandern kann. */
    onPreview: (alternative: AnchorAlternative | null) => void;
}) {
    const suggestion = useAnchorSuggestions();
    // Die Wahl hängt an ihrem Block, nicht an einem Index für sich. So gilt sie
    // beim Wechsel zu einer anderen Gewohnheit von selbst nicht mehr — statt
    // nachträglich zurückgesetzt zu werden.
    const [chosen, setChosen] = useState<{
        blockId: number;
        index: number;
    } | null>(null);

    const chosenIndex =
        block !== null && chosen?.blockId === block.id ? chosen.index : null;

    useEffect(() => {
        if (block === null) {
            return;
        }

        onPreview(null);
        void suggestion.load(suggestions.url(block.id));
        // Nur der Wechsel des Blocks stößt das an; `suggestion` ändert sich bei
        // jedem Ladeschritt und würde eine Schleife auslösen.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [block]);

    function choose(index: number) {
        if (block === null) {
            return;
        }

        setChosen({ blockId: block.id, index });
        onPreview(suggestion.alternatives[index] ?? null);
    }

    function dismiss() {
        onPreview(null);
        onOpenChange(false);
    }

    function apply() {
        const alternative =
            chosenIndex === null ? null : suggestion.alternatives[chosenIndex];

        if (block === null || !alternative) {
            return;
        }

        router.post(
            store.url(block.id),
            {
                // Damit die KI später weiß, welcher ihrer Vorschläge es
                // geworden ist — und die übrigen nicht ein zweites Mal
                // anbietet.
                suggestion_id: alternative.id,
                ...(alternative.situation
                    ? { trigger_situation: alternative.situation }
                    : {
                          scheduled_time: alternative.time,
                          scheduled_days: alternative.days,
                      }),
            },
            { preserveScroll: true },
        );

        onPreview(null);
        onOpenChange(false);
    }

    return (
        <Sheet
            open={block !== null}
            onOpenChange={(open) => !open && dismiss()}
        >
            <SheetContent
                side="bottom"
                className="mx-auto max-h-[85vh] max-w-lg gap-0 overflow-y-auto rounded-t-2xl px-5 pt-6 pb-8"
            >
                <SheetHeader className="gap-2 p-0">
                    <SheetTitle className="flex items-center gap-2 text-[11px] font-semibold tracking-[0.11em] text-primary uppercase">
                        <Sparkles
                            className="size-3.5"
                            strokeWidth={2}
                            aria-hidden="true"
                        />
                        Mir ist was aufgefallen
                    </SheetTitle>
                    <SheetDescription className="text-left text-[15px] leading-relaxed text-foreground">
                        {suggestion.observation ?? (
                            <Skeleton className="inline-block h-5 w-3/4 align-middle" />
                        )}
                    </SheetDescription>
                </SheetHeader>

                <p className="mt-5 text-sm leading-relaxed text-muted-foreground">
                    Bisher: {block?.anchor}. Vielleicht passt ein anderer Moment
                    besser?
                </p>

                <div className="mt-4 flex flex-col gap-2">
                    {suggestion.loading ? (
                        <>
                            <Skeleton className="h-16 rounded-[14px]" />
                            <Skeleton className="h-16 rounded-[14px]" />
                            <Skeleton className="h-16 rounded-[14px]" />
                        </>
                    ) : suggestion.failed ? (
                        <AiSuggestionFailure
                            onRetry={() =>
                                block &&
                                void suggestion.load(suggestions.url(block.id))
                            }
                        />
                    ) : (
                        suggestion.alternatives.map((alternative, index) => (
                            <button
                                key={alternative.id}
                                type="button"
                                aria-pressed={chosenIndex === index}
                                onClick={() => choose(index)}
                                className={cn(
                                    'cursor-pointer rounded-[14px] border-2 bg-card px-4 py-3 text-left transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
                                    chosenIndex === index
                                        ? 'border-primary'
                                        : 'border-border hover:border-secondary',
                                )}
                            >
                                <span className="block text-[15px] leading-snug font-semibold">
                                    {alternativeLabel(alternative)}
                                </span>
                                <span className="mt-1 block text-xs leading-relaxed text-muted-foreground">
                                    {alternative.reason}
                                </span>
                            </button>
                        ))
                    )}
                </div>

                <div className="mt-5 flex gap-3">
                    <button
                        type="button"
                        onClick={dismiss}
                        className={`${ACTION_BUTTON} border border-primary text-primary hover:bg-accent`}
                    >
                        Lass so
                    </button>
                    <button
                        type="button"
                        onClick={apply}
                        disabled={chosenIndex === null}
                        className={`${ACTION_BUTTON} bg-primary text-primary-foreground hover:bg-primary/90`}
                    >
                        Übernehmen
                    </button>
                </div>
            </SheetContent>
        </Sheet>
    );
}
