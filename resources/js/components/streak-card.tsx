import { router } from '@inertiajs/react';
import { Repeat, X } from 'lucide-react';
import { destroy } from '@/routes/habits/streak-card';

export interface Streak {
    /** Die Gewohnheit, zu der die Serie gehört — und der Schlüssel der Karte. */
    id: number;
    /** Länge der Serie in vorgesehenen Terminen. */
    count: number;
    /** „Tage" bei täglichen Gewohnheiten, sonst „Mal" — vom Server bestimmt. */
    unit: string;
    /** Die Gewohnheit, zu der die Serie gehört. */
    title: string;
}

/**
 * Die laufenden Serien — bis zu drei nebeneinander.
 *
 * **Milchglas statt einer Farbfläche.** Die Karte war vollflächig `primary`,
 * und Designsprache §5.4 lässt genau eine solche Fläche zu: „ihre Wirkung
 * hängt davon ab, dass sie allein bleibt." Genau daran scheiterte die zweite
 * Karte — drei leuchtende Blöcke wären ein Wettbewerb gewesen, kein Hinweis.
 * Als Glas liegen sie als eine Ebene über der Übersicht, tragen ihre Zahl
 * ruhig und lassen die eine gesättigte Fläche des Systems wieder frei.
 *
 * Zwei bewusste Abweichungen von §5.4 bleiben bestehen:
 *
 * - **Kein ✦-Wasserzeichen.** §7 derselben Datei hat ✦ inzwischen fest als
 *   KI-Marker vergeben. Hier würde es „KI-Vorschlag" behaupten.
 * - **Kein Flammen-Icon.** Das wäre der Duolingo-Ton. `Repeat` benennt, worum
 *   es geht — Wiederholung —, ohne zu feiern (§1.4 „kein Alarm", §8
 *   „beobachtend, nicht wertend").
 *
 * Eine Karte erscheint nur oberhalb von `Habit::StreakMinimum` und verschwindet
 * beim Bruch wortlos: keine Meldung, kein Zurückzählen, kein Hinweis auf den
 * verbrauchten Kulanztag.
 *
 * **Und sie lässt sich wegnehmen.** Das blasse × oben rechts nimmt genau diese
 * eine Karte von der Übersicht; die Serie läuft weiter und wird weiter
 * gezählt. Wer auf die eigene Zahl nicht schauen will, soll das dürfen —
 * `progress-tracking.md` verlangt für den Fortschritt „einen ehrlichen, nicht
 * strafenden Kontext", und dazu gehört, ihn wegklicken zu dürfen. Zurück holt
 * sie das ⋯-Menü der Gewohnheit.
 */
export function StreakCards({ streaks }: { streaks: Streak[] }) {
    return (
        <ul
            aria-label="Laufende Serien"
            /* Umbrechende Reihe statt festem Raster: Eine Serie nimmt die ganze
               Breite, zwei teilen sie, drei stehen zu dritt — ohne dass eine
               leere Spalte danebensteht. */
            className="flex flex-wrap gap-3"
        >
            {streaks.map((streak) => (
                <li
                    key={streak.id}
                    className="glass relative flex min-w-52 flex-1 flex-col items-center rounded-2xl px-5 py-6 text-center"
                >
                    {/* Blass und klein: Es ist der Ausgang, nicht das Thema
                        der Karte. Die Trefferfläche ist trotzdem 44 Pixel
                        groß, das Zeichen darin nur 14 — ein × in Fingergröße
                        wäre lauter als die Zahl darunter. */}
                    <button
                        type="button"
                        onClick={() =>
                            router.delete(destroy.url(streak.id), {
                                preserveScroll: true,
                            })
                        }
                        className="absolute top-1 right-1 flex size-11 cursor-pointer items-center justify-center rounded-full text-glass-muted/60 transition-colors duration-[var(--duration-press)] ease-out hover:text-glass-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        <X className="size-3.5" aria-hidden="true" />
                        <span className="sr-only">
                            Serie von {streak.title} ausblenden
                        </span>
                    </button>

                    <Repeat
                        className="size-6 text-primary"
                        strokeWidth={1.5}
                        aria-hidden="true"
                    />

                    <p className="mt-3 text-[clamp(2rem,6vw,2.25rem)] leading-none font-bold tracking-[-0.02em] text-glass-foreground tabular-nums">
                        {streak.count}
                    </p>

                    <p className="type-eyebrow mt-2 text-glass-muted">
                        {streak.unit} in Folge
                    </p>

                    <p className="mt-1 text-xs text-glass-muted">
                        {streak.title}
                    </p>
                </li>
            ))}
        </ul>
    );
}
