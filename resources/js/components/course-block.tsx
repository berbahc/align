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
 * Sie liegt auf derselben Achse wie die Gewohnheiten und soll auf einen Blick
 * als andere Art zu erkennen sein — deshalb eine eigene Fläche statt nur einer
 * anderen Kante:
 *
 * - **Sandfarben gefüllt.** `bg-sand` ist die oberste Stufe der Flächenleiter
 *   (siehe `app.css`): eine Stufe über der erledigten Gewohnheit, zwei über
 *   der offenen. In beiden Modi heller als der Block darunter — was vorn
 *   liegt, ist heller, im Hellen wie im Dunkeln. Derselbe warme Ton wie
 *   überall; eine zweite Farbfamilie hätte den Tag in zwei Kalender zerlegt.
 * - **Keine Hakenspalte.** Die 44 Pixel rechts entfallen ganz. Das allein
 *   liest sich als „hier ist nichts abzuhaken", bevor irgendetwas anderes
 *   verstanden ist.
 * - **Durchgezogene linke Kante in `olive-mid`.** Durchgezogen, weil eine
 *   Vorlesung eine echte Uhrzeit hat — gestrichelt heißt in diesem Kalender
 *   „ungefähr hier". `olive-mid` statt `primary`, weil `primary` an einer
 *   linken Kante „das hast du dir vorgenommen" bedeutet, und das hat sich
 *   niemand vorgenommen.
 *
 * Antippen öffnet den Kurs — Ändern, ein Ausfall, Löschen —, denn hier liegt
 * er, und hier fasst man ihn an. Kein Haken, kein Ziehen: Ein Kurs wird nicht
 * abgehakt und rückt nicht.
 */
export function CourseBlock({
    placed,
    onOpen,
}: {
    placed: PlacedBlock<Course>;
    /** Öffnet den Kurs — dort stehen Ändern, Ausfall und Löschen. */
    onOpen?: (block: Course) => void;
}) {
    const { block, top, height, lane, lanes } = placed;
    const spacious = height >= SPACIOUS;
    const Surface = onOpen ? 'button' : 'div';

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
            <Surface
                type={onOpen ? 'button' : undefined}
                onClick={onOpen ? () => onOpen(block) : undefined}
                aria-label={`${block.kindLabel} ${block.title}, ${block.timeRange.replace('–', 'bis')}`}
                className={cn(
                    'flex h-full w-full items-center gap-2.5 overflow-hidden rounded-xl border-l-[3px] border-l-olive-mid bg-sand px-2.5 text-left',
                    onOpen
                        ? 'cursor-pointer transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-sand/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.97]'
                        : 'cursor-default',
                    spacious ? 'py-2' : 'py-1',
                )}
            >
                {/* Ohne eigene Kachel: Eine Fläche unter dem Symbol müsste
                    heller sein als der Block — und was im Hellen heller ist,
                    ist im Dunkeln dunkler. Der gefüllte Block trägt die
                    Zugehörigkeit ohnehin; das Symbol steht direkt darauf. */}
                {spacious && (
                    <GraduationCap
                        className="size-5 shrink-0 text-olive-mid"
                        strokeWidth={1.5}
                        aria-hidden="true"
                    />
                )}

                <span className="min-w-0 flex-1">
                    <span className="type-eyebrow block truncate text-olive-mid">
                        {block.timeRange}
                        {block.moved && ' · heute verlegt'}
                    </span>
                    <span className="block truncate text-sm font-semibold text-foreground">
                        {block.title}
                    </span>
                    {spacious && block.location && (
                        <span className="block truncate text-xs text-olive-mid">
                            {block.kindLabel} · {block.location}
                        </span>
                    )}
                </span>
            </Surface>
        </li>
    );
}
