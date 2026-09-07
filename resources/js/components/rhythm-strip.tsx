import type { CSSProperties } from 'react';
import { cn } from '@/lib/utils';
import type { RhythmDay } from '@/types';

/**
 * Die drei Zustände eines Tages im Rhythmus.
 *
 * Als eigener Typ, weil {@see HabitBoard} und {@see RhythmLegend} beide auf ihn
 * zugreifen: Eine Legende, deren Töne von den erklärten Marken abweichen, ist
 * schlimmer als gar keine.
 */
export type DayState = 'done' | 'open' | 'unplanned';

export function rhythmState(day: RhythmDay): DayState {
    if (day.completed) {
        return 'done';
    }

    return day.scheduled ? 'open' : 'unplanned';
}

/** Die eine Quelle für das Aussehen einer Marke. */
const MARK: Record<DayState, string> = {
    // Die einzige gesättigte Farbe des Systems (§1.2) — sie sagt „hier ist
    // etwas passiert" und sonst nichts.
    done: 'bg-primary',
    // Dieselbe Stufe wie die Spur des Fortschrittsbalkens (§5.3): sichtbar
    // vorhanden, aber leer.
    open: 'bg-sand',
    // Keine Fläche, nur ein Punkt — erkennbar keine Marke. Ein leeres Kästchen
    // läse sich als versäumt; an diesen Tagen war nie etwas vorgesehen.
    unplanned: 'bg-transparent',
};

/** Wie die drei Zustände heißen — §8: beobachtend, nicht wertend. */
const MARK_LABEL: Record<DayState, string> = {
    done: 'Erledigt',
    // Nicht „verpasst" und nicht „fehlt": Der Tag stand an, mehr sagt der
    // Rhythmus nicht. §5.2 hält für denselben Zustand „wartet" fest.
    open: 'Offen',
    unplanned: 'Nicht vorgesehen',
};

/**
 * Ein Tag als Marke.
 *
 * Die Größe kommt von außen, weil dieselbe Marke in zwei Größen auftritt: im
 * Blatt so groß, dass sie sich mit dem Finger unterscheiden lässt, in der
 * Legende so klein, dass sie neben dem Wort steht statt darüber.
 */
export function RhythmMark({
    state,
    size,
    className,
    style,
}: {
    state: DayState;
    size: string;
    className?: string;
    style?: CSSProperties;
}) {
    return (
        <span
            aria-hidden="true"
            style={style}
            className={cn(
                'flex shrink-0 items-center justify-center rounded-[5px]',
                size,
                MARK[state],
                className,
            )}
        >
            {/* `border` statt `track`: Der Punkt muss auf drei Gründen stehen
                können — auf der weißen Karte, im Band des heutigen Tages und
                auf dem Seitengrund, wo die Legende steht. */}
            {state === 'unplanned' && (
                <span className="size-1.5 rounded-full bg-border" />
            )}
        </span>
    );
}

/**
 * Was die drei Töne im Blatt bedeuten.
 *
 * Einmal auf der Seite, nicht an jeder Zeile: Eine Legende, die sich fünfmal
 * wiederholt, ist keine Erklärung mehr, sondern Rauschen.
 *
 * Sie steht jetzt **unter** dem Blatt statt darüber. Über der Liste war sie das
 * Erste, was man las — drei Wörter über Farbtönen, bevor überhaupt etwas zu
 * sehen war, das sie erklären. Unter dem Blatt beantwortet sie die Frage in dem
 * Moment, in dem sie entsteht.
 *
 * Die Marken kommen aus derselben Quelle wie die im Blatt ({@see MARK}). Eine
 * Legende muss mit der Sache mitwandern, die sie erklärt, sonst erklärt sie
 * irgendwann etwas Falsches.
 */
export function RhythmLegend({ className }: { className?: string }) {
    const states: DayState[] = ['done', 'open', 'unplanned'];

    return (
        <p
            className={cn(
                'flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-muted-foreground',
                className,
            )}
        >
            <span className="sr-only">
                Die Marken bei jeder Gewohnheit zeigen die letzten sieben Tage:
            </span>
            {states.map((state) => (
                <span key={state} className="flex items-center gap-1.5">
                    <RhythmMark
                        state={state}
                        size="size-3.5"
                        className="rounded-[4px]"
                    />
                    {MARK_LABEL[state]}
                </span>
            ))}
            {/* Dass sich die Marken anfassen lassen, sieht man ihnen nicht an:
                Sie sind klein, still und standen sieben Versionen lang nur da.
                Ein Satz an der Stelle, an der ohnehin erklärt wird, was sie
                bedeuten — nicht als Anleitung, sondern als Angebot. */}
            <span className="basis-full">
                Einen Tag antippen trägt ihn nach.
            </span>
        </p>
    );
}
