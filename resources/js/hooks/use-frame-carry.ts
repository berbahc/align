import type { FormDataType } from '@inertiajs/core';
import { useHttp } from '@inertiajs/react';
import { useState } from 'react';

/** Eine Gewohnheit, die mit dem Rahmen wandert. */
export interface CarriedHabit {
    habitId: number;
    title: string;
    /** Wo sie lag — „08:00". */
    from: string;
    /** Wo sie läge — „11:00". */
    to: string;
}

/** Eine, die nicht mitkann, und der Satz warum. */
export interface BlockedHabit {
    habitId: number;
    title: string;
    reason: string;
}

export interface CarryPreview {
    moves: CarriedHabit[];
    blocked: BlockedHabit[];
}

/** Hat der neue Rahmen überhaupt Folgen? */
export function isQuiet(preview: CarryPreview | null): boolean {
    return (
        preview !== null &&
        preview.moves.length === 0 &&
        preview.blocked.length === 0
    );
}

/**
 * Was ein neuer Rahmen mit den Gewohnheiten machen würde.
 *
 * Erst zeigen, dann übernehmen — dasselbe Muster wie `use-day-order`, nur
 * ohne KI: Die Antwort ist eine Rechnung, keine Meinung, und der Server hat
 * sie so gerechnet, wie er sie hinterher auch ausführt.
 *
 * Ein Ausfall ist kein Grund, das Speichern zu blockieren: Der Schlafplan
 * gehört dem Nutzer. Wer die Vorschau nicht bekommt, speichert eben ohne sie
 * — dann bleibt alles liegen, wo es liegt, und das ist der Stand von vorher.
 */
export function useFrameCarry<TPayload extends FormDataType<TPayload>>(
    empty: TPayload,
) {
    const request = useHttp<TPayload, CarryPreview>(empty);
    const [preview, setPreview] = useState<CarryPreview | null>(null);
    const [failed, setFailed] = useState(false);

    /**
     * Fragen — und die Antwort dorthin geben, wo entschieden wird.
     *
     * `onResult` bekommt sie direkt, nicht erst über den nächsten Durchlauf:
     * Der Aufrufer muss im selben Zug entscheiden, ob er den Dialog zeigt oder
     * gleich speichert, und ein Zustand, der erst später ankommt, käme dafür
     * zu spät. `null` heißt „nicht zu erfahren" — dann wird ohne Vorschau
     * gespeichert, statt das Speichern an unserem Ausfall scheitern zu lassen.
     */
    function ask(
        url: string,
        payload: TPayload,
        onResult?: (result: CarryPreview | null) => void,
    ) {
        setFailed(false);
        setPreview(null);

        // `transform` statt `setData`: Der Zustand käme erst im nächsten
        // Durchlauf an, die Anfrage geht aber sofort raus — sie schickte dann
        // den vorigen Rahmen und bekäme eine Vorschau auf die falsche Frage.
        request.transform(() => payload);

        return request
            .post(url, {
                onSuccess: (response) => {
                    const result: CarryPreview = {
                        moves: response.moves ?? [],
                        blocked: response.blocked ?? [],
                    };

                    setPreview(result);
                    onResult?.(result);
                },
                onHttpException: () => {
                    setFailed(true);
                    onResult?.(null);

                    return false;
                },
                onNetworkError: () => {
                    setFailed(true);
                    onResult?.(null);

                    return false;
                },
            })
            .catch(() => undefined);
    }

    function forget() {
        setPreview(null);
        setFailed(false);
    }

    return {
        preview,
        failed,
        asking: request.processing,
        ask,
        forget,
    };
}
