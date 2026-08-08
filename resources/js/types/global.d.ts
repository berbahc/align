import type { Auth } from '@/types/auth';
import type { HabitReminder } from '@/types/habit';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            /** Gewohnheiten mit fester Uhrzeit, für die heute eine Erinnerung ansteht. */
            habitReminders: HabitReminder[];
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
        flashDataType: {
            /**
             * Bestätigung nach dem Anlegen. Trägt den nächsten Termin, weil
             * eine Gewohnheit mit fester Uhrzeit heute nicht anstehen muss und
             * auf der Übersicht sonst spurlos verschwände.
             */
            habitCreated?: {
                title: string;
                /** Satzteil wie „ab heute" oder „am Montag um 17:00". */
                when: string;
                scheduledToday: boolean;
            };
        };
    }
}
