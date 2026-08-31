import { usePage } from '@inertiajs/react';
import { AlarmClock } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { dismissAlarm, useSleepStatus } from '@/hooks/use-sleep';

/**
 * Der Weckton: zwei kurze Pieptöne, dann Pause — in WebAudio erzeugt.
 *
 * Keine Audiodatei, weil es keine braucht: Ein Sinuston mit Hüllkurve reicht,
 * und die App bleibt frei von Assets, die nur für diesen einen Moment laden.
 * Browser lassen Ton erst nach einer Nutzergeste zu — konnte der Kontext
 * nicht starten, bleibt der Wecker eben stumm und nur sichtbar.
 */
function startChime(): (() => void) | null {
    const AudioContextClass = window.AudioContext;

    if (AudioContextClass === undefined) {
        return null;
    }

    const context = new AudioContextClass();

    void context.resume().catch(() => undefined);

    function beep(at: number) {
        const oscillator = context.createOscillator();
        const gain = context.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.value = 880;
        gain.gain.setValueAtTime(0.0001, at);
        gain.gain.exponentialRampToValueAtTime(0.3, at + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, at + 0.35);

        oscillator.connect(gain);
        gain.connect(context.destination);
        oscillator.start(at);
        oscillator.stop(at + 0.4);
    }

    function ring() {
        const now = context.currentTime;

        beep(now);
        beep(now + 0.5);
    }

    ring();
    const timer = window.setInterval(ring, 2_000);

    return () => {
        window.clearInterval(timer);
        void context.close().catch(() => undefined);
    };
}

/**
 * Der Wecker — ein Vollbild zur Aufstehzeit, solange ein Tab offen ist.
 *
 * Das eine Mal, in dem die App den Bildschirm für sich beansprucht: Ein
 * Wecker, der sich übersehen lässt, ist keiner. Er klingelt höchstens zehn
 * Minuten und merkt sich das Ausschalten im Browser — ein Seitenwechsel weckt
 * niemanden zweimal.
 */
export function WakeAlarm() {
    const { sleep } = usePage().props;
    const { alarm } = useSleepStatus(sleep ?? null);

    // Nur ein Anstoß zum Neu-Rechnen: Das Gedächtnis des Ausschaltens liegt
    // im localStorage ({@see dismissAlarm}), und `useSleepStatus` liest es
    // bei jedem Render — der Zähler holt den nächsten Render nur vor, statt
    // auf den 30-Sekunden-Takt zu warten.
    const [, bump] = useState(0);

    const ringing = alarm !== null;
    const stopChime = useRef<(() => void) | null>(null);

    useEffect(() => {
        if (ringing && stopChime.current === null) {
            stopChime.current = startChime();
        }

        if (!ringing && stopChime.current !== null) {
            stopChime.current();
            stopChime.current = null;
        }

        return () => {
            stopChime.current?.();
            stopChime.current = null;
        };
    }, [ringing]);

    if (alarm === null) {
        return null;
    }

    function dismiss() {
        dismissAlarm();
        bump((count) => count + 1);
    }

    return (
        <div
            role="alertdialog"
            aria-modal="true"
            aria-label="Wecker"
            /* Die Fläche kommt herein, statt schlagartig dazustehen: Apple §12
               — eine Fläche, die erscheint, soll ankommen wie Material, nicht
               wie ein Schalter. Bei „reduzierte Bewegung" bleibt die
               Überblendung, die Skalierung entfällt. */
            className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-8 bg-primary p-6 text-primary-foreground motion-safe:animate-in motion-safe:duration-[var(--duration-fluid)] motion-safe:ease-[var(--ease-fluid)] motion-safe:fade-in motion-safe:zoom-in-[0.98]"
        >
            <span className="flex size-20 items-center justify-center rounded-full bg-primary-foreground/10">
                <AlarmClock
                    className="size-10 motion-safe:animate-pulse"
                    strokeWidth={1.5}
                    aria-hidden="true"
                />
            </span>

            <div className="text-center">
                <p className="type-numeral text-6xl">{alarm.wakeTime}</p>
                <p className="mt-3 text-lg text-primary-foreground/80">
                    Guten Morgen — dein Tag beginnt.
                </p>
            </div>

            {/* Der eine Knopf, den es hier gibt, trägt die Umkehrfarbe: Auf der
                vollflächigen Olivfläche ist Weiß die Handlung, nicht Oliv. */}
            <button
                type="button"
                autoFocus
                onClick={dismiss}
                className="inline-flex h-14 cursor-pointer items-center justify-center rounded-2xl bg-primary-foreground px-10 text-lg font-semibold text-primary transition-[scale,background-color] duration-[var(--duration-press)] ease-out focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary-foreground motion-safe:active:scale-[0.97]"
            >
                Wecker aus
            </button>
        </div>
    );
}
