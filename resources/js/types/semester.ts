import type { Weekday } from './habit';

/** Die Art einer Veranstaltung — für das Auge, nicht für die Rechnung. */
export type CourseKind =
    'vorlesung' | 'uebung' | 'seminar' | 'praktikum' | 'sonstiges';

/** Eine Art als Wahl im Formular. */
export interface CourseKindOption {
    value: CourseKind;
    label: string;
}

/**
 * Was an einem einzelnen Datum anders ist als jede Woche.
 *
 * Ohne `timeRange` fällt der Kurs an dem Tag aus; mit ihm liegt er dort
 * woanders.
 */
export interface CourseExceptionRow {
    /** „YYYY-MM-DD". */
    onDate: string;
    /** „Mo, 7. September" — fertig formatiert. */
    dateLabel: string;
    cancelled: boolean;
    timeRange: string | null;
}

/** Ein Kurs als Zeile im Semesterplan. */
export interface CourseRow {
    id: number;
    title: string;
    kind: CourseKind;
    /** „Vorlesung", „Übung" … — fertig formatiert. */
    kindLabel: string;
    weekday: Weekday;
    /** „10:00" — für das Formular. */
    startsAt: string;
    endsAt: string;
    /** „10:00 – 11:30" — für die Anzeige. */
    timeRange: string;
    location: string | null;
    exceptions: CourseExceptionRow[];
}

/** Der Zeitraum, in dem der Plan gilt. */
export interface SemesterPlan {
    title: string;
    /** „YYYY-MM-DD". */
    startsOn: string;
    endsOn: string;
    /** „13.10. – 07.02." */
    rangeLabel: string;
    /** Läuft er gerade? Sonst belegen seine Kurse keine Zeit mehr. */
    isCurrent: boolean;
}

/**
 * Ein Kurs als Block im Stundenraster.
 *
 * Weder abhakbar noch verschiebbar: Er ist kein Vorsatz, sondern eine
 * Tatsache. `kind` unterscheidet ihn im Raster von einer Gewohnheit.
 */
export interface CourseBlock {
    kind: 'course';
    /** Negativ — Gewohnheiten tragen positive Kennungen, die Verabredung die 0. */
    id: number;
    title: string;
    courseKind: CourseKind;
    /** „Vorlesung", „Übung" … — fertig formatiert. */
    kindLabel: string;
    startMinute: number;
    durationMinutes: number;
    /** „08:00 – 09:30". */
    timeRange: string;
    location: string | null;
    /** Liegt der Kurs an diesem Tag ausnahmsweise hier? */
    moved: boolean;
}
