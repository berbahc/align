import { useId } from 'react';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

/** Strahlen im 45°-Raster, gemessen vom Mittelpunkt (12,12) nach außen. */
const RAY_ANGLES = [0, 45, 90, 135, 180, 225, 270, 315];

const RAY_INNER_RADIUS = 8;
const RAY_OUTER_RADIUS = 10;

/**
 * Umschalter zwischen hellem und dunklem Modus.
 *
 * Bewusst zweiwertig: Der Knopf beantwortet genau die Frage „hell oder
 * dunkel". Die dritte Möglichkeit — dem Betriebssystem folgen — bleibt den
 * Einstellungen vorbehalten (`settings/appearance`), sonst müsste ein
 * Symbol drei Zustände tragen, die man ihm nicht ansieht.
 *
 * Steht die Anzeige auf „System", entscheidet der aufgelöste Zustand: Wer bei
 * dunklem System klickt, will hell — nicht zurück auf System.
 *
 * Die Sonne wird zum Mond, indem ein zweiter Kreis von rechts oben über die
 * Scheibe schiebt und eine Sichel ausstanzt (SVG-Maske). Ein Formwechsel
 * statt zweier Symbole, die einander ersetzen: Das passt zum Tageswechsel,
 * um den sich die App ohnehin dreht. Bewegt werden nur `transform` und
 * `opacity` — 200 ms, keine Federn (Designsprache §6).
 */
export function AppearanceToggle({ className }: { className?: string }) {
    const { resolvedAppearance, updateAppearance } = useAppearance();

    const isDark = resolvedAppearance === 'dark';
    const maskId = useId();

    return (
        <button
            type="button"
            onClick={() => updateAppearance(isDark ? 'light' : 'dark')}
            aria-pressed={isDark}
            aria-label={
                isDark
                    ? 'Zum hellen Modus wechseln'
                    : 'Zum dunklen Modus wechseln'
            }
            title={isDark ? 'Heller Modus' : 'Dunkler Modus'}
            className={cn(
                'relative inline-flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-md',
                'text-muted-foreground transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                'hover:bg-accent hover:text-accent-foreground',
                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
                // Sichtbar bleiben 36 px, damit der Knopf neben dem
                // Sidebar-Schalter nicht aus der Reihe fällt. Die Trefferfläche
                // wächst unsichtbar auf 44 px — die Untergrenze für Finger.
                "after:absolute after:-inset-1 after:content-['']",
                className,
            )}
        >
            <svg
                viewBox="0 0 24 24"
                className="size-5 motion-reduce:**:transition-none"
                aria-hidden="true"
                focusable="false"
            >
                <mask id={maskId}>
                    <rect x="0" y="0" width="24" height="24" fill="white" />
                    {/* Im hellen Modus weit außerhalb geparkt, damit die
                        Scheibe voll bleibt. */}
                    <circle
                        cx="12"
                        cy="12"
                        r="6"
                        fill="black"
                        className={cn(
                            'transition-transform duration-[var(--duration-fluid)] ease-[var(--ease-fluid)] ease-out',
                            isDark
                                ? 'translate-x-[7px] translate-y-[-6px]'
                                : 'translate-x-[24px] translate-y-[-24px]',
                        )}
                    />
                </mask>

                <circle
                    cx="12"
                    cy="12"
                    r="5.5"
                    fill="currentColor"
                    mask={`url(#${maskId})`}
                    className={cn(
                        'origin-center transition-transform duration-[var(--duration-fluid)] ease-[var(--ease-fluid)] ease-out',
                        isDark && 'scale-[1.35]',
                    )}
                />

                <g
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    className={cn(
                        'origin-center transition-[opacity,transform] duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                        isDark && 'scale-50 opacity-0',
                    )}
                >
                    {RAY_ANGLES.map((angle) => {
                        const radians = (angle * Math.PI) / 180;

                        return (
                            <line
                                key={angle}
                                x1={12 + RAY_INNER_RADIUS * Math.cos(radians)}
                                y1={12 - RAY_INNER_RADIUS * Math.sin(radians)}
                                x2={12 + RAY_OUTER_RADIUS * Math.cos(radians)}
                                y2={12 - RAY_OUTER_RADIUS * Math.sin(radians)}
                            />
                        );
                    })}
                </g>
            </svg>
        </button>
    );
}
