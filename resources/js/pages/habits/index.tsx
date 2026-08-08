import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, Dumbbell, GlassWater, Moon, Plus } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { ToggleSwitch } from '@/components/ui/toggle-switch';
import { requestReminderPermission } from '@/hooks/use-habit-reminders';
import { dashboard } from '@/routes';
import { create } from '@/routes/habits';
import { update } from '@/routes/habits/reminder';
import { updateAll } from '@/routes/habits/reminders';
import type { ManagedHabit } from '@/types';

// Muss zu DIRECTION_ICONS im Wizard passen — dieselbe Kategorie darf nicht
// je nach Bildschirm ein anderes Zeichen tragen.
const BEHAVIOR_ICONS = {
    nutrition: GlassWater,
    movement: Dumbbell,
    learning: BookOpen,
    other: Moon,
} as const;

const EYEBROW = 'text-[11px] font-semibold tracking-[0.11em] uppercase';

interface HabitsIndexProps {
    habits: ManagedHabit[];
}

export default function HabitsIndex({ habits }: HabitsIndexProps) {
    const remindable = habits.filter((habit) => habit.canRemind);
    const allRemindersOn =
        remindable.length > 0 &&
        remindable.every((habit) => habit.reminderEnabled);

    /**
     * Die Berechtigung wird erst beim Einschalten erfragt, nie beim Aufruf der
     * Seite. Ein ungefragter Systemdialog ist genau die Aufdringlichkeit, die
     * die App vermeiden soll — und wer ihn wegklickt, ist dauerhaft gesperrt.
     */
    async function enable(enabled: boolean, submit: () => void) {
        if (enabled) {
            await requestReminderPermission();
        }

        submit();
    }

    function toggleOne(habit: ManagedHabit, enabled: boolean) {
        void enable(enabled, () =>
            router.patch(
                update.url(habit.id),
                { enabled },
                { preserveScroll: true },
            ),
        );
    }

    function toggleAll(enabled: boolean) {
        void enable(enabled, () =>
            router.put(updateAll.url(), { enabled }, { preserveScroll: true }),
        );
    }

    return (
        <>
            <Head title="Gewohnheiten" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
                <header className="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
                    <div>
                        <h1 className="text-[clamp(1.75rem,4vw,2rem)] leading-tight font-bold text-primary">
                            Gewohnheiten
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {habits.length === 0
                                ? 'Noch nichts angelegt.'
                                : `${habits.length} von 5 aktiv`}
                        </p>
                    </div>

                    {habits.length < 5 && (
                        <Link
                            href={create()}
                            className="inline-flex h-10 cursor-pointer items-center gap-2 rounded-full border border-primary px-4 text-sm font-semibold text-primary transition-colors duration-200 hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            <Plus className="size-4" aria-hidden="true" />
                            Neu hinzufügen
                        </Link>
                    )}
                </header>

                {remindable.length > 0 && (
                    <Card>
                        <CardContent className="flex items-center justify-between gap-4">
                            <div className="min-w-0">
                                <p className="text-[15px] font-semibold">
                                    Erinnerung 10 Min vorher
                                </p>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    Für alle Gewohnheiten mit fester Uhrzeit
                                </p>
                            </div>
                            <ToggleSwitch
                                checked={allRemindersOn}
                                onChange={toggleAll}
                                label="Erinnerung für alle Gewohnheiten mit fester Uhrzeit"
                            />
                        </CardContent>
                    </Card>
                )}

                {habits.length === 0 ? (
                    <Card>
                        <CardContent>
                            <p className="text-sm leading-relaxed text-muted-foreground">
                                Sobald du eine Gewohnheit angelegt hast,
                                erscheint sie hier — mit ihrem Auslöser und der
                                Möglichkeit, eine Erinnerung zu setzen.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {habits.map((habit) => {
                            const Icon = BEHAVIOR_ICONS[habit.behaviorType];

                            return (
                                <li key={habit.id}>
                                    <Card>
                                        <CardContent className="flex items-center gap-3">
                                            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sand text-primary">
                                                <Icon
                                                    className="size-5"
                                                    strokeWidth={1.5}
                                                    aria-hidden="true"
                                                />
                                            </span>

                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate text-[15px] leading-snug font-semibold">
                                                    {habit.title}
                                                </span>
                                                <span className="mt-0.5 block truncate text-xs text-muted-foreground">
                                                    {habit.scheduleLabel}
                                                </span>
                                            </span>

                                            <span className="flex shrink-0 flex-col items-end gap-1">
                                                <ToggleSwitch
                                                    checked={
                                                        habit.reminderEnabled
                                                    }
                                                    disabled={!habit.canRemind}
                                                    onChange={(enabled) =>
                                                        toggleOne(
                                                            habit,
                                                            enabled,
                                                        )
                                                    }
                                                    label={`Erinnerung für ${habit.title}`}
                                                />
                                                <span
                                                    className={`${EYEBROW} text-muted-foreground`}
                                                >
                                                    {habit.canRemind
                                                        ? '10 Min vorher'
                                                        : 'ohne Uhrzeit'}
                                                </span>
                                            </span>
                                        </CardContent>
                                    </Card>
                                </li>
                            );
                        })}
                    </ul>
                )}

                {/* Beobachtend statt belehrend (§8): der Satz erklärt, warum
                    manche Schalter nicht greifen, ohne es zum Mangel zu machen. */}
                {habits.some((habit) => !habit.canRemind) && (
                    <p className="text-xs leading-relaxed text-muted-foreground">
                        Erinnerungen gibt es für Gewohnheiten mit fester
                        Uhrzeit. Gewohnheiten, die an einer Situation hängen,
                        melden sich nicht von selbst — die Situation ist ihr
                        Auslöser.
                    </p>
                )}
            </div>
        </>
    );
}

HabitsIndex.layout = {
    breadcrumbs: [
        { title: 'Übersicht', href: dashboard() },
        { title: 'Gewohnheiten', href: '' },
    ],
};
