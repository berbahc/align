import { useHttp } from '@inertiajs/react';
import { useState } from 'react';

/** Eine Gewohnheit an ihrem vorgeschlagenen Platz. */
export interface OrderedHabit {
    id: number;
    title: string;
    /** Der neue Beginn, „07:30". */
    time: string;
    /** Die belegte Spanne als fertige Zeile, „07:30 – 08:00". */
    timeRange: string;
}

interface OrderResponse {
    /** Ein Satz, worin die Ordnung liegt. */
    reason: string;
    order: OrderedHabit[];
}

/**
 * Der Satz aus einer abgelehnten Antwort — oder nichts.
 *
 * Der Rumpf kommt als Zeichenkette; ein unlesbarer darf nicht dazu führen,
 * dass die Oberfläche gar nichts sagt.
 */
export function readMessage(body: string): string | null {
    try {
        const parsed: unknown = JSON.parse(body);

        return typeof parsed === 'object' &&
            parsed !== null &&
            typeof (parsed as { message?: unknown }).message === 'string'
            ? (parsed as { message: string }).message
            : null;
    } catch {
        return null;
    }
}

/**
 * Holt eine neue Ordnung für einen ganzen Tag.
 *
 * Vier Zustände statt drei: Neben „lädt", „da" und „ausgefallen" gibt es hier
 * die begründete Absage — der Tag ist zu voll, oder es gibt zu wenig zu
 * ordnen. Das ist kein Ausfall, sondern eine Auskunft, und sie kommt vom
 * Server mit einem eigenen Satz (422 statt 503).
 */
export function useDayOrder() {
    const request = useHttp<{ date: string }, OrderResponse>({ date: '' });
    const [order, setOrder] = useState<OrderedHabit[]>([]);
    const [reason, setReason] = useState<string | null>(null);
    const [refusal, setRefusal] = useState<string | null>(null);
    const [failed, setFailed] = useState(false);

    function load(url: string, date: string) {
        setFailed(false);
        setRefusal(null);
        setReason(null);
        setOrder([]);

        request.setData({ date });

        return request
            .post(url, {
                onSuccess: (response) => {
                    setReason(response.reason);
                    setOrder(response.order ?? []);
                },
                onHttpException: (response) => {
                    // 422 trägt einen Satz, der erklärt, warum es nicht geht —
                    // ihn als Ausfall zu zeigen wäre eine Ausrede statt einer
                    // Antwort. Alles andere ist ein echter Ausfall.
                    const message =
                        response.status === 422
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
        order,
        reason,
        /** Die begründete Absage — kein Ausfall, sondern eine Auskunft. */
        refusal,
        failed,
        loading: request.processing,
        load,
    };
}
