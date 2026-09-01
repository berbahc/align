import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { GraduatedHabitRow } from '@/components/graduated-habit-row';
import { HabitLimitNote } from '@/components/habit-limit-note';
import { ManagedHabitRow } from '@/components/managed-habit-row';
import { Card, CardContent } from '@/components/ui/card';
import { ToggleSwitch } from '@/components/ui/toggle-switch';
import { requestReminderPermission } from '@/hooks/use-habit-reminders';
import { OUTLINE_BUTTON } from '@/lib/interaction';
import { dashboard } from '@/routes';
import { create } from '@/routes/habits';
import { store as graduate } from '@/routes/habits/graduation';
import { update } from '@/routes/habits/reminder';
import { updateAll } from '@/routes/habits/reminders';
import type { GraduatedHabit, HabitGroup, ManagedHabit } from '@/types';

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

    const groups = (
        [
            { key: 'heute', heading: 'Steht heute an', group: 'today' },
            { key: 'spaeter', heading: 'Steht später an', group: 'later' },
        ] satisfies { key: string; heading: string; group: HabitGroup }[]
    )
        .map((block) => ({
            ...block,
            entries: habits.filter((habit) => habit.group === block.group),
        }))
        .filter((block) => block.entries.length > 0);

    // Überschriften lohnen nur, wenn sie etwas voneinander abgrenzen. Wer nur
    // Gewohnheiten einer Art hat, sähe sonst einen Titel über einer Liste ohne
    // Gegenstück — und am Wochenende stünde „Steht später an" über allem.
    const splitIntoBlocks = groups.length > 1;

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
                        <h1 className="type-title text-primary">
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
                        <Link href={create()} className={OUTLINE_BUTTON}>
                            <Plus className="size-4" aria-hidden="true" />
                            Neu hinzufügen
                        </Link>
                    )}
                </header>

                {/* Ein Sammelschalter über genau einem Schalter ist derselbe
                    Schalter zweimal — er lohnt erst, wenn er etwas
                    zusammenfasst (§16 Simplicity). */}
                {remindable.length > 1 && (
                    <Card>
                        <CardContent className="flex items-center justify-between gap-4">
                            <p className="min-w-0 text-[15px] font-semibold">
                                Alle {remindable.length} Erinnerungen
                            </p>
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
                                Noch keine Gewohnheit angelegt.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    /* Zwei Blöcke statt einer pro Tag: Bei höchstens fünf
                       Gewohnheiten wären fünf Überschriften mehr Gliederung als
                       Inhalt. Was hier zählt, ist die Unterscheidung — betrifft
                       mich heute oder später; der genaue Tag steht ohnehin in
                       der Zeile. */
                    <div className="flex flex-col gap-6">
                        {groups.map(({ key, heading, entries }) => (
                            <section key={key} aria-labelledby={key}>
                                {/* Die Überschrift erscheint nur, wenn es etwas
                                    abzugrenzen gibt. Steht alles heute an, wäre
                                    sie ein Titel über einer Liste ohne
                                    Gegenstück — Gliederung ohne Grenze. */}
                                {splitIntoBlocks ? (
                                    <h2
                                        id={key}
                                        className={
                                            'type-eyebrow mb-2 text-muted-foreground'
                                        }
                                    >
                                        {heading}
                                    </h2>
                                ) : (
                                    <h2 id={key} className="sr-only">
                                        {heading}
                                    </h2>
                                )}

                                <ul className="flex flex-col gap-3">
                                    {entries.map((habit) => (
                                        <ManagedHabitRow
                                            key={habit.id}
                                            habit={habit}
                                            onToggleReminder={toggleOne}
                                            onEnd={endHabit}
                                        />
                                    ))}
                                </ul>
                            </section>
                        ))}
                    </div>
                )}

                {graduatedHabits.length > 0 && (
                    <section className="flex flex-col gap-3">
                        <div>
                            <h2
                                className={'type-eyebrow text-muted-foreground'}
                            >
                                Beendet
                            </h2>
                            <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                                Der Verlauf bleibt erhalten.
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

                {/* Erklärt die fehlenden Schalter in einigen Zeilen — ohne
                    diesen Satz sähe es nach einem Fehler aus. */}
                {habits.some((habit) => !habit.canRemind) && (
                    <p className="text-xs leading-relaxed text-muted-foreground">
                        Erinnern lässt sich nur, was eine feste Uhrzeit hat.
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
