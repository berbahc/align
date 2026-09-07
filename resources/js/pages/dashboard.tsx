import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { AppointmentNotice } from '@/components/appointment-notice';
import { AppointmentRequestNotice } from '@/components/appointment-request-notice';
import { AppointmentSheet } from '@/components/appointment-sheet';
import { FriendRequestNotice } from '@/components/friend-request-notice';
import { HabitRow } from '@/components/habit-row';
import { SectionHeading } from '@/components/section-heading';
import { SleepCard } from '@/components/sleep-card';
import type { SleepCardData } from '@/components/sleep-card';
import { StartingHelpSheet } from '@/components/starting-help-sheet';
import { StreakCards } from '@/components/streak-card';
import type { Streak } from '@/components/streak-card';
import { Card, CardContent } from '@/components/ui/card';
import { UpcomingAppointments } from '@/components/upcoming-appointments';
import { useCountedNumber } from '@/hooks/use-counted-number';
import { OUTLINE_BUTTON, PRIMARY_BUTTON, QUIET_LINK } from '@/lib/interaction';
import { dashboard } from '@/routes';
import { destroy as dismissNotice } from '@/routes/appointment-notices';
import { destroy as dissolve } from '@/routes/appointments';
import { create, index as habitsIndex } from '@/routes/habits';
import { destroy, store } from '@/routes/habits/completions';
import type {
    AppointmentNotice as Notice,
    AppointmentRequest,
    UpcomingAppointment,
    FriendshipPerson,
    Habit,
} from '@/types';

interface TodayProgress {
    completed: number;
    /** Das Tagesziel — schlicht die Anzahl aktiver Gewohnheiten. */
    total: number;
    percentage: number;
}

interface DashboardProps {
    greeting: string;
    today: string;
    /** Offene Freundschaftsanfragen — Mockup A2 zeigt sie auf dem Home-Screen. */
    friendRequests: FriendshipPerson[];
    /** Offene Verabredungs-Anfragen — Screen A2. */
    appointmentRequests: AppointmentRequest[];
    /** Absagen, die einmal erscheinen und beim Wegklicken verschwinden. */
    appointmentNotices: Notice[];
    /** Was mit jemandem ansteht — zugesagt oder von einem selbst gefragt. */
    upcomingAppointments: UpcomingAppointment[];
    /** Der eigene Kreis, für die Auswahl in Screen A1. */
    friends: FriendshipPerson[];
    /** Screen A5: aus heißt, der Weg zur Verabredung wird nicht angeboten. */
    appointmentsEnabled: boolean;
    /** Der Rahmen des heutigen Tages: Schlafen heute, Aufstehen morgen. */
    sleepCard: SleepCardData;
    todayProgress: TodayProgress;
    /** Anteil erfüllter Tage der letzten 30 Tage; null, solange es keine Gewohnheiten gibt. */
    /**
     * Erledigte und geplante Tage über alle Gewohnheiten, 30 Tage weit.
     *
     * Dieselbe Form wie auf der Gewohnheiten-Seite, nur über alle statt über
     * eine. Bewusst kein Prozentwert: Er ist nach Häufigkeit gewichtet, und
     * niemand liest ihn so. Null, solange es keine Gewohnheiten gibt.
     */
    consistency: { done: number; scheduled: number } | null;
    /** Die stärkste laufende Serie; null unterhalb von Habit::StreakMinimum. */
    /** Die laufenden Serien — höchstens drei, sonst leer. */
    streaks: Streak[];
    /** Nur die heute vorgesehenen Gewohnheiten. */
    habits: Habit[];
    /** Alle aktiven — auch die, die heute nicht anstehen. */
    activeCount: number;
    /** Obergrenze gleichzeitig aktiver Gewohnheiten, aus Habit::MaxActivePerUser. */
    maxActive: number;
}

export default function Dashboard({
    greeting,
    today,
    friendRequests,
    appointmentRequests,
    appointmentNotices,
    upcomingAppointments,
    friends,
    appointmentsEnabled,
    sleepCard,
    todayProgress,
    consistency,
    streaks,
    habits,
    activeCount,
    maxActive,
}: DashboardProps) {
    const { auth } = usePage().props;
    const firstName = auth.user?.name.split(' ')[0] ?? '';
    const selfInitial = (auth.user?.name.charAt(0) ?? '').toUpperCase();

    const countedPercentage = useCountedNumber(todayProgress.percentage);

    // Welche Gewohnheit gerade im Starthilfe-Sheet steht; null heißt zu.
    const [stuckOn, setStuckOn] = useState<Habit | null>(null);

    // Welche Gewohnheit gerade im Verabredungs-Sheet steht; null heißt zu.
    const [askingFor, setAskingFor] = useState<Habit | null>(null);

    /**
     * Die Wiederholung: dieselbe Person, eine neue Frage — §7.
     *
     * Getrennt vom Fall oben, weil zwei Dinge anders sind: Die Person ist
     * vorgewählt, und die Tage kommen aus der Gewohnheit, auf der wiederholt
     * wird — bei der gefragten Seite ist das die übernommene, nicht die
     * fremde, auf der die alte Verabredung hing.
     */
    const [repeating, setRepeating] = useState<{
        habit: Habit;
        friendId: number;
    } | null>(null);

    // Die Zeile, auf die eine Absage gerade verwiesen hat.
    const [highlighted, setHighlighted] = useState<number | null>(null);
    const fadeHighlight = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(
        () => () => {
            if (fadeHighlight.current !== null) {
                clearTimeout(fadeHighlight.current);
            }
        },
        [],
    );

    /**
     * „Mach ich trotzdem": Die Absage betraf den Tag, nicht die Gewohnheit.
     *
     * Sie steht ohnehin schon in der Liste — es gibt nichts anzulegen und
     * nichts abzuhaken. Was fehlt, ist der Hinweis, welche Zeile gemeint ist,
     * und den gibt der Ring für ein paar Sekunden.
     */
    function carryOn(notice: Notice) {
        const habitId = notice.habitId;

        router.delete(dismissNotice.url(notice.id), {
            preserveScroll: true,
            onSuccess: () => {
                if (habitId === null) {
                    return;
                }

                const row = document.getElementById(`habit-${habitId}`);

                // Abgesagt wird bis zu drei Tage im Voraus — die Gewohnheit
                // muss deshalb nicht heute anstehen. Dann steht sie nicht in
                // der Tagesliste, und der Weg führt dorthin, wo alle stehen.
                if (row === null) {
                    router.visit(habitsIndex());

                    return;
                }

                setHighlighted(habitId);
                row.scrollIntoView({
                    behavior: window.matchMedia(
                        '(prefers-reduced-motion: reduce)',
                    ).matches
                        ? 'auto'
                        : 'smooth',
                    block: 'center',
                });

                fadeHighlight.current = setTimeout(
                    () => setHighlighted(null),
                    2500,
                );
            },
        });
    }

    /**
     * Die Verabredung dieser Zeile auflösen.
     *
     * Zurückziehen und Absagen sind derselbe Weg — beides löscht den Eintrag
     * und hinterlässt keine Notiz. Er steht jetzt in der Zeile, weil die
     * Verabredung dort steht: Eine eigene Karte darunter sagte zweimal
     * dasselbe.
     */
    function withdraw(habit: Habit) {
        if (habit.appointmentId === null) {
            return;
        }

        router.delete(dissolve.url(habit.appointmentId), {
            preserveScroll: true,
        });
    }

    /**
     * „Nochmal ausmachen?" aus der erledigten Zeile.
     *
     * Die Zeile trägt die Gewohnheit schon; sie ist auch die, auf der
     * wiederholt wird, denn sie gehört einem selbst.
     */
    function repeatFromRow(habit: Habit) {
        if (
            habit.companion === null ||
            habit.companion.repeatHabitId === null
        ) {
            return;
        }

        setRepeating({
            habit: {
                ...habit,
                appointmentDays: habit.companion.repeatDays,
            },
            friendId: friendIdOf(habit.companion.name),
        });
    }

    /**
     * Dasselbe aus der Karte unter „Zusammen".
     *
     * Hier gehört die alte Verabredung einer fremden Gewohnheit — wiederholt
     * wird auf der eigenen, die der Server als `repeatHabitId` nennt.
     */
    function repeatFromCard(appointment: UpcomingAppointment) {
        if (appointment.repeatHabitId === null) {
            return;
        }

        const own = habits.find(
            (candidate) => candidate.id === appointment.repeatHabitId,
        );

        if (own === undefined) {
            return;
        }

        setRepeating({
            habit: { ...own, appointmentDays: appointment.repeatDays },
            friendId: friendIdOf(appointment.name),
        });
    }

    /**
     * Die Kennung zum Namen aus dem eigenen Kreis.
     *
     * Die Verabredung trägt Name und Initiale, das Sheet wählt über die
     * Kennung. Findet sich niemand, bleibt die Vorwahl leer — dann ist es
     * derselbe Weg wie beim ersten Mal, nur ohne Abkürzung.
     */
    function friendIdOf(name: string): number {
        return friends.find((friend) => friend.name === name)?.id ?? 0;
    }

    function toggle(habit: Habit) {
        const markingDone = habit.completedAt === null;
        const now = new Date().toLocaleTimeString('de-DE', {
            hour: '2-digit',
            minute: '2-digit',
        });

        // Optimistisch, damit das Abhaken sofort reagiert. Inertia rollt bei
        // einem Fehler von selbst zurück — der Server bleibt die Wahrheit.
        const visit = router.optimistic((props: DashboardProps) => {
            const next = props.habits.map((candidate) =>
                candidate.id === habit.id
                    ? { ...candidate, completedAt: markingDone ? now : null }
                    : candidate,
            );
            const completed = next.filter(
                (candidate) => candidate.completedAt !== null,
            ).length;

            return {
                habits: next,
                todayProgress: {
                    ...props.todayProgress,
                    completed,
                    percentage:
                        props.todayProgress.total === 0
                            ? 0
                            : Math.round(
                                  (completed / props.todayProgress.total) * 100,
                              ),
                },
            };
        });

        if (markingDone) {
            visit.post(store.url(habit.id), { preserveScroll: true });
        } else {
            visit.delete(destroy.url(habit.id), { preserveScroll: true });
        }
    }

    return (
        <>
            <Head title="Übersicht" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 sm:p-6">
                <header className="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
                    <div>
                        {/* §10 — auf Desktop trägt die Überschrift die Farbe.
                            Negative Laufweite, weil Buchstaben mit wachsendem
                            Grad optisch auseinanderfallen (Apple, „The Details
                            of UI Typography"): groß enger, klein weiter. */}
                        <h1 className="type-display text-primary">
                            {greeting}, {firstName}.
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {today}
                        </p>
                    </div>
                </header>

                {/* Mockup A2: die Anfrage steht über den Gewohnheiten, nicht
                    darunter — sie ist das Einzige auf dieser Seite, das eine
                    andere Person betrifft und auf eine Antwort wartet. */}
                <FriendRequestNotice requests={friendRequests} />
                <AppointmentRequestNotice requests={appointmentRequests} />

                {/* Die Absage steht bei den Dingen, die andere Menschen
                    betreffen — und nicht bei den Gewohnheiten, wo sie wie ein
                    eigenes Versäumnis aussähe. */}
                <AppointmentNotice
                    notices={appointmentNotices}
                    onCarryOn={carryOn}
                />

                {todayProgress.total > 0 && (
                    /* §12/§16 — auf dieser Seite trägt genau eine Fläche: die
                       des Tages. Sie liegt höher (warmer Schatten), ist innen
                       großzügiger und stellt die Zahl größer. Alles andere
                       ordnet sich flach darunter. Ohne diesen Unterschied
                       lesen drei gleich schwere weiße Karten als Liste, nicht
                       als Hierarchie. */
                    <Card className="gap-0 border-transparent py-7 shadow-[var(--shadow-lift)]">
                        <CardContent className="px-6">
                            <p className={'type-eyebrow text-muted-foreground'}>
                                Heute
                            </p>

                            {/* §3.3 — gemischte Gewichte in einer Zeile:
                                Zahl 700/primary, Wort 400/muted. Die Zahl
                                läuft mit dem Balken hoch statt zu springen;
                                `tabular-nums` hält die Breite dabei ruhig. */}
                            <p className="mt-2 flex items-baseline gap-2">
                                <span className="text-[clamp(2.75rem,10vw,3.5rem)] leading-none font-bold tracking-[-0.03em] text-primary tabular-nums">
                                    {countedPercentage} %
                                </span>
                                <span className="text-base text-muted-foreground">
                                    Erledigt
                                </span>
                            </p>

                            {/* §5.3 — voll gerundet, Füllung primary, Spur sand,
                                nie ein Prozentwert im Balken.

                                Die Breite läuft auf der kritisch gedämpften
                                Grundkurve: schnell weg vom alten Wert, ruhig
                                in den neuen hinein, ohne Überschwingen. Der
                                Balken misst, er feiert nicht. */}
                            <div
                                role="img"
                                aria-label={`${todayProgress.completed} von ${todayProgress.total} Gewohnheiten heute erledigt`}
                                className="mt-5 h-2.5 w-full overflow-hidden rounded-full bg-sand"
                            >
                                <div
                                    className="h-full rounded-full bg-primary transition-[width] duration-[var(--duration-fluid)] ease-[var(--ease-fluid)] motion-reduce:transition-none"
                                    style={{
                                        width: `${todayProgress.percentage}%`,
                                    }}
                                />
                            </div>

                            <p className="mt-2 flex flex-wrap justify-between gap-x-4 text-[11px] text-muted-foreground">
                                <span>
                                    {todayProgress.completed} von{' '}
                                    {todayProgress.total} Gewohnheiten
                                </span>
                                {/* „Mal" und nicht „Tage": Die Zahl ist eine
                                    Summe über alle Gewohnheiten und kann
                                    deshalb größer sein als 30. „60 Tage in den
                                    letzten 30 Tagen" wäre ein Widerspruch.
                                    Dieselbe Unterscheidung trifft
                                    {@see Habit::streakUnit()} schon für die
                                    Serie. Je Gewohnheit stimmt „Tage",
                                    deshalb steht es auf der
                                    Gewohnheiten-Seite. */}
                                {consistency !== null && (
                                    <span>
                                        Letzte 30 Tage:{' '}
                                        <span className="font-semibold text-foreground tabular-nums">
                                            {consistency.done} von{' '}
                                            {consistency.scheduled}
                                        </span>{' '}
                                        Mal erledigt
                                    </span>
                                )}
                            </p>
                        </CardContent>
                    </Card>
                )}

                {/* Unter dem Tagesfortschritt: erst was heute gilt, dann was
                    über den Tag hinausreicht. Bis zu drei Serien — sie sind
                    Milchglas und keine Farbfläche mehr, deshalb steht §5.4
                    ihrer Mehrzahl nicht mehr entgegen. */}
                {streaks.length > 0 && <StreakCards streaks={streaks} />}

                <section aria-labelledby="heutige-gewohnheiten">
                    {/* Die Zeile unter dem Titel erklärt die Uhrspalte links:
                        Die Liste ist keine Sammlung, sie ist der Verlauf des
                        Tages. Ohne diesen Satz sah man die Sortierung, ohne
                        sie zu erkennen. */}
                    <SectionHeading
                        id="heutige-gewohnheiten"
                        title="Heutige Gewohnheiten"
                        hint="Was heute ansteht, von früh nach spät."
                        action={
                            /* §5.6 Outline-Variante: transparent, 1px primary.
                               Entfällt im leeren Zustand — dort steht schon der
                               gefüllte Knopf, und zwei Wege zum selben Ziel
                               lassen den Nutzer wählen, wo es nichts zu wählen
                               gibt. Ab fünf Gewohnheiten entfällt er ebenfalls,
                               weil das Anlegen dann ohnehin abgewiesen würde. */
                            activeCount > 0 && activeCount < maxActive ? (
                                <Link
                                    href={create()}
                                    className={OUTLINE_BUTTON}
                                >
                                    <Plus
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Neu hinzufügen
                                </Link>
                            ) : undefined
                        }
                    />

                    {/* Flach: die Arbeitsfläche des Tages, nicht seine
                        Kopfzeile. Der Unterschied zur Tageskarte ist die
                        Höhe, nicht die Farbe. */}
                    <Card className="mt-3 gap-0 py-5 shadow-none">
                        <CardContent className="px-5">
                            {habits.length > 0 ? (
                                // §5.1 — keine Trennlinien, Struktur über Abstand.
                                <ul className="flex flex-col gap-6">
                                    {habits.map((habit) => (
                                        <HabitRow
                                            key={habit.id}
                                            habit={habit}
                                            selfInitial={selfInitial}
                                            onToggle={toggle}
                                            onStuck={setStuckOn}
                                            onAskCompany={
                                                appointmentsEnabled
                                                    ? setAskingFor
                                                    : null
                                            }
                                            onWithdraw={withdraw}
                                            onRepeat={repeatFromRow}
                                            highlighted={
                                                highlighted === habit.id
                                            }
                                        />
                                    ))}
                                </ul>
                            ) : activeCount === 0 ? (
                                <div className="flex flex-col items-start gap-4">
                                    <p className="text-sm leading-relaxed text-muted-foreground">
                                        Noch keine Gewohnheit.
                                    </p>
                                    <Link
                                        href={create()}
                                        className={`${PRIMARY_BUTTON} w-auto`}
                                    >
                                        <Plus
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                        Erste Gewohnheit anlegen
                                    </Link>
                                </div>
                            ) : (
                                /* Kein leerer Zustand, sondern ein freier Tag.
                                   §1.5: benannt wird, was gilt — nicht, was
                                   fehlt. Deshalb auch kein Knopf zum Anlegen. */
                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    Für heute ist nichts vorgesehen.{' '}
                                    {activeCount === 1
                                        ? 'Deine Gewohnheit ist für andere Wochentage eingeplant'
                                        : `Deine ${activeCount} Gewohnheiten sind für andere Wochentage eingeplant`}
                                    . Unter{' '}
                                    <Link
                                        href={habitsIndex()}
                                        className={QUIET_LINK}
                                    >
                                        Gewohnheiten
                                    </Link>{' '}
                                    siehst du, für welche.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </section>

                {/* Unter der Tagesliste, nicht darüber: Die Verabredung ist
                    eine Ergänzung des Tages, keine Meldung, die ihn anführt.
                    Anfragen, die eine Antwort brauchen, stehen weiterhin oben. */}
                <UpcomingAppointments
                    appointments={upcomingAppointments}
                    selfInitial={selfInitial}
                    onRepeat={repeatFromCard}
                />

                {/* Ganz unten, wo der Tag endet: Der Rahmen ist der ruhigste
                    Teil der Übersicht — er will nichts, er sagt nur, wann
                    Schluss ist und wann es weitergeht. */}
                <SleepCard sleep={sleepCard} />
            </div>

            <StartingHelpSheet
                habit={stuckOn}
                onOpenChange={(open) => !open && setStuckOn(null)}
            />

            {/* Ein Sheet für zwei Wege: neu fragen und nochmal fragen. Der
                Unterschied ist nur die Vorwahl — derselbe Weg, dieselben drei
                Taps, und die Entscheidung bleibt jedes Mal eine eigene. */}
            <AppointmentSheet
                habit={repeating?.habit ?? askingFor}
                friends={friends}
                days={
                    repeating?.habit.appointmentDays ??
                    askingFor?.appointmentDays ??
                    []
                }
                preselect={repeating?.friendId ?? null}
                onOpenChange={(open) => {
                    if (!open) {
                        setAskingFor(null);
                        setRepeating(null);
                    }
                }}
            />
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Übersicht',
            href: dashboard(),
        },
    ],
};
