<?php

namespace App\Enums;

/**
 * Die Einheit, in der der Umfang einer Gewohnheit gemessen wird.
 *
 * Bis hierher steckte die Menge im Titel: „20 Minuten spazieren", „2 Liter
 * Wasser trinken". Das las sich konkret, war aber eine fremde Zahl — und genau
 * die Zahl ist der individuellste Teil einer Gewohnheit. Wer 35 Minuten geht,
 * musste den Satz umschreiben.
 *
 * Der Umfang ist deshalb ein eigenes Feld, und diese Aufzählung ist seine
 * einzige Quelle: Schrittweite und Grenzen stehen hier, nicht im Browser. Die
 * Oberfläche bekommt sie über {@see options()} als Prop, damit ein geänderter
 * Höchstwert nicht an zwei Stellen nachgezogen werden muss.
 *
 * Vier Einheiten, nicht mehr: Sie decken die Vorschläge aus
 * {@see BehaviorType::suggestions()} ab und lassen sich in einer Reihe Chips
 * zeigen. Ein Umfang ist immer freiwillig — „Treppe statt Aufzug" hat keinen.
 */
enum MeasureUnit: string
{
    case Minutes = 'minutes';
    case Pages = 'pages';
    case Liters = 'liters';
    case Times = 'times';

    /**
     * @return list<array{value: string, label: string, short: string, step: float, min: float, max: float}>
     */
    public static function options(): array
    {
        return array_map(fn (self $unit): array => [
            'value' => $unit->value,
            'label' => $unit->label(),
            'short' => $unit->short(),
            'step' => $unit->step(),
            'min' => $unit->min(),
            'max' => $unit->max(),
        ], self::cases());
    }

    /**
     * Die ausgeschriebene Einheit — sie steht am Stepper, wo Platz ist.
     */
    public function label(): string
    {
        return match ($this) {
            self::Minutes => 'Minuten',
            self::Pages => 'Seiten',
            self::Liters => 'Liter',
            self::Times => 'Mal',
        };
    }

    /**
     * Die Kurzform für die Zeile in der Liste, wo sie hinter dem Titel steht.
     */
    public function short(): string
    {
        return match ($this) {
            self::Minutes => 'Min',
            self::Pages => 'Seiten',
            self::Liters => 'L',
            self::Times => 'Mal',
        };
    }

    /**
     * Um wie viel ein Tastendruck den Wert verschiebt.
     *
     * Minuten springen in Fünferschritten wie schon der Uhrzeit-Stepper —
     * Gewohnheiten brauchen keine Minutengenauigkeit. Liter in halben, weil
     * 1,5 Liter ein üblicheres Ziel ist als 1 oder 2.
     */
    public function step(): float
    {
        return match ($this) {
            self::Minutes => 5,
            self::Liters => 0.5,
            self::Pages, self::Times => 1,
        };
    }

    public function min(): float
    {
        return match ($this) {
            self::Minutes => 5,
            self::Liters => 0.5,
            self::Pages, self::Times => 1,
        };
    }

    /**
     * Die Obergrenze ist keine Bevormundung, sondern eine Plausibilitätsgrenze:
     * Sie fängt den Vertipper ab (240 statt 24 Liter), nicht den Ehrgeiz.
     */
    public function max(): float
    {
        return match ($this) {
            self::Minutes => 240,
            self::Pages => 100,
            self::Liters => 5,
            self::Times => 50,
        };
    }

    /**
     * Der Umfang als fertige Zeile: „20 Min", „1,5 L", „10 Seiten".
     *
     * Die App-Locale ist nicht deutsch, die Oberfläche schon — das Komma steht
     * deshalb hier und nicht in `number_format()`s Voreinstellung. Ganze Zahlen
     * verlieren ihre Nachkommastelle: „2 L", nicht „2,0 L".
     */
    public function format(float $amount): string
    {
        $rounded = round($amount, 1);

        $number = $rounded === floor($rounded)
            ? (string) (int) $rounded
            : str_replace('.', ',', (string) $rounded);

        return $number.' '.$this->short();
    }
}
