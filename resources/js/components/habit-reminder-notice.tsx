import { router, usePage } from '@inertiajs/react';
import { Check, Clock, X } from 'lucide-react';
import { useState } from 'react';
import { useDueReminders } from '@/hooks/use-habit-reminders';
import type { DueReminder } from '@/hooks/use-habit-reminders';
import { store } from '@/routes/habits/completions';

/**
 * Der Wann-Teil einer anstehenden Gewohnheit.
 *
 * §1.5: benannt wird, was gilt, nicht was fehlt. Deshalb „steht seit 17:00 an"
 * und nicht „überfällig" oder „verpasst".
 */
function timing({ reminder, minutesUntil }: DueReminder): string {
    if (minutesUntil > 1) {
        return `in ${minutesUntil} Minuten`;
    }

    if (minutesUntil >= 0) {
        return 'gleich';
    }

    return `seit ${reminder.scheduledTime}`;
}

/**
 * Zeigt an, welche Gewohnheit gerade ansteht — in der App statt im System.
 *
 * Die Systembenachrichtigung lief nur bei geöffnetem Tab und hatte damit den
 * Preis einer Benachrichtigung ohne ihren Nutzen: Erlaubnisdialog, stiller
 * Ausfall bei „Nicht stören", kein Einfluss auf den Ton. Hier greift nichts
 * davon, und das Abhaken steht direkt daneben.
 *
 * Kein Modal: ein Hinweis, der den Bildschirm sperrt, ist eine Forderung.
 */
export function HabitReminderNotice() {
    const { habitReminders } = usePage().props;
    const due = useDueReminders(habitReminders ?? []);
    const [hidden, setHidden] = useState<number[]>([]);

    const visible = due.filter(({ reminder }) => !hidden.includes(reminder.id));

    if (visible.length === 0) {
        return null;
    }

    return (
        <div
            role="status"
            className="mx-auto mt-4 flex w-full max-w-3xl flex-col gap-2"
        >
            {visible.map((item) => (
                <div
                    key={item.reminder.id}
                    className="flex items-center gap-3 rounded-xl border border-primary/25 bg-sand px-4 py-3"
                >
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <Clock
                            className="size-5"
                            strokeWidth={1.5}
                            aria-hidden="true"
                        />
                    </span>

                    <span className="min-w-0 flex-1">
                        <span className="block truncate text-[15px] leading-snug font-semibold">
                            {item.reminder.title}
                        </span>
                        <span className="mt-0.5 block text-xs text-muted-foreground">
                            steht {timing(item)} an
                        </span>
                    </span>

                    <button
                        type="button"
                        onClick={() =>
                            router.post(
                                store.url(item.reminder.id),
                                {},
                                { preserveScroll: true },
                            )
                        }
                        className="inline-flex h-11 shrink-0 cursor-pointer items-center gap-2 rounded-full bg-primary px-4 text-sm font-semibold text-primary-foreground transition-colors duration-200 hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        <Check className="size-4" aria-hidden="true" />
                        Abhaken
                    </button>

                    <button
                        type="button"
                        onClick={() =>
                            setHidden((ids) => [...ids, item.reminder.id])
                        }
                        className="-mr-2 flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full text-muted-foreground transition-colors duration-200 hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        <X className="size-4" aria-hidden="true" />
                        <span className="sr-only">
                            Hinweis zu {item.reminder.title} ausblenden
                        </span>
                    </button>
                </div>
            ))}
        </div>
    );
}
