import type { CSSProperties } from 'react';
import { cn } from '@/lib/utils';
import type { RhythmDay } from '@/types';

/**
 * Die drei Zustände eines Tages im Streifen.
 *
 * Als eigener Typ, weil {@see RhythmStrip} und {@see RhythmLegend} beide auf
 * ihn zugreifen: Eine Legende, deren Töne von den erklärten Marken abweichen,
 * ist schlimmer als gar keine.
 */
type DayState = 'done' | 'open' | 'unplanned';

function stateOf(day: RhythmDay): DayState {
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
    // Streifen nicht. §5.2 hält für denselben Zustand „wartet" fest.
    open: 'Offen',
    unplanned: 'Nicht vorgesehen',
};

function Mark({
    state,
    className,
    style,
}: {
    state: DayState;
    className?: string;
    style?: CSSProperties;
}) {
    return (
        <span
            aria-hidden="true"
            style={style}
            className={cn(
                'flex size-5 shrink-0 items-center justify-center rounded-[5px]',
                MARK[state],
                className,
            )}
        >
            {/* `border` statt `track`: Der Punkt muss auf beiden Gründen
                stehen können. Im Streifen liegt er auf der weißen Karte, in
                der Legende auf dem Seitengrund — und dort verschwand `track`
                vollständig. Eine Legende, deren Marke anders aussieht als das
                Erklärte, erklärt nichts. */}
            {state === 'unplanned' && (
                <span className="size-1.5 rounded-full bg-border" />
            )}
        </span>
    );
}

/**
 * Die letzten sieben Tage einer Gewohnheit als Streifen.
 *
 * Die Gewohnheiten-Liste zeigte bis hierher ausschließlich Einstellungen —
 * wann, wie lange, ob erinnert wird. Fünf Gewohnheiten sahen dadurch gleich
 * aus, weil Einstellungen immer gleich aussehen. Der Streifen ist das eine
 * Element, das jede Zeile unterscheidbar macht: Er zeigt keinen Plan, sondern
 * einen Verlauf, und der ist bei jeder Gewohnheit ein anderer.
 *
 * **Drei Zustände, nicht zwei.** Ein Samstag ohne Mo–Fr-Gewohnheit ist keine
 * Lücke. Genau dafür liefert {@see Habit::weekOverview()} `scheduled` neben
 * `completed` — ohne diese Unterscheidung wäre der Streifen eine Anklage gegen
 * Tage, an denen nie etwas vorgesehen war.
 *
 * **Bewusst kein Streak.** Nichts zählt hoch, nichts bricht, nichts wird
 * zurückgesetzt (§1.4 „kein Alarm", §1.5 „benennen, was da ist").
 *
 * **Keine Zahl am Streifen.** Sieben Marken lassen sich abzählen; eine
 * danebenstehende Zahl sagte dasselbe noch einmal. Die Zeile darunter nennt
 * stattdessen die Konsistenzrate — dreißig Tage statt sieben, also eine
 * Auskunft, die der Streifen nicht gibt.
 */
export function RhythmStrip({
    days,
    title,
    className,
}: {
    days: RhythmDay[];
    /** Der Titel der Gewohnheit — nur für die Vorlesehilfe. */
    title: string;
    className?: string;
}) {
    const scheduled = days.filter((day) => day.scheduled).length;
    const done = days.filter((day) => day.completed).length;

    // Der letzte Eintrag ist immer heute: `weekOverview()` zählt von `until`
    // rückwärts und liefert die Reihe in zeitlicher Ordnung.
    const todayIndex = days.length - 1;

    return (
        // Die Marken sind für die Vorlesehilfe unsichtbar: Sieben Tage einzeln
        // vorzulesen ergibt eine Litanei, aus der niemand etwas mitnimmt. Der
        // Satz sagt dasselbe in einem Zug.
        <div
            role="img"
            aria-label={
                scheduled === 0
                    ? `${title}: in den letzten sieben Tagen nicht vorgesehen`
                    : `${title}: ${done} von ${scheduled} vorgesehenen Tagen der letzten Woche erledigt`
            }
            className={cn('flex shrink-0 gap-1', className)}
        >
            {days.map((day, index) => (
                <span
                    key={day.date}
                    aria-hidden="true"
                    className="flex w-5 flex-col items-center gap-1"
                >
                    <Mark
                        state={stateOf(day)}
                        // Der Einzug läuft von links nach rechts durch die
                        // Woche — die Bewegung zeigt die Richtung, in der die
                        // Zeit vergeht (Apple §8: die Zwischenbilder sollen
                        // sagen, worauf es hinausläuft).
                        className="motion-safe:animate-in motion-safe:fill-mode-backwards motion-safe:zoom-in-75 motion-safe:fade-in"
                        style={{
                            animationDuration: 'var(--duration-fluid)',
                            animationTimingFunction: 'var(--ease-fluid)',
                            animationDelay: `${index * 40}ms`,
                        }}
                    />

                    {/* „Heute" steht in der Beschriftung, nicht als Ring um die
                        Marke: Der Ring läge auch um Tage, an denen nichts
                        vorgesehen war, und ein umrandetes leeres Kästchen läse
                        sich als vierter Zustand, den es nicht gibt. */}
                    <span
                        className={cn(
                            'block w-full truncate text-center text-[10px] leading-none',
                            index === todayIndex
                                ? 'font-semibold text-muted-foreground'
                                : 'text-faintest',
                        )}
                    >
                        {day.label}
                    </span>
                </span>
            ))}
        </div>
    );
}

/**
 * Was die drei Töne im Streifen bedeuten.
 *
 * Einmal auf der Seite, nicht an jeder Karte: Eine Legende, die sich fünfmal
 * wiederholt, ist keine Erklärung mehr, sondern Rauschen. Sie steht über der
 * Liste, weil man sie beim ersten Blick braucht — und ist leise genug, um
 * danach nicht zu stören.
 *
 * Die Marken kommen aus derselben Quelle wie die im Streifen ({@see MARK}).
 * Eine Legende muss mit der Sache mitwandern, die sie erklärt, sonst erklärt
 * sie irgendwann etwas Falsches.
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
                Der Streifen an jeder Gewohnheit zeigt die letzten sieben Tage:
            </span>
            {states.map((state) => (
                <span key={state} className="flex items-center gap-1.5">
                    <Mark state={state} className="size-3.5 rounded-[4px]" />
                    {MARK_LABEL[state]}
                </span>
            ))}
        </p>
    );
}
