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
 * `takenDays` sind die Wochentage, an denen ihn schon eine andere Gewohnheit
 * hält, `takenBy` deren Titel. Eine Situation trägt **pro Tag** genau eine
 * Gewohnheit: zwei Dinge im selben Moment sind kein Plan — an verschiedenen
 * Tagen dagegen liegt nichts übereinander.
 */
export interface SituationChoice {
    situation: string;
    takenBy: string | null;
    takenDays: Weekday[];
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

export interface Habit {
    id: number;
    title: string;
    /** Der Wann-Teil, fertig formatiert: „nach dem Aufstehen" oder „17:00 · Mo–Fr". */
    scheduleLabel: string;
    /**
     * Dieselbe Auskunft, aufgeteilt — die Übersicht braucht beide Hälften
     * getrennt: Die Uhrzeit steht dort in einer eigenen Spalte, damit der Tag
     * von oben nach unten als Plan lesbar ist.
     *
     * Null, wo es keine Uhr gibt („nach dem Aufstehen"). Dann trägt
     * `repeatLabel` den Zeitpunkt.
     */
    timeLabel: string | null;
    /** Die Wiederholung: „Mo–Fr", „täglich", „nur an diesem Tag". */
    repeatLabel: string | null;
    behaviorType: BehaviorType;
    /**
     * Der Schlüssel der Katalog-Vorlage („tagebuch"), sonst null.
     *
     * Entscheidet das Icon: Die Vorlage weiß, worum es geht, die
     * Verhaltensrichtung ordnet nur fachlich ein. Null bei Gewohnheiten aus
     * der Zeit der freien Eingabe — die fallen auf die Richtung zurück.
     */
    templateKey: string | null;
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
     * `pending` heißt: gefragt, aber noch nicht zugesagt. Auch dieser Fall
     * gehört in die Zeile, sonst stünde die Gewohnheit zweimal auf der Seite
     * — einmal hier und einmal als Karte unter „Zusammen".
     *
     * Bewusst ohne Fortschritt der anderen Person: Das wäre durch die
     * Hintertür doch ein Dauerstatus (community_feature3.md §6).
     */
    companion: {
        name: string;
        initial: string;
        pending: boolean;
        /**
         * Die **eigene** Gewohnheit für „Nochmal ausmachen?" — null, solange
         * der eigene Anteil offen ist. Sagt nichts über die andere Person.
         */
        repeatHabitId: number | null;
        repeatDays: AppointmentDay[];
    } | null;
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
 * Wo eine Gewohnheit in der Liste steht: geparkt, heute fällig oder später.
 *
 * `displaced` steht zuerst: Was seinen Platz verloren hat, wartet auf eine
 * Entscheidung — und gehört damit vor das, was nur ansteht.
 *
 * **`today` und `later` erreichen die Oberfläche nicht mehr.** Zwei
 * Überschriften daraus zu machen hieße, die Frage der Übersicht ein zweites Mal
 * zu stellen; das Blatt beantwortet sie ohnehin genauer in der Spalte des
 * heutigen Tages. Beide Werte kommen weiter vom Server, weil dieselbe
 * Unterscheidung die Sortierung entscheidet — gezeichnet wird nur noch, ob
 * etwas geparkt ist.
 */
export type HabitGroup = 'displaced' | 'today' | 'later';

/** Eine Gewohnheit in der Verwaltungsansicht — dort zählt die Planung, nicht der heutige Tag. */
/**
 * Ein Tag im Rhythmusstreifen der Gewohnheiten-Liste.
 *
 * Drei Zustände aus zwei Feldern: erfüllt (`completed`), vorgesehen und offen
 * (`scheduled` ohne `completed`), gar nicht vorgesehen (weder noch). Der dritte
 * Fall ist der Grund, aus dem es zwei Felder braucht — ein Samstag ohne
 * Mo–Fr-Gewohnheit darf nicht wie ein versäumter Tag aussehen.
 */
export interface RhythmDay {
    /** „2026-09-05" — nur als stabiler Schlüssel der Liste. */
    date: string;
    /** Der Wochentag als Kürzel, „Mo" bis „So". */
    label: string;
    scheduled: boolean;
    completed: boolean;
}

export interface ManagedHabit {
    id: number;
    title: string;
    scheduleLabel: string;
    behaviorType: BehaviorType;
    /**
     * Der Schlüssel der Katalog-Vorlage („tagebuch"), sonst null.
     *
     * Entscheidet das Icon: Die Vorlage weiß, worum es geht, die
     * Verhaltensrichtung ordnet nur fachlich ein. Null bei Gewohnheiten aus
     * der Zeit der freien Eingabe — die fallen auf die Richtung zurück.
     */
    templateKey: string | null;
    /** Die Dauer als fertige Zeile („20 Min"), sonst null. */
    measureLabel: string | null;
    /** Nur bei fester Uhrzeit lässt sich eine Erinnerung setzen. */
    canRemind: boolean;
    reminderEnabled: boolean;
    /**
     * Wann die Gewohnheit das nächste Mal ansteht: „heute", „morgen",
     * „am Freitag" — die Größe, nach der die Liste sortiert ist. Null, solange
     * kein Wochentag gewählt ist.
     *
     * **Wird zurzeit nicht gezeichnet.** Die Zeile trug den Wert einmal vor dem
     * Zeitplan („morgen · 19:00 · Mo–Sa"). Er ist der Blick auf die nächsten
     * Stunden und gehört damit der Übersicht; hier zählt der Plan, und der
     * steht ohnehin in derselben Zeile. Der Wert bleibt im Zug, weil er die
     * Reihenfolge erklärt und billig ist — wer ihn wieder zeigen will, hat ihn.
     */
    nextOccurrence: string | null;
    /** Der Block der Liste, in dem die Zeile steht — vom Server bestimmt. */
    group: HabitGroup;
    /**
     * Die laufende Serie als fertige Zeile („12× in Folge"), sonst null.
     *
     * Gezählt werden vorgesehene Termine, nicht Kalendertage — eine
     * Mo–Fr-Gewohnheit bricht am Wochenende nicht.
     *
     * **Wird auf dieser Seite nicht mehr gezeichnet.** Die Serien stehen auf
     * der Übersicht als eigene Karten; hier standen sie ein zweites Mal, klein
     * unter der Bilanz. Der Wert bleibt im Zug, weil er billig ist und die
     * Zeile ihn jederzeit wieder zeigen könnte — wie {@see nextOccurrence}.
     */
    streak: string | null;
    /**
     * Ob die Serie dieser Gewohnheit von der Übersicht genommen wurde.
     *
     * Trägt genau einen Eintrag im ⋯-Menü: den Weg zurück. Das × auf der Karte
     * nimmt sie weg, hier kommt sie wieder.
     */
    streakHidden: boolean;
    /**
     * Die letzten sieben Tage als Streifen — das Element, das die Zeilen
     * voneinander unterscheidbar macht.
     */
    rhythm: RhythmDay[];
    /**
     * Die Konsistenz der letzten 30 Tage als die zwei Zahlen, aus denen sie
     * besteht: erledigte und vorgesehene Tage.
     *
     * Die zweite Zeitachse neben dem Streifen — der zeigt eine Woche, diese
     * Angabe einen Monat. Bewusst kein Prozentwert: Bei einer Gewohnheit mit
     * acht vorgesehenen Tagen im Fenster macht ein ausgelassenes Wochenende
     * daraus „75 %", was nach Note klingt. „6 von 8 Tagen" sagt dasselbe,
     * ohne zu urteilen.
     *
     * Null, solange es noch kein Fenster gibt.
     */
    consistency: {
        done: number;
        scheduled: number;
        /**
         * Wahr, wenn das Anlegedatum das Fenster beschnitten hat. Dann sind es
         * weniger als 30 Tage, und die Zeile muss das sagen: „1 von 1" ohne
         * diesen Hinweis verspricht einen Monat und zeigt einen Tag.
         */
        sinceStart: boolean;
    } | null;
    /**
     * Wann die Gewohnheit angefangen hat, als „06.08.2026".
     *
     * Dasselbe Datum, an dem auch das Fenster der Konsistenz beginnt. Beide
     * stehen im selben Kasten und dürfen sich nicht widersprechen.
     */
    startedOn: string | null;
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
 * Der Auftrag „mach diese fremde Gewohnheit zu deiner".
 *
 * Der Assistent zum Anlegen bekommt sie statt einer leeren Wahl: Die Vorlage
 * steht fest, der erste Schritt entfällt, alles andere wird geplant wie bei
 * jeder neuen Gewohnheit. Was beim Speichern beantwortet ist, reist mit —
 * die Absage-Notiz und die offene Anfrage.
 */
export interface HabitAdoption {
    blueprint: HabitBlueprint;
    noticeId: number | null;
    appointmentId: number | null;
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
    /**
     * Die Gewohnheit, an die angeknüpft wird — die dritte Form eines
     * Zeitpunkts.
     *
     * Eine bestehende Gewohnheit ist der zuverlässigste Auslöser, den es gibt:
     * Sie hat eine feste Stelle im Tag und weiß ihre Uhrzeit selbst, während
     * eine Situation nur ungefähr weiß, wann sie stattfindet.
     */
    chainToId?: number;
    chainToTitle?: string;
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
    /** Unterscheidet die Gewohnheit vom Kurs, wenn beide im Raster liegen. */
    kind: 'habit';
    /**
     * Wer an diesem Tag mitmacht — null, wenn die Gewohnheit allein ansteht.
     *
     * Dasselbe Feld wie in {@see Habit}, damit der Kalender an einem Tag nicht
     * weniger zeigt als die Zeile auf der Übersicht. Und wie dort bewusst ohne
     * den Fortschritt der anderen Person (community_feature3.md §6).
     */
    companion: { name: string; initial: string } | null;
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
    /**
     * Der erste Tag, an dem ein Kurs den alten Platz wegnimmt — nur bei
     * Verdrängten. Das Ziel des Wegs „selbst umlegen": Einen neuen Platz
     * wählt man dort, wo der Kurs steht, der den alten genommen hat.
     */
    conflictDate?: string | null;
    /** Die Dauer als fertige Zeile („20 Min"), sonst null. */
    measureLabel: string | null;
    /**
     * Die belegte Spanne („17:00 – 17:20"), sonst null.
     *
     * Gibt es nur, wo eine feste Uhrzeit auf eine Dauer trifft.
     */
    timeRange: string | null;
    behaviorType: BehaviorType;
    /**
     * Der Schlüssel der Katalog-Vorlage („tagebuch"), sonst null.
     *
     * Entscheidet das Icon: Die Vorlage weiß, worum es geht, die
     * Verhaltensrichtung ordnet nur fachlich ein. Null bei Gewohnheiten aus
     * der Zeit der freien Eingabe — die fallen auf die Richtung zurück.
     */
    templateKey: string | null;
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
    /** Läuft an diesem Tag etwas aus dem Semesterplan? */
    hasLectures: boolean;
    /**
     * Steht an diesem Tag etwas mit jemandem an?
     *
     * Bewusst kein Punkt in `planned`: Wer gefragt wurde, führt die
     * Gewohnheit nicht und kann sie nie abhaken — der Tag sähe für immer
     * unerledigt aus, und das wäre der Vorwurf, den ein Rückblick nicht
     * erheben darf. Wie beim Vorlesungstag also: nicht wie viel, nur ob.
     */
    hasAppointment: boolean;
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
    /**
     * Der Schlüssel der Katalog-Vorlage („tagebuch"), sonst null.
     *
     * Entscheidet das Icon: Die Vorlage weiß, worum es geht, die
     * Verhaltensrichtung ordnet nur fachlich ein. Null bei Gewohnheiten aus
     * der Zeit der freien Eingabe — die fallen auf die Richtung zurück.
     */
    templateKey: string | null;
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
