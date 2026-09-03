import type { AppointmentDay } from './friendship';

export type BehaviorType = 'nutrition' | 'movement' | 'learning' | 'other';

/**
 * Die Kategorie des Gewohnheitskatalogs — der Bereich des Studienalltags.
 *
 * Spiegelt `HabitCategory` im Backend. Die Kategorie ist die Einstiegsfrage
 * des Wizards; die fachliche Einordnung für die Plateau-Schätzung bleibt
 * `BehaviorType` und hängt an der einzelnen Vorlage.
 */
export type HabitCategory = 'sport' | 'uni' | 'alltag' | 'erholung';

/** Eine Vorlage aus dem Katalog — mehr braucht die Kachel nicht. */
export interface HabitTemplateOption {
    key: string;
    title: string;
    /** Startwert des Dauer-Steppers, in Minuten. */
    defaultMinutes: number;
}

/** Eine Kategorie samt ihrer Vorlagen — kommt aus `HabitCategory::options()`. */
export interface HabitCategoryOption {
    value: HabitCategory;
    label: string;
    description: string;
    templates: HabitTemplateOption[];
}

/**
 * Schrittweite und Grenzen der Dauer — kommt aus `MeasureUnit::minutesLimits()`.
 *
 * Bewusst nicht als TS-Konstanten hier: Der Stepper im Browser und die
 * Validierung auf dem Server müssen dieselben Werte benutzen, und dafür darf
 * es nur eine Quelle geben.
 */
export interface DurationLimits {
    step: number;
    min: number;
    max: number;
}

/**
 * Wie eine Gewohnheit im Tag verankert ist.
 *
 * `dynamic` hängt an einer Situation („nach dem Aufstehen") und gilt an jedem
 * Tag. `fixed` hängt an einer Uhrzeit und gilt nur an den gewählten Wochentagen
 * — nur diese Form lässt sich erinnern.
 *
 * `chained` hängt an einer anderen Gewohnheit und beginnt, wo die aufhört —
 * das Domino-Prinzip aus time-blocking.md. Ihren Platz im Tag leiht sie sich.
 */
export type ScheduleType = 'dynamic' | 'fixed' | 'chained';

/** ISO-Wochentag: 1 = Montag … 7 = Sonntag. */
export type Weekday = 1 | 2 | 3 | 4 | 5 | 6 | 7;

/**
 * Ein Moment im Tag, den man als Auslöser wählen kann.
 *
 * `takenBy` trägt den Titel der Gewohnheit, die ihn schon hält — dann ist der
 * Moment vergeben. Eine Situation trägt genau eine Gewohnheit: zwei Dinge im
 * selben Moment sind kein Plan.
 */
export interface SituationChoice {
    situation: string;
    takenBy: string | null;
}

/**
 * Der Rahmen eines Wochentags: wann der Tag anfängt und wann er endet.
 *
 * Spiegelt `User::sleepWindows()`. Gewohnheiten lassen sich nur innerhalb
 * dieses Rahmens auf eine Uhrzeit legen — der Server weist alles andere ab,
 * die Oberfläche sagt es vorher.
 */
export interface SleepWindow {
    weekday: Weekday;
    /** „07:00" — ab hier ist der Tag wach. */
    wakeTime: string;
    /** „23:00" — ab hier ist er es nicht mehr. Vor `wakeTime` heißt: nach Mitternacht. */
    bedtime: string;
    /** Klingelt der Wecker an diesem Morgen? */
    alarmEnabled: boolean;
}

/**
 * Der geteilte Schlaf-Zustand für Hinweis und Wecker — auf jeder Seite.
 *
 * Drei Tage, nicht einer: Eine Schlafenszeit nach Mitternacht gehört zum
 * gestrigen Wochentag, und der Wecker von morgen kann klingeln, während der
 * Tab noch offen ist.
 */
export interface SleepShared {
    yesterday: SleepWindow;
    today: SleepWindow;
    tomorrow: SleepWindow;
    /** Die Erinnerung vor der Schlafenszeit — ein Schalter für die ganze Woche. */
    reminderEnabled: boolean;
    /** Vorlauf der Erinnerung in Minuten, aus `SleepSchedule::BedtimeReminderLeadMinutes`. */
    leadMinutes: number;
}

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

/**
 * Die Einheit, in der der Umfang einer Gewohnheit gemessen wird.
 *
 * Neu vergeben wird nur noch `minutes` — der Katalog kennt nur Aktivitäten
 * mit Dauer. Die übrigen Werte existieren in alten Gewohnheiten weiter.
 */
export type MeasureUnit = 'minutes' | 'pages' | 'liters' | 'times';

export interface Habit {
    id: number;
    title: string;
    /** Der Wann-Teil, fertig formatiert: „nach dem Aufstehen" oder „17:00 · Mo–Fr". */
    scheduleLabel: string;
    behaviorType: BehaviorType;
    /** Die Dauer als fertige Zeile („20 Min"), sonst null. */
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

/** Der Block der Gewohnheiten-Liste: steht heute an oder später. */
export type HabitGroup = 'today' | 'later';

/** Eine Gewohnheit in der Verwaltungsansicht — dort zählt die Planung, nicht der heutige Tag. */
export interface ManagedHabit {
    id: number;
    title: string;
    scheduleLabel: string;
    behaviorType: BehaviorType;
    /** Die Dauer als fertige Zeile („20 Min"), sonst null. */
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
     * Der Katalog-Bereich („Sport & Bewegung"), sonst null.
     *
     * Null bei Gewohnheiten aus der Zeit der freien Eingabe — sie laufen
     * weiter, gehören aber zu keinem Bereich.
     */
    categoryLabel: string | null;
}

/**
 * Eine Gewohnheit als Vorlage, aus der eine eigene werden kann.
 *
 * Nur der Bauplan, nicht die Gewohnheit: Der Warum-Satz und der kleinste
 * Schritt reisen nicht mit, weil sie zu einer Person gehören und nicht zu einer
 * Gewohnheit. Der Verlauf ohnehin nicht — beim Übernehmen beginnt Tag eins.
 */
export interface HabitBlueprint {
    title: string;
    /**
     * Die Katalog-Vorlage hinter der Gewohnheit.
     *
     * Null bei Gewohnheiten aus der Zeit der freien Eingabe — die lassen sich
     * nicht mehr übernehmen, weil es außerhalb des Katalogs kein Anlegen gibt.
     */
    templateKey: string | null;
    behaviorType: BehaviorType;
    /** Die Dauer in Minuten, mit der die Übernahme startet. */
    durationMinutes: number;
    /** Die Dauer als fertige Zeile, für die Vorschau im Übernahme-Sheet. */
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
    /**
     * Wo der Block im Stundenraster liegt, als Minute seit Mitternacht.
     *
     * Null nur, wenn die Gewohnheit gar keine Stelle im Tag hat — dann gibt es
     * nichts hinzulegen.
     */
    startMinute: number | null;
    /** Wie hoch er ist. Ohne Dauer zeichnet das Raster eine Mindesthöhe. */
    durationMinutes: number | null;
    /**
     * Ist die Stelle eine Uhrzeit oder eine Näherung?
     *
     * „17:00" ist eine Zusage, „nach dem Frühstück" eine Gegend. Das Raster
     * zeichnet das Zweite gestrichelt und ohne Uhrzeit — sonst behauptete die
     * Zeichnung etwas, das die Gewohnheit nicht sagt.
     */
    exact: boolean;
    /**
     * Liegt die Gewohnheit heute ausnahmsweise hier?
     *
     * Dann steht im Block-Sheet der Weg zurück. Ohne ihn wäre eine
     * Verschiebung nur durch erneutes Ziehen rückgängig zu machen.
     */
    shifted: boolean;
    /**
     * Die Planungsart — was ein Zug antasten würde.
     *
     * Der Anker allein verriete es nicht: Ein für heute verschobener Moment
     * sieht aus wie eine feste Uhrzeit.
     */
    scheduleType: ScheduleType;
    /** Die Dauer als fertige Zeile („20 Min"), sonst null. */
    measureLabel: string | null;
    /**
     * Die belegte Spanne („17:00 – 17:20"), sonst null.
     *
     * Gibt es nur, wo eine feste Uhrzeit auf eine Dauer trifft.
     */
    timeRange: string | null;
    behaviorType: BehaviorType;
    smallestStep: string | null;
    /** Der eigene Warum-Satz — erscheint nur im Starthilfe-Sheet. */
    motivation: string | null;
    completed: boolean;
    /** Beendete Gewohnheiten bleiben in ihrer Vergangenheit sichtbar. */
    graduated: boolean;
    /**
     * Die Gewohnheit, an der dieser Block hängt — sonst null.
     *
     * Liegt sie direkt darüber, zieht die Achse einen Steg dazwischen: Was
     * zusammengehört, soll auch zusammenhängend aussehen.
     */
    chainedToId: number | null;
}

/**
 * Ein Tag im Monatsraster.
 *
 * Punkte statt Prozent: `planned` sagt, wie viele Gewohnheiten anstanden,
 * `done`, wie viele davon liefen. Eine Quote über einem einzelnen Tag wäre
 * eine Note, und der Monat soll ein Rückblick sein, kein Zeugnis.
 */
export interface MonthDay {
    /** „YYYY-MM-DD" — zugleich das Ziel des Tippens. */
    date: string;
    dayOfMonth: number;
    /** Gehört der Tag zum gezeigten Monat oder füllt er nur die Woche auf? */
    inMonth: boolean;
    isToday: boolean;
    /** Ein künftiger Tag ist nicht offen, er ist noch nicht dran. */
    isFuture: boolean;
    planned: number;
    done: number;
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
