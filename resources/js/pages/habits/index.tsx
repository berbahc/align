import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { GraduatedHabitRow } from '@/components/graduated-habit-row';
import { HabitBoard } from '@/components/habit-board';
import type { BoardSection } from '@/components/habit-board';
import { HabitLimitNote } from '@/components/habit-limit-note';
import { RhythmLegend } from '@/components/rhythm-strip';
import { Card, CardContent } from '@/components/ui/card';
import { ToggleSwitch } from '@/components/ui/toggle-switch';
import { requestReminderPermission } from '@/hooks/use-habit-reminders';
import { OUTLINE_BUTTON, PRIMARY_BUTTON, QUIET_LINK } from '@/lib/interaction';
import { calendar, dashboard } from '@/routes';
import { create } from '@/routes/habits';
import {
    destroy as removeCompletion,
    store as addCompletion,
} from '@/routes/habits/completions';
import { store as graduate } from '@/routes/habits/graduation';
import { update } from '@/routes/habits/reminder';
import { updateAll } from '@/routes/habits/reminders';
import type { GraduatedHabit, ManagedHabit, RhythmDay } from '@/types';

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

    /**
     * Die Zählung im Kopf — und was sie bewusst nicht mehr sagt.
     *
     * Sie las einmal „4 aktiv · 3 stehen heute an". Die zweite Hälfte ist die
     * Frage der Übersicht, und die beantwortet sie dort besser: mit einer
     * Liste, die man abhaken kann. Hier stand sie als dritte Heute-Aussage
     * neben der Gliederung und dem Band — auf einer Seite, auf der es laut
     * {@see HabitController::index()} „um die Gewohnheit an sich" geht, nicht
     * um den heutigen Tag.
     *
     * Verloren geht nichts: Was heute ansteht, sagt das Blatt in der Spalte
     * des heutigen Tages, und zwar für jede Gewohnheit einzeln statt als
     * Summe.
     *
     * An der Grenze zählt weiter die Grenze — „5 von 5 aktiv" beziffert ein
     * Kontingent, und das ist eine Auskunft, die es sonst nirgends gibt.
     */
    const countLabel =
        habits.length > 0
            ? isAtLimit
                ? `${habits.length} von ${maxActive} aktiv`
                : habits.length === 1
                  ? '1 aktive Gewohnheit'
                  : `${habits.length} aktive Gewohnheiten`
            : graduatedHabits.length > 0
              ? 'Keine aktive Gewohnheit.'
              : 'Noch nichts angelegt.';
    const allRemindersOn =
        remindable.length > 0 &&
        remindable.every((habit) => habit.reminderEnabled);

    /**
     * Das Blatt in höchstens zwei Abschnitten — und nur einer trägt einen
     * sichtbaren Titel.
     *
     * **„Steht heute an" und „Steht später an" sind weggefallen.** Sie
     * gliederten die Seite nach derselben Frage, die die Übersicht stellt, und
     * das Blatt beantwortet sie ohnehin schon genauer: Die Spalte des heutigen
     * Tages sagt je Gewohnheit, ob sie heute ansteht — gefülltes Kästchen
     * heißt ja, ein Punkt heißt nein. Eine Überschrift darüber war dieselbe
     * Auskunft ein zweites Mal, nur gröber.
     *
     * **Die Reihenfolge bleibt unangetastet.** Der Server sortiert nach dem
     * nächsten Termin ({@see HabitController::index()}), heute zuerst, dann
     * morgen, dann der Rest der Woche. Was oben steht, steht weiter oben — es
     * wird nur nicht mehr angesagt. Wo es zählt, sagt es die Zeile selbst:
     * „morgen · 19:00" steht bei jeder Gewohnheit, die heute nicht dran ist.
     *
     * **„Braucht einen neuen Platz" behält seinen Titel.** Der Abschnitt sagt
     * nichts über heute, sondern dass eine Entscheidung offen ist — und ohne
     * den Satz mit dem Weg in den Kalender wäre er eine Sackgasse.
     */
    const displaced = habits.filter((habit) => habit.group === 'displaced');
    const placed = habits.filter((habit) => habit.group !== 'displaced');

    const sections: BoardSection[] = [
        {
            key: 'ohne-platz',
            heading: 'Braucht einen neuen Platz',
            showHeading: true,
            group: 'displaced' as const,
            entries: displaced,
            note: (
                <p className="text-xs leading-relaxed text-muted-foreground">
                    Ein Kurs liegt jetzt auf dem alten Platz. Im{' '}
                    <Link href={calendar()} className={QUIET_LINK}>
                        Kalender
                    </Link>{' '}
                    siehst du den Kurs und kannst eine neue Zeit aussuchen.
                </p>
            ),
        },
        {
            key: 'gewohnheiten',
            // Nur für die Vorlesehilfe: Sie braucht einen Namen für die Liste,
            // das Auge braucht keinen — darüber steht schon der Seitentitel.
            heading: 'Deine Gewohnheiten',
            showHeading: false,
            group: 'today' as const,
            entries: placed,
        },
    ].filter((section) => section.entries.length > 0);

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
     * Einen Tag der Woche abhaken — oder das Häkchen zurücknehmen.
     *
     * Der kürzeste Weg zum häufigsten Fall: Wer gestern vergessen hat, sieht
     * die Lücke hier im Streifen und musste bisher über Kalender, Tag und
     * Haken gehen. `completed_on` reist in beide Richtungen mit; der Server
     * prüft damit das Nachtrag-Fenster und weist Tage ab, an denen die
     * Gewohnheit nicht vorgesehen war — dieselbe Strecke wie in der
     * Tagesansicht.
     */
    function toggleDay(habit: ManagedHabit, day: RhythmDay) {
        if (day.completed) {
            router.delete(removeCompletion.url(habit.id), {
                data: { completed_on: day.date },
                preserveScroll: true,
            });

            return;
        }

        router.post(
            addCompletion.url(habit.id),
            { completed_on: day.date },
            { preserveScroll: true },
        );
    }

    /**
     * Ohne Rückfrage: die Gewohnheit rutscht sichtbar ins Archiv, wo
     * „Wiederaufnehmen" einen Klick entfernt ist. Ein Dialog würde eine
     * Endgültigkeit behaupten, die hier nicht besteht.
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
     * oben. Die Betonung steht ein paar Sekunden und geht von selbst — sie ist
     * eine Antwort auf einen Klick, kein Zustand.
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

            {/* Schmaler als die Übersicht, breiter als eine Kartenspalte: Das
                Blatt braucht Platz für Name, sieben Tage und die Bilanz — und
                nicht mehr. Auf 1280 Pixeln lag zwischen dem Namen und seinen
                Marken sonst eine handbreite Lücke, und die Zeile fiel
                auseinander. */}
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 sm:p-6">
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
                            <p className="text-sm text-muted-foreground md:mt-1">
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
                    <div className="flex flex-col gap-3">
                        {/* Die eine gehobene Fläche der Seite (§12: Höhe ist
                            Hierarchie, und genau zwei Stufen, damit sie etwas
                            bedeuten). Alles Weitere liegt flach auf. */}
                        <Card className="gap-0 border-transparent py-4 shadow-[var(--shadow-lift)] sm:py-5">
                            <CardContent className="px-2 sm:px-5">
                                <HabitBoard
                                    sections={sections}
                                    landed={landed}
                                    onToggleReminder={toggleOne}
                                    onToggleDay={toggleDay}
                                    onEnd={endHabit}
                                />
                            </CardContent>
                        </Card>

                        <RhythmLegend className="px-1" />
                    </div>
                )}

                {/* Zwei Abschnitte, die beide nachrangig sind — nebeneinander
                    statt untereinander. Als zwei weitere Blöcke im Stapel
                    hätten sie dasselbe Gewicht bekommen wie das Blatt darüber,
                    obwohl man sie selten braucht. */}
                {(graduatedHabits.length > 0 ||
                    remindable.length > 1 ||
                    habits.some((habit) => !habit.canRemind)) && (
                    <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
                        {graduatedHabits.length > 0 && (
                            <section className="flex flex-col gap-3">
                                <div>
                                    <h2 className="type-eyebrow text-muted-foreground">
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
                                                landed ===
                                                `graduated-habit-${habit.id}`
                                            }
                                            onReactivated={() =>
                                                showLanding(
                                                    `managed-habit-${habit.id}`,
                                                )
                                            }
                                        />
                                    ))}
                                </ul>
                            </section>
                        )}

                        {/* Der Sammelschalter stand einmal ganz oben — das
                            Erste auf dem Gewohnheiten-Tab war damit eine
                            Benachrichtigungs-Voreinstellung. Er steht hier bei
                            dem Satz, der zur selben Sache gehört.

                            Ein Sammelschalter über genau einem Schalter ist
                            derselbe Schalter zweimal — er lohnt erst, wenn er
                            etwas zusammenfasst (§16 Simplicity). */}
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
                                                Alle {remindable.length}{' '}
                                                Erinnerungen
                                            </p>
                                            <ToggleSwitch
                                                checked={allRemindersOn}
                                                onChange={toggleAll}
                                                label="Erinnerung für alle Gewohnheiten mit fester Uhrzeit"
                                            />
                                        </CardContent>
                                    </Card>
                                )}

                                {/* Erklärt die fehlenden Einträge in einigen
                                    Menüs — ohne diesen Satz sähe es nach einem
                                    Fehler aus. */}
                                {habits.some((habit) => !habit.canRemind) && (
                                    <p className="text-xs leading-relaxed text-muted-foreground">
                                        Erinnern lässt sich nur, was eine feste
                                        Uhrzeit hat. Ein- und ausschalten kannst
                                        du sie im ⋯-Menü der Gewohnheit.
                                    </p>
                                )}
                            </section>
                        )}
                    </div>
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
