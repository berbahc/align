import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    BookOpen,
    CircleCheck,
    Dumbbell,
    GlassWater,
    Moon,
    MoreHorizontal,
    Plus,
} from 'lucide-react';
import { GraduatedHabitRow } from '@/components/graduated-habit-row';
import { HabitLimitNote } from '@/components/habit-limit-note';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ToggleSwitch } from '@/components/ui/toggle-switch';
import { requestReminderPermission } from '@/hooks/use-habit-reminders';
import { dashboard } from '@/routes';
import { create } from '@/routes/habits';
import { store as graduate } from '@/routes/habits/graduation';
import { update } from '@/routes/habits/reminder';
import { updateAll } from '@/routes/habits/reminders';
import type { GraduatedHabit, ManagedHabit } from '@/types';

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
    graduatedHabits: GraduatedHabit[];
    /** Obergrenze gleichzeitig aktiver Gewohnheiten, aus Habit::MaxActivePerUser. */
    maxActive: number;
}

export default function HabitsIndex({
    habits,
    graduatedHabits,
    maxActive,
}: HabitsIndexProps) {
    const { errors } = usePage().props;
    const remindable = habits.filter((habit) => habit.canRemind);
    const isAtLimit = habits.length >= maxActive;

    const countLabel =
        habits.length > 0
            ? `${habits.length} von ${maxActive} aktiv`
            : graduatedHabits.length > 0
              ? 'Keine aktive Gewohnheit.'
              : 'Noch nichts angelegt.';
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

    /**
     * Ohne Rückfrage: die Gewohnheit rutscht sichtbar ins Archiv direkt
     * darunter, wo „Wiederaufnehmen" einen Klick entfernt ist. Ein Dialog
     * würde eine Endgültigkeit behaupten, die hier nicht besteht.
     */
    function endHabit(habit: ManagedHabit) {
        router.post(graduate.url(habit.id), {}, { preserveScroll: true });
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

                        {/* Erst an der Grenze trägt die Zählung den Hinweis:
                            vorher erklärt er eine Einschränkung, die noch
                            niemanden trifft. */}
                        {isAtLimit ? (
                            <HabitLimitNote
                                max={maxActive}
                                label={countLabel}
                            />
                        ) : (
                            <p className="mt-1 text-sm text-muted-foreground">
                                {countLabel}
                            </p>
                        )}
                    </div>

                    {!isAtLimit && (
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

                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-11 shrink-0 cursor-pointer text-muted-foreground hover:bg-accent"
                                                    >
                                                        <MoreHorizontal
                                                            className="size-5"
                                                            aria-hidden="true"
                                                        />
                                                        <span className="sr-only">
                                                            Aktionen für{' '}
                                                            {habit.title}
                                                        </span>
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem
                                                        className="cursor-pointer"
                                                        onSelect={() =>
                                                            endHabit(habit)
                                                        }
                                                    >
                                                        <CircleCheck aria-hidden="true" />
                                                        Beenden
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </CardContent>
                                    </Card>
                                </li>
                            );
                        })}
                    </ul>
                )}

                {graduatedHabits.length > 0 && (
                    <section className="flex flex-col gap-3">
                        <div>
                            <h2 className={`${EYEBROW} text-muted-foreground`}>
                                Beendet
                            </h2>
                            <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                                Zählt nicht mehr gegen die fünf Plätze und
                                erinnert nicht mehr. Der Verlauf bleibt — bis du
                                die Gewohnheit endgültig löschst.
                            </p>
                        </div>

                        {errors.habit && (
                            <p className="text-xs text-destructive">
                                {errors.habit}
                            </p>
                        )}

                        <ul className="flex flex-col gap-2">
                            {graduatedHabits.map((habit) => (
                                <GraduatedHabitRow
                                    key={habit.id}
                                    habit={habit}
                                    canReactivate={!isAtLimit}
                                />
                            ))}
                        </ul>
                    </section>
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
