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
                <CollapsibleTrigger className="inline-flex size-6 cursor-pointer items-center justify-center rounded-full text-muted-foreground transition-colors duration-[var(--duration-press)] ease-out hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring">
                    <Info className="size-4" aria-hidden="true" />
                    <span className="sr-only">
                        {open ? 'Erklärung ausblenden' : `Warum nur ${max}?`}
                    </span>
                </CollapsibleTrigger>
            </p>

            <CollapsibleContent>
                {/* Dieselbe Ebene wie der Erklärkasten im Blatt: eine sandige
                    Fläche mit Kante und Schatten statt einer gestrichelten
                    Skizze. Beide beantworten dieselbe Frage — „was steht da
                    eigentlich?" — und sollen deshalb gleich aussehen. */}
                <div className="glass mt-3 rounded-xl px-4 py-3">
                    <p className="text-[13px] font-semibold text-glass-foreground">
                        Warum nur {max}?
                    </p>
                    <p className="mt-1 text-xs leading-relaxed">
                        Läuft eine schon von allein, beende sie. Dann ist der
                        Platz frei für eine neue.
                    </p>
                </div>
            </CollapsibleContent>
        </Collapsible>
    );
}
