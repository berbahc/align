import { Link } from '@inertiajs/react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

/**
 * Die Navigation am unteren Rand — nur auf dem Telefon.
 *
 * Unter dem Tablet-Breakpoint verschwindet die Seitenleiste hinter einem
 * Knopf, und die fünf Ecken der App wären zwei Tipps entfernt. Auf dem
 * Telefon gehört die Navigation dorthin, wo der Daumen ist: unten, immer
 * sichtbar, ein Tipp pro Ecke. Dieselben fünf Einträge wie in der
 * Seitenleiste — eine Quelle, zwei Orte.
 *
 * Der aktive Eintrag trägt ein sandfarbenes Kissen hinter dem Symbol: die
 * oberste Stufe der Flächenleiter, dieselbe wie ein erledigter Block. Kein
 * Strich, kein Punkt — was vorn liegt, ist heller.
 */
export function MobileNav({ items }: { items: NavItem[] }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <nav
            aria-label="Hauptnavigation"
            className="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-card/95 backdrop-blur-sm md:hidden"
            style={{ paddingBottom: 'env(safe-area-inset-bottom)' }}
        >
            <ul className="flex h-16 items-stretch">
                {items.map((item) => {
                    const active = isCurrentOrParentUrl(item.href);

                    return (
                        <li key={item.title} className="flex flex-1">
                            <Link
                                href={item.href}
                                prefetch
                                aria-current={active ? 'page' : undefined}
                                className={cn(
                                    'flex min-w-0 flex-1 cursor-pointer flex-col items-center justify-center gap-0.5 text-[11px] leading-none font-semibold transition-colors duration-[var(--duration-press)] ease-out focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.94]',
                                    active
                                        ? 'text-primary'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                <span
                                    className={cn(
                                        'flex h-7 w-12 items-center justify-center rounded-full transition-[background-color] duration-[var(--duration-press)] ease-out',
                                        active && 'bg-sand',
                                    )}
                                >
                                    {item.icon && (
                                        <item.icon
                                            className="size-5"
                                            strokeWidth={active ? 2 : 1.75}
                                            aria-hidden="true"
                                        />
                                    )}
                                </span>
                                {/* Eigene Zeilenhöhe: `leading-none` oben und
                                    das `overflow-hidden` von `truncate` schnitten
                                    zusammen die Umlautpunkte ab — „Übersicht"
                                    stand als „Ubersicht" in der Leiste. */}
                                <span className="truncate leading-tight">
                                    {item.title}
                                </span>
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
