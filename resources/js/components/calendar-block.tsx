import { Check } from 'lucide-react';
import { BEHAVIOR_ICONS } from '@/lib/behavior-icons';
import { cn } from '@/lib/utils';
import type { CalendarBlock as Block } from '@/types';

/**
 * Ein Block auf der Tagesachse.
 *
 * Der Anker steht klein darüber, die Gewohnheit groß darunter — die Situation
 * ist der Grund, aus dem die Gewohnheit an dieser Stelle liegt, und liest sich
 * deshalb als Überschrift, nicht als Beiwerk (ki-assistent-design.md, Screen 2).
 *
 * Kein Stundenraster: Die Reihenfolge trägt die Struktur. Eine Achse mit
 * Uhrzeiten wäre ein Terminkalender, und der erzeugt genau das Pflichtgefühl,
 * das die App vermeiden will.
 */
export function CalendarBlock({
    block,
    canComplete,
    onToggle,
    onAdjust,
    ghost = false,
    faded = false,
    chained = false,
}: {
    block: Block;
    canComplete: boolean;
    onToggle: (block: Block) => void;
    onAdjust?: (block: Block) => void;
    /** Der Vorschlag der KI an seiner neuen Stelle — gestrichelt, noch nicht wahr. */
    ghost?: boolean;
    /** Der bisherige Platz, während der Ghost woanders liegt. */
    faded?: boolean;
    /** Hängt dieser Block an dem direkt darüber? Dann trägt er einen Steg. */
    chained?: boolean;
}) {
    const Icon = BEHAVIOR_ICONS[block.behaviorType];

    return (
        <li
            className={cn(
                'flex flex-col gap-2 rounded-[14px] p-4 transition-opacity duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                ghost && 'border-2 border-dashed border-primary bg-sand/40',
                faded && 'opacity-40',
                !ghost && !faded && 'bg-card',
                // Der Steg schließt die Lücke zum Block darüber: Was
                // aneinanderhängt, soll auch zusammenhängend aussehen
                // (ki-assistent-design3.md §Ketten).
                chained &&
                    'relative before:absolute before:-top-3 before:left-9 before:h-3 before:w-0.5 before:bg-sand',
            )}
        >
            {/* Wo eine Dauer auf eine feste Uhrzeit trifft, tritt die belegte
                Spanne an die Stelle des Ankers: Sie sagt dasselbe und dazu, wann
                der Platz wieder frei ist. Das bleibt eine Zeile Text — eine nach
                Dauer skalierte Blockhöhe wäre das Stundenraster, das oben aus
                gutem Grund ausgeschlossen ist. */}
            <p className="type-eyebrow text-muted-foreground">
                {block.timeRange ?? block.anchor}
            </p>

            <div className="flex items-center gap-3">
                <span
                    className={cn(
                        'flex size-11 shrink-0 items-center justify-center transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                        block.completed
                            ? 'rounded-full bg-primary text-primary-foreground'
                            : 'rounded-xl bg-sand text-primary',
                    )}
                >
                    <Icon
                        className="size-5"
                        strokeWidth={1.5}
                        aria-hidden="true"
                    />
                </span>

                <span className="min-w-0 flex-1">
                    {/* Der Titel darf zwei Zeilen brauchen, statt sich mit der
                        Dauer eine zu teilen und dabei abgeschnitten zu werden:
                        „Vorlesung nachbereiten …" nennt die Gewohnheit nur
                        halb, und die halbe Auskunft ist die schlechtere
                        (§16 Craft). Die Dauer steht deshalb darunter. */}
                    <span
                        className={cn(
                            'block text-[15px] leading-snug font-semibold text-balance',
                            block.completed
                                ? 'text-olive-mid'
                                : 'text-foreground',
                        )}
                    >
                        {block.title}
                    </span>

                    {/* Steht die Spanne schon oben, wäre „20 Min" hier ihre
                        Wiederholung — die Dauer erscheint nur, wo sie sonst
                        nirgends abzulesen ist. */}
                    {block.measureLabel !== null &&
                        block.timeRange === null && (
                            <span className="mt-0.5 block text-xs text-muted-foreground">
                                {block.measureLabel}
                            </span>
                        )}
                    {block.smallestStep && !block.completed && (
                        <span className="mt-0.5 block truncate text-xs text-muted-foreground">
                            → {block.smallestStep}
                        </span>
                    )}
                </span>

                {/* Ohne Nachtrag-Recht bleibt der Zustand sichtbar, aber
                    unantastbar: ein toter Knopf wäre eine Einladung, die
                    zurückgewiesen wird. */}
                {canComplete && !ghost ? (
                    <button
                        type="button"
                        onClick={() => onToggle(block)}
                        aria-pressed={block.completed}
                        aria-label={
                            block.completed
                                ? `${block.title} als noch offen markieren`
                                : `${block.title} als erledigt markieren`
                        }
                        className="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        <span
                            className={cn(
                                'flex size-7 items-center justify-center rounded-full transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                block.completed
                                    ? 'bg-primary'
                                    : 'border-2 border-dashed border-sand',
                            )}
                        >
                            {block.completed && (
                                <Check
                                    className="size-4 text-primary-foreground"
                                    strokeWidth={2.5}
                                    aria-hidden="true"
                                />
                            )}
                        </span>
                    </button>
                ) : (
                    <span
                        aria-hidden="true"
                        className={cn(
                            'flex size-7 shrink-0 items-center justify-center rounded-full',
                            block.completed
                                ? 'bg-primary'
                                : 'border-2 border-dashed border-sand',
                        )}
                    >
                        {block.completed && (
                            <Check
                                className="size-4 text-primary-foreground"
                                strokeWidth={2.5}
                            />
                        )}
                    </span>
                )}
            </div>

            {/* Der Weg zur KI steht nur an lebenden Gewohnheiten: eine beendete
                verschiebt man nicht mehr, und einen Ghost erst recht nicht. */}
            {onAdjust && !ghost && !block.graduated && (
                <button
                    type="button"
                    onClick={() => onAdjust(block)}
                    className="cursor-pointer self-start text-xs font-semibold text-primary underline underline-offset-4 transition-colors duration-[var(--duration-press)] ease-out hover:text-primary/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                >
                    ✦ Passt der Zeitpunkt?
                </button>
            )}
        </li>
    );
}
