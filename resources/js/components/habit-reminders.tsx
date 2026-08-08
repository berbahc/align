import { usePage } from '@inertiajs/react';
import { useHabitReminders } from '@/hooks/use-habit-reminders';

/**
 * Hängt den Erinnerungs-Wecker an die gesamte App.
 *
 * Rendert nichts — sie existiert nur, weil `useHabitReminders` einen
 * Komponentenkontext braucht. Sitzt in `withApp`, damit die Erinnerung auf
 * jeder Seite läuft und nicht nur dort, wo Gewohnheiten sichtbar sind.
 */
export function HabitReminders() {
    const { habitReminders } = usePage().props;

    useHabitReminders(habitReminders ?? []);

    return null;
}
