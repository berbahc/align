import { Pause, Play } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { INTRO_SCENES } from '@/components/onboarding-intro-scenes';
import type { IntroScene } from '@/components/onboarding-intro-scenes';
import { PRIMARY_BUTTON, QUIET_BUTTON } from '@/lib/interaction';
import { cn } from '@/lib/utils';

/** Wie lange eine abtretende Szene noch im Raster liegt — siehe `intro-leave`. */
const LEAVE_MS = 320;

/**
 * Läuft der Film von selbst? Nur, wenn niemand Bewegung abbestellt hat.
 *
 * Eine Szene, die nach fünf Sekunden von allein weiterzieht, ist für jemanden
 * mit dieser Einstellung keine Erleichterung, sondern eine Frist. Wer Bewegung
 * abstellt, blättert selbst — der Inhalt bleibt derselbe, nur die Uhr fällt weg.
 */
function usesMotion(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    return !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/**
 * Der Auftakt — sieben Bilder bis zur ersten Frage.
 *
 * Er läuft von selbst und lässt sich trotzdem in die Hand nehmen: Tippen
 * rechts geht weiter, links zurück, Halten pausiert, `Esc` überspringt. Die
 * beiden Tippflächen sind echte Knöpfe und keine Klickfänger auf einem `div`,
 * sonst wäre der Film mit Tastatur und Vorlesehilfe nicht zu bedienen.
 *
 * WCAG 2.2.2 verlangt für alles, was länger als fünf Sekunden von selbst
 * läuft, eine erreichbare Pause. Halten allein genügt dafür nicht — es gibt
 * deshalb einen sichtbaren Knopf und die Leertaste.
 *
 * Der Zähler hängt an nichts als sich selbst. Ein Clip, der noch lädt, hält
 * den Film nicht auf: Er zeigt so lange seine ruhige Fläche, und die Szene
 * zieht weiter, wenn ihre Zeit um ist.
 *
 * Auf dem letzten Bild hört er auf zu zählen. Der Übergang ins Einrichten ist
 * der einzige Schnitt des Films, den nicht der Film macht, sondern der
 * Nutzer — sonst stünde man im Formular, bevor man den letzten Satz gelesen
 * hat.
 */
export function OnboardingIntro({
    onDone,
    startAtEnd = false,
}: {
    onDone: () => void;
    /**
     * Bei der letzten Szene einsteigen statt bei der ersten.
     *
     * So kommt zurück, wer aus der ersten Frage zurückgeht: Er landet auf dem
     * Bild, das er zuletzt gesehen hat, und nicht wieder am Anfang eines
     * Films, den er gerade zu Ende gesehen hat.
     */
    startAtEnd?: boolean;
}) {
    const [index, setIndex] = useState(
        startAtEnd ? INTRO_SCENES.length - 1 : 0,
    );
    /** Die Szene, die gerade abtritt — für die Überblendung. */
    const [leaving, setLeaving] = useState<number | null>(null);
    /** 1 = vorwärts, -1 = zurück. Sie entscheidet, wohin die Bilder ziehen. */
    const [direction, setDirection] = useState<1 | -1>(1);
    const [paused, setPaused] = useState(false);
    const [motion] = useState(usesMotion);

    const scene = INTRO_SCENES[index] as IntroScene;
    const isLast = index === INTRO_SCENES.length - 1;

    /**
     * Wie viel von der laufenden Szene noch aussteht — und für welche.
     *
     * Beim Pausieren wird der Rest festgehalten, statt die Szene neu zu
     * starten: Sonst wäre jede Pause eine Wiederholung.
     *
     * Der zweite Wert ist der Grund, aus dem hier zwei stehen. Der Aufräumer
     * des Zählers rechnet die verstrichene Zeit ab, und er läuft auch beim
     * Szenenwechsel — dann schriebe er sie der **nächsten** Szene an, und die
     * käme mit null Millisekunden zur Welt. Genau so wurde jede zweite Szene
     * übersprungen. `timing` sagt, für welche Szene `remaining` gilt; stimmt
     * es nicht mehr, ist der Wert Müll und wird verworfen.
     */
    const remaining = useRef(scene.duration);
    const timing = useRef(index);
    const startedAt = useRef(0);

    const go = useCallback(
        (next: number, towards: 1 | -1) => {
            setDirection(towards);
            setLeaving(index);
            setIndex(next);
        },
        [index],
    );

    const forward = useCallback(() => {
        if (isLast) {
            onDone();

            return;
        }

        go(index + 1, 1);
    }, [go, index, isLast, onDone]);

    const back = useCallback(() => {
        if (index === 0) {
            return;
        }

        go(index - 1, -1);
    }, [go, index]);

    // Die abgetretene Szene wieder aus dem Raster nehmen, sobald sie
    // ausgelaufen ist. Ohne das läge sie für immer unter der neuen.
    useEffect(() => {
        if (leaving === null) {
            return;
        }

        const timer = window.setTimeout(() => setLeaving(null), LEAVE_MS);

        return () => window.clearTimeout(timer);
    }, [leaving]);

    // Der Fortlauf. Kein Timer bei abbestellter Bewegung und keiner in Pause.
    useEffect(() => {
        // Neue Szene, volle Zeit — noch vor dem Blick auf `remaining`, denn
        // was der letzte Aufräumer dort hinterlassen hat, gehört der Szene
        // davor.
        if (timing.current !== index) {
            timing.current = index;
            remaining.current = INTRO_SCENES[index]!.duration;
        }

        // Das letzte Bild bleibt stehen. Von hier führt nur ein Druck
        // weiter — der Film kippt niemanden ungefragt ins Einrichten, und
        // wer den Satz zu Ende lesen will, kann das.
        if (!motion || paused || isLast) {
            return;
        }

        startedAt.current = Date.now();
        const timer = window.setTimeout(forward, remaining.current);

        return () => {
            window.clearTimeout(timer);
            remaining.current = Math.max(
                remaining.current - (Date.now() - startedAt.current),
                0,
            );
        };
    }, [forward, index, isLast, motion, paused]);

    useEffect(() => {
        function onKey(event: KeyboardEvent) {
            if (event.key === 'ArrowRight') {
                forward();
            } else if (event.key === 'ArrowLeft') {
                back();
            } else if (event.key === 'Escape') {
                onDone();
            } else if (event.key === ' ' || event.key === 'Spacebar') {
                // Nur, wenn nicht gerade ein Knopf den Fokus hat — sonst
                // pausierte die Leertaste und drückte den Knopf zugleich.
                if (document.activeElement?.tagName !== 'BUTTON') {
                    event.preventDefault();
                    setPaused((current) => !current);
                }
            } else {
                return;
            }
        }

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [back, forward, onDone]);

    return (
        <section
            aria-label="Was Align macht"
            className="relative flex flex-1 flex-col gap-6"
        >
            {/* Der Filmstreifen — dieselbe Form wie die Schrittleiste des
                Assistenten, damit Film und Anlegen ein Ablauf bleiben. */}
            <div className="flex items-center gap-1.5" aria-hidden="true">
                {INTRO_SCENES.map((candidate, position) => (
                    <span
                        key={candidate.id}
                        className="h-1 flex-1 overflow-hidden rounded-full bg-sand"
                    >
                        <span
                            className={cn(
                                // `w-0` ist der Grundzustand: Ein Block ohne
                                // Breite füllt sonst seine Spur, und alle
                                // sechs Balken stünden von Anfang an voll da.
                                'block h-full w-0 rounded-full bg-primary',
                                position < index && 'w-full',
                                // Der laufende Balken zählt die Szene ab —
                                // außer auf dem letzten Bild, das keine Uhr
                                // mehr hat. Dort steht er einfach voll.
                                position === index && 'w-full',
                                position === index &&
                                    !isLast &&
                                    'intro-progress',
                            )}
                            {...(position === index && !isLast
                                ? {
                                      'data-running': '',
                                      ...(paused ? { 'data-paused': '' } : {}),
                                  }
                                : {})}
                            style={
                                position === index && !isLast
                                    ? ({
                                          '--intro-duration': `${scene.duration}ms`,
                                      } as React.CSSProperties)
                                    : undefined
                            }
                        />
                    </span>
                ))}
            </div>

            <div
                className="relative flex flex-1 flex-col justify-center"
                style={{ '--intro-dir': direction } as React.CSSProperties}
            >
                <div className="intro-stage">
                    {leaving !== null && leaving !== index && (
                        <Scene
                            key={INTRO_SCENES[leaving]!.id}
                            scene={INTRO_SCENES[leaving]!}
                            position={leaving}
                            state="leaving"
                        />
                    )}
                    <Scene
                        key={scene.id}
                        scene={scene}
                        position={index}
                        state="entering"
                    />
                </div>
            </div>

            {/* Die beiden Tippflächen. Sie liegen über dem Bild, aber unter
                den Knöpfen darunter — und sie sind Knöpfe, keine Fangflächen:
                Ein Film, den man nur mit dem Daumen bedienen kann, ist für
                einen Teil der Nutzer kein Film. */}
            {motion && (
                <div className="pointer-events-none absolute inset-x-0 top-0 bottom-24 flex">
                    <button
                        type="button"
                        aria-label="Vorige Szene"
                        disabled={index === 0}
                        onClick={back}
                        onPointerDown={() => setPaused(true)}
                        onPointerUp={() => setPaused(false)}
                        onPointerCancel={() => setPaused(false)}
                        className="pointer-events-auto w-1/3 cursor-pointer focus-visible:outline-2 focus-visible:-outline-offset-4 focus-visible:outline-ring disabled:cursor-default"
                    />
                    <button
                        type="button"
                        aria-label="Nächste Szene"
                        onClick={forward}
                        onPointerDown={() => setPaused(true)}
                        onPointerUp={() => setPaused(false)}
                        onPointerCancel={() => setPaused(false)}
                        className="pointer-events-auto flex-1 cursor-pointer focus-visible:outline-2 focus-visible:-outline-offset-4 focus-visible:outline-ring"
                    />
                </div>
            )}

            <div className="relative mx-auto flex w-full max-w-md flex-col gap-3">
                {isLast ? (
                    <button
                        type="button"
                        onClick={onDone}
                        className={PRIMARY_BUTTON}
                    >
                        Fangen wir mit deinem Tag an
                    </button>
                ) : (
                    !motion && (
                        <button
                            type="button"
                            onClick={forward}
                            className={PRIMARY_BUTTON}
                        >
                            Weiter
                        </button>
                    )
                )}

                {!motion && index > 0 && (
                    <button
                        type="button"
                        onClick={back}
                        className={QUIET_BUTTON}
                    >
                        Zurück
                    </button>
                )}

                {motion && !isLast && (
                    <button
                        type="button"
                        onClick={() => setPaused((current) => !current)}
                        className={cn(
                            QUIET_BUTTON,
                            'flex items-center gap-1.5 self-center',
                        )}
                    >
                        {paused ? (
                            <Play className="size-3.5" aria-hidden="true" />
                        ) : (
                            <Pause className="size-3.5" aria-hidden="true" />
                        )}
                        {paused ? 'Weiterlaufen lassen' : 'Anhalten'}
                    </button>
                )}
            </div>
        </section>
    );
}

/**
 * Ein einzelnes Bild.
 *
 * `data-beat` staffelt, was zuerst kommt: erst das Bild, dann das Kapitel,
 * dann der Satz. Der Text liegt in einer `aria-live`-Fläche, damit eine
 * Vorlesehilfe jede Szene mitbekommt — das Bild daneben ist für sie
 * ausgeblendet und sagt nichts, was der Text nicht auch sagt.
 */
function Scene({
    scene,
    position,
    state,
}: {
    scene: IntroScene;
    /** Das wievielte Bild — für die Kapitelzeile. */
    position: number;
    state: 'entering' | 'leaving';
}) {
    return (
        <div
            className={cn(
                // Untereinander auf dem Handy, nebeneinander ab dem großen
                // Schirm: Dort ist das Bild breiter als hoch, und ein Satz
                // unter einem 660 Pixel breiten Bild stünde so weit unten,
                // dass man ihn erst sucht. Das Bild bekommt die größere
                // Spalte — es trägt die Szene, der Satz benennt sie.
                //
                // Oben ausgerichtet und die Textspalte etwas tiefer: Zwei
                // mittig ausgerichtete Spalten ergeben eine Waage, keine
                // Komposition. So läuft der Blick schräg vom Bild in den Satz.
                'intro-scene flex flex-col gap-7',
                'lg:grid lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)] lg:items-start lg:gap-14',
                state === 'entering' ? 'intro-entering' : 'intro-leaving',
            )}
        >
            <div data-beat="0" style={{ '--beat': 0 } as React.CSSProperties}>
                {scene.visual}
            </div>

            <div
                aria-live={state === 'entering' ? 'polite' : 'off'}
                className="flex flex-col lg:pt-10"
            >
                {/* Die Kapitelzeile: Nummer und Kapitel, sonst nichts. Sie
                    sagt, wo im Film man steht und ob gerade ein Problem oder
                    eine Antwort zu sehen ist. */}
                <p
                    data-beat="1"
                    style={{ '--beat': 1 } as React.CSSProperties}
                    className="type-eyebrow mb-4 flex items-center gap-4 text-faint"
                >
                    <span className="tabular-nums">
                        {String(position + 1).padStart(2, '0')}
                    </span>
                    {scene.chapter}
                </p>

                <h2
                    data-beat="2"
                    style={{ '--beat': 2 } as React.CSSProperties}
                    className="intro-headline"
                >
                    {scene.headline}
                </h2>

                <p
                    data-beat="3"
                    style={{ '--beat': 3 } as React.CSSProperties}
                    className="mt-4 max-w-prose text-[15px] leading-relaxed text-muted-foreground lg:text-base"
                >
                    {scene.aside}
                </p>
            </div>
        </div>
    );
}
