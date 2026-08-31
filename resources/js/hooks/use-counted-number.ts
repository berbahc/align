import { useEffect, useRef, useState } from 'react';

/**
 * Zählt eine Zahl auf ihren neuen Wert, statt sie springen zu lassen.
 *
 * Apple, „Designing Fluid Interfaces" §1: Rückmeldung gehört *während* der
 * Handlung sichtbar gemacht, nicht nur an ihrem Ende. Der Tagesfortschritt
 * ändert sich beim Abhaken um einen ganzen Sprung — Balken und Zahl laufen
 * deshalb gemeinsam dorthin, mit derselben Kurve.
 *
 * Beim ersten Rendern wird nicht gezählt. Eine Zahl, die bei jedem Seiten-
 * aufruf hochläuft, wäre eine Vorführung; hier ist die Bewegung die Antwort
 * auf einen Klick, den es vorher gab.
 *
 * Bei `prefers-reduced-motion: reduce` steht der Zielwert sofort da.
 */
export function useCountedNumber(target: number, duration = 420): number {
    const [displayed, setDisplayed] = useState(target);

    // Was gerade auf dem Schirm steht. Wird beim erneuten Abhaken mitten im
    // Zählen als Startwert gelesen — §3: von der Ist-Darstellung aus
    // weiterlaufen, nie vom logischen Vorgängerwert, sonst springt es.
    const onScreen = useRef(target);
    const frame = useRef<number | null>(null);
    const mounted = useRef(false);

    const show = (value: number) => {
        onScreen.current = value;
        setDisplayed(value);
    };

    useEffect(() => {
        if (
            !mounted.current ||
            typeof window === 'undefined' ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            mounted.current = true;
            show(target);

            return;
        }

        const from = onScreen.current;
        const started = performance.now();

        const step = (now: number) => {
            const progress = Math.min((now - started) / duration, 1);
            // Dieselbe Auslaufkurve wie --ease-fluid, damit Zahl und Balken
            // sich unterwegs nicht auseinanderziehen.
            const eased = 1 - Math.pow(1 - progress, 3);

            show(Math.round(from + (target - from) * eased));

            if (progress < 1) {
                frame.current = requestAnimationFrame(step);
            }
        };

        frame.current = requestAnimationFrame(step);

        return () => {
            if (frame.current !== null) {
                cancelAnimationFrame(frame.current);
            }
        };
    }, [target, duration]);

    return displayed;
}
