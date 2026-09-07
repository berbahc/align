import { router } from '@inertiajs/react';
import { RotateCcw, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { HabitGlyph } from '@/components/habit-glyph';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { destroy as deleteHabit } from '@/routes/habits';
import { destroy as reactivate } from '@/routes/habits/graduation';
import type { GraduatedHabit } from '@/types';

/**
 * Eine beendete Gewohnheit im Archiv.
 *
 * Die Kachel ist ruhig statt gedämpft: `muted` heißt hier „abgelegt", nicht
 * „gescheitert". Kein Durchstreichen — die Tage sind gelaufen, nicht entwertet.
 *
 * Wiederaufnehmen steht links und ist die naheliegende Handlung; das Löschen
 * ist ein Icon am Rand und fragt nach, weil es der eine Weg ist, auf dem in
 * dieser App Verlauf verloren geht.
 */
export function GraduatedHabitRow({
    habit,
    canReactivate,
    highlighted = false,
    onReactivated,
}: {
    habit: GraduatedHabit;
    /** Falsch, wenn bereits fünf Gewohnheiten aktiv sind. */
    canReactivate: boolean;
    /**
     * Kurz betont, weil die Gewohnheit gerade hier gelandet ist.
     *
     * Kein Zustand des Archivs, sondern eine Antwort auf „Beenden": Wer etwas
     * beendet, soll sehen, wohin es gewandert ist, statt es zu suchen.
     */
    highlighted?: boolean;
    /** Sagt Bescheid, sobald sie wieder oben in der Liste steht. */
    onReactivated?: () => void;
}) {
    const [confirming, setConfirming] = useState(false);
    const [working, setWorking] = useState(false);

    const history =
        habit.completionCount === 1
            ? '1 Tag abgehakt'
            : `${habit.completionCount} Tage abgehakt`;

    return (
        <li
            id={`graduated-habit-${habit.id}`}
            className={cn(
                /* Eine Mulde statt einer gestrichelten Kontur: Was gefestigt
                   ist, liegt im Blatt und wartet — nicht darüber. */
                'hollow flex items-center gap-3 rounded-xl border px-4 py-3 transition-shadow duration-300',
                highlighted &&
                    'ring-2 ring-primary ring-offset-4 ring-offset-background',
            )}
        >
            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                <HabitGlyph habit={habit} className="size-5" />
            </span>

            <span className="min-w-0 flex-1">
                <span className="block truncate text-[15px] leading-snug font-semibold">
                    {habit.title}
                </span>
                <span className="mt-0.5 block truncate text-xs text-muted-foreground">
                    Beendet am {habit.graduatedOn} · {history}
                </span>
            </span>

            <span className="flex shrink-0 items-center gap-1">
                <Button
                    variant="ghost"
                    disabled={!canReactivate || working}
                    onClick={() => {
                        setWorking(true);
                        router.delete(reactivate.url(habit.id), {
                            preserveScroll: true,
                            onSuccess: () => onReactivated?.(),
                            onFinish: () => setWorking(false),
                        });
                    }}
                    className="h-11 cursor-pointer gap-2 px-3 text-primary hover:bg-accent"
                    title={
                        canReactivate
                            ? undefined
                            : 'Erst Platz schaffen. Fünf Gewohnheiten sind bereits aktiv.'
                    }
                >
                    <RotateCcw className="size-4" aria-hidden="true" />
                    <span className="sr-only sm:not-sr-only">
                        Wiederaufnehmen
                    </span>
                </Button>

                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => setConfirming(true)}
                    className="size-11 cursor-pointer text-muted-foreground hover:bg-accent hover:text-destructive"
                >
                    <Trash2 className="size-4" aria-hidden="true" />
                    <span className="sr-only">
                        {habit.title} endgültig löschen
                    </span>
                </Button>
            </span>

            <Dialog open={confirming} onOpenChange={setConfirming}>
                <DialogContent>
                    <DialogTitle>
                        „{habit.title}" endgültig löschen?
                    </DialogTitle>
                    <DialogDescription>
                        {habit.completionCount === 0
                            ? 'Die Gewohnheit verschwindet vollständig. Das lässt sich nicht rückgängig machen.'
                            : `${history} gehen dabei verloren. Das lässt sich nicht rückgängig machen. Solange die Gewohnheit im Archiv liegt, bleibt der Verlauf erhalten.`}
                    </DialogDescription>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button
                                variant="secondary"
                                className="h-11 cursor-pointer"
                            >
                                Abbrechen
                            </Button>
                        </DialogClose>

                        <Button
                            variant="destructive"
                            disabled={working}
                            className="h-11 cursor-pointer"
                            onClick={() => {
                                setWorking(true);
                                router.delete(deleteHabit.url(habit.id), {
                                    preserveScroll: true,
                                    onFinish: () => {
                                        setWorking(false);
                                        setConfirming(false);
                                    },
                                });
                            }}
                        >
                            Endgültig löschen
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </li>
    );
}
