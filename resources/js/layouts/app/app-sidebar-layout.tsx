import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar, mainNavItems } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { MobileNav } from '@/components/mobile-nav';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            {/* Auf dem Telefon liegt die Navigation unten; der Inhalt lässt
                ihr Platz, damit die letzte Zeile nicht darunter verschwindet. */}
            <AppContent
                variant="sidebar"
                className="overflow-x-hidden max-md:pb-[calc(4rem+env(safe-area-inset-bottom))]"
            >
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
            <MobileNav items={mainNavItems} />
        </AppShell>
    );
}
