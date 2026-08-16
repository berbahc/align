import { Minus, Plus, X } from 'lucide-react';
import { CHOICE_TILE, STEPPER_BUTTON } from '@/components/schedule-picker';
import { shiftMeasure } from '@/lib/measure';
import { cn } from '@/lib/utils';
import type { MeasureUnit, MeasureUnitOption } from '@/types';

/**
 * Der Umfang einer Gewohnheit — Menge und Einheit.
 *
 * Bis hierher steckte die Zahl im Titel („20 Minuten spazieren") und war damit
 * eine fremde Vorgabe. Genau sie ist aber der individuellste Teil: Der eine
 * geht 20 Minuten, die andere 45.
 *
 * Zwei Zustände, weil ein Umfang immer freiwillig ist. Ohne Einheit steht hier
 * nur ein leises Angebot; „Treppe statt Aufzug" misst sich nicht, und eine
 * erfundene Zahl wäre dort schlechter als gar keine. Mit Einheit steht der
 * Stepper da — samt Weg zurück.
 */
export function MeasurePicker({
    units,
    amount,
    unit,
    onChange,
}: {
    units: MeasureUnitOption[];
    amount: number | null;
    unit: MeasureUnit | '';
    onChange: (amount: number | null, unit: MeasureUnit | '') => void;
}) {
    const option = units.find((candidate) => candidate.value === unit);

    if (option === undefined || amount === null) {
        return (
            <div className="flex flex-wrap gap-2">
                {units.map((candidate) => (
                    <button
                        key={candidate.value}
                        type="button"
                        onClick={() =>
                            // Die Untergrenze ist der ehrlichste Startwert: Sie
                            // ist die kleinste Menge, die die Einheit zulässt,
                            // und „klein anfangen" ist ohnehin die Empfehlung
                            // des Schrittes, in dem das hier steht.
                            onChange(candidate.min, candidate.value)
                        }
                        className={cn(
                            CHOICE_TILE,
                            'border-dashed border-border px-3 py-2 text-xs text-muted-foreground hover:border-secondary',
                        )}
                    >
                        {candidate.label}
                    </button>
                ))}
            </div>
        );
    }

    return (
        <div className="flex items-center justify-between gap-3 rounded-2xl bg-card px-4 py-3">
            <div className="flex items-center gap-3">
                <button
                    type="button"
                    disabled={amount <= option.min}
                    onClick={() =>
                        onChange(shiftMeasure(amount, option, -1), unit)
                    }
                    aria-label={`${option.label} verringern`}
                    className={STEPPER_BUTTON}
                >
                    <Minus className="size-4" aria-hidden="true" />
                </button>

                <span className="min-w-24 text-center text-lg leading-none font-semibold tabular-nums">
                    {String(amount).replace('.', ',')}{' '}
                    <span className="text-sm font-normal text-muted-foreground">
                        {option.label}
                    </span>
                </span>

                <button
                    type="button"
                    disabled={amount >= option.max}
                    onClick={() =>
                        onChange(shiftMeasure(amount, option, 1), unit)
                    }
                    aria-label={`${option.label} erhöhen`}
                    className={STEPPER_BUTTON}
                >
                    <Plus className="size-4" aria-hidden="true" />
                </button>
            </div>

            <button
                type="button"
                onClick={() => onChange(null, '')}
                aria-label="Umfang entfernen"
                className={STEPPER_BUTTON}
            >
                <X className="size-4" aria-hidden="true" />
            </button>
        </div>
    );
}
