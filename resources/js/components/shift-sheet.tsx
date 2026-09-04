import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { BlockConflict } from '@/lib/day-grid';
import { timeLabel } from '@/lib/day-grid';
import { OUTLINE_BUTTON, PRIMARY_BUTTON } from '@/lib/interaction';
import type { CalendarBlock } from '@/types';

const ACTION_BUTTON =
    'inline-flex h-12 flex-1 items-center justify-center rounded-xl px-4 text-[15px] font-semibold disabled:pointer-events-none disabled:opacity-50';

/** Was der Zug mitnimmt: Titel und neue Uhrzeit. */
interface Follower {
    title: string;
    minute: number;
}

/**
 * Die Frage nach dem Loslassen: nur heute oder immer?
 *
 * Sie ist der Grund, warum das Ziehen überhaupt vertretbar ist. „Heute mache
 * ich das später" und „ab jetzt immer um zwei" sehen als Geste gleich aus und
 * meinen völlig Verschiedenes — ohne diese Rückfrage würde die App aus einem
 * Fingerwisch eine Planänderung machen und hätte damit nicht zugehört.
 *
 * Alles, was der dauerhafte Weg kostet, steht **vor** der Entscheidung: ein
 * wegfallender Auslöser, eine gelöste Kette, die Gewohnheiten, die mitrutschen.
 * Für „nur heute" gilt nichts davon — deshalb steht es auch nicht dort.
 */
export function ShiftSheet({
    block,
    minute,
    followers,
    conflict,
    error,
    onOpenChange,
    onConfirm,
}: {
    block: CalendarBlock | null;
    minute: number;
    followers: Follower[];
    /** Was heute schon an dieser Stelle liegt — dann geht gar nichts. */
    conflict: BlockConflict | null;
    /** Was der Server abgewiesen hat, nachdem geklickt wurde. */
    error: string | null;
    onOpenChange: (open: boolean) => void;
    onConfirm: (scope: 'today' | 'always') => void;
}) {
    const target = timeLabel(minute);

    return (
        <Sheet open={block !== null} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="mx-auto max-h-[85vh] max-w-lg gap-0 overflow-y-auto rounded-t-2xl px-5 pt-6 pb-8"
            >
                <SheetHeader className="gap-2 p-0">
                    <SheetTitle className="type-eyebrow text-left text-muted-foreground">
                        Neuer Platz im Tag
                    </SheetTitle>
                    <SheetDescription className="text-left text-[15px] leading-relaxed text-foreground">
                        „{block?.title}" liegt jetzt bei{' '}
                        <span className="font-semibold tabular-nums">
                            {target}
                        </span>
                        . Soll das nur für heute gelten?
                    </SheetDescription>
                </SheetHeader>

                {/* Was mitkommt. Eine Kette heißt „danach" — bliebe der
                    Nachfolger stehen, wäre sein eigener Anker gelogen. Dass er
                    mitrutscht, darf man trotzdem nicht erst hinterher merken. */}
                {followers.length > 0 && (
                    <ul className="mt-4 flex flex-col gap-1 rounded-[14px] bg-sand/60 px-4 py-3">
                        {followers.map((follower) => (
                            <li
                                key={follower.title}
                                className="text-sm leading-relaxed"
                            >
                                „{follower.title}" rutscht mit auf{' '}
                                <span className="font-semibold tabular-nums">
                                    {timeLabel(follower.minute)}
                                </span>
                                .
                            </li>
                        ))}
                    </ul>
                )}

                {/* §1.5 — benennt, was gilt, und sagt, was zu tun ist.
                    Zwei Sätze, weil es zwei Fälle sind: Eine Gewohnheit hat
                    man sich selbst vorgenommen und kann sie verschieben. Eine
                    Vorlesung kommt von der Uni — ihr einen Ausweg anzubieten,
                    den es nicht gibt, wäre schlimmer als keiner. */}
                {conflict !== null && (
                    <p
                        role="alert"
                        className="mt-4 rounded-[14px] border border-primary/25 bg-accent px-4 py-3 text-sm leading-relaxed"
                    >
                        {conflict.kind === 'course' ? (
                            <>
                                Während „{conflict.title}" geht das nicht — der
                                Kurs kommt von der Uni und rückt nicht. Such der
                                Gewohnheit eine Zeit davor oder danach, mit
                                einer Viertelstunde Luft.
                            </>
                        ) : (
                            <>
                                „{conflict.title}" liegt heute schon dort, und
                                dazwischen braucht es eine Viertelstunde Luft.
                                Verschiebe die zuerst, dann ist hier Platz.
                            </>
                        )}
                    </p>
                )}

                {error !== null && (
                    <p
                        role="alert"
                        className="mt-4 rounded-[14px] border border-primary/25 bg-accent px-4 py-3 text-sm leading-relaxed"
                    >
                        {error}
                    </p>
                )}

                <div className="mt-5 flex gap-3">
                    <button
                        type="button"
                        onClick={() => onConfirm('today')}
                        disabled={conflict !== null}
                        className={`${OUTLINE_BUTTON} ${ACTION_BUTTON} border-primary text-primary hover:bg-accent`}
                    >
                        Nur heute
                    </button>
                    <button
                        type="button"
                        onClick={() => onConfirm('always')}
                        disabled={conflict !== null}
                        className={`${PRIMARY_BUTTON} ${ACTION_BUTTON} w-auto`}
                    >
                        Immer um {target}
                    </button>
                </div>

                {/* Der Preis des dauerhaften Wegs, vor der Entscheidung. Für
                    „nur heute" gilt nichts davon: Auslöser und Kette bleiben. */}
                {block !== null && consequence(block) !== null && (
                    <p className="mt-5 border-t border-border pt-4 text-sm leading-relaxed text-muted-foreground">
                        {consequence(block)}
                    </p>
                )}
            </SheetContent>
        </Sheet>
    );
}

/**
 * Was „immer" kostet — oder nichts, wenn die Gewohnheit ohnehin an der Uhr hängt.
 *
 * Der Situationsanker ist laut Lally der wirksamere: Eine Situation löst
 * Verhalten von selbst aus, eine Uhrzeit muss man sich merken. Ihn gegen eine
 * Uhrzeit zu tauschen ist eine echte Entscheidung, und ein Fingerwisch darf sie
 * nicht heimlich treffen.
 */
function consequence(block: CalendarBlock): string | null {
    if (block.scheduleType === 'dynamic') {
        return `„Immer" macht daraus eine feste Uhrzeit — der Auslöser „${block.anchor}" fällt dann weg.`;
    }

    if (block.scheduleType === 'chained') {
        return `„Immer" macht daraus eine feste Uhrzeit — sie hängt dann nicht mehr ${block.anchor}.`;
    }

    return null;
}
