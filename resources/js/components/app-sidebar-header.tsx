import { usePage } from '@inertiajs/react';
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
import { useInitials } from '@/hooks/use-initials';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage().props;
    const getInitials = useInitials();

    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                {/* Auf dem Telefon übernimmt die Leiste unten — ein Knopf,
                    der dieselben fünf Einträge noch einmal aufklappt, wäre
                    ein zweiter Weg zum selben Ort. */}
                <SidebarTrigger className="-ml-1 max-md:hidden" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <AppearanceToggle className="ml-auto" />

            {/* Was sonst im Fuß der Seitenleiste steht — Einstellungen,
                Abmelden — braucht auf dem Telefon einen eigenen Platz. */}
            {auth.user && (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button
                            type="button"
                            aria-label="Konto"
                            className="flex size-11 cursor-pointer items-center justify-center rounded-full transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.94] md:hidden"
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
        </header>
    );
}
