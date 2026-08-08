import { FlashNotice } from '@/components/flash-notice';
import { HabitReminders } from '@/components/habit-reminders';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs}>
            <FlashNotice />
            {children}
            {/* Hier und nicht in `withApp`: dort wäre der Wecker ein
                Geschwister des Inertia-Providers, und `usePage()` würde
                werfen. Das Layout trägt alle angemeldeten Seiten. */}
            <HabitReminders />
        </AppLayoutTemplate>
    );
}
