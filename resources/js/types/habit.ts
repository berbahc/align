export type BehaviorType = 'nutrition' | 'movement' | 'learning' | 'other';

export interface Habit {
    id: number;
    title: string;
    /** Der Wenn-Teil der Wenn-Dann-Planung, z. B. „nach dem Aufstehen". */
    triggerSituation: string;
    behaviorType: BehaviorType;
    focusMinutes: number | null;
    /** Uhrzeit der heutigen Erfüllung („07:30"), sonst null. */
    completedAt: string | null;
}
