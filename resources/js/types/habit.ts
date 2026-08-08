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
    /** Uhrzeit der heutigen Erfüllung („07:30"), sonst null. */
    completedAt: string | null;
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
