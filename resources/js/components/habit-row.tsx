import { Check } from 'lucide-react';
import { PersonCircle } from '@/components/person-circle';
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

    const subtitle = isDone
        ? `Abgeschlossen · ${habit.completedAt} Uhr`
        : [
              companion && `mit ${companion.name}`,
              habit.scheduleLabel,
              habit.focusMinutes && `${habit.focusMinutes} Min`,
          ]
              .filter(Boolean)
              .join(' · ');

    return (
        <li
            id={`habit-${habit.id}`}
            className={cn(
                'flex flex-col gap-2 rounded-2xl transition-shadow duration-300',
                highlighted &&
                    'ring-2 ring-primary ring-offset-4 ring-offset-card',
            )}
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
                    <span
                        className={cn(
                            'flex size-11 shrink-0 items-center justify-center transition-colors duration-200',
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
                            'block truncate text-[15px] leading-snug font-semibold',
                            // §2.3 empfiehlt für erledigte Titel `olive-mid` statt
                            // Gold — Gold erreicht auf `surface` nur 2.46:1.
                            isDone ? 'text-olive-mid' : 'text-foreground',
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
                    className="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full transition-colors duration-200 hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                >
                    <span
                        className={cn(
                            'flex size-7 items-center justify-center rounded-full transition-colors duration-200',
                            isDone
                                ? 'bg-primary'
                                : 'border-2 border-dashed border-sand',
                        )}
                    >
                        {isDone && (
                            <Check
                                className="size-4 text-primary-foreground"
                                strokeWidth={2.5}
                                aria-hidden="true"
                            />
                        )}
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
                            className="cursor-pointer text-xs font-semibold text-primary underline underline-offset-4 transition-colors duration-200 hover:text-primary/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            Mit jemandem zusammen?
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={() => onStuck(habit)}
                        className="cursor-pointer text-xs font-semibold text-primary underline underline-offset-4 transition-colors duration-200 hover:text-primary/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        Ich komm nicht rein
                    </button>
                </div>
            )}
        </li>
    );
}
