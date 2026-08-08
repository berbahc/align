import { BookOpen, Check, Dumbbell, GlassWater, Moon } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Habit } from '@/types';

// Muss zu DIRECTION_ICONS im Wizard passen — dieselbe Kategorie darf nicht
// je nach Bildschirm ein anderes Zeichen tragen.
const BEHAVIOR_ICONS = {
    nutrition: GlassWater,
    movement: Dumbbell,
    learning: BookOpen,
    other: Moon,
} as const;

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
    onToggle,
}: {
    habit: Habit;
    onToggle: (habit: Habit) => void;
}) {
    const Icon = BEHAVIOR_ICONS[habit.behaviorType];
    const isDone = habit.completedAt !== null;

    const subtitle = isDone
        ? `Abgeschlossen · ${habit.completedAt} Uhr`
        : [
              habit.scheduleLabel,
              habit.focusMinutes && `${habit.focusMinutes} Min`,
          ]
              .filter(Boolean)
              .join(' · ');

    return (
        <li className="flex items-center gap-3">
            <span
                className={cn(
                    'flex size-11 shrink-0 items-center justify-center transition-colors duration-200',
                    isDone
                        ? 'rounded-full bg-primary text-primary-foreground'
                        : 'rounded-xl bg-sand text-primary',
                )}
            >
                <Icon className="size-5" strokeWidth={1.5} aria-hidden="true" />
            </span>

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
        </li>
    );
}
