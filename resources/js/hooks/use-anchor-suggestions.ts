import { useHttp } from '@inertiajs/react';
import { useState } from 'react';
import type { AnchorAlternative } from '@/types';

interface SuggestionResponse {
    /** Was der KI aufgefallen ist — steht vor dem Vorschlag. */
    observation: string;
    alternatives: AnchorAlternative[];
}

/**
 * Holt Beobachtung und Alternativen für einen anderen Zeitpunkt.
 *
 * Derselbe Zuschnitt wie `use-smallest-step`: drei Zustände, weil es keinen
 * Ersatz für einen ausgefallenen KI-Aufruf gibt und die Oberfläche das
 * benennen können muss.
 */
export function useAnchorSuggestions() {
    const request = useHttp<Record<string, never>, SuggestionResponse>({});
    const [observation, setObservation] = useState<string | null>(null);
    const [alternatives, setAlternatives] = useState<AnchorAlternative[]>([]);
    const [failed, setFailed] = useState(false);

    function load(url: string) {
        setFailed(false);
        setObservation(null);
        setAlternatives([]);

        return request
            .post(url, {
                onSuccess: (response) => {
                    setObservation(response.observation);
                    setAlternatives(response.alternatives ?? []);
                },
                onHttpException: () => {
                    setFailed(true);

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
        observation,
        alternatives,
        failed,
        loading: request.processing,
        load,
    };
}
