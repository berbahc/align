<?php

namespace App\Enums;

/**
 * Die Art einer Veranstaltung im Stundenplan.
 *
 * Für die Rechnung ist sie gleichgültig — belegte Zeit ist belegte Zeit, und
 * eine Übung sperrt den Kalender genauso wie eine Vorlesung. Sie steht für das
 * Auge da: Ein Stundenplan aus acht Zeilen „Analysis I" wäre richtig und
 * trotzdem unlesbar.
 *
 * Vier Fälle, weil der vierte der Ausweg ist. Wer einen Sprachkurs, ein
 * Tutorium oder eine Sprechstunde einträgt, soll nicht daran scheitern, dass
 * die Liste sein Wort nicht kennt.
 *
 * Kein Praktikum: Das ist kein Kurs neben anderen, sondern nimmt ein halbes
 * Jahr am Stück — dann fällt der Stundenplan als Ganzes weg, nicht eine Zeile
 * darin.
 */
enum CourseKind: string
{
    case Vorlesung = 'vorlesung';
    case Uebung = 'uebung';
    case Seminar = 'seminar';
    case Sonstiges = 'sonstiges';

    /**
     * Die Arten als Wahl für die Oberfläche — die eine Quelle der Liste.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $kind): array => [
            'value' => $kind->value,
            'label' => $kind->label(),
        ], self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Vorlesung => 'Vorlesung',
            self::Uebung => 'Übung',
            self::Seminar => 'Seminar',
            self::Sonstiges => 'Sonstiges',
        };
    }
}
