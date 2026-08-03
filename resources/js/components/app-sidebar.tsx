import { Link } from '@inertiajs/react';
import { LayoutGrid, Repeat, TrendingUp, Users } from 'lucide-react';
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
import { community, dashboard, journey } from '@/routes';
import { index as habitsIndex } from '@/routes/habits';
import type { NavItem } from '@/types';

// Reihenfolge wie im Desktop-Mockup: Übersicht, Gewohnheiten, Verlauf,
// Community. Der Mockup-Punkt „Analytics" heißt hier „Verlauf", weil
// habit-journey.md die Kurve pro Gewohnheit und rein individuell vorsieht.
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
        title: 'Verlauf',
        href: journey(),
        icon: TrendingUp,
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
