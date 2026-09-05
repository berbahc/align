import type { ReactNode } from 'react';

/**
 * Die Überschrift eines Abschnitts — Titel, Erklärung, Handlung.
 *
 * Die Übersicht trug drei Abschnitte in drei verschiedenen Graden: eine H2
 * („Heutige Gewohnheiten"), darunter eine Kleinversalien-Zeile („ZUSAMMEN")
 * und darunter noch eine in einer Karte. Drei gleichrangige Bereiche, drei
 * verschiedene Formen — und keiner sagte, wofür er da ist. Wer zweimal
 * denselben Namen und dieselbe Person las, konnte nicht wissen, was der
 * Unterschied zwischen den Blöcken ist.
 *
 * Deshalb ein Grad für alle und eine Zeile darunter, die den Bereich benennt
 * statt ihn nur zu betiteln. §3.2: H2 ist 20–22/700 in `ink`; die Erklärung
 * ist Caption in `muted`.
 */
export function SectionHeading({
    id,
    title,
    hint,
    action,
}: {
    /** Die Kennung für `aria-labelledby` am umgebenden Abschnitt. */
    id?: string;
    title: string;
    /**
     * Ein Satz, der sagt, was hier steht — nicht was man tun soll.
     *
     * §1.5 und §8: benennen, nicht auffordern.
     */
    hint?: string;
    /** Der Weg, der zu diesem Abschnitt gehört — steht rechts. */
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
            <div className="min-w-0">
                <h2 id={id} className="type-subheading">
                    {title}
                </h2>
                {hint !== undefined && (
                    <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                        {hint}
                    </p>
                )}
            </div>
            {action}
        </div>
    );
}
