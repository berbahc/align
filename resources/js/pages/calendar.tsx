import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import {
    AdjustmentSheet,
    alternativeLabel,
} from '@/components/adjustment-sheet';
import { CalendarBlock } from '@/components/calendar-block';
import { Card, CardContent } from '@/components/ui/card';
import { calendar } from '@/routes';
import { destroy, store } from '@/routes/habits/completions';
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
}

const NAV_BUTTON =
    'flex size-11 shrink-0 items-center justify-center rounded-full text-primary transition-colors duration-200 hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';

export default function Calendar({
    date,
    heading,
    isToday,
    canComplete,
    previousDate,
    nextDate,
    blocks,
}: CalendarProps) {
    /** Welcher Block gerade im Anpassungs-Sheet steht; null heißt zu. */
    const [adjusting, setAdjusting] = useState<Block | null>(null);
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
                            className="cursor-pointer text-sm font-semibold text-primary underline underline-offset-4 transition-colors duration-200 hover:text-primary/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            Zurück zu heute
                        </Link>
                    </div>
                )}

                <Card className="gap-0 py-5">
                    <CardContent className="px-5">
                        {blocks.length > 0 ? (
                            <>
                                <ul className="flex flex-col gap-3">
                                    {axis.map(({ block, ghost, faded }) => (
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
                                            ghost={ghost}
                                            faded={faded}
                                        />
                                    ))}
                                </ul>

                                {/* Kein Fehler, sondern eine Grenze: Was der
                                    Wochenstreifen nicht mehr zeigt, lässt sich
                                    auch nicht mehr nachtragen. */}
                                {!canComplete && (
                                    <p className="mt-5 border-t border-border pt-4 text-sm leading-relaxed text-muted-foreground">
                                        Dieser Tag lässt sich nur noch ansehen.
                                        Nachtragen geht für die letzten sieben
                                        Tage.
                                    </p>
                                )}
                            </>
                        ) : (
                            /* §1.5 — benannt wird, was gilt, nicht was fehlt. */
                            <p className="text-sm leading-relaxed text-muted-foreground">
                                Für diesen Tag war nichts vorgesehen.
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
        </>
    );
}

Calendar.layout = {
    breadcrumbs: [{ title: 'Kalender', href: calendar() }],
};
