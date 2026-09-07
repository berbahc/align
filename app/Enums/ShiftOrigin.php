<?php

namespace App\Enums;

/**
 * Wer den Umzug eines Tages veranlasst hat.
 *
 * Der Unterschied ist nur beim Zurücknehmen wichtig, aber dort ist er
 * entscheidend: Wer seine Aufstehzeit für heute wieder zurücksetzt, will die
 * Züge los, die daraus entstanden sind — nicht die, die er an demselben Tag
 * von Hand gemacht hat. Ohne diese Spalte wäre beides dieselbe Zeile, und
 * das Zurücknehmen fräße Entscheidungen, die niemand zurückgenommen hat.
 */
enum ShiftOrigin: string
{
    /** Von Hand gelegt — im Raster gezogen oder für eine Verabredung. */
    case Manual = 'manual';

    /** Mitgezogen, weil der Rahmen dieses Tages ein anderer war. */
    case Frame = 'frame';
}
