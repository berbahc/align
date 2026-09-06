import { useHttp } from '@inertiajs/react';
import { useState } from 'react';
import { readMessage } from '@/hooks/use-day-order';
import type { NewPlace, UnplacedHabit } from '@/types';

interface NewPlacesResponse {
    /** Ein Satz über das Ergebnis — „Drei von vier passen wieder in deinen Tag." */
    reason: string;
    places: NewPlace[];
    /** Was die Person selbst hinlegt: kein Fenster, oder vom Modell weggelassen. */
    unplaced: UnplacedHabit[];
}

/**
 * Der Aufruf für neue Plätze — nach dem Muster von `use-day-order`.
 *
 * Vier Zustände, weil es vier Antworten gibt: läuft, kam etwas, kam nichts
 * (503 — nochmal versuchen), oder die Frage war unnötig (422 — die Absage
 * ist eine Auskunft, kein Ausfall).
 */
export function useNewPlaces() {
    const request = useHttp<Record<string, never>, NewPlacesResponse>({});
    const [reason, setReason] = useState<string | null>(null);
    const [places, setPlaces] = useState<NewPlace[]>([]);
    const [unplaced, setUnplaced] = useState<UnplacedHabit[]>([]);
    const [refusal, setRefusal] = useState<string | null>(null);
    const [failed, setFailed] = useState(false);

    function load(url: string) {
        setFailed(false);
        setRefusal(null);
        setReason(null);
        setPlaces([]);
        setUnplaced([]);

        return request
            .post(url, {
                onSuccess: (response) => {
                    setReason(response.reason);
                    setPlaces(response.places ?? []);
                    setUnplaced(response.unplaced ?? []);
                },
                onHttpException: (response) => {
                    const message =
                        response.status === 409
                            ? readMessage(response.data)
                            : null;

                    if (message !== null) {
                        setRefusal(message);
                    } else {
                        setFailed(true);
                    }

                    return false;
                },
                onNetworkError: () => {
                    setFailed(true);

                    return false;
                },
            })
            .catch(() => undefined);
    }

    return {
        reason,
        places,
        unplaced,
        refusal,
        failed,
        loading: request.processing,
        load,
    };
}
