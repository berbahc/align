import type { Weekday } from './habit';

/** Die Art einer Veranstaltung — für das Auge, nicht für die Rechnung. */
export type CourseKind = 'vorlesung' | 'uebung' | 'seminar' | 'sonstiges';

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

/** Der Zeitraum, in dem der Plan gilt. */
export interface SemesterPlan {
    title: string;
    /** „YYYY-MM-DD". */
    startsOn: string;
    endsOn: string;
    /** „13.10. – 07.02." */
    rangeLabel: string;
    /** Läuft er gerade? Sonst belegen seine Kurse im Kalender keine Zeit. */
    isCurrent: boolean;
    /** Fängt er erst noch an? Dann steht der Plan schon, wirkt aber noch nicht. */
    startsInFuture: boolean;
    /** „1. Oktober 2026" — fertig formatiert. */
    startsOnLabel: string;
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
    /** Die echte Kennung — für Ändern, Ausfall und Löschen. */
    courseId: number;
    title: string;
    courseKind: CourseKind;
    /** „Vorlesung", „Übung" … — fertig formatiert. */
    kindLabel: string;
    weekday: Weekday;
    /** „10:00" — für das Formular. */
    startsAt: string;
    endsAt: string;
    startMinute: number;
    durationMinutes: number;
    /** „08:00 – 09:30" — die Spanne an diesem Tag. */
    timeRange: string;
    location: string | null;
    /** Liegt der Kurs an diesem Tag ausnahmsweise hier? */
    moved: boolean;
    exceptions: CourseExceptionRow[];
}

/**
 * Eine Gewohnheit, die durch den Stundenplan ihren Platz verloren hat.
 *
 * Nicht verloren — geparkt. Die alte Uhrzeit bleibt als Erinnerung, damit
 * der neue Platz nah daran liegen kann.
 */
export interface DisplacedHabit {
    id: number;
    title: string;
    /** „10:15" — wann sie lief; null bei einer Gewohnheit ohne eigene Zeit. */
    previousTime: string | null;
    /** Die fertige Zeile: „braucht einen neuen Platz · lief bisher 10:15". */
    previousLabel: string;
    /** „YYYY-MM-DD", ab wann der Platz weg ist — null, wenn schon jetzt. */
    from: string | null;
    /** „1. Oktober" — fertig formatiert. */
    fromLabel: string | null;
}

/** Ein neuer Platz, den die KI für eine verdrängte Gewohnheit gefunden hat. */
export interface NewPlace {
    id: number;
    title: string;
    /** Der gemerkte Vorschlag — reist beim Übernehmen zurück, damit die KI weiß, was genommen wurde. */
    suggestionId: number;
    /** Wo sie lag, als Zeile: „braucht einen neuen Platz · lief bisher 10:15". */
    previousLabel: string;
    /** „11:45". */
    time: string;
    days: Weekday[];
    /** „11:45 · Mo–Fr" — dieselbe Form wie jeder Anker. */
    label: string;
    /** „11:45 – 12:15". */
    timeRange: string;
    reason: string;
}

/** Eine verdrängte Gewohnheit, für die es keinen Vorschlag gibt. */
export interface UnplacedHabit {
    id: number;
    title: string;
    previousLabel: string;
    /** Warum nicht — kein Fenster, oder vom Modell weggelassen. */
    message: string;
}
