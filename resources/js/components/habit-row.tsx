import { Check, Undo2 } from 'lucide-react';
import { PersonCircle } from '@/components/person-circle';
import { useSwipeToggle } from '@/hooks/use-swipe-toggle';
import { BEHAVIOR_ICONS } from '@/lib/behavior-icons';
import { cn } from '@/lib/utils';
import type { Habit } from '@/types';

/**
 * Habit-Zeile nach Designsprache §5.2.
 *
 * Die Icon-Kachel wechselt von eckig-hell zu rund-gefüllt: Form und Farbe
 * ändern sich gemeinsam, damit der Zustand auch ohne Farbwahrnehmung
 * unterscheidbar bleibt. Der offene Kreis ist gestrichelt („wartet"), nie
 * leer-durchgezogen („fehlt"). Kein Durchstreichen, kein Ausgrauen.
 */
export function HabitRow({
    habit,
    selfInitial,
    onToggle,
    onStuck,
    onAskCompany,
    highlighted = false,
}: {
    habit: Habit;
    /** Die eigene Initiale — die linke Hälfte des Doppel-Zeichens aus §3.2. */
    selfInitial: string;
    onToggle: (habit: Habit) => void;
    onStuck: (habit: Habit) => void;
    /** Null blendet den Weg zur Verabredung aus — Screen A5. */
    onAskCompany: ((habit: Habit) => void) | null;
    /**
     * Kurz betont, nachdem eine Absage hierher verwiesen hat.
     *
     * Kein Zustand der Gewohnheit, sondern eine Antwort auf einen Klick: Wer
     * „Mach ich trotzdem" tippt, soll sehen, welche Zeile gemeint ist.
     */
    highlighted?: boolean;
}) {
    const Icon = BEHAVIOR_ICONS[habit.behaviorType];
    const isDone = habit.completedAt !== null;
    const companion = habit.companion;

    /**
     * §7 — was nach rechts hinausgeht, kommt von rechts zurück. Abgehakt wird
     * nach rechts, zurückgenommen nach links; die Richtung benennt also, was
     * passiert, und die Rücknahme ist der sichtbare Umkehrweg.
     *
     * Der Knopf bleibt der eigentliche Bedienweg — die Geste ist eine Zugabe
     * für den Daumen und wird von Tastatur und Vorlesehilfe nicht gebraucht.
     */
    const {
        offset: swipeOffset,
        threshold: swipeThreshold,
        dragging: swiping,
        attach: swipeRow,
        onPointerDown,
        onClickCapture,
    } = useSwipeToggle({
        direction: isDone ? 'left' : 'right',
        onCommit: () => onToggle(habit),
    });

    /** Wie weit die Fläche hinter der Zeile freiliegt, 0…1. */
    const revealed = Math.min(Math.abs(swipeOffset) / swipeThreshold, 1);

    const subtitle = isDone
        ? `Abgeschlossen · ${habit.completedAt} Uhr`
        : [
              companion && `mit ${companion.name}`,
              habit.scheduleLabel,
              habit.measureLabel,
          ]
              .filter(Boolean)
              .join(' · ');

    return (
        <li
            id={`habit-${habit.id}`}
            className={cn(
                'rounded-2xl transition-shadow duration-300',
                highlighted &&
                    'ring-2 ring-primary ring-offset-4 ring-offset-card',
            )}
        >
            <div className="relative overflow-hidden rounded-2xl">
                {/* Was hinter der Zeile liegt und beim Ziehen freigegeben
                    wird. §8: Die Zwischenbilder sollen schon zeigen, worauf
                    die Bewegung hinausläuft — das Zeichen wächst mit der
                    Geste und steht erst voll da, wenn Loslassen auslöst.

                    Seite und Zeichen hängen an der Auslenkung, nicht am
                    Zustand der Gewohnheit: Beim Auslösen kippt der Zustand
                    sofort, die Zeile federt aber noch zurück. Hinge die
                    Fläche am Zustand, würde sie mitten in der Bewegung die
                    Seite wechseln und die Lücke bliebe leer. */}
                <span
                    aria-hidden="true"
                    className={cn(
                        // Das Zeichen sitzt an der Außenkante, nicht in der
                        // Mitte der Fläche: Beim Vorführen liegen nur ~26px
                        // frei, und ein zentriertes Zeichen bliebe darin
                        // unsichtbar — die Vorführung zeigte dann nur Farbe.
                        'absolute inset-y-0 flex w-24 items-center',
                        swipeOffset >= 0
                            ? 'left-0 justify-start bg-primary pl-3 text-primary-foreground'
                            : 'right-0 justify-end bg-sand pr-3 text-primary',
                    )}
                    style={{ opacity: Math.min(revealed * 1.6, 1) }}
                >
                    {/* Nur das Zeichen wächst — die Fläche selbst bleibt
                        stehen, sonst schrumpfte die Farbe mit. */}
                    <span
                        style={{
                            transform: `scale(${0.7 + revealed * 0.3})`,
                        }}
                    >
                        {swipeOffset >= 0 ? (
                            <Check className="size-5" strokeWidth={2.5} />
                        ) : (
                            <Undo2 className="size-5" strokeWidth={2} />
                        )}
                    </span>
                </span>

                <div
                    ref={swipeRow}
                    onPointerDown={onPointerDown}
                    onClickCapture={onClickCapture}
                    className={cn(
                        // `touch-pan-y` überlässt das senkrechte Scrollen dem
                        // Browser und behält nur die Waagerechte für uns.
                        //
                        // Der Greif-Zeiger bleibt, auch wenn die Vorführung
                        // längst aufgehört hat: Er kostet nichts, steht immer
                        // da und sagt dasselbe wie die Bewegung.
                        'relative flex cursor-grab touch-pan-y flex-col gap-2 bg-card',
                        swiping && 'cursor-grabbing select-none',
                    )}
                    style={{
                        transform: `translate3d(${swipeOffset}px, 0, 0)`,
                    }}
                >
                    <div className="flex items-center gap-3">
                        {/* §3.2 — die dritte Ausprägung der Icon-Kachel: zwei Kreise
                    statt einem. Kein neues Element, keine neue Farbe, kein
                    neues Symbol. Form *und* Anzahl unterscheiden sich, die
                    Information hängt also nicht an der Farbe. */}
                        {companion !== null && !isDone ? (
                            <span
                                className="flex shrink-0 -space-x-2"
                                aria-label={`Zusammen mit ${companion.name}`}
                            >
                                <PersonCircle initial={selfInitial} />
                                <PersonCircle
                                    initial={companion.initial}
                                    className="ring-2 ring-background"
                                />
                            </span>
                        ) : (
                            /* Die Kachel wechselt nicht, sie verformt sich: Radius und
                       Farbe laufen gemeinsam auf der Grundkurve. Ein harter
                       Umschlag läse sich als Austausch, ein Übergang als
                       derselbe Gegenstand in einem anderen Zustand. */
                            <span
                                className={cn(
                                    'flex size-11 shrink-0 items-center justify-center transition-[background-color,color,border-radius] duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                    isDone
                                        ? 'rounded-full bg-primary text-primary-foreground'
                                        : 'rounded-xl bg-sand text-primary',
                                )}
                            >
                                <Icon
                                    className="size-5"
                                    strokeWidth={1.5}
                                    aria-hidden="true"
                                />
                            </span>
                        )}

                        <span className="min-w-0 flex-1">
                            <span
                                className={cn(
                                    'block truncate text-[15px] leading-snug font-semibold transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                    // §2.3 empfiehlt für erledigte Titel `olive-mid` statt
                                    // Gold — Gold erreicht auf `surface` nur 2.46:1.
                                    isDone
                                        ? 'text-olive-mid'
                                        : 'text-foreground',
                                )}
                            >
                                {habit.title}
                            </span>
                            <span className="mt-0.5 block truncate text-xs text-muted-foreground">
                                {subtitle}
                            </span>
                        </span>

                        {/* Die Fläche ist 44px groß, damit sie als Tippziel taugt; der
                sichtbare Kreis bleibt bei 28px wie im Mockup. */}
                        <button
                            type="button"
                            onClick={() => onToggle(habit)}
                            aria-pressed={isDone}
                            aria-label={
                                isDone
                                    ? `${habit.title} als noch offen markieren`
                                    : `${habit.title} als erledigt markieren`
                            }
                            className="group/check flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            {/* §1 — der Druckpunkt sitzt auf `:active`, also auf dem
                        Drücken, nicht auf dem Loslassen. Eigene Ebene, weil
                        er in 110ms fällt, während der Zustand darunter in
                        einer halben Sekunde umschlägt. */}
                            <span className="transition-transform duration-[var(--duration-press)] ease-out motion-safe:group-active/check:scale-90">
                                <span
                                    className={cn(
                                        'flex size-7 items-center justify-center rounded-full transition-[background-color,border-color] duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                        isDone
                                            ? 'bg-primary'
                                            : 'border-2 border-dashed border-sand',
                                    )}
                                >
                                    {/* §4 — Überschwingen nur da, wo etwas einrastet.
                                Das Häkchen ist der eine Moment auf dieser
                                Seite, an dem eine Handlung greift; es federt
                                minimal über 1 hinaus und setzt sich. */}
                                    {isDone && (
                                        <Check
                                            className="size-4 text-primary-foreground duration-[var(--duration-pop)] ease-[var(--ease-pop)] motion-safe:animate-in motion-safe:zoom-in-50"
                                            strokeWidth={2.5}
                                            aria-hidden="true"
                                        />
                                    )}
                                </span>
                            </span>
                        </button>
                    </div>

                    {/* Der vorbereitete Handgriff und der Weg ins Starthilfe-Sheet
                gehören zum offenen Zustand. Bei einer erledigten Gewohnheit
                verschwinden beide — es gibt nichts mehr anzustoßen, und ein
                stehengebliebener Anstoß läse sich wie eine Nachforderung.

                Eingerückt auf Höhe des Titels, damit die Zeile als Fortsetzung
                der Gewohnheit gelesen wird und nicht als eigener Eintrag. */}
                    {!isDone && (
                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1 pl-14">
                            {habit.smallestStep && (
                                <span className="text-xs leading-relaxed text-muted-foreground">
                                    → {habit.smallestStep}
                                </span>
                            )}
                            {/* Der Weg zur Verabredung steht neben der Starthilfe:
                        beides sind Angebote für denselben Moment, in dem eine
                        Gewohnheit noch offen ist. Er verschwindet, sobald
                        jemand mitmacht — eine zweite Person pro Verabredung
                        ist die Obergrenze (§4). */}
                            {onAskCompany !== null && companion === null && (
                                <button
                                    type="button"
                                    onClick={() => onAskCompany(habit)}
                                    className="cursor-pointer text-xs font-semibold text-primary underline underline-offset-4 transition-colors duration-[var(--duration-press)] ease-out hover:text-primary/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring active:text-primary/60"
                                >
                                    Mit jemandem zusammen?
                                </button>
                            )}
                            <button
                                type="button"
                                onClick={() => onStuck(habit)}
                                className="cursor-pointer text-xs font-semibold text-primary underline underline-offset-4 transition-colors duration-[var(--duration-press)] ease-out hover:text-primary/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring active:text-primary/60"
                            >
                                Ich komm nicht rein
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </li>
    );
}
