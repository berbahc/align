import { ChevronDown, ChevronUp } from 'lucide-react';
import { STEPPER_BUTTON } from '@/lib/interaction';

/** Minuten springen in Fünferschritten — niemand plant minutengenau. */
const MINUTE_STEP = 5;

function shift(value: string, unit: 'hour' | 'minute', direction: 1 | -1) {
    const [hours = 0, minutes = 0] = value.split(':').map(Number);

    if (unit === 'hour') {
        // Modulo zweimal, weil JavaScript bei negativen Zahlen negativ bleibt.
        return `${String((((hours + direction) % 24) + 24) % 24).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }

    const stepped = minutes + direction * MINUTE_STEP;

    return `${String(hours).padStart(2, '0')}:${String(((stepped % 60) + 60) % 60).padStart(2, '0')}`;
}

/**
 * Eine Uhrzeit in zwei Säulen: Stunde und Minute, je mit Pfeilen.
 *
 * Kein natives Zeitfeld, weil dessen Darstellung je Browser eine andere ist
 * und die App die Fünferschritte will — die Uhrzeit einer Gewohnheit ist ein
 * Rahmen, kein Termin auf die Minute.
 *
 * `size` unterscheidet den großen Auftritt (der eine Stepper im Wizard) vom
 * kompakten (zwei nebeneinander im Schlafplan).
 */
export function TimeStepper({
    value,
    onChange,
    label,
    size = 'large',
}: {
    value: string;
    onChange: (value: string) => void;
    /** Wofür die Uhrzeit steht — für Screenreader an jedem Pfeil. */
    label: string;
    size?: 'large' | 'compact';
}) {
    const [hours = '00', minutes = '00'] = value.split(':');

    const digits =
        size === 'large' ? 'type-numeral text-5xl' : 'type-numeral text-2xl';

    const colon =
        size === 'large' ? 'pt-1 text-4xl font-bold' : 'text-xl font-bold';

    return (
        <div
            className={
                size === 'large'
                    ? 'flex items-start gap-4'
                    : 'flex items-start gap-2'
            }
        >
            {(
                [
                    ['hour', hours, 'Stunde', 'Std'],
                    ['minute', minutes, 'Minute', 'Min'],
                ] as const
            ).map(([unit, digit, full, short], index) => (
                <div
                    key={unit}
                    className={
                        size === 'large'
                            ? 'flex items-center gap-4'
                            : 'flex items-center gap-2'
                    }
                >
                    {index === 1 && (
                        <span className={colon} aria-hidden="true">
                            :
                        </span>
                    )}
                    <div className="flex flex-col items-center gap-1">
                        <button
                            type="button"
                            onClick={() => onChange(shift(value, unit, 1))}
                            aria-label={`${label}: ${full} erhöhen`}
                            className={STEPPER_BUTTON}
                        >
                            <ChevronUp className="size-4" aria-hidden="true" />
                        </button>
                        <span
                            className={digits}
                            aria-label={`${label}: ${full} ${digit}`}
                        >
                            {digit}
                        </span>
                        {size === 'large' && (
                            <span className="text-xs text-muted-foreground">
                                {short}
                            </span>
                        )}
                        <button
                            type="button"
                            onClick={() => onChange(shift(value, unit, -1))}
                            aria-label={`${label}: ${full} verringern`}
                            className={STEPPER_BUTTON}
                        >
                            <ChevronDown
                                className="size-4"
                                aria-hidden="true"
                            />
                        </button>
                    </div>
                </div>
            ))}
        </div>
    );
}
