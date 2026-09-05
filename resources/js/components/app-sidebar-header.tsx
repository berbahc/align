import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { mainNavItems } from '@/components/app-sidebar';
import { AppearanceToggle } from '@/components/appearance-toggle';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

/**
 * Der Kopf der Seite — auf dem Telefon ein anderer als am Schreibtisch.
 *
 * Am Schreibtisch steht links die Seitenleiste, und der Kopf trägt den Weg
 * dorthin zurück: Brotkrumen und den Knopf, der die Leiste ein- und ausklappt.
 *
 * Auf dem Telefon gibt es beides nicht. Die Navigation liegt unten
 * ({@see MobileNav}), und ein Pfad aus zwei Gliedern wäre dort keine
 * Orientierung, sondern eine Zeile, die den halben Kopf füllt. Stattdessen
 * steht in der Mitte genau der Name, der unten gerade leuchtet — dieselbe
 * Quelle, zwei Orte —, links die Bildmarke und rechts, was zum Konto gehört.
 */
export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth, name } = usePage().props;
    const getInitials = useInitials();
    const { isCurrentOrParentUrl } = useCurrentUrl();

    /**
     * Der Name des Tabs, in dem man gerade steht.
     *
     * `isCurrentOrParentUrl` vergleicht mit dem Anfang des Pfades, damit auch
     * ein einzelner Tag (`/calendar/2026-09-05`) noch „Kalender" heißt und der
     * Wizard (`/habits/create`) „Gewohnheiten". Was zu keinem Tab gehört — die
     * Einstellungen etwa — nimmt die letzte Brotkrume, erst danach den Namen
     * der App.
     */
    const heading =
        mainNavItems.find((item) => isCurrentOrParentUrl(item.href))?.title ??
        breadcrumbs.at(-1)?.title ??
        name;

    return (
        <header className="flex h-16 shrink-0 items-center border-b border-sidebar-border/50 px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12">
            {/* Telefon: Marke — Titel — Konto.
                Drei Spalten statt `justify-between`: Rechts steht mehr als
                links, und nur mit zwei gleich breiten Außenspalten sitzt der
                Titel wirklich in der Mitte statt ungefähr dort. */}
            <div className="grid w-full grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 md:hidden">
                {/* Kein Link: Die Leiste unten führt schon zur Übersicht, und
                    ein zweiter Weg zum selben Ort wäre einer zu viel. Die
                    Marke schaltet selbst zwischen hell und dunkel um — gezeigt
                    wird die Kachel, die sich vom Hintergrund abhebt. */}
                <AppLogoIcon className="size-8 justify-self-start" />

                {/* Kein `h1`: Die Seite darunter trägt ihre eigene Überschrift,
                    und zwei erste Überschriften wären eine zu viel. Hier steht
                    Orientierung, nicht der Titel des Inhalts. */}
                <span className="truncate text-[15px] leading-none font-semibold text-foreground">
                    {heading}
                </span>

                <div className="flex items-center justify-self-end">
                    <AppearanceToggle />

                    {/* Was am Schreibtisch im Fuß der Seitenleiste steht —
                        Einstellungen, Abmelden — braucht hier einen Platz. */}
                    {auth.user && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button
                                    type="button"
                                    aria-label="Konto"
                                    className="flex size-11 cursor-pointer items-center justify-center rounded-full transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.94]"
                                >
                                    <Avatar className="size-8 overflow-hidden rounded-full">
                                        <AvatarImage
                                            src={auth.user.avatar}
                                            alt={auth.user.name}
                                        />
                                        <AvatarFallback className="rounded-full bg-sand text-sm font-semibold text-primary">
                                            {getInitials(auth.user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                className="min-w-56 rounded-lg"
                                align="end"
                                side="bottom"
                            >
                                <UserMenuContent user={auth.user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            </div>

            {/* Ab Tablet: der Weg zurück, wie gehabt. */}
            <div className="hidden w-full items-center gap-2 md:flex">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
                <AppearanceToggle className="ml-auto" />
            </div>
        </header>
    );
}
