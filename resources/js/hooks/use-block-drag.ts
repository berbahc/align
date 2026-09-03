import { useCallback, useEffect, useRef, useState } from 'react';
import { HOUR_HEIGHT, snapMinute } from '@/lib/day-grid';
import type { GridBounds } from '@/lib/day-grid';
import type { CalendarBlock } from '@/types';

/**
 * Wie lange der Finger stillhalten muss, bevor der Block sich löst.
 *
 * 400 ms ist die Schwelle, die iOS für „Aufnehmen und Ziehen" verwendet: lang
 * genug, dass ein Wisch über die Liste nicht versehentlich etwas mitnimmt,
 * kurz genug, dass es sich nicht nach Warten anfühlt.
 */
const LONG_PRESS_MS = 400;

/**
 * Wie weit der Finger vorher wandern darf.
 *
 * Darüber war es Scrollen, kein Griff — und Scrollen darf ein Kalender nie
 * verlieren. Im Zweifel gewinnt die Liste, nicht der Block.
 */
const SLOP_PX = 8;

export interface BlockDrag {
    id: number;
    /** Die aktuelle Zielminute, schon gerastet. */
    minute: number;
    /** Wo der Block herkam — für die Frage, ob sich überhaupt etwas geändert hat. */
    origin: number;
}

/**
 * Einen Block im Raster aufnehmen und woandershin legen.
 *
 * Ohne Bibliothek: Das Projekt hat keine, und eine für eine einzige Geste zu
 * holen wiegt schwerer als die Geste. Pointer-Events können alles, was hier
 * gebraucht wird — sie fassen Maus, Finger und Stift zusammen und liefern mit
 * `setPointerCapture` die Zusage, dass die Bewegung bei dem Element bleibt, an
 * dem sie angefangen hat.
 *
 * Die Reihenfolge ist der ganze Trick: Erst nach dem Langdruck wird
 * `touch-action` abgeschaltet. Vorher gehört die vertikale Bewegung dem
 * Scrollen, und der Browser bricht den Griff von selbst ab
 * (`pointercancel`) — genau das soll er.
 */
export function useBlockDrag({
    bounds,
    enabled,
    onDrop,
}: {
    bounds: GridBounds;
    enabled: boolean;
    /** Der Zug ist zu Ende und die Minute hat sich geändert. */
    onDrop: (drag: BlockDrag) => void;
}) {
    const [drag, setDrag] = useState<BlockDrag | null>(null);

    /** Was zwischen `pointerdown` und `pointerup` gilt, ohne neu zu rendern. */
    const grip = useRef<{
        id: number;
        startY: number;
        origin: number;
        duration: number;
        timer: number;
        held: boolean;
    } | null>(null);

    /** Nach einem Zug darf der folgende Klick den Block nicht auch noch öffnen. */
    const justDragged = useRef(false);

    const release = useCallback(() => {
        if (grip.current !== null) {
            window.clearTimeout(grip.current.timer);
            grip.current = null;
        }

        setDrag(null);
    }, []);

    // Ein unterbrochener Zug (Seitenwechsel, verlorener Fokus) darf keinen
    // Timer hinterlassen, der ins Leere feuert.
    useEffect(() => release, [release]);

    function onPointerDown(event: React.PointerEvent, block: CalendarBlock) {
        if (!enabled || block.startMinute === null || block.graduated) {
            return;
        }

        const target = event.currentTarget as HTMLElement;

        grip.current = {
            id: block.id,
            startY: event.clientY,
            origin: block.startMinute,
            duration: block.durationMinutes ?? 15,
            held: false,
            timer: window.setTimeout(() => {
                if (grip.current === null) {
                    return;
                }

                grip.current.held = true;
                target.setPointerCapture(event.pointerId);
                setDrag({
                    id: grip.current.id,
                    minute: grip.current.origin,
                    origin: grip.current.origin,
                });
            }, LONG_PRESS_MS),
        };
    }

    function onPointerMove(event: React.PointerEvent) {
        const held = grip.current;

        if (held === null) {
            return;
        }

        const dy = event.clientY - held.startY;

        // Noch nicht aufgenommen: Wandert der Finger, war es Scrollen.
        if (!held.held) {
            if (Math.abs(dy) > SLOP_PX) {
                release();
            }

            return;
        }

        setDrag({
            id: held.id,
            origin: held.origin,
            minute: snapMinute(
                held.origin + (dy / HOUR_HEIGHT) * 60,
                bounds,
                held.duration,
            ),
        });
    }

    function onPointerUp() {
        const held = grip.current;
        const current = drag;

        release();

        if (held?.held !== true || current === null) {
            return;
        }

        justDragged.current = true;
        // Der Klick folgt unmittelbar auf das Loslassen; danach darf wieder
        // geöffnet werden.
        window.setTimeout(() => {
            justDragged.current = false;
        }, 0);

        if (current.minute !== current.origin) {
            onDrop(current);
        }
    }

    return {
        drag,
        /** Hat gerade ein Zug geendet? Dann ist der Klick sein Nachhall. */
        swallowsClick: () => justDragged.current,
        cancel: release,
        handlers: {
            onPointerDown,
            onPointerMove,
            onPointerUp,
            onPointerCancel: release,
        },
    };
}
