import { Head, usePage } from '@inertiajs/react';
import { HabitRow } from '@/components/habit-row';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';
import type { Habit } from '@/types';

interface DashboardProps {
    greeting: string;
    today: string;
    /** Anteil erfüllter Tage der letzten 30 Tage; null, solange es keine Gewohnheiten gibt. */
    consistency: number | null;
    habits: Habit[];
}

const EYEBROW = 'text-[11px] font-semibold tracking-[0.11em] uppercase';

export default function Dashboard({
    greeting,
    today,
    consistency,
    habits,
}: DashboardProps) {
    const { auth } = usePage().props;
    const firstName = auth.user?.name.split(' ')[0] ?? '';

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

                {consistency !== null && (
                    <Card className="gap-0 py-5">
                        <CardContent className="px-5">
                            <p className={`${EYEBROW} text-muted-foreground`}>
                                Konsistenz · letzte 30 Tage
                            </p>

                            {/* §3.3 — gemischte Gewichte in einer Zeile:
                                Zahl 700/primary, Wort 400/muted. */}
                            <p className="mt-2 flex items-baseline gap-2">
                                <span className="text-[clamp(2rem,6vw,2.25rem)] leading-none font-bold text-primary tabular-nums">
                                    {consistency} %
                                </span>
                                <span className="text-base text-muted-foreground">
                                    Erreicht
                                </span>
                            </p>

                            {/* §5.3 — voll gerundet, Füllung primary, Spur sand,
                                nie ein Prozentwert im Balken. */}
                            <div
                                role="img"
                                aria-label={`Konsistenz ${consistency} Prozent in den letzten 30 Tagen`}
                                className="mt-4 h-2 w-full overflow-hidden rounded-full bg-sand"
                            >
                                <div
                                    className="h-full rounded-full bg-primary transition-[width] duration-200 ease-out"
                                    style={{ width: `${consistency}%` }}
                                />
                            </div>
                            <p className="mt-2 text-[11px] text-muted-foreground">
                                Anteil der Tage, an denen du deine Gewohnheiten
                                erfüllt hast.
                            </p>
                        </CardContent>
                    </Card>
                )}

                <section aria-labelledby="heutige-gewohnheiten">
                    <h2
                        id="heutige-gewohnheiten"
                        className="text-xl leading-tight font-bold"
                    >
                        Heutige Gewohnheiten
                    </h2>

                    <Card className="mt-3 gap-0 py-5">
                        <CardContent className="px-5">
                            {habits.length > 0 ? (
                                // §5.1 — keine Trennlinien, Struktur über Abstand.
                                <ul className="flex flex-col gap-5">
                                    {habits.map((habit) => (
                                        <HabitRow
                                            key={habit.id}
                                            habit={habit}
                                        />
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Du verfolgst noch keine Gewohnheiten. Bis zu
                                    fünf gleichzeitig halten den Fokus schmal.
                                </p>
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
