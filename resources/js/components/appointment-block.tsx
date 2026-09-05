import { Check } from 'lucide-react';
import { PersonCircle } from '@/components/person-circle';
import { ASSUMED_MINUTES, timeLabel } from '@/lib/day-grid';
import type { PlacedBlock } from '@/lib/day-grid';
import { cn } from '@/lib/utils';
import type { AppointmentBlock as Appointment } from '@/types';

/** Ab dieser Höhe ist Platz für die Zeile mit dem Namen. */
const SPACIOUS = 64;

/** Derselbe Spalt zwischen zwei Spalten wie beim Gewohnheitsblock. */
const LANE_GAP = 4;

/**
 * Eine zugesagte Verabredung im Stundenraster.
 *
 * Bis hierher stand sie in keinem Kalender: Der Tag lädt die eigenen
 * Gewohnheiten, und wer zusagt, führt keine. Der gemeinsame Termin war damit
 * für die gefragte Seite unsichtbar — zugesagt und dann verschwunden.
 *
 * Die dritte Art im Raster, und sie muss sich von beiden anderen unterscheiden
 * lassen:
 *
 * - **Doppel-Zeichen statt Symbolkachel.** Zwei Kreise an der Stelle, an der
 *   eine Gewohnheit ihr Verhaltenssymbol trägt — dasselbe Zeichen wie in der
 *   Zeile auf der Übersicht (§3.2). Form *und* Anzahl tragen die Information,
 *   nicht die Farbe. Es steht auch im flachen Block: Es ist das Einzige, was
 *   diesen Block auf einen Blick als gemeinsamen ausweist.
 * - **Keine Hakenspalte.** Wie beim Kurs: Die Gewohnheit gehört jemand
 *   anderem, hier ist nichts abzuhaken. Das sieht man, bevor man es liest.
 * - **`bg-sand` wie der Kurs.** Dieselbe Stufe der Flächenleiter, weil beide
 *   dasselbe sind: eine Tatsache im Tag, die man selbst nicht verrückt.
 * - **Linke Kante in `primary`, nicht `olive-mid`.** Genau der Unterschied zum
 *   Kurs: `primary` heißt an einer Kante „das hat sich jemand vorgenommen",
 *   und das trifft hier zu — nur eben nicht auf einen allein. Gestrichelt,
 *   solange der Anker keine echte Uhr hat: Die Verabredung erfindet keine
 *   eigene Zeit, sie teilt die der anderen Person (community_feature3.md §4).
 *
 * Bewusst ohne Fortschritt der anderen Person (§6): Der Block sagt, dass etwas
 * gemeinsam ansteht, nicht wie es läuft.
 *
 * **Abhaken lässt er sich trotzdem.** Wer zusagt, macht mit und hat danach
 * dasselbe getan — der Haken hängt an der Verabredung und meldet nur den
 * eigenen Teil. Er sieht aus wie der an einer Gewohnheit, weil es dieselbe
 * Handlung ist; die fragende Seite erfährt davon nichts.
 */
export function AppointmentBlock({
    placed,
    selfInitial,
    onToggle,
}: {
    placed: PlacedBlock<Appointment>;
    /** Die eigene Initiale — die linke Hälfte des Doppel-Zeichens aus §3.2. */
    selfInitial: string;
    /** Den eigenen Haken setzen oder zurücknehmen. */
    onToggle: (block: Appointment) => void;
}) {
    const { block, top, height, lane, lanes } = placed;
    const spacious = height >= SPACIOUS;

    const span =
        block.timeRange ??
        (block.exact
            ? `${timeLabel(block.startMinute)} – ca. ${timeLabel(block.startMinute + ASSUMED_MINUTES)}`
            : block.anchor);

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
                role="img"
                aria-label={`Zusammen mit ${block.name}: ${block.title}, ${span}`}
                className={cn(
                    'flex h-full w-full items-center gap-2.5 overflow-hidden rounded-xl bg-sand pl-2.5 text-left',
                    // Die Kante trägt dieselbe Auskunft wie beim eigenen Block:
                    // durchgezogen heißt Uhrzeit, gestrichelt heißt ungefähr hier.
                    block.exact
                        ? 'border-l-[3px] border-primary'
                        : 'border-l-[3px] border-dashed border-primary',
                    spacious ? 'py-2' : 'py-1',
                )}
            >
                {/* §3.2 — zwei Kreise statt einer Kachel. Kleiner als in der
                    Zeile auf der Übersicht, weil ein flacher Block nur 44
                    Pixel hoch ist; das Zeichen bleibt dasselbe. */}
                <span aria-hidden="true" className="flex shrink-0 -space-x-1.5">
                    <PersonCircle
                        initial={selfInitial}
                        className="size-7 text-xs"
                    />
                    <PersonCircle
                        initial={block.initial}
                        className="size-7 text-xs ring-2 ring-sand"
                    />
                </span>

                <span className="min-w-0 flex-1">
                    <span className="type-eyebrow block truncate text-primary">
                        {span}
                    </span>
                    <span className="block truncate text-sm font-semibold text-foreground">
                        {block.title}
                    </span>
                    {spacious && (
                        <span className="block truncate text-xs text-olive-mid">
                            mit {block.name}
                        </span>
                    )}
                </span>

                {/* Dieselbe 44-Pixel-Spalte wie an der eigenen Gewohnheit —
                    was gleich aussieht, tut hier auch dasselbe. Außerhalb des
                    Nachtragefensters bleibt der Zustand sichtbar, aber
                    unantastbar: Ein toter Knopf wäre eine Einladung, die
                    zurückgewiesen wird. */}
                {block.canComplete ? (
                    <button
                        type="button"
                        onClick={() => onToggle(block)}
                        aria-pressed={block.completed}
                        aria-label={
                            block.completed
                                ? `${block.title} mit ${block.name} als noch offen markieren`
                                : `${block.title} mit ${block.name} als erledigt markieren`
                        }
                        className="flex w-11 shrink-0 cursor-pointer items-center justify-center self-stretch transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
                    >
                        <Tick completed={block.completed} />
                    </button>
                ) : (
                    <span
                        aria-hidden="true"
                        className="flex w-11 shrink-0 items-center justify-center self-stretch"
                    >
                        <Tick completed={block.completed} />
                    </span>
                )}
            </div>
        </li>
    );
}

/**
 * Der Haken — gefüllt heißt erledigt, gestrichelt heißt offen.
 *
 * Dieselbe Form wie an der eigenen Gewohnheit ({@see CalendarBlock}): Es ist
 * dieselbe Handlung, und zwei Zeichen dafür wären zwei Bedeutungen.
 */
function Tick({ completed }: { completed: boolean }) {
    return (
        <span
            className={cn(
                'flex size-6 items-center justify-center rounded-full transition-[background-color,border-color] duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                completed
                    ? 'bg-primary'
                    : 'border-2 border-dashed border-olive-mid/50',
            )}
        >
            {completed && (
                <Check
                    className="size-3.5 text-primary-foreground duration-[var(--duration-pop)] ease-[var(--ease-pop)] motion-safe:animate-in motion-safe:zoom-in-50"
                    strokeWidth={2.5}
                    aria-hidden="true"
                />
            )}
        </span>
    );
}
