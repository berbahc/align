import { cn } from '@/lib/utils';

/**
 * Was die KI gerade tut.
 *
 * Vier Zustände, weil die Oberfläche genau vier kennt: Der Weg zur KI steht da
 * (`idle`), der Aufruf läuft (`thinking`), die Antwort steht (`speaking`), der
 * Aufruf kam nicht durch (`stumped`). Sie kommen nirgends neu dazu — jeder
 * KI-Hook liefert `loading` und `failed`, mehr braucht es nicht.
 */
export type MascotState = 'idle' | 'thinking' | 'speaking' | 'stumped';

/**
 * Das Wesen, das für die KI spricht.
 *
 * Align hat vier KI-Funktionen, und alle vier zeigten sich durch dasselbe
 * stumme `✦` in vierzehn Pixeln. Die stärkste Funktion der Umfrage (Starthilfe
 * ø 4,16) war damit der leiseste Teil der Oberfläche — man konnte die App
 * benutzen, ohne je zu bemerken, dass hier etwas mitdenkt.
 *
 * Die Figur ist kein neues Zeichen, sondern das vorhandene, gewachsen:
 * `designsprache.md` §8 hält `✦` ohnehin schon für KI-Momente frei und
 * verwendet es „für nichts anderes". Der vierstrahlige Stern ist der Körper,
 * vier schmale Strahlen dazwischen machen daraus einen Kranz, zwei Punkte
 * daraus ein Gegenüber.
 *
 * **Kein Rot, kein Ausrufezeichen, kein trauriges Gesicht.** Auch der
 * Fehlzustand bleibt beobachtend (`ki-assistent-design.md` §2): Die Strahlen
 * hängen, der Blick wird zu zwei Strichen, mehr nicht. Die KI kommt gerade
 * nicht durch — sie hat nichts falsch gemacht, und der Nutzer erst recht nicht.
 *
 * **Die Zustände sind auch im Stillstand zu unterscheiden.** Neigung,
 * Blickrichtung und Augenform tragen sie; die Bewegung setzt nur obendrauf und
 * entfällt bei `prefers-reduced-motion` vollständig. Was sich nur durch
 * Bewegung unterschiede, wäre für einen Teil der Nutzer gar nicht da.
 */
export function AiMascot({
    state = 'idle',
    variant = 'figure',
    className,
}: {
    state?: MascotState;
    /**
     * `mark` ist das reine Zeichen ohne Augen — es ersetzt das `✦` an den
     * Wegen zur KI, wo vierzehn Pixel kein Gesicht tragen. `figure` hat Augen
     * und steht dort, wo die KI wirklich spricht.
     */
    variant?: 'mark' | 'figure';
    className?: string;
}) {
    return (
        <svg
            viewBox="0 0 24 24"
            aria-hidden="true"
            className={cn('mascot', `mascot-${state}`, 'size-6', className)}
        >
            {/* Der Strahlenkranz: vier schmale Striche auf den Diagonalen.
                Dort hat der Körper seine Taille — seine konkaven Flanken
                reichen über die Diagonale nur bis Radius 4,7, die Striche
                beginnen bei 5,2. Auf den Achsen lägen sie unter den Zacken
                und wären unsichtbar; sichtbar würden sie erst beim Drehen,
                und dann als Fetzen, die unter der Figur hervorschauen.

                Eigene Gruppe, weil nur der Kranz sich dreht: Ein rotierender
                Körper mit Augen sähe aus, als kippte die Figur um. */}
            <g className="mascot-rays" fill="currentColor" opacity={0.4}>
                <rect
                    x="11.55"
                    y="3.9"
                    width="0.9"
                    height="2.6"
                    rx="0.45"
                    transform="rotate(45 12 12)"
                />
                <rect
                    x="11.55"
                    y="3.9"
                    width="0.9"
                    height="2.6"
                    rx="0.45"
                    transform="rotate(135 12 12)"
                />
                <rect
                    x="11.55"
                    y="3.9"
                    width="0.9"
                    height="2.6"
                    rx="0.45"
                    transform="rotate(225 12 12)"
                />
                <rect
                    x="11.55"
                    y="3.9"
                    width="0.9"
                    height="2.6"
                    rx="0.45"
                    transform="rotate(315 12 12)"
                />
            </g>

            {/* Der Körper — das `✦` aus §8, mit konkaven Flanken. Vier
                Viertelkreise, die zur Mitte hin einschwingen. */}
            <g className="mascot-body">
                <path
                    fill="currentColor"
                    d="M12 0.6C12 6.9 17.1 12 23.4 12 17.1 12 12 17.1 12 23.4 12 17.1 6.9 12 0.6 12 6.9 12 12 6.9 12 0.6Z"
                />

                {/* Die Augen liegen auf dem Körper, nicht als Aussparung
                    darin: `primary-foreground` auf `primary` ist dasselbe
                    Paar, das schon der Personenkreis benutzt — es trägt in
                    beiden Modi, während eine Aussparung die Farbe des
                    Untergrunds erraten müsste, und der ist mal `card` und mal
                    `sand`. */}
                {variant === 'figure' && (
                    <g
                        className="mascot-eyes"
                        fill="var(--color-primary-foreground)"
                    >
                        <circle
                            className="mascot-eye"
                            cx="9.8"
                            cy="10.8"
                            r="1.4"
                        />
                        <circle
                            className="mascot-eye"
                            cx="14.2"
                            cy="10.8"
                            r="1.4"
                        />
                        {/* Der ruhende Blick des Fehlzustands: zwei Striche.
                            Sie ersetzen die Punkte, statt sie zu ergänzen —
                            welche gelten, entscheidet die Zustandsklasse. */}
                        <rect
                            className="mascot-dash"
                            x="8.15"
                            y="10.25"
                            width="3.3"
                            height="1.1"
                            rx="0.55"
                        />
                        <rect
                            className="mascot-dash"
                            x="12.55"
                            y="10.25"
                            width="3.3"
                            height="1.1"
                            rx="0.55"
                        />
                    </g>
                )}
            </g>
        </svg>
    );
}
