<?php

namespace App\Enums;

/**
 * Was einer Zusage im Weg steht.
 *
 * Die Unterscheidung entscheidet über den Ausweg und damit über den Satz auf
 * der Karte: Eine eigene Gewohnheit lässt sich für diesen einen Tag verlegen,
 * ein Kurs nicht, und gegen den eigenen Schlafrahmen hilft kein Verschieben,
 * sondern nur ein anderer Tag. Einen Ausweg anzubieten, den es nicht gibt, ist
 * schlimmer als keiner.
 *
 * Als Aufzählung und nicht als Wahrheitswert: „beweglich, ja oder nein" trug
 * zwei Fälle, aber es sind drei — und der dritte hat gar keinen Block, gegen
 * den er kollidiert.
 */
enum AppointmentConflictKind: string
{
    /** Eine eigene Gewohnheit. Sie kann für diesen Tag rücken. */
    case Habit = 'habit';

    /** Ein Kurs aus dem Semesterplan. Er rückt nicht. */
    case Course = 'course';

    /** Die Zeit liegt außerhalb des eigenen Tages — dort wird geschlafen. */
    case Night = 'night';
}
