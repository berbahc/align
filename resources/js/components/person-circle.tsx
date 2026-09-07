import { cn } from '@/lib/utils';

/**
 * Eine Person als gefüllter Kreis mit ihrer Initiale.
 *
 * community_feature3.md §3.2 macht daraus später das Doppel-Zeichen der
 * Verabredung: zwei dieser Kreise mit 8 px Überlappung an der Stelle, wo sonst
 * die Icon-Kachel sitzt. Deshalb steht der einzelne Kreis schon hier für sich —
 * das Paar setzt darauf auf, statt ihn zu kopieren.
 *
 * Der offene Zustand ist gestrichelt: designsprache.md §7.3 legt gestrichelt
 * als „noch nicht festgelegt" fest, dieselbe Bedeutung wie beim offenen
 * Habit-Kreis. Eine eigene Sozial-Farbe gibt es bewusst nicht — §1.2 kennt
 * genau einen gesättigten Buntton.
 */
export function PersonCircle({
    initial,
    pending = false,
    className,
}: {
    /** Erster Buchstabe des Namens. Bei `pending` unbenutzt. */
    initial?: string;
    /** Noch nicht zugesagt — der Kreis bleibt leer und gestrichelt. */
    pending?: boolean;
    className?: string;
}) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'inline-flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                pending
                    ? 'hollow border-[1.5px] text-faint'
                    : 'bg-primary text-primary-foreground',
                className,
            )}
        >
            {pending ? '?' : initial}
        </span>
    );
}
