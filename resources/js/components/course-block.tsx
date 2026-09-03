import { GraduationCap } from 'lucide-react';
import type { PlacedBlock } from '@/lib/day-grid';
import { cn } from '@/lib/utils';
import type { CourseBlock as Course } from '@/types';

/** Ab dieser Höhe ist Platz für die Kachel und den Ort. */
const SPACIOUS = 64;

/** Derselbe Spalt zwischen zwei Spalten wie beim Gewohnheitsblock. */
const LANE_GAP = 4;

/**
 * Eine Veranstaltung im Stundenraster.
 *
 * Sie liegt auf derselben Achse wie die Gewohnheiten, sagt aber mit jedem
 * ihrer Merkmale, dass sie eine andere Art ist — und zwar durch Weglassen:
 *
 * - **Keine Hakenspalte.** Die 44 Pixel rechts entfallen ganz. Das allein
 *   liest sich als „hier ist nichts abzuhaken", bevor irgendetwas anderes
 *   verstanden ist.
 * - **Hohl statt gefüllt.** `bg-canvas` mit vollem Rahmen, gegen das gefüllte
 *   `bg-track` der Gewohnheit.
 * - **Durchgezogene linke Kante in `olive-mid`.** Durchgezogen, weil eine
 *   Vorlesung eine echte Uhrzeit hat — gestrichelt heißt in diesem Kalender
 *   „ungefähr hier". `olive-mid` statt `primary`, weil `primary` an einer
 *   linken Kante „das hast du dir vorgenommen" bedeutet, und das hat sich
 *   niemand vorgenommen.
 *
 * Und sie ist von Bauart nicht bedienbar: ein `div`, kein Knopf. Ein Kurs, der
 * sich anfassen ließe, würde das Versprechen des Rasters brechen, dass alles,
 * was reagiert, auch etwas tut.
 */
export function CourseBlock({ placed }: { placed: PlacedBlock<Course> }) {
    const { block, top, height, lane, lanes } = placed;
    const spacious = height >= SPACIOUS;

    return (
        <li
            className="absolute"
            style={{
                top,
                height,
                width: `calc((100% - ${(lanes - 1) * LANE_GAP}px) / ${lanes})`,
                left: `calc((((100% - ${(lanes - 1) * LANE_GAP}px) / ${lanes}) + ${LANE_GAP}px) * ${lane})`,
            }}
        >
            <div
                aria-label={`${block.kindLabel} ${block.title}, ${block.timeRange.replace('–', 'bis')}`}
                className={cn(
                    'flex h-full w-full cursor-default items-center gap-2.5 overflow-hidden rounded-xl border border-l-[3px] border-sand border-l-olive-mid bg-canvas px-2.5',
                    spacious ? 'py-2' : 'py-1',
                )}
            >
                {spacious && (
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-track text-olive-mid">
                        <GraduationCap
                            className="size-4"
                            strokeWidth={1.5}
                            aria-hidden="true"
                        />
                    </span>
                )}

                <span className="min-w-0 flex-1">
                    <span className="type-eyebrow block truncate text-muted-foreground">
                        {block.timeRange}
                        {block.moved && ' · heute verlegt'}
                    </span>
                    <span className="block truncate text-sm font-medium text-foreground">
                        {block.title}
                    </span>
                    {spacious && block.location && (
                        <span className="block truncate text-xs text-muted-foreground">
                            {block.kindLabel} · {block.location}
                        </span>
                    )}
                </span>
            </div>
        </li>
    );
}
