import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Moon, Sun } from 'lucide-react';
import { useState } from 'react';
import {
    AdjustmentSheet,
    alternativeLabel,
} from '@/components/adjustment-sheet';
import { CalendarBlock } from '@/components/calendar-block';
import { DayOrderSheet } from '@/components/day-order-sheet';
import { StartingHelpSheet } from '@/components/starting-help-sheet';
import { Card, CardContent } from '@/components/ui/card';
import { QUIET_LINK } from '@/lib/interaction';
import { calendar } from '@/routes';
import { destroy, store } from '@/routes/habits/completions';
import { show as sleepShow } from '@/routes/sleep';
import type { AnchorAlternative, CalendarBlock as Block } from '@/types';

interface CalendarProps {
    /** Der angezeigte Tag als „YYYY-MM-DD". */
    date: string;
    /** Die Datumszeile im Kopf, fertig formatiert. */
    heading: string;
    isToday: boolean;
    /** Nur im Nachtrag-Fenster und nicht in der Zukunft lässt sich abhaken. */
    canComplete: boolean;
    /** Null, sobald es davor keine Gewohnheiten mehr gab. */
    previousDate: string | null;
    nextDate: string;
    blocks: Block[];
    /** Der Rahmen des gezeigten Tages: wann er anfängt … */
    wakeTime: string;
    /** … und wann er endet. Beide Marker führen zum Schlafplan. */
    bedtime: string;
}

const NAV_BUTTON =
    'flex size-11 shrink-0 items-center justify-center rounded-full text-primary transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';

/**
 * Ein Rand des Tages auf der Achse — Aufstehen oben, Schlafenszeit unten.
 *
 * Kein Block, sondern eine Grenze: Der Marker hat keinen Haken und keine
 * Dauer, er sagt nur, wo der Tag anfängt und aufhört. Er führt zum
 * Schlafplan, weil er dort herkommt.
 */
function FrameMarker({
    icon: Icon,
    label,
    time,
}: {
    icon: typeof Sun;
    label: string;
    time: string;
}) {
    return (
        <Link
            href={sleepShow()}
            className="flex items-center gap-3 rounded-xl px-1 py-1 text-muted-foreground transition-colors duration-[var(--duration-press)] ease-out hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
        >
            <span className="flex size-8 shrink-0 items-center justify-center">
                <Icon className="size-4" strokeWidth={1.5} aria-hidden="true" />
            </span>
            <span className="text-xs font-semibold tabular-nums">{time}</span>
            <span className="text-xs">{label}</span>
            <span className="ml-1 h-px flex-1 bg-border" aria-hidden="true" />
        </Link>
    );
}

export default function Calendar({
    date,
    heading,
    isToday,
    canComplete,
    previousDate,
    nextDate,
    blocks,
    wakeTime,
    bedtime,
}: CalendarProps) {
    /** Welcher Block gerade im Anpassungs-Sheet steht; null heißt zu. */
    const [adjusting, setAdjusting] = useState<Block | null>(null);
    /** Welcher Block gerade in der Starthilfe steht; null heißt zu. */
    const [stuckOn, setStuckOn] = useState<Block | null>(null);
    /** Steht die Frage nach der Tagesordnung offen? */
    const [ordering, setOrdering] = useState(false);
    /** Die vorgemerkte Alternative — sie erzeugt den Ghost auf der Achse. */
    const [preview, setPreview] = useState<AnchorAlternative | null>(null);

    /**
     * Die Achse mit dem Vorschlag darin.
     *
     * Der Ghost liegt an der Stelle, an die der Block wandern würde; sein
     * bisheriger Platz bleibt als blasse Kontur stehen. Man sieht das Vorher
     * und das Nachher nebeneinander, bevor irgendetwas entschieden ist
     * (ki-assistent-design3.md §6).
     */
    const axis: { block: Block; ghost: boolean; faded: boolean }[] = blocks.map(
        (block) => ({ block, ghost: false, faded: false }),
    );

    if (adjusting && preview) {
        const ghost: Block = {
            ...adjusting,
            anchor: alternativeLabel(preview),
            anchorHour: preview.anchorHour,
            // Die Spanne des bisherigen Platzes gilt am neuen nicht mehr. Sie
            // hier nachzurechnen hieße, die Server-Logik im Browser zu
            // wiederholen — der Ghost zeigt deshalb den Anker des Vorschlags,
            // und die Spanne kommt zurück, sobald er übernommen ist.
            timeRange: null,
        };

        const index = axis.findIndex(
            (entry) => entry.block.id === adjusting.id,
        );

        if (index >= 0) {
            axis[index] = { ...axis[index], faded: true };
        }

        axis.push({ block: ghost, ghost: true, faded: false });
        axis.sort((a, b) => a.block.anchorHour - b.block.anchorHour);
    }

    /**
     * Abhaken für den angezeigten Tag, nicht für heute.
     *
     * `completed_on` reist bei beiden Richtungen mit — der Server prüft damit
     * das Nachtrag-Fenster und weist Tage ab, an denen die Gewohnheit gar nicht
     * vorgesehen war.
     */
    function toggle(block: Block) {
        if (block.completed) {
            router.delete(destroy.url(block.id), {
                data: { completed_on: date },
                preserveScroll: true,
            });

            return;
        }

        router.post(
            store.url(block.id),
            { completed_on: date },
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Kalender" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
                <header className="flex items-center gap-2">
                    {/* Pfeile sind Links, kein Client-State: die URL trägt den
                        Tag, übersteht ein Neuladen und lässt sich teilen. */}
                    {previousDate ? (
                        <Link
                            href={calendar({ query: { date: previousDate } })}
                            aria-label="Ein Tag zurück"
                            className={NAV_BUTTON}
                        >
                            <ChevronLeft
                                className="size-5"
                                aria-hidden="true"
                            />
                        </Link>
                    ) : (
                        <span
                            className={`${NAV_BUTTON} opacity-30`}
                            aria-hidden="true"
                        >
                            <ChevronLeft className="size-5" />
                        </span>
                    )}

                    <h1 className="flex-1 text-center text-[clamp(1.125rem,4vw,1.5rem)] leading-tight font-bold text-primary">
                        {heading}
                    </h1>

                    <Link
                        href={calendar({ query: { date: nextDate } })}
                        aria-label="Ein Tag vor"
                        className={NAV_BUTTON}
                    >
                        <ChevronRight className="size-5" aria-hidden="true" />
                    </Link>
                </header>

                {!isToday && (
                    <div className="flex justify-center">
                        <Link
                            href={calendar()}
                            className="cursor-pointer text-sm font-semibold text-primary underline underline-offset-4 transition-colors duration-[var(--duration-press)] ease-out hover:text-primary/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            Zurück zu heute
                        </Link>
                    </div>
                )}

                <Card className="gap-0 py-5">
                    <CardContent className="px-5">
                        {/* Der Rahmen umschließt die Achse: Der Tag beginnt
                            beim Aufstehen und endet bei der Schlafenszeit —
                            beides kommt aus dem Schlafplan und führt dorthin. */}
                        <FrameMarker
                            icon={Sun}
                            label="Aufstehen"
                            time={wakeTime}
                        />

                        {blocks.length > 0 ? (
                            <ul className="my-3 flex flex-col gap-3">
                                {axis.map(({ block, ghost, faded }, index) => (
                                    <CalendarBlock
                                        key={
                                            ghost
                                                ? `${block.id}-ghost`
                                                : block.id
                                        }
                                        block={block}
                                        canComplete={canComplete}
                                        onToggle={toggle}
                                        onAdjust={setAdjusting}
                                        onStuck={setStuckOn}
                                        ghost={ghost}
                                        faded={faded}
                                        // Der Steg erscheint nur, wenn der
                                        // Vorgänger auch wirklich direkt
                                        // darüber liegt — sonst zeigte er auf
                                        // den falschen Block.
                                        chained={
                                            block.chainedToId !== null &&
                                            axis[index - 1]?.block.id ===
                                                block.chainedToId
                                        }
                                    />
                                ))}
                            </ul>
                        ) : (
                            /* §1.5 — benannt wird, was gilt, nicht was fehlt. */
                            <p className="my-3 px-1 text-sm leading-relaxed text-muted-foreground">
                                Für diesen Tag war nichts vorgesehen.
                            </p>
                        )}

                        <FrameMarker
                            icon={Moon}
                            label="Schlafenszeit"
                            time={bedtime}
                        />

                        {/* Der Weg zur Tagesordnung steht unter der Achse, weil
                            er den ganzen Tag betrifft und nicht eine Zeile. Ab
                            zwei Gewohnheiten: bei einer gibt es keine
                            Reihenfolge. */}
                        {blocks.length > 1 && canComplete && (
                            <button
                                type="button"
                                onClick={() => setOrdering(true)}
                                className={`${QUIET_LINK} mt-4 block text-xs`}
                            >
                                ✦ Tag neu ordnen
                            </button>
                        )}

                        {/* Kein Fehler, sondern eine Grenze: Was der
                            Wochenstreifen nicht mehr zeigt, lässt sich auch
                            nicht mehr nachtragen. */}
                        {!canComplete && (
                            <p className="mt-5 border-t border-border pt-4 text-sm leading-relaxed text-muted-foreground">
                                Nachtragen geht für die letzten sieben Tage.
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>

            <AdjustmentSheet
                block={adjusting}
                onOpenChange={(open) => !open && setAdjusting(null)}
                onPreview={setPreview}
            />

            {/* Dieselbe Starthilfe wie auf der Übersicht: Wo die Gewohnheit
                steht, soll auch der Weg stehen, sie kleiner zu machen. */}
            <StartingHelpSheet
                habit={stuckOn}
                onOpenChange={(open) => !open && setStuckOn(null)}
            />

            <DayOrderSheet
                open={ordering}
                date={date}
                blocks={blocks}
                onOpenChange={setOrdering}
            />
        </>
    );
}

Calendar.layout = {
    breadcrumbs: [{ title: 'Kalender', href: calendar() }],
};
