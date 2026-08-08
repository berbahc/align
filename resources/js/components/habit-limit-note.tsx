import { Info } from 'lucide-react';
import { useState } from 'react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';

/**
 * Erklärt die Obergrenze — und erscheint erst, wenn sie erreicht ist.
 *
 * Bewusst kein Tooltip: Auf dem Handy gibt es kein Hover, der Hinweis wäre
 * dort unerreichbar. Ein Knopf, der einen Absatz aufklappt, funktioniert mit
 * Maus, Tastatur und Finger gleichermaßen.
 *
 * Der Ton folgt §8: die Grenze wird als Schutz benannt, nicht als Verbot, und
 * der letzte Satz zeigt den Ausweg — sonst bliebe eine Sackgasse stehen.
 */
export function HabitLimitNote({
    max,
    label,
}: {
    max: number;
    /** Die Zählung, neben der das Zeichen steht — „5 von 5 aktiv". */
    label: string;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <p className="mt-1 flex items-center gap-1.5 text-sm text-muted-foreground">
                {label}
                <CollapsibleTrigger className="inline-flex size-6 cursor-pointer items-center justify-center rounded-full text-muted-foreground transition-colors duration-200 hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring">
                    <Info className="size-4" aria-hidden="true" />
                    <span className="sr-only">
                        {open ? 'Erklärung ausblenden' : `Warum nur ${max}?`}
                    </span>
                </CollapsibleTrigger>
            </p>

            <CollapsibleContent>
                <div className="mt-3 rounded-xl border border-dashed px-4 py-3">
                    <p className="text-[13px] font-semibold">
                        Warum nur {max}?
                    </p>
                    <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                        Jede Gewohnheit, die noch nicht sitzt, kostet dich
                        täglich einen Vorsatz. Fünf davon sind genug. Läuft eine
                        schon von allein, beende sie und der Platz ist dann frei
                        für eine neue Gewohnheit.
                    </p>
                </div>
            </CollapsibleContent>
        </Collapsible>
    );
}
