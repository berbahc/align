import { router } from '@inertiajs/react';
import { Check, Sparkles } from 'lucide-react';
import { useEffect } from 'react';
import { AiSuggestionFailure } from '@/components/ai-suggestion';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { useSmallestStep } from '@/hooks/use-smallest-step';
import { store } from '@/routes/habits/completions';
import { smaller } from '@/routes/habits/smallest-step';
import type { Habit } from '@/types';

const ACTION_BUTTON =
    'inline-flex h-12 flex-1 cursor-pointer items-center justify-center gap-2 rounded-xl px-4 text-[15px] font-semibold transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:opacity-50';

/**
 * Das Starthilfe-Sheet — der Moment, in dem jemand feststeckt.
 *
 * Es tut genau eine Sache: die Gewohnheit auf einen einzigen winzigen Handgriff
 * herunterbrechen. Kein Chat, keine Liste, keine fünf Optionen — der Aufgabe-
 * grund ist Überforderung, und ein Bildschirm mit Auswahl würde sie
 * reproduzieren (Starthilfe ø 4,16, Platz 1 der Umfrage).
 *
 * „Noch kleiner" und „Passt" sind gleich breit und gleich prominent. Bei ø 3,92
 * Schuldgefühl darf der Ausweg nicht der kleinere Knopf sein.
 */
export function StartingHelpSheet({
    habit,
    onOpenChange,
}: {
    habit: Habit | null;
    onOpenChange: (open: boolean) => void;
}) {
    const suggestion = useSmallestStep();

    /**
     * Was im Sheet steht: der zuletzt geholte Schritt, sonst der vorbereitete.
     *
     * Abgeleitet statt gespeichert — es gibt keinen dritten Zustand, den man
     * mit den beiden Quellen aus der Synchronisation bringen könnte.
     */
    const step = suggestion.steps[0] ?? habit?.smallestStep ?? null;

    // Beim Öffnen gilt der vorbereitete Schritt. Gibt es keinen, fragt die KI
    // einen an — die Starthilfe muss auch für Gewohnheiten funktionieren, die
    // ohne Schritt angelegt wurden.
    useEffect(() => {
        if (habit === null) {
            return;
        }

        if (habit.smallestStep === null) {
            void suggestion.load(smaller.url(habit.id), {});

            return;
        }

        // Sonst bliebe der Schritt der zuvor geöffneten Gewohnheit stehen.
        suggestion.reset();
        // Nur der Wechsel der Gewohnheit stößt das an; `suggestion` ändert
        // sich bei jedem Ladeschritt und würde eine Schleife auslösen.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [habit]);

    function goSmaller() {
        if (habit === null) {
            return;
        }

        void suggestion.load(smaller.url(habit.id), {
            current: step ?? undefined,
        });
    }

    /**
     * Der Teilschritt zählt als erledigter Tag.
     *
     * K7: „bei ø 3,92 Schuldgefühl muss der Teilschritt als Erfolg zählen,
     * nicht als halbe Niederlage." Es gibt deshalb keinen zweiten Begriff von
     * „erledigt" — es ist dieselbe Route wie der Haken in der Liste.
     */
    function confirm() {
        if (habit === null) {
            return;
        }

        router.post(store.url(habit.id), {}, { preserveScroll: true });
        onOpenChange(false);
    }

    return (
        <Sheet open={habit !== null} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="mx-auto max-w-lg gap-0 rounded-t-2xl px-5 pt-6 pb-8"
            >
                <SheetHeader className="gap-2 p-0">
                    <SheetTitle className="type-eyebrow flex items-center gap-2 text-primary">
                        <Sparkles
                            className="size-3.5"
                            strokeWidth={2}
                            aria-hidden="true"
                        />
                        Fang ganz klein an
                    </SheetTitle>
                    <SheetDescription className="text-left text-sm leading-relaxed">
                        {habit?.title} fühlt sich gerade nach viel an. Mach nur
                        das:
                    </SheetDescription>
                </SheetHeader>

                <div className="mt-5 min-h-24 rounded-[14px] bg-sand/60 p-5">
                    {suggestion.loading ? (
                        <div className="flex flex-col gap-2">
                            <Skeleton className="h-5" />
                            <Skeleton className="h-5 w-2/3" />
                        </div>
                    ) : (
                        <p className="text-lg leading-snug font-semibold">
                            {step}
                        </p>
                    )}
                </div>

                {/* Scheitert das Zerlegen, bleibt der bisherige Schritt stehen
                    — die Absage ersetzt ihn nicht, sie steht daneben. */}
                {suggestion.failed && (
                    <div className="mt-3">
                        <AiSuggestionFailure onRetry={goSmaller} />
                    </div>
                )}

                <div className="mt-5 flex gap-3">
                    <button
                        type="button"
                        onClick={goSmaller}
                        disabled={suggestion.loading}
                        className={`${ACTION_BUTTON} border border-primary text-primary hover:bg-accent`}
                    >
                        Noch kleiner
                    </button>
                    <button
                        type="button"
                        onClick={confirm}
                        className={`${ACTION_BUTTON} bg-primary text-primary-foreground hover:bg-primary/90`}
                    >
                        <Check className="size-4" aria-hidden="true" />
                        Passt
                    </button>
                </div>

                <p className="mt-4 text-center text-sm text-muted-foreground">
                    Das reicht für heute.
                </p>

                {/* Der Warum-Satz, gelegentlich statt dauerhaft: hier steht er
                    im Moment des Zögerns, wo er trägt — nicht in der Liste, wo
                    er abstumpfen würde. */}
                {habit?.motivation && (
                    <p className="mt-5 border-t border-border pt-4 text-sm leading-relaxed text-muted-foreground">
                        Du wolltest das, „{habit.motivation}".
                    </p>
                )}
            </SheetContent>
        </Sheet>
    );
}
