import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { GraduatedHabitRow } from '@/components/graduated-habit-row';
import { HabitLimitNote } from '@/components/habit-limit-note';
import { ManagedHabitRow } from '@/components/managed-habit-row';
import { RhythmLegend } from '@/components/rhythm-strip';
import { Card, CardContent } from '@/components/ui/card';
import { ToggleSwitch } from '@/components/ui/toggle-switch';
import { requestReminderPermission } from '@/hooks/use-habit-reminders';
import { OUTLINE_BUTTON, PRIMARY_BUTTON, QUIET_LINK } from '@/lib/interaction';
import { calendar, dashboard } from '@/routes';
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

    // Welche Zeile gerade betont ist, nachdem sie hierher gewandert ist.
    const [landed, setLanded] = useState<string | null>(null);
    const fadeLanding = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(
        () => () => {
            if (fadeLanding.current !== null) {
                clearTimeout(fadeLanding.current);
            }
        },
        [],
    );
    const remindable = habits.filter((habit) => habit.canRemind);
    const isAtLimit = habits.length >= maxActive;

    const dueToday = habits.filter((habit) => habit.group === 'today').length;

    // An der Grenze zählt die Grenze — sonst zählt der Tag. „5 von 5 aktiv"
    // beziffert ein Kontingent und sagt nichts über heute; wer nicht am Limit
    // ist, will wissen, was ansteht.
    const countLabel =
        habits.length > 0
            ? isAtLimit
                ? `${habits.length} von ${maxActive} aktiv`
                : `${habits.length} aktiv · ${
                      dueToday === 1
                          ? '1 steht heute an'
                          : `${dueToday} stehen heute an`
                  }`
            : graduatedHabits.length > 0
              ? 'Keine aktive Gewohnheit.'
              : 'Noch nichts angelegt.';
    const allRemindersOn =
        remindable.length > 0 &&
        remindable.every((habit) => habit.reminderEnabled);

    const groups = (
        [
            {
                key: 'ohne-platz',
                heading: 'Braucht einen neuen Platz',
                group: 'displaced',
            },
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
     *
     * „Sichtbar" war bis hierher eine Behauptung: Die Zeile verschwand oben
     * und erschien unten, und wer das Archiv nicht ohnehin im Blick hatte, sah
     * nur, dass etwas weg war. Der Ring holt sie ein — dieselbe Antwort, die
     * die Übersicht auf „Mach ich trotzdem" gibt.
     */
    function endHabit(habit: ManagedHabit) {
        router.post(
            graduate.url(habit.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => showLanding(`graduated-habit-${habit.id}`),
            },
        );
    }

    /**
     * Zeigt, wo etwas gelandet ist, statt es finden zu lassen.
     *
     * Gilt in beide Richtungen: beendet nach unten, wieder aufgenommen nach
     * oben. Der Ring steht ein paar Sekunden und geht von selbst — er ist eine
     * Antwort auf einen Klick, kein Zustand.
     */
    function showLanding(id: string) {
        const row = document.getElementById(id);

        if (row === null) {
            return;
        }

        setLanded(id);
        row.scrollIntoView({
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)')
                .matches
                ? 'auto'
                : 'smooth',
            block: 'center',
        });

        fadeLanding.current = setTimeout(() => setLanded(null), 2500);
    }

    return (
        <>
            <Head title="Gewohnheiten" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
                <header className="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
                    <div>
                        {/* Auf dem Telefon steht der Name schon im Kopf, und
                            zweimal „Gewohnheiten" untereinander ist keine
                            Überschrift, sondern ein Echo. Die Zählung darunter
                            bleibt — sie sagt etwas, das der Kopf nicht sagt. */}
                        <h1 className="type-title text-primary max-md:hidden">
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

                {habits.length === 0 ? (
                    /* Der Weg gehört in den leeren Zustand, nicht nur in die
                       Kopfzeile: Wer hier landet, hat nichts zu lesen und
                       braucht etwas zu tun. Dieselbe Auflösung wie auf der
                       Übersicht. */
                    <Card>
                        <CardContent className="flex flex-col items-start gap-4">
                            <p className="text-sm leading-relaxed text-muted-foreground">
                                Noch keine Gewohnheit angelegt.
                            </p>
                            <Link
                                href={create()}
                                className={`${PRIMARY_BUTTON} w-auto`}
                            >
                                <Plus className="size-4" aria-hidden="true" />
                                Erste Gewohnheit anlegen
                            </Link>
                        </CardContent>
                    </Card>
                ) : (
                    /* Zwei Blöcke statt einer pro Tag: Bei höchstens fünf
                       Gewohnheiten wären fünf Überschriften mehr Gliederung als
                       Inhalt. Was hier zählt, ist die Unterscheidung — betrifft
                       mich heute oder später; der genaue Tag steht ohnehin in
                       der Zeile. */
                    <div className="flex flex-col gap-6">
                        {/* Einmal für die ganze Seite, direkt über der ersten
                            Zeile: Die Töne im Streifen sind ohne Erklärung
                            raterei, und fünf Legenden wären fünfmal dieselbe
                            Erklärung. Sie steht über der Liste, weil man sie
                            beim ersten Blick braucht — und ist leise genug, um
                            danach nicht zu stören. */}
                        <RhythmLegend />

                        {groups.map(({ key, heading, group, entries }) => (
                            <section
                                key={key}
                                aria-labelledby={key}
                                className={
                                    /* Was seinen Platz verloren hat, wartet auf
                                       eine Entscheidung — und sah bisher aus wie
                                       alles andere. Der Kalender markiert
                                       denselben Fall längst mit dieser Fläche;
                                       die Liste zog nur eine Kleinversalien-
                                       Zeile darüber und bot keinen Ausweg.
                                       Accent statt Rot: Hier ist nichts schief-
                                       gegangen, hier fehlt eine Wahl (§1.4). */
                                    group === 'displaced'
                                        ? 'rounded-xl border border-primary/25 bg-accent p-4'
                                        : undefined
                                }
                            >
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

                                {group === 'displaced' && (
                                    <p className="mb-3 text-xs leading-relaxed text-muted-foreground">
                                        Ein Kurs liegt jetzt auf dem alten
                                        Platz. Im{' '}
                                        <Link
                                            href={calendar()}
                                            className={QUIET_LINK}
                                        >
                                            Kalender
                                        </Link>{' '}
                                        siehst du den Kurs und kannst eine neue
                                        Zeit aussuchen.
                                    </p>
                                )}

                                <ul className="flex flex-col gap-3">
                                    {entries.map((habit) => (
                                        <ManagedHabitRow
                                            key={habit.id}
                                            habit={habit}
                                            onToggleReminder={toggleOne}
                                            onEnd={endHabit}
                                            highlighted={
                                                landed ===
                                                `managed-habit-${habit.id}`
                                            }
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
                                    highlighted={
                                        landed === `graduated-habit-${habit.id}`
                                    }
                                    onReactivated={() =>
                                        showLanding(`managed-habit-${habit.id}`)
                                    }
                                />
                            ))}
                        </ul>
                    </section>
                )}

                {/* Der Sammelschalter stand bisher ganz oben — das Erste auf
                    dem Gewohnheiten-Tab war damit eine Benachrichtigungs-
                    Voreinstellung. Er steht jetzt hier unten bei dem Satz, der
                    zur selben Sache gehört: Beide reden über Erinnerungen, und
                    zusammen sind sie ein Abschnitt statt zweier Einsprengsel.
                    Weggefallen ist nichts, der Weg ist derselbe.

                    Ein Sammelschalter über genau einem Schalter ist derselbe
                    Schalter zweimal — er lohnt erst, wenn er etwas zusammen-
                    fasst (§16 Simplicity). */}
                {(remindable.length > 1 ||
                    habits.some((habit) => !habit.canRemind)) && (
                    <section
                        aria-labelledby="erinnerungen"
                        className="flex flex-col gap-3"
                    >
                        <h2
                            id="erinnerungen"
                            className="type-eyebrow text-muted-foreground"
                        >
                            Erinnerungen
                        </h2>

                        {remindable.length > 1 && (
                            <Card className="gap-0 py-4 shadow-none">
                                <CardContent className="flex items-center justify-between gap-4 px-4">
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

                        {/* Erklärt die fehlenden Einträge in einigen Menüs —
                            ohne diesen Satz sähe es nach einem Fehler aus. */}
                        {habits.some((habit) => !habit.canRemind) && (
                            <p className="text-xs leading-relaxed text-muted-foreground">
                                Erinnern lässt sich nur, was eine feste Uhrzeit
                                hat. Ein- und ausschalten kannst du sie im
                                ⋯-Menü der Gewohnheit.
                            </p>
                        )}
                    </section>
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
