import { Link } from '@inertiajs/react';
import {
    CalendarDays,
    GraduationCap,
    LayoutGrid,
    Moon,
    Repeat,
    Users,
} from 'lucide-react';
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
import { show as semesterShow } from '@/routes/semester';
import { show as sleepShow } from '@/routes/sleep';
import type { NavItem } from '@/types';

// Der dritte Eintrag hieß im Figma-Design „Verlauf" und stand für
// habit-journey.md — das einzige Feature ganz ohne Umfragedaten (Auswertung
// §9: die Skala wurde nie erhoben). Der Kalender löst dasselbe Versprechen
// ein, mit Belegen: 20 von 25 Befragten planen ohnehin mit einem Kalender
// oder Planer.
//
// „Schlaf" ist der fünfte und kommt aus dem Rahmen-Feature: Aufstehen und
// Schlafenszeit begrenzen, wann Gewohnheiten überhaupt Platz haben. Er steht
// hinter dem Kalender, weil er dieselbe Frage von der anderen Seite stellt —
// nicht „was steht an", sondern „wie lang ist der Tag".
//
// „Semester" steht zwischen beiden, weil es derselben Art ist: ein Rahmen, der
// sagt, wann nichts geht. Der Schlafplan begrenzt den Tag von außen, der
// Stundenplan von innen.
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
        title: 'Semester',
        href: semesterShow(),
        icon: GraduationCap,
    },
    {
        title: 'Schlaf',
        href: sleepShow(),
        icon: Moon,
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
