import { useHttp } from '@inertiajs/react';
import { useState } from 'react';

/** Was beide Endpunkte zurückgeben: ein bis drei fertig formulierte Schritte. */
interface SuggestionResponse {
    steps: string[];
}

/**
 * Die Angaben, aus denen ein Vorschlag entsteht.
 *
 * Beim Anlegen kennt die App die Gewohnheit noch nicht — deshalb reisen die
 * Katalog-Vorlage, die Dauer und die Situation mit. Im Alltag steht sie in
 * der Datenbank, dann reicht der Schritt, der sich gerade zu groß anfühlt.
 */
interface SmallestStepPayload {
    template_key?: string;
    target_amount?: number;
    trigger_situation?: string;
    current?: string;
}

/**
 * Holt Vorschläge für den kleinsten nächsten Schritt.
 *
 * Drei Zustände statt zwei: es lädt, es liegen Schritte vor, oder der Aufruf
 * ist gescheitert. Der dritte ist kein Randfall — die App hat bewusst keinen
 * Ersatz für einen ausgefallenen KI-Aufruf, also muss die Oberfläche ihn
 * benennen können.
 */
export function useSmallestStep() {
    const request = useHttp<SmallestStepPayload, SuggestionResponse>({});
    const [steps, setSteps] = useState<string[]>([]);
    const [failed, setFailed] = useState(false);

    function load(url: string, payload: SmallestStepPayload) {
        setFailed(false);
        setSteps([]);

        // `setData` schreibt die Werte synchron in die Referenz, die `post`
        // liest — die beiden Zeilen dürfen deshalb direkt aufeinanderfolgen.
        request.setData(payload);

        return request
            .post(url, {
                onSuccess: (response) => setSteps(response.steps ?? []),
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

    /** Verwirft, was zuvor geholt wurde — etwa beim Wechsel der Gewohnheit. */
    function reset() {
        setSteps([]);
        setFailed(false);
    }

    return { steps, failed, loading: request.processing, load, reset };
}
