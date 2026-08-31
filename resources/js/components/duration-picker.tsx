import { Minus, Plus } from 'lucide-react';
import { STEPPER_BUTTON } from '@/lib/interaction';
import type { DurationLimits } from '@/types';

/**
 * Die Dauer einer Gewohnheit, in Minuten — immer da, nie leer.
 *
 * Der Vorgänger (MeasurePicker) kannte vier Einheiten und den Zustand „ohne
 * Umfang". Beides gibt es nicht mehr: Der Katalog enthält nur Aktivitäten,
 * die eine Spanne im Tag belegen, und ohne Dauer wüsste das Time-Blocking
 * nicht, wann der Platz wieder frei ist. Der Startwert kommt aus der Vorlage
 * und ist ein Angebot — die Zahl ist der individuellste Teil der Gewohnheit.
 */
export function DurationPicker({
    minutes,
    limits,
    onChange,
}: {
    minutes: number;
    limits: DurationLimits;
    onChange: (minutes: number) => void;
}) {
    function shift(direction: 1 | -1) {
        const stepped = minutes + direction * limits.step;

        onChange(Math.min(Math.max(stepped, limits.min), limits.max));
    }

    return (
        <div className="flex items-center justify-center gap-3 rounded-2xl bg-card px-4 py-3">
            <button
                type="button"
                disabled={minutes <= limits.min}
                onClick={() => shift(-1)}
                aria-label="Dauer verringern"
                className={STEPPER_BUTTON}
            >
                <Minus className="size-4" aria-hidden="true" />
            </button>

            <span className="min-w-28 text-center text-lg leading-none font-semibold tabular-nums">
                {minutes}{' '}
                <span className="text-sm font-normal text-muted-foreground">
                    Minuten
                </span>
            </span>

            <button
                type="button"
                disabled={minutes >= limits.max}
                onClick={() => shift(1)}
                aria-label="Dauer erhöhen"
                className={STEPPER_BUTTON}
            >
                <Plus className="size-4" aria-hidden="true" />
            </button>
        </div>
    );
}
