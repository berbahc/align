import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { calendar } from '@/routes';
import { week as calendarWeek } from '@/routes/calendar';

/**
 * Die Ebenen des Kalenders, zwischen denen man wechselt.
 *
 * Der Monat zeigt, ob ein Tag voll war; die Woche, wie Kurse und
 * Gewohnheiten zueinander liegen. Der Tag hat keinen Knopf hier — er hat eine
 * eigene Adresse und wird aus beiden heraus geöffnet.
 *
 * Zwei Adressen und kein Client-Zustand: Die Ansicht übersteht ein Neuladen
 * und lässt sich teilen, genau wie der einzelne Tag.
 *
 * Die Auswahl ist ein 2px-Rahmen und keine Füllung — dasselbe leise Muster wie
 * bei jeder anderen Einfachauswahl in dieser App (Designsprache §5.5).
 */
export function CalendarViews({ active }: { active: 'month' | 'week' }) {
    return (
        <nav
            aria-label="Ansicht des Kalenders"
            className="flex items-center justify-center gap-2"
        >
            <ViewLink href={calendar()} active={active === 'month'}>
                Monat
            </ViewLink>
            <ViewLink href={calendarWeek()} active={active === 'week'}>
                Woche
            </ViewLink>
        </nav>
    );
}

function ViewLink({
    href,
    active,
    children,
}: {
    href: ReturnType<typeof calendar>;
    active: boolean;
    children: string;
}) {
    return (
        <Link
            href={href}
            aria-current={active ? 'page' : undefined}
            className={cn(
                'flex h-11 cursor-pointer items-center rounded-[14px] border-2 bg-card px-4 text-sm font-semibold transition-[border-color,color,scale] duration-[var(--duration-press)] ease-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.97]',
                active
                    ? 'border-primary text-primary'
                    : 'border-border text-muted-foreground hover:border-secondary hover:text-foreground',
            )}
        >
            {children}
        </Link>
    );
}
