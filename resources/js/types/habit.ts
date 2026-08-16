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

/** Die Einheit, in der der Umfang einer Gewohnheit gemessen wird. */
export type MeasureUnit = 'minutes' | 'pages' | 'liters' | 'times';

/**
 * Eine Einheit mit ihren Grenzen — kommt als Prop aus `MeasureUnit::options()`.
 *
 * Schrittweite und Grenzen stehen bewusst nicht als TS-Konstanten hier: Der
 * Stepper im Browser und die Validierung auf dem Server müssen dieselben Werte
 * benutzen, und dafür darf es nur eine Quelle geben.
 */
export interface MeasureUnitOption {
    value: MeasureUnit;
    /** Ausgeschrieben, für den Stepper: „Minuten". */
    label: string;
    /** Kurzform, für die Zeile in der Liste: „Min". */
    short: string;
    step: number;
    min: number;
    max: number;
}

export interface Habit {
    id: number;
    title: string;
    /** Der Wann-Teil, fertig formatiert: „nach dem Aufstehen" oder „17:00 · Mo–Fr". */
    scheduleLabel: string;
    behaviorType: BehaviorType;
    /** Der Umfang als fertige Zeile („20 Min", „1,5 L"), sonst null. */
    measureLabel: string | null;
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
    /** Der Umfang als fertige Zeile („20 Min", „1,5 L"), sonst null. */
    measureLabel: string | null;
    /** Nur bei fester Uhrzeit lässt sich eine Erinnerung setzen. */
    canRemind: boolean;
    reminderEnabled: boolean;
    /**
     * Wann die Gewohnheit das nächste Mal ansteht: „heute", „morgen",
     * „am Freitag" — die Größe, nach der die Liste sortiert ist. Null, solange
     * kein Wochentag gewählt ist.
     */
    nextOccurrence: string | null;
    /** Trennt die beiden Blöcke der Liste: steht heute an oder später. */
    dueToday: boolean;
    /**
     * Die laufende Serie als fertige Zeile („12× in Folge"), sonst null.
     *
     * Gezählt werden vorgesehene Termine, nicht Kalendertage — eine
     * Mo–Fr-Gewohnheit bricht am Wochenende nicht.
     */
    streak: string | null;
}

/**
 * Eine Gewohnheit als Vorlage, aus der eine eigene werden kann.
 *
 * Nur der Bauplan, nicht die Gewohnheit: Der Warum-Satz und der kleinste
 * Schritt reisen nicht mit, weil sie zu einer Person gehören und nicht zu einer
 * Gewohnheit. Der Verlauf ohnehin nicht — beim Übernehmen beginnt Tag eins.
 *
 * Der Umfang reist mit: Er beschreibt, was gemacht wird, nicht warum — und
 * lässt sich nach dem Übernehmen umstellen.
 */
export interface HabitBlueprint {
    title: string;
    behaviorType: BehaviorType;
    targetAmount: number | null;
    targetUnit: MeasureUnit | null;
    /** Der Umfang als fertige Zeile, für die Vorschau im Übernahme-Sheet. */
    measureLabel: string | null;
    scheduleType: ScheduleType;
    triggerSituation: string | null;
    /** Feste Uhrzeit im Format „17:00", sonst null. */
    scheduledTime: string | null;
    scheduledDays: Weekday[] | null;
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
    /**
     * Die Zeile im Gedächtnis der KI, zu der dieser Vorschlag gehört.
     *
     * Reist beim Übernehmen zurück, damit der Server weiß, welcher der
     * angebotenen Zeitpunkte es geworden ist — ein Textvergleich wäre geraten.
     */
    id: number;
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
