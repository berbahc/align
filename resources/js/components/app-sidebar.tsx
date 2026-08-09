import { Link } from '@inertiajs/react';
import { CalendarDays, LayoutGrid, Repeat, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { calendar, community, dashboard } from '@/routes';
import { index as habitsIndex } from '@/routes/habits';
import type { NavItem } from '@/types';

// Vier Einträge, wie das Figma-Design sie vorgibt. Der dritte hieß „Verlauf"
// und stand für habit-journey.md — das einzige Feature ganz ohne Umfragedaten
// (Auswertung §9: die Skala wurde nie erhoben). Der Kalender löst dasselbe
// Versprechen ein, mit Belegen: 20 von 25 Befragten planen ohnehin mit einem
// Kalender oder Planer.
const mainNavItems: NavItem[] = [
    {
        title: 'Übersicht',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Gewohnheiten',
        href: habitsIndex(),
        icon: Repeat,
    },
    {
        title: 'Kalender',
        href: calendar(),
        icon: CalendarDays,
    },
    {
        title: 'Community',
        href: community(),
        icon: Users,
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
