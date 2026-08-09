import { Sparkles } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Der Rahmen, der einen KI-Vorschlag als solchen ausweist.
 *
 * ki-assistent-design.md §2: Vorschläge der KI sind immer sichtbar markiert
 * und immer ablehnbar. Wer nicht erkennt, wem er zustimmt, kann nicht
 * widersprechen — deshalb bekommt die KI eine eigene Fläche und ein eigenes
 * Zeichen (✦), statt sich unter die App-eigenen Elemente zu mischen.
 *
 * Der Ton der Zeile ist beobachtend, nie wertend: kein Ausrufezeichen, kein
 * Rot, kein Lob.
 */
export function AiSuggestion({
    label = 'Vorschlag',
    children,
    className,
}: {
    label?: string;
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('rounded-[14px] bg-sand/60 p-4', className)}>
            <p className="flex items-center gap-1.5 text-[11px] font-semibold tracking-[0.11em] text-primary uppercase">
                <Sparkles
                    className="size-3.5"
                    strokeWidth={2}
                    aria-hidden="true"
                />
                {label}
            </p>

            <div className="mt-3">{children}</div>
        </div>
    );
}

/**
 * Die Absage, wenn der Aufruf nicht durchkommt.
 *
 * Bewusst ohne Ersatzvorschläge und ohne Alarmfarbe: die App sagt, dass es
 * gerade nicht geht, und bietet einen zweiten Versuch an. Der Weg weiter bleibt
 * daneben immer offen.
 */
export function AiSuggestionFailure({ onRetry }: { onRetry: () => void }) {
    return (
        <div className="rounded-[14px] border border-border p-4">
            <p className="text-sm leading-relaxed text-muted-foreground">
                Die Vorschläge lassen sich gerade nicht laden.
            </p>
            <button
                type="button"
                onClick={onRetry}
                className="mt-2 cursor-pointer text-sm font-semibold text-primary underline underline-offset-4 transition-colors duration-200 hover:text-primary/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
            >
                Nochmal versuchen
            </button>
        </div>
    );
}
