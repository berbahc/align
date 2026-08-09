export type BehaviorType = 'nutrition' | 'movement' | 'learning' | 'other';

/**
 * Wie eine Gewohnheit im Tag verankert ist.
 *
 * `dynamic` hängt an einer Situation („nach dem Aufstehen") und gilt an jedem
 * Tag. `fixed` hängt an einer Uhrzeit und gilt nur an den gewählten Wochentagen
 * — nur diese Form lässt sich erinnern.
 */
export type ScheduleType = 'dynamic' | 'fixed';

/** ISO-Wochentag: 1 = Montag … 7 = Sonntag. */
export type Weekday = 1 | 2 | 3 | 4 | 5 | 6 | 7;

export interface Habit {
    id: number;
    title: string;
    /** Der Wann-Teil, fertig formatiert: „nach dem Aufstehen" oder „17:00 · Mo–Fr". */
    scheduleLabel: string;
    behaviorType: BehaviorType;
    focusMinutes: number | null;
    /**
     * Der vorbereitete erste Handgriff („Stell das Glas ans Bett").
     * Null, solange keiner formuliert wurde — der Schritt ist optional.
     */
    smallestStep: string | null;
    /** Der eigene Warum-Satz; erscheint nur im Starthilfe-Sheet. */
    motivation: string | null;
    /** Uhrzeit der heutigen Erfüllung („07:30"), sonst null. */
    completedAt: string | null;
    /**
     * Wer heute mitmacht — null, wenn die Gewohnheit allein ansteht.
     *
     * Bewusst ohne Fortschritt der anderen Person: Das wäre durch die
     * Hintertür doch ein Dauerstatus (community_feature3.md §6).
     */
    companion: { name: string; initial: string } | null;
    /** Die zugesagte Verabredung von heute, zum Auflösen. */
    appointmentId: number | null;
}

/** Eine Gewohnheit in der Verwaltungsansicht — dort zählt die Planung, nicht der heutige Tag. */
export interface ManagedHabit {
    id: number;
    title: string;
    scheduleLabel: string;
    behaviorType: BehaviorType;
    /** Nur bei fester Uhrzeit lässt sich eine Erinnerung setzen. */
    canRemind: boolean;
    reminderEnabled: boolean;
}

/**
 * Ein von der KI vorgeschlagener anderer Zeitpunkt.
 *
 * Vorgeschlagen wird immer in der Form, die der Nutzer selbst gewählt hat:
 * eine situative Gewohnheit bekommt `situation`, eine feste `time` und `days`.
 * Die Planungsart zu wechseln ist keine Anpassung, sondern eine andere
 * Entscheidung.
 */
export interface AnchorAlternative {
    situation?: string;
    time?: string;
    days?: Weekday[];
    /** Ein Satz, warum dieser Zeitpunkt tragen könnte. */
    reason: string;
    /** Wo der Block läge, würde man ihn übernehmen — vom Server bestimmt. */
    anchorHour: number;
}

/**
 * Eine Gewohnheit an ihrer Stelle im Tag — die Kalenderansicht.
 *
 * Anders als bei der Tagesliste gehört der Zustand hier zu einem bestimmten
 * Datum, nicht zu „heute": `completed` meint den angezeigten Tag.
 */
export interface CalendarBlock {
    id: number;
    title: string;
    /** Der Anker als Kopfzeile: „nach dem Aufstehen" oder „17:00 · Mo–Fr". */
    anchor: string;
    /** Die Stelle im Tag als Stunde — sortiert die Achse. */
    anchorHour: number;
    behaviorType: BehaviorType;
    smallestStep: string | null;
    completed: boolean;
    /** Beendete Gewohnheiten bleiben in ihrer Vergangenheit sichtbar. */
    graduated: boolean;
}

/**
 * Eine beendete Gewohnheit im Archiv.
 *
 * Sie zählt nicht gegen das Limit von fünf und löst nichts mehr aus, behält
 * aber jeden abgehakten Tag — `completionCount` beziffert, was ein endgültiges
 * Löschen kosten würde.
 */
export interface GraduatedHabit {
    id: number;
    title: string;
    scheduleLabel: string;
    behaviorType: BehaviorType;
    /** Tag des Beendens, formatiert als „08.08.2026". */
    graduatedOn: string;
    completionCount: number;
}

/**
 * Das Nötigste, damit der Erinnerungs-Hook auf jeder Seite arbeiten kann.
 * Kommt als geteilte Eigenschaft aus dem Backend.
 */
export interface HabitReminder {
    id: number;
    title: string;
    /** Feste Uhrzeit im Format „17:00". */
    scheduledTime: string;
    scheduledDays: Weekday[];
    /** Heute schon abgehakt? Dann entfällt die Erinnerung. */
    completedToday: boolean;
}
