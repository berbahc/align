import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { AppointmentNotice } from '@/components/appointment-notice';
import { AppointmentRequestNotice } from '@/components/appointment-request-notice';
import { AppointmentSheet } from '@/components/appointment-sheet';
import { FriendRequestNotice } from '@/components/friend-request-notice';
import { HabitAdoptionSheet } from '@/components/habit-adoption-sheet';
import { HabitRow } from '@/components/habit-row';
import type { ScheduleTypeOption } from '@/components/schedule-picker';
import { StartingHelpSheet } from '@/components/starting-help-sheet';
import { StreakCard } from '@/components/streak-card';
import type { Streak } from '@/components/streak-card';
import { Card, CardContent } from '@/components/ui/card';
import { UpcomingAppointments } from '@/components/upcoming-appointments';
import { dashboard } from '@/routes';
import { destroy as dismissNotice } from '@/routes/appointment-notices';
import { create, index as habitsIndex } from '@/routes/habits';
import { destroy, store } from '@/routes/habits/completions';
import type {
    AppointmentNotice as Notice,
    AppointmentRequest,
    UpcomingAppointment,
    FriendshipPerson,
    Habit,
    HabitBlueprint,
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
    /** Für das Übernehmen einer fremden Gewohnheit — dieselbe Wahl wie beim Anlegen. */
    scheduleTypes: ScheduleTypeOption[];
    triggerSuggestions: string[];
    todayProgress: TodayProgress;
    /** Anteil erfüllter Tage der letzten 30 Tage; null, solange es keine Gewohnheiten gibt. */
    consistency: number | null;
    /** Die stärkste laufende Serie; null unterhalb von Habit::StreakMinimum. */
    streak: Streak | null;
    /** Nur die heute vorgesehenen Gewohnheiten. */
    habits: Habit[];
    /** Alle aktiven — auch die, die heute nicht anstehen. */
    activeCount: number;
    /** Obergrenze gleichzeitig aktiver Gewohnheiten, aus Habit::MaxActivePerUser. */
    maxActive: number;
}

const EYEBROW = 'text-[11px] font-semibold tracking-[0.11em] uppercase';

export default function Dashboard({
    greeting,
    today,
    friendRequests,
    appointmentRequests,
    appointmentNotices,
    upcomingAppointments,
    friends,
    appointmentsEnabled,
    scheduleTypes,
    triggerSuggestions,
    todayProgress,
    consistency,
    streak,
    habits,
    activeCount,
    maxActive,
}: DashboardProps) {
    const { auth } = usePage().props;
    const firstName = auth.user?.name.split(' ')[0] ?? '';
    const selfInitial = (auth.user?.name.charAt(0) ?? '').toUpperCase();

    // Welche Gewohnheit gerade im Starthilfe-Sheet steht; null heißt zu.
    const [stuckOn, setStuckOn] = useState<Habit | null>(null);

    // Welche Gewohnheit gerade im Verabredungs-Sheet steht; null heißt zu.
    const [askingFor, setAskingFor] = useState<Habit | null>(null);

    // Welche fremde Gewohnheit gerade zum Übernehmen offen steht, und aus
    // welcher Absage heraus — die Notiz verschwindet dann mit.
    const [adopting, setAdopting] = useState<{
        blueprint: HabitBlueprint;
        noticeId?: number;
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
                        {/* §10 — auf Desktop trägt die Überschrift die Farbe. */}
                        <h1 className="text-[clamp(1.75rem,4vw,2rem)] leading-tight font-bold text-primary">
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
                <AppointmentRequestNotice
                    requests={appointmentRequests}
                    onAdopt={(request) =>
                        setAdopting({ blueprint: request.blueprint })
                    }
                />

                {/* Die Absage steht bei den Dingen, die andere Menschen
                    betreffen — und nicht bei den Gewohnheiten, wo sie wie ein
                    eigenes Versäumnis aussähe. */}
                <AppointmentNotice
                    notices={appointmentNotices}
                    onAdopt={(notice) =>
                        notice.blueprint !== null &&
                        setAdopting({
                            blueprint: notice.blueprint,
                            noticeId: notice.id,
                        })
                    }
                    onCarryOn={carryOn}
                />

                {todayProgress.total > 0 && (
                    <Card className="gap-0 py-5">
                        <CardContent className="px-5">
                            <p className={`${EYEBROW} text-muted-foreground`}>
                                Heute
                            </p>

                            {/* §3.3 — gemischte Gewichte in einer Zeile:
                                Zahl 700/primary, Wort 400/muted. */}
                            <p className="mt-2 flex items-baseline gap-2">
                                <span className="text-[clamp(2rem,6vw,2.25rem)] leading-none font-bold text-primary tabular-nums">
                                    {todayProgress.percentage} %
                                </span>
                                <span className="text-base text-muted-foreground">
                                    Erledigt
                                </span>
                            </p>

                            {/* §5.3 — voll gerundet, Füllung primary, Spur sand,
                                nie ein Prozentwert im Balken. */}
                            <div
                                role="img"
                                aria-label={`${todayProgress.completed} von ${todayProgress.total} Gewohnheiten heute erledigt`}
                                className="mt-4 h-2 w-full overflow-hidden rounded-full bg-sand"
                            >
                                <div
                                    className="h-full rounded-full bg-primary transition-[width] duration-200 ease-out"
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
                                {consistency !== null && (
                                    <span>
                                        Konsistenz der letzten 30 Tage:{' '}
                                        {consistency} %
                                    </span>
                                )}
                            </p>
                        </CardContent>
                    </Card>
                )}

                {/* Unter dem Tagesfortschritt: erst was heute gilt, dann was
                    über den Tag hinausreicht. §5.4 lässt genau eine farbige
                    Fläche zu — deshalb steht hier nur die stärkste Serie. */}
                {streak !== null && <StreakCard streak={streak} />}

                <section aria-labelledby="heutige-gewohnheiten">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2
                            id="heutige-gewohnheiten"
                            className="text-xl leading-tight font-bold"
                        >
                            Heutige Gewohnheiten
                        </h2>

                        {/* §5.6 Outline-Variante: transparent, 1px primary.
                            Entfällt im leeren Zustand — dort steht schon der
                            gefüllte Knopf, und zwei Wege zum selben Ziel
                            lassen den Nutzer wählen, wo es nichts zu wählen
                            gibt. Ab fünf Gewohnheiten entfällt er ebenfalls,
                            weil das Anlegen dann ohnehin abgewiesen würde. */}
                        {activeCount > 0 && activeCount < maxActive && (
                            <Link
                                href={create()}
                                className="inline-flex h-11 cursor-pointer items-center gap-1.5 rounded-full border border-primary px-4 text-sm font-semibold text-primary transition-colors duration-200 hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            >
                                <Plus className="size-4" aria-hidden="true" />
                                Neu hinzufügen
                            </Link>
                        )}
                    </div>

                    <Card className="mt-3 gap-0 py-5">
                        <CardContent className="px-5">
                            {habits.length > 0 ? (
                                // §5.1 — keine Trennlinien, Struktur über Abstand.
                                <ul className="flex flex-col gap-5">
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
                                            highlighted={
                                                highlighted === habit.id
                                            }
                                        />
                                    ))}
                                </ul>
                            ) : activeCount === 0 ? (
                                <div className="flex flex-col items-start gap-4">
                                    <p className="text-sm leading-relaxed text-muted-foreground">
                                        Du verfolgst noch keine Gewohnheiten.
                                        Fang mit einer an — bis zu fünf
                                        gleichzeitig halten den Fokus schmal.
                                    </p>
                                    <Link
                                        href={create()}
                                        className="inline-flex h-12 cursor-pointer items-center gap-2 rounded-xl bg-primary px-6 text-[15px] font-semibold text-primary-foreground transition-colors duration-200 hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
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
                                        : `Deine ${activeCount} Gewohnheiten sind für andere Wochentage eingeplant`}{' '}
                                    — unter{' '}
                                    <Link
                                        href={habitsIndex()}
                                        className="cursor-pointer font-semibold text-primary underline underline-offset-4 transition-colors duration-200 hover:text-primary/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
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
                />
            </div>

            <StartingHelpSheet
                habit={stuckOn}
                onOpenChange={(open) => !open && setStuckOn(null)}
            />

            <HabitAdoptionSheet
                blueprint={adopting?.blueprint ?? null}
                noticeId={adopting?.noticeId}
                scheduleTypes={scheduleTypes}
                triggerSuggestions={triggerSuggestions}
                onOpenChange={(open) => !open && setAdopting(null)}
            />

            <AppointmentSheet
                habit={askingFor}
                friends={friends}
                days={askingFor?.appointmentDays ?? []}
                onOpenChange={(open) => !open && setAskingFor(null)}
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
