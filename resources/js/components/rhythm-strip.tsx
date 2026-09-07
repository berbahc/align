import { Check } from 'lucide-react';
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

/**
 * Die eine Quelle für das Aussehen einer Marke.
 *
 * **Ein Haken, kein Kästchen.** Zwei verschieden helle Quadrate waren eine
 * Legende weit von ihrer Bedeutung entfernt: Man musste lernen, dass dunkel
 * „erledigt" heißt. Der Haken sagt es von selbst, und die zwei Zustände
 * unterscheiden sich in der Form, nicht nur im Ton — gefüllt gegen Kontur
 * bleibt auch ohne Farbwahrnehmung lesbar.
 *
 * Der offene Tag trägt denselben Haken, nur ungefüllt: Er ist die Stelle, an
 * die er gehört, nicht der Vorwurf, dass er fehlt (§1.4 — kein Alarm).
 *
 * Drei Zustände, drei Stufen derselben Form: Fläche, Kontur, gestrichelte
 * Kontur. Der Haken ist da oder nicht. Ein Punkt kommt hier nicht mehr vor —
 * er gehört dem Kalender, wo er eine Gewohnheit bedeutet.
 */
const MARK: Record<DayState, string> = {
    // Die einzige gesättigte Farbe des Systems (§1.2) — sie sagt „hier ist
    // etwas passiert" und sonst nichts.
    done: 'bg-primary text-primary-foreground',
    // Kontur statt Fläche — und der Haken trägt die Aussage, der Kasten nur
    // seinen Platz. Deshalb sind die beiden verschieden stark: die Kante leise,
    // der Haken lesbar.
    //
    // `sand` war für beides zu wenig. Als Füllung reichte der Ton, als Strich
    // nicht: Eine Fläche liest sich über ihre Helligkeit, eine Linie über ihren
    // Kontrast, und 1,55:1 ist für eine Linie schlicht zu wenig. Oliv bei 75 %
    // erreicht 3,8:1 auf der Karte und 3,1:1 im Band des heutigen Tages — die
    // 3:1, die WCAG 1.4.11 für ein bedeutungstragendes Zeichen verlangt.
    //
    // Derselbe Farbton wie „erledigt" und trotzdem keine Verwechslung: Der
    // Unterschied liegt in der Form, nicht im Ton. Gefüllt gegen Kontur bleibt
    // auch ohne Farbwahrnehmung lesbar (§1.2 bleibt gewahrt — gesättigt und
    // flächig ist weiterhin nur das Erledigte).
    open: 'border border-primary/35 text-primary/75',
    // Keine Fläche und kein Haken: Ein leerer Haken läse sich als versäumt, und
    // an diesen Tagen war nie etwas vorgesehen. Es bleibt die gestrichelte
    // Kante — sie hält den Platz, damit die Woche ihre sieben Stellen behält,
    // und sagt „hier war nichts geplant" statt „hier fehlt etwas".
    //
    // Vorher stand hier ein Punkt. Der ist im Kalender vergeben: Dort ist ein
    // Punkt **eine Gewohnheit** — blass offen, gefüllt erledigt. Dieselbe Form
    // hieß also auf der einen Seite „eine Gewohnheit" und auf der anderen
    // „gar keine". Zwei Legenden, die sich widersprechen, erklären nichts.
    unplanned: 'border border-dashed border-border bg-transparent',
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
            {/* Die Strichstärke ist kräftiger als überall sonst: Bei 14 Pixeln
                Kantenlänge verschwindet ein Haken mit 1,5 in seiner eigenen
                Fläche. Die Größe ist relativ, damit derselbe Haken im Blatt
                und in der Legende gleich sitzt. */}
            {state !== 'unplanned' && (
                <Check className="size-[70%]" strokeWidth={3.25} />
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
