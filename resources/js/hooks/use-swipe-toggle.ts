import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Ziehen als Weg, eine Gewohnheit abzuhaken.
 *
 * Umgesetzt nach Apple, „Designing Fluid Interfaces" (WWDC 2018). Die vier
 * Punkte, an denen sich eine Geste entscheidet:
 *
 * - **1:1** — die Zeile hängt am Zeiger, nicht an einem Endzustand (§2).
 * - **Gummiband** — an der Grenze wird der Widerstand größer, statt dass es
 *   hart stehenbleibt (§9).
 * - **Schwung** — beim Loslassen wird nicht der Ort geprüft, sondern wohin
 *   die Bewegung *läuft* (§6). Ein kurzer Schnipser reicht deshalb.
 * - **Übergabe** — die Feder startet mit genau der Geschwindigkeit des
 *   Zeigers, damit zwischen Ziehen und Zurückfedern keine Naht sichtbar ist
 *   (§5), und sie ist jederzeit greifbar (§3).
 *
 * Es gibt keine Federbibliothek im Projekt, also rechnet der Integrator unten
 * die Feder selbst. Eine CSS-Transition könnte das nicht: sie kann weder eine
 * Anfangsgeschwindigkeit übernehmen noch mitten im Lauf sauber übernommen
 * werden.
 */

/** Apples Wert aus dem Beispielcode; 0.998 entspricht dem Scroll-Auslauf. */
const DECELERATION = 0.998;

/** §10 — erst nach 10px steht fest, ob gewischt oder gescrollt wird. */
const AXIS_LOCK = 10;

/** Feder für das Zurückfedern: Dämpfung 0.8, Response 0.35s — wie --ease-pop. */
const DAMPING = 0.8;
const RESPONSE = 0.35;

/** Nach so langer Ruhe gilt eine Trackpad-Geste als losgelassen. */
const WHEEL_IDLE = 90;

/**
 * Wohin die Bewegung ausläuft, wenn man jetzt loslässt.
 *
 * Nicht die Physik-Formel v²/2a, sondern der exponentielle Auslauf aus Apples
 * Beispielcode — nur der trifft das Gefühl von Scroll-Trägheit.
 */
function project(velocity: number): number {
    return ((velocity / 1000) * DECELERATION) / (1 - DECELERATION);
}

/**
 * Widerstand jenseits der Grenze: je weiter, desto weniger folgt die Zeile.
 * Nichts steht abrupt, aber es wird spürbar, dass da nichts mehr kommt.
 */
function rubberband(overshoot: number, dimension: number): number {
    const constant = 0.55;

    return (
        (overshoot * dimension * constant) /
        (dimension + constant * Math.abs(overshoot))
    );
}

export interface SwipeToggle {
    /** Aktuelle Auslenkung in Pixeln, vorzeichenbehaftet. */
    offset: number;
    /** Strecke, ab der Loslassen auslöst — in Pixeln. */
    threshold: number;
    /** Liegt gerade ein Zeiger auf der Zeile? */
    dragging: boolean;
    /** Auf das Element, das gezogen werden soll. */
    attach: (node: HTMLElement | null) => void;
    onPointerDown: (event: React.PointerEvent) => void;
    /**
     * Ein Klick unmittelbar nach einer Zieh-Geste ist keiner — er würde sonst
     * den Knopf auslösen, über dem der Zeiger zufällig losgelassen wurde.
     */
    onClickCapture: (event: React.MouseEvent) => void;
}

export function useSwipeToggle({
    direction,
    onCommit,
    enabled = true,
}: {
    /** Die Richtung, in die es etwas auszulösen gibt. */
    direction: 'right' | 'left';
    onCommit: () => void;
    enabled?: boolean;
}): SwipeToggle {
    const [offset, setOffset] = useState(0);
    const [dragging, setDragging] = useState(false);
    const [threshold, setThreshold] = useState(96);

    const node = useRef<HTMLElement | null>(null);
    const frame = useRef<number | null>(null);

    // Was gerade auf dem Schirm steht — der Startwert jeder Übernahme (§3).
    const onScreen = useRef(0);

    const gesture = useRef<{
        pointerId: number | null;
        startX: number;
        startY: number;
        axis: 'x' | 'y' | null;
        /** Die letzten Punkte, für die Geschwindigkeit beim Loslassen. */
        trail: { x: number; time: number }[];
        dragged: boolean;
    } | null>(null);

    const wheel = useRef<{
        trail: { x: number; time: number }[];
        idle: ReturnType<typeof setTimeout> | null;
        /** Aufsummierte Waagerechte, bevor die Geste überhaupt greift. */
        pull: number;
        engaged: boolean;
    }>({ trail: [], idle: null, pull: 0, engaged: false });

    const sign = direction === 'right' ? 1 : -1;

    // Über eine Ref, damit die Fenster- und Rad-Listener nicht bei jedem
    // Rendern neu gehängt werden — mitten in einer Geste wäre das fatal.
    const commit = useRef(onCommit);

    useEffect(() => {
        commit.current = onCommit;
    });

    /** Ziel und Geschwindigkeit der laufenden Feder — beide umlenkbar. */
    const target = useRef(0);
    const velocity = useRef(0);

    const stopSpring = useCallback(() => {
        if (frame.current !== null) {
            cancelAnimationFrame(frame.current);
            frame.current = null;
        }

        velocity.current = 0;
    }, []);

    const show = useCallback((value: number) => {
        onScreen.current = value;
        setOffset(value);
    }, []);

    const reducedMotion = () =>
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /**
     * Federt von der aktuellen Darstellung auf ein Ziel.
     *
     * §3 — bekommt eine laufende Feder ein neues Ziel, wird sie *umgelenkt*
     * und nicht neu gestartet: Ziel und Geschwindigkeit liegen in Refs, die
     * Schleife liest sie in jedem Bild. Ein Neustart würde die Geschwindigkeit
     * auf null setzen, und genau das ist die „Mauer", die Apple beschreibt.
     *
     * `initialVelocity` ist deshalb optional. Angegeben wird sie nur dort, wo
     * wirklich etwas übergeben wird — beim Loslassen (§5).
     */
    const springTo = useCallback(
        (to: number, initialVelocity?: number) => {
            target.current = to;

            if (initialVelocity !== undefined) {
                velocity.current = initialVelocity;
            }

            if (reducedMotion()) {
                stopSpring();
                show(to);

                return;
            }

            if (frame.current !== null) {
                // Läuft schon — sie übernimmt das neue Ziel im nächsten Bild.
                return;
            }

            const w0 = (2 * Math.PI) / RESPONSE;
            let x = onScreen.current;
            let last = performance.now();

            const step = (now: number) => {
                // Bei einem Tab-Wechsel wird der Abstand groß; ohne Deckel
                // schießt der Integrator dann weg.
                const dt = Math.min((now - last) / 1000, 1 / 30);
                last = now;

                const distance = x - target.current;

                velocity.current +=
                    (-w0 * w0 * distance -
                        2 * DAMPING * w0 * velocity.current) *
                    dt;
                x += velocity.current * dt;

                if (
                    Math.abs(distance) < 0.3 &&
                    Math.abs(velocity.current) < 12
                ) {
                    show(target.current);
                    velocity.current = 0;
                    frame.current = null;

                    return;
                }

                show(x);
                frame.current = requestAnimationFrame(step);
            };

            frame.current = requestAnimationFrame(step);
        },
        [show, stopSpring],
    );

    /** Geschwindigkeit in px/s aus den letzten ~100ms der Spur. */
    function velocityOf(trail: { x: number; time: number }[]): number {
        const now = performance.now();
        const recent = trail.filter((point) => now - point.time < 100);

        if (recent.length < 2) {
            return 0;
        }

        const first = recent[0];
        const last = recent[recent.length - 1];
        const seconds = (last.time - first.time) / 1000;

        return seconds > 0 ? (last.x - first.x) / seconds : 0;
    }

    /**
     * Loslassen. §6: Nicht der erreichte Ort entscheidet, sondern der, auf den
     * die Bewegung zuläuft — ein Schnipser über die halbe Strecke genügt.
     */
    const release = useCallback(
        (velocity: number) => {
            const projected = onScreen.current + project(velocity);

            if (projected * sign >= threshold) {
                commit.current();
            }

            springTo(0, velocity);
        },
        [sign, springTo, threshold],
    );

    /**
     * §9 — beide Enden sind weich. Gegen die Richtung gibt es nichts
     * auszulösen, jenseits der Auslösestrecke nichts mehr zu holen: in beiden
     * Fällen folgt die Zeile gedämpft weiter, statt starr stehenzubleiben.
     */
    const resist = useCallback(
        (delta: number) => {
            const width = node.current?.offsetWidth ?? 320;
            const pulled = delta * sign;

            if (pulled < 0) {
                return rubberband(delta, width) * 0.5;
            }

            const limit = threshold * 1.5;

            if (pulled <= limit) {
                return delta;
            }

            return sign * (limit + rubberband(pulled - limit, width) * 0.4);
        },
        [sign, threshold],
    );

    const onPointerDown = useCallback(
        (event: React.PointerEvent) => {
            if (!enabled || event.button !== 0) {
                return;
            }

            // Die laufende Feder wird hier *nicht* angehalten. Ein Klick auf
            // den Knopf ist noch keine Zieh-Geste — hielte das Drücken die
            // Bewegung an, bliebe die Zeile bei jedem Klick auf halbem Weg
            // stehen. Übernommen wird sie erst, wenn wirklich gezogen wird.
            gesture.current = {
                pointerId: event.pointerId,
                startX: event.clientX,
                startY: event.clientY,
                axis: null,
                trail: [],
                dragged: false,
            };
        },
        [enabled],
    );

    const onClickCapture = useCallback((event: React.MouseEvent) => {
        if (gesture.current?.dragged) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, []);

    // Zeigerbewegung hängt am Fenster, nicht am Element: der Zeiger darf die
    // Zeile verlassen, ohne dass die Geste abreißt (§2, `setPointerCapture`
    // erledigt dasselbe, greift aber erst nach dem Achsen-Entscheid).
    useEffect(() => {
        function move(event: PointerEvent) {
            const active = gesture.current;

            if (active === null || active.pointerId !== event.pointerId) {
                return;
            }

            let dx = event.clientX - active.startX;
            const dy = event.clientY - active.startY;

            if (active.axis === null) {
                const sideways = Math.abs(dx);
                const upright = Math.abs(dy);

                // §10 — beide Deutungen laufen parallel, und der Verlierer
                // wird erst verworfen, wenn die Absicht *klar* ist. Ein Zug
                // auf dem Trackpad zittert immer senkrecht mit; entschiede
                // hier schon das erste Bild über der Schwelle, stürbe die
                // Geste an einem Ausreißer — und danach täte sich bis zum
                // Loslassen nichts mehr, obwohl der Finger weiterzieht.
                if (upright >= AXIS_LOCK && upright > sideways * 1.5) {
                    gesture.current = null;

                    return;
                }

                if (sideways < AXIS_LOCK || sideways <= upright) {
                    // Noch unentschieden: weiter zusehen, nichts verwerfen.
                    return;
                }

                active.axis = 'x';

                // §3 — *jetzt* wird die laufende Feder übernommen, und zwar da,
                // wo sie gerade steht. Der Nullpunkt der Geste wird auf diesen
                // Moment gelegt, sonst spränge die Zeile beim Zupacken.
                stopSpring();
                active.startX = event.clientX - onScreen.current;
                dx = onScreen.current;

                active.dragged = true;
                setDragging(true);

                // Auf der Zeile selbst, nicht auf dem getroffenen Kind: ein
                // Knopf darunter kann verschwinden, die Zeile bleibt.
                //
                // Abgesichert, weil das Fangen wirft, wenn der Zeiger in
                // diesem Moment nicht (mehr) aktiv ist. Ungefangen risse der
                // Wurf den Rest dieser Funktion mit — und die Zeile bewegte
                // sich nie, obwohl die Geste erkannt wurde. Fangen ist eine
                // Verbesserung, keine Bedingung: Ohne es endet die Geste nur
                // früher, wenn der Zeiger die Zeile verlässt.
                try {
                    node.current?.setPointerCapture(event.pointerId);
                } catch {
                    // Dann eben ohne.
                }
            }

            active.trail.push({ x: dx, time: performance.now() });

            if (active.trail.length > 8) {
                active.trail.shift();
            }

            show(resist(dx));
        }

        function up(event: PointerEvent) {
            const active = gesture.current;

            if (active === null || active.pointerId !== event.pointerId) {
                return;
            }

            gesture.current =
                active.axis === 'x' ? { ...active, pointerId: null } : null;

            if (active.axis !== 'x') {
                return;
            }

            setDragging(false);
            release(velocityOf(active.trail));

            // Der Klick, der gleich folgt, gehört noch zu dieser Geste.
            window.setTimeout(() => {
                gesture.current = null;
            }, 0);
        }

        window.addEventListener('pointermove', move);
        window.addEventListener('pointerup', up);
        window.addEventListener('pointercancel', up);

        return () => {
            window.removeEventListener('pointermove', move);
            window.removeEventListener('pointerup', up);
            window.removeEventListener('pointercancel', up);
        };
    }, [release, resist, show, stopSpring]);

    // Zwei Finger auf dem Trackpad sind kein Zeiger, sondern ein Rad-Ereignis.
    // Damit dieselbe Geste auch am Laptop läuft, wird die waagerechte Achse
    // hier abgefangen — und nur sie, damit Scrollen unangetastet bleibt.
    useEffect(() => {
        const element = node.current;

        if (element === null || !enabled) {
            return;
        }

        // Das Ref-Objekt selbst ist über die Lebenszeit stabil; die Kopie
        // stellt nur sicher, dass das Aufräumen dieselbe Uhr stoppt.
        const pending = wheel.current;

        function onWheel(event: WheelEvent) {
            // Ein Zeiger zieht bereits — dann gehört die Zeile dem.
            if (gesture.current !== null || event.ctrlKey) {
                return;
            }

            const sideways = Math.abs(event.deltaX);
            const upright = Math.abs(event.deltaY);

            // Senkrechtes Scrollen auf einem Trackpad ist nie exakt senkrecht:
            // Es kommen ständig Bilder mit einem, zwei Pixeln Seitwärtsdrift.
            // `deltaX > deltaY` allein reicht deshalb nicht — die Zeile würde
            // beim gewöhnlichen Scrollen zucken und sich am Ende sogar selbst
            // abhaken. Die Waagerechte muss klar überwiegen.
            if (sideways < 2 || sideways <= upright * 2) {
                // Eine deutlich senkrechte Bewegung beendet eine begonnene
                // Wisch-Geste: Der Nutzer scrollt jetzt, er wischt nicht mehr.
                if (upright > sideways) {
                    pending.pull = 0;
                }

                return;
            }

            // §10 — dieselbe Hysterese wie beim Zeiger: Erst nach einer
            // Strecke steht fest, dass gewischt und nicht gescrollt wird. Was
            // sich bis dahin angesammelt hat, geht nicht verloren; es ist die
            // erste Auslenkung, sobald die Geste greift.
            let step = -event.deltaX;

            if (!pending.engaged) {
                pending.pull += step;

                if (Math.abs(pending.pull) < AXIS_LOCK) {
                    // Auch die angesammelte Strecke verfällt nach einer Ruhe.
                    // Ohne das summierte sich seitliche Scroll-Drift über die
                    // ganze Sitzung und löste irgendwann grundlos aus.
                    if (pending.idle !== null) {
                        clearTimeout(pending.idle);
                    }

                    pending.idle = setTimeout(() => {
                        pending.pull = 0;
                        pending.idle = null;
                    }, WHEEL_IDLE);

                    return;
                }

                pending.engaged = true;
                step = pending.pull;
                stopSpring();
            }

            event.preventDefault();

            show(resist(onScreen.current + step));

            pending.trail.push({
                x: onScreen.current,
                time: performance.now(),
            });

            if (pending.trail.length > 8) {
                pending.trail.shift();
            }

            if (pending.idle !== null) {
                clearTimeout(pending.idle);
            }

            // Ein Rad-Ereignis meldet kein Ende. Die Ruhe danach ist das Ende.
            pending.idle = setTimeout(() => {
                const velocity = velocityOf(pending.trail);
                const engaged = pending.engaged;

                pending.trail = [];
                pending.idle = null;
                pending.pull = 0;
                pending.engaged = false;

                if (engaged) {
                    release(velocity);
                }
            }, WHEEL_IDLE);
        }

        element.addEventListener('wheel', onWheel, { passive: false });

        return () => {
            element.removeEventListener('wheel', onWheel);
            pending.pull = 0;
            pending.engaged = false;

            if (pending.idle !== null) {
                clearTimeout(pending.idle);
            }
        };
    }, [enabled, release, resist, show, stopSpring]);

    useEffect(() => stopSpring, [stopSpring]);

    const attach = useCallback((next: HTMLElement | null) => {
        node.current = next;

        if (next !== null) {
            // Ein Drittel der Zeile, gedeckelt — auf breiten Schirmen soll die
            // Strecke nicht mitwachsen, die Hand bleibt ja gleich groß.
            setThreshold(Math.min(112, Math.max(64, next.offsetWidth * 0.3)));
        }
    }, []);

    return {
        offset,
        threshold,
        dragging,
        attach,
        onPointerDown,
        onClickCapture,
    };
}
