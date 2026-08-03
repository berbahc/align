import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { HabitRow } from '@/components/habit-row';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { create } from '@/routes/habits';
import { destroy, store } from '@/routes/habits/completions';
import type { Habit } from '@/types';

interface TodayProgress {
    completed: number;
    /** Das Tagesziel — schlicht die Anzahl aktiver Gewohnheiten. */
    total: number;
    percentage: number;
}

interface DashboardProps {
    greeting: string;
    today: string;
    todayProgress: TodayProgress;
    /** Anteil erfüllter Tage der letzten 30 Tage; null, solange es keine Gewohnheiten gibt. */
    consistency: number | null;
    habits: Habit[];
}

const EYEBROW = 'text-[11px] font-semibold tracking-[0.11em] uppercase';

export default function Dashboard({
    greeting,
    today,
    todayProgress,
    consistency,
    habits,
}: DashboardProps) {
    const { auth } = usePage().props;
    const firstName = auth.user?.name.split(' ')[0] ?? '';

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

                <section aria-labelledby="heutige-gewohnheiten">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2
                            id="heutige-gewohnheiten"
                            className="text-xl leading-tight font-bold"
                        >
                            Heutige Gewohnheiten
                        </h2>

                        {/* §5.6 Outline-Variante: transparent, 1px primary. */}
                        <Link
                            href={create()}
                            className="inline-flex h-11 cursor-pointer items-center gap-1.5 rounded-full border border-primary px-4 text-sm font-semibold text-primary transition-colors duration-200 hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            <Plus className="size-4" aria-hidden="true" />
                            Neu hinzufügen
                        </Link>
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
                                            onToggle={toggle}
                                        />
                                    ))}
                                </ul>
                            ) : (
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
                            )}
                        </CardContent>
                    </Card>
                </section>
            </div>
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
