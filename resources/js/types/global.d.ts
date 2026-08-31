import type { Auth } from '@/types/auth';
import type { AppointmentDay } from '@/types/friendship';
import type { HabitReminder, SleepShared } from '@/types/habit';

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
            /** Der Schlafrahmen der umliegenden Tage — für Hinweis und Wecker. */
            sleep: SleepShared | null;
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
                /** Trägt den letzten Schritt: die Verabredung hängt daran. */
                id: number;
                title: string;
                /** Der Wann-Teil, fertig formatiert. */
                anchor: string;
                /** Satzteil wie „ab heute" oder „am Montag um 17:00". */
                when: string;
                scheduledToday: boolean;
                /** Die nächsten Termine dieser Gewohnheit, höchstens drei. */
                days: AppointmentDay[];
            };
            /**
             * Bestätigung nach einer Anpassung. Trägt den vorherigen Anker mit,
             * weil Gewohnheiten sich sonst nirgends bearbeiten lassen — ein Weg,
             * der nur vorwärts führt, wäre bei einem Vorschlag der KI die
             * falsche Richtung.
             */
            habitAdjusted?: {
                habitId: number;
                title: string;
                /** Der neue Anker, fertig formatiert. */
                anchor: string;
                /** Der bisherige, als Text für den Rückweg. */
                previousLabel: string;
                /** Die Felder, mit denen sich der bisherige wiederherstellen lässt. */
                previous: {
                    trigger_situation?: string;
                    scheduled_time?: string;
                    scheduled_days?: number[];
                };
            };
        };
    }
}
