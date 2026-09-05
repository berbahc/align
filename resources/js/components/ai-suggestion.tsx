import { AiMascot } from '@/components/ai-mascot';
import type { MascotState } from '@/components/ai-mascot';
import { cn } from '@/lib/utils';

/**
 * Der Rahmen, der einen KI-Vorschlag als solchen ausweist.
 *
 * ki-assistent-design.md §2: Vorschläge der KI sind immer sichtbar markiert
 * und immer ablehnbar. Wer nicht erkennt, wem er zustimmt, kann nicht
 * widersprechen — deshalb bekommt die KI eine eigene Fläche und ein eigenes
 * Zeichen, statt sich unter die App-eigenen Elemente zu mischen.
 *
 * Das Zeichen ist jetzt die Figur ({@see AiMascot}) statt eines stummen `✦`:
 * dasselbe Zeichen aus §8, nur groß genug, dass man sieht, wer da spricht.
 *
 * Der Ton der Zeile ist beobachtend, nie wertend: kein Ausrufezeichen, kein
 * Rot, kein Lob.
 */
export function AiSuggestion({
    label = 'Vorschlag',
    state = 'idle',
    children,
    className,
}: {
    label?: string;
    /** Was die KI gerade tut — kommt aus `loading` und `failed` des Aufrufs. */
    state?: MascotState;
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('rounded-[14px] bg-sand/60 p-4', className)}>
            <p className="type-eyebrow flex items-center gap-2 text-primary">
                <AiMascot state={state} className="size-8 shrink-0" />
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
 *
 * Die Figur steht auch hier — sie hatte an dieser Stelle bisher gar kein
 * Zeichen, und ohne eines liest sich die Absage wie ein Systemfehler statt wie
 * derselbe Gegenüber, der eben noch überlegt hat.
 */
export function AiSuggestionFailure({ onRetry }: { onRetry: () => void }) {
    return (
        <div className="flex items-start gap-3 rounded-[14px] border border-border p-4">
            <AiMascot
                state="stumped"
                className="mt-0.5 size-8 shrink-0 text-primary"
            />

            <div>
                <p className="text-sm leading-relaxed text-muted-foreground">
                    Die Vorschläge lassen sich gerade nicht laden.
                </p>
                <button
                    type="button"
                    onClick={onRetry}
                    className="mt-2 cursor-pointer text-sm font-semibold text-primary underline underline-offset-4 transition-colors duration-[var(--duration-press)] ease-out hover:text-primary/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                >
                    Nochmal versuchen
                </button>
            </div>
        </div>
    );
}
