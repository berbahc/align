import type { MeasureUnit, MeasureUnitOption } from '@/types';

/**
 * Der Umfang als fertige Zeile: „20 Min", „1,5 L", „10 Seiten".
 *
 * Spiegelt `MeasureUnit::format()` im Backend. Doppelt gehalten aus demselben
 * Grund wie `formatWeekdays()`: Der Wizard zeigt die Zeile, bevor der Server
 * sie je gesehen hat — beide müssen dasselbe ergeben, sonst springt der Text
 * beim Speichern.
 *
 * Die Grenzen und Schrittweiten stehen dagegen **nicht** hier, sondern kommen
 * über `units` aus `MeasureUnit::options()`. Nur so bleibt der Stepper mit der
 * Validierung deckungsgleich.
 */
export function formatMeasure(
    amount: number | null,
    unit: MeasureUnit | null,
    units: MeasureUnitOption[],
): string | null {
    if (amount === null || unit === null) {
        return null;
    }

    const option = units.find((candidate) => candidate.value === unit);

    if (option === undefined) {
        return null;
    }

    // Ganze Zahlen verlieren ihre Nachkommastelle („2 L", nicht „2,0 L"), der
    // Rest bekommt das deutsche Komma — die App-Locale ist nicht deutsch, die
    // Oberfläche schon.
    const rounded = Math.round(amount * 10) / 10;
    const number = Number.isInteger(rounded)
        ? String(rounded)
        : String(rounded).replace('.', ',');

    return `${number} ${option.short}`;
}

/**
 * Den Wert um einen Schritt verschieben, innerhalb der Grenzen der Einheit.
 *
 * Gerundet wird auf eine Nachkommastelle, weil 0,5er-Schritte in Gleitkomma
 * sonst „1.7999999999999998" ergeben können.
 */
export function shiftMeasure(
    amount: number,
    option: MeasureUnitOption,
    direction: 1 | -1,
): number {
    const stepped = amount + direction * option.step;

    return (
        Math.round(Math.min(Math.max(stepped, option.min), option.max) * 10) /
        10
    );
}
