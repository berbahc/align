import { router } from '@inertiajs/react';
import {
    BookOpen,
    Dumbbell,
    GlassWater,
    Moon,
    RotateCcw,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { destroy as deleteHabit } from '@/routes/habits';
import { destroy as reactivate } from '@/routes/habits/graduation';
import type { GraduatedHabit } from '@/types';

// Muss zu BEHAVIOR_ICONS in habit-row.tsx passen — dieselbe Kategorie darf
// nicht je nach Bildschirm ein anderes Zeichen tragen.
const BEHAVIOR_ICONS = {
    nutrition: GlassWater,
    movement: Dumbbell,
    learning: BookOpen,
    other: Moon,
} as const;

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
}: {
    habit: GraduatedHabit;
    /** Falsch, wenn bereits fünf Gewohnheiten aktiv sind. */
    canReactivate: boolean;
}) {
    const [confirming, setConfirming] = useState(false);
    const [working, setWorking] = useState(false);

    const Icon = BEHAVIOR_ICONS[habit.behaviorType];

    const history =
        habit.completionCount === 1
            ? '1 Tag abgehakt'
            : `${habit.completionCount} Tage abgehakt`;

    return (
        <li className="flex items-center gap-3 rounded-xl border border-dashed px-4 py-3">
            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                <Icon className="size-5" strokeWidth={1.5} aria-hidden="true" />
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
                            onFinish: () => setWorking(false),
                        });
                    }}
                    className="h-11 cursor-pointer gap-2 px-3 text-primary hover:bg-accent"
                    title={
                        canReactivate
                            ? undefined
                            : 'Erst Platz schaffen — fünf Gewohnheiten sind bereits aktiv.'
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
                            : `${history} gehen dabei verloren. Das lässt sich nicht rückgängig machen — solange die Gewohnheit im Archiv liegt, bleibt der Verlauf erhalten.`}
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
