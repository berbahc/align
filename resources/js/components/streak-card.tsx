import { Repeat } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

export interface Streak {
    /** Länge der Serie in vorgesehenen Terminen. */
    count: number;
    /** „Tage" bei täglichen Gewohnheiten, sonst „Mal" — vom Server bestimmt. */
    unit: string;
    /** Die Gewohnheit, zu der die Serie gehört. */
    title: string;
}

/**
 * Streak-Karte nach Designsprache §5.4.
 *
 * Vollflächig `primary`, zentriert: Icon → Zahl → Label → Gewohnheit. Die
 * einzige farbige Fläche auf der Übersicht; ihre Wirkung hängt laut §5.4 davon
 * ab, dass sie allein bleibt.
 *
 * Zwei bewusste Abweichungen von §5.4:
 *
 * - **Kein ✦-Wasserzeichen.** §7 derselben Datei hat ✦ inzwischen fest als
 *   KI-Marker vergeben. Hier würde es „KI-Vorschlag" behaupten.
 * - **Kein Flammen-Icon.** Das wäre der Duolingo-Ton. `Repeat` benennt, worum
 *   es geht — Wiederholung —, ohne zu feiern (§1.4 „kein Alarm", §8
 *   „beobachtend, nicht wertend").
 *
 * Die Karte erscheint nur oberhalb von `Habit::StreakMinimum` und verschwindet
 * beim Bruch wortlos: keine Meldung, kein Zurückzählen, kein Hinweis auf den
 * verbrauchten Kulanztag.
 */
export function StreakCard({ streak }: { streak: Streak }) {
    return (
        <Card className="gap-0 border-transparent bg-primary py-6">
            <CardContent className="flex flex-col items-center px-5 text-center">
                <Repeat
                    className="size-6 text-primary-foreground"
                    strokeWidth={1.5}
                    aria-hidden="true"
                />

                <p className="mt-3 text-[clamp(2rem,6vw,2.25rem)] leading-none font-bold text-primary-foreground tabular-nums">
                    {streak.count}
                </p>

                <p className="mt-2 text-[11px] font-semibold tracking-[0.11em] text-primary-foreground/70 uppercase">
                    {streak.unit} in Folge
                </p>

                <p className="mt-1 text-xs text-primary-foreground/70">
                    {streak.title}
                </p>
            </CardContent>
        </Card>
    );
}
