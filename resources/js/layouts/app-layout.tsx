import { FlashNotice } from '@/components/flash-notice';
import { HabitReminderNotice } from '@/components/habit-reminder-notice';
import { HabitReminders } from '@/components/habit-reminders';
import { SleepNotice } from '@/components/sleep-notice';
import { WakeAlarm } from '@/components/wake-alarm';
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
            <HabitReminderNotice />
            <SleepNotice />
            {children}
            {/* Hier und nicht in `withApp`: dort wäre der Wecker ein
                Geschwister des Inertia-Providers, und `usePage()` würde
                werfen. Das Layout trägt alle angemeldeten Seiten. */}
            <HabitReminders />
            <WakeAlarm />
        </AppLayoutTemplate>
    );
}
