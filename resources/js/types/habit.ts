import type { AppointmentDay } from './friendship';

export type BehaviorType = 'nutrition' | 'movement' | 'learning' | 'other';

/**
 * Wie eine Gewohnheit im Tag verankert ist.
 *
 * `dynamic` hängt an einer Situation („nach dem Aufstehen") und gilt an jedem
 * Tag. `fixed` hängt an einer Uhrzeit und gilt nur an den gewählten Wochentagen
 * — nur diese Form lässt sich erinnern.
 *
 * `chained` hängt an einer anderen Gewohnheit und beginnt, wo die aufhört —
 * das Domino-Prinzip aus time-blocking.md. Ihren Platz im Tag leiht sie sich.
 *
 * `opportunistic` hat gar keinen Platz im Tag: „Treppe statt Aufzug" ergibt
 * sich, wo die Gelegenheit auftaucht. Solche Gewohnheiten stehen nicht auf der
 * Tagesachse und werden nicht als Quote gemessen — versäumt hat nichts, wer an
 * keinem Aufzug vorbeikam.
 */
export type ScheduleType = 'dynamic' | 'fixed' | 'chained' | 'opportunistic';

/** ISO-Wochentag: 1 = Montag … 7 = Sonntag. */
export type Weekday = 1 | 2 | 3 | 4 | 5 | 6 | 7;

/**
 * Eine Gewohnheit, an die sich eine andere anhängen lässt.
 *
 * `startsAt` ist die Uhrzeit, zu der der Anschluss anfinge — null, wenn die
 * Kette an einer Situation hängt und niemand die Uhr kennt.
 */
export interface ChainCandidate {
    id: number;
    title: string;
    anchor: string;
    startsAt: string | null;
}

/**
 * Ein belegtes Fenster im Tag — Grundlage des Überschneidungshinweises.
 *
 * `from === to` heißt: Zeitpunkt ohne bekannte Dauer. Er belegt nur seine eine
 * Minute, weil niemand weiß, wie lange er wirklich dauert.
 */
export interface BusySlot {
    id: number;
    title: string;
    days: Weekday[];
    from: string;
    to: string;
}

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
    /**
     * Die Tage, an denen sich diese Gewohnheit zu zweit angehen lässt.
     *
     * Steht an der Zeile, nicht an der Seite: Eine Mo–Fr-Gewohnheit lässt sich
     * freitags nicht für morgen verabreden, eine tägliche schon.
     */
    appointmentDays: AppointmentDay[];
}

/**
 * Der Block der Gewohnheiten-Liste.
 *
 * `whenever` ist keine dritte Zeitangabe, sondern ihr Gegenteil: Was sich
 * ergibt, steht an keinem Tag an und kann an jedem vorkommen. Unter „später"
 * stünde es falsch — später heißt, dass ein Termin bevorsteht.
 */
export type HabitGroup = 'today' | 'later' | 'whenever';

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
    /** Der Block der Liste, in dem die Zeile steht — vom Server bestimmt. */
    group: HabitGroup;
    /**
     * Die laufende Serie als fertige Zeile („12× in Folge"), sonst null.
     *
     * Gezählt werden vorgesehene Termine, nicht Kalendertage — eine
     * Mo–Fr-Gewohnheit bricht am Wochenende nicht.
     */
    streak: string | null;
    /**
     * Die blanke Zahl statt einer Serie („7× in 30 Tagen"), sonst null.
     *
     * Nur für Gewohnheiten, die sich ergeben: Zwei Tage ohne Gelegenheit würden
     * jede Serie reißen lassen, obwohl nichts versäumt wurde.
     */
    recentCount: string | null;
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
    /** Der Umfang als fertige Zeile („20 Min", „10 Seiten"), sonst null. */
    measureLabel: string | null;
    /**
     * Die belegte Spanne („17:00 – 17:20"), sonst null.
     *
     * Gibt es nur, wo eine feste Uhrzeit auf eine Dauer trifft — nur Minuten
     * sind eine Dauer, „10 Seiten" belegt keinen Platz im Tag.
     */
    timeRange: string | null;
    behaviorType: BehaviorType;
    smallestStep: string | null;
    completed: boolean;
    /** Beendete Gewohnheiten bleiben in ihrer Vergangenheit sichtbar. */
    graduated: boolean;
    /**
     * Lässt sich der Zeitpunkt überhaupt verbessern?
     *
     * Nur wo es einen gibt. Was sich ergibt, hat keinen — der
     * `✦ Passt der Zeitpunkt?`-Chip hätte dort nichts anzubieten.
     */
    adjustable: boolean;
    /**
     * Die Gewohnheit, an der dieser Block hängt — sonst null.
     *
     * Liegt sie direkt darüber, zieht die Achse einen Steg dazwischen: Was
     * zusammengehört, soll auch zusammenhängend aussehen.
     */
    chainedToId: number | null;
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
