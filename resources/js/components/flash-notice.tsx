import { usePage } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
import { useState } from 'react';

/**
 * Bestätigung nach dem Anlegen einer Gewohnheit.
 *
 * Sie bleibt stehen, bis sie weggeklickt wird — kein Auto-Ausblenden, weil der
 * Satz eine Auskunft trägt („steht am Montag") und nicht bloß ein Lob ist. Wer
 * langsamer liest, verliert sie sonst.
 *
 * `role="status"` statt `alert`: Screenreader lesen sie vor, ohne die aktuelle
 * Ausgabe zu unterbrechen.
 */
export function FlashNotice() {
    const { flash } = usePage();
    const created = flash.habitCreated;
    // Gemerkt wird die weggeklickte Meldung, nicht ein Ja/Nein. Eine neue
    // Meldung trägt eine andere Kennung und ist damit von selbst wieder
    // sichtbar — ohne Effekt, der den Zustand nachträglich zurücksetzt.
    const [dismissed, setDismissed] = useState<string | null>(null);

    const key = created ? `${created.title}|${created.when}` : null;

    if (!created || dismissed === key) {
        return null;
    }

    return (
        <div
            role="status"
            className="mx-auto mt-4 flex w-full max-w-3xl items-start gap-3 rounded-xl border border-primary/25 bg-accent px-4 py-3"
        >
            <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                <Check
                    className="size-4"
                    strokeWidth={2.5}
                    aria-hidden="true"
                />
            </span>

            <p className="min-w-0 flex-1 text-sm leading-relaxed">
                <span className="font-semibold">„{created.title}"</span> ist
                angelegt. Sie steht {created.when} in deiner Tagesliste.
                {!created.scheduledToday && (
                    <span className="text-muted-foreground">
                        {' '}
                        Heute ist sie nicht vorgesehen — deshalb siehst du sie
                        in der Übersicht noch nicht.
                    </span>
                )}
            </p>

            <button
                type="button"
                onClick={() => setDismissed(key)}
                className="-m-2 flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full text-muted-foreground transition-colors duration-200 hover:bg-sand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
            >
                <X className="size-4" aria-hidden="true" />
                <span className="sr-only">Hinweis schließen</span>
            </button>
        </div>
    );
}
