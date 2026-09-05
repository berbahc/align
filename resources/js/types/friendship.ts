import type { BehaviorType, HabitBlueprint } from './habit';

/**
 * Eine Person, wie sie im Community-Bereich erscheint.
 *
 * `id` ist immer die **Freundschaft**, nie die Person: Annehmen, Absagen und
 * Entfernen sprechen alle denselben Eintrag an.
 */
export type FriendshipPerson = {
    id: number;
    name: string;
    /** Erster Buchstabe des Namens, serverseitig gebildet. */
    initial: string;
};

/**
 * Einer der Tage, die Screen A1 zur Wahl stellt.
 *
 * Höchstens drei, und keine Kalendertage, sondern die nächsten Termine der
 * Gewohnheit: Eine Mo–Fr-Gewohnheit bietet samstags Montag, Dienstag,
 * Mittwoch an. Wer seltener übt, hat entsprechend weniger zur Wahl.
 */
export type AppointmentDay = {
    /** ISO-Datum, so wie der Server es zurückerwartet. */
    value: string;
    /** „heute", „morgen" oder der Wochentag. */
    label: string;
};

/**
 * Eine offene Verabredungs-Anfrage.
 *
 * `id` ist die Verabredung. `anchor` ist der Anker der **fragenden** Seite —
 * die Verabredung erfindet keine eigene Zeitlogik.
 */
export type AppointmentRequest = {
    id: number;
    /**
     * Wer fragt — als Kennung, damit sich mehrere Fragen derselben Person
     * unter einer Kopfzeile bündeln lassen. Über den Namen ginge das auch,
     * aber zwei Freunde dürfen gleich heißen.
     */
    requesterId: number;
    name: string;
    initial: string;
    title: string;
    anchor: string;
    day: string;
    /** Das Datum als ISO-Zeile — für das Verschieben des eigenen Tages. */
    date: string;
    /**
     * Die Gewohnheit als Vorlage — für den dritten Weg neben ja und nein.
     *
     * Wer gefragt wird, sieht hier eine Gewohnheit, die er selbst nicht führt.
     * Manchmal ist die Antwort „das will ich auch", und dafür braucht die
     * Übernahme eine Vorbelegung.
     */
    blueprint: HabitBlueprint;
    /**
     * Was der Zusage im Weg steht — die eigene Gewohnheit zur selben Zeit.
     *
     * Null heißt: Der Platz ist frei. Sonst nennt sie die Gewohnheit, ihre
     * Spanne und die Zeiten, an die sie sich für diesen einen Tag legen
     * ließe. Solange etwas hier steht, wäre eine Zusage eine Doppelbuchung.
     */
    conflict: AppointmentConflict | null;
};

/**
 * Eine eigene Gewohnheit, die zur Zeit der Verabredung schon läuft.
 *
 * `options` sind Ausweichzeiten für **diesen einen Tag** — die Gewohnheit
 * selbst bleibt, wo sie ist. Eine leere Liste heißt: An dem Tag ist sonst
 * nirgends Platz.
 */
export type AppointmentConflict = {
    habitId: number;
    title: string;
    /** „07:30" — Beginn der eigenen Gewohnheit an diesem Tag. */
    from: string;
    /** „08:00" — und wann der Platz wieder frei wäre. */
    to: string;
    options: { time: string; label: string }[];
};

/**
 * Eine Absage, die einmal erscheint und beim Wegklicken gelöscht wird.
 *
 * Beide Sätze kommen fertig formuliert vom Server — die Oberfläche kennt den
 * Unterschied zwischen abgelehnt und aufgelöst nicht, weil sie ihn nicht
 * braucht: Beide sehen gleich aus und verschwinden gleich.
 */
export type AppointmentNotice = {
    id: number;
    /** „Passt Berbahc diesmal nicht." */
    message: string;
    /** Woran es hing — Titel der Gewohnheit, bei einer Absage plus Tag. */
    detail: string;
    /**
     * Die eigene Gewohnheit, um die es ging — sie läuft ohne die andere Person
     * weiter. Null, wenn sie einem nicht gehört; dann steht `blueprint`.
     */
    habitId: number | null;
    /**
     * Die Vorlage zum Übernehmen — nur für die Seite, die die Gewohnheit nicht
     * führt. Genau eines von beiden ist gesetzt, nie beide.
     */
    blueprint: HabitBlueprint | null;
};

/**
 * Was mit jemandem ansteht — zugesagt oder von einem selbst gefragt.
 *
 * Höchstens eine Woche weit und nach dem Tag spurlos weg: Das ist der
 * Unterschied zum gemeinsamen Kalender, der mit Top-2 46 % abgelehnt wurde.
 */
export type UpcomingAppointment = {
    id: number;
    name: string;
    initial: string;
    title: string;
    anchor: string;
    day: string;
    /** Falsch heißt: gefragt, aber noch nicht beantwortet. */
    accepted: boolean;
    /** Wahr, wenn die Anfrage von einem selbst kam. */
    iAsked: boolean;
    /**
     * Der **eigene** Haken — null auf der fragenden Seite.
     *
     * Wer selbst gefragt hat, hakt seine eigene Gewohnheit ab; für ihn gibt
     * es hier nichts. Und was die andere Person getan hat, steht bewusst
     * nirgends: Das wäre der Dauerstatus, den community_feature3.md §6
     * ausschließt.
     */
    completed: boolean | null;
    /** Nur am Tag selbst und im Nachtragefenster danach. */
    canComplete: boolean;
    /**
     * Die **eigene** Gewohnheit, auf der sich das wiederholen ließe.
     *
     * Null heißt: nichts zu wiederholen — entweder ist der eigene Anteil noch
     * offen, oder man führt die Gewohnheit gar nicht. Wer gefragt wurde und
     * nicht übernommen hat, wird gefragt, statt zu fragen.
     *
     * Sagt nichts über die andere Person: Es ist die eigene Gewohnheit mit
     * ihren eigenen Tagen (community_feature3.md §6).
     */
    repeatHabitId: number | null;
    /** Die Tage, an denen genau diese Gewohnheit als Nächstes ansteht. */
    repeatDays: AppointmentDay[];
};

/**
 * Eine zugesagte Verabredung an ihrer Stelle im Tag — die Kalenderansicht.
 *
 * Die Gewohnheit gehört jemand anderem. Sie liegt im Raster wie eine eigene,
 * lässt sich aber weder abhaken noch ziehen noch anpassen — wie ein Kurs, und
 * aus demselben Grund: Sie ist kein eigener Vorsatz.
 *
 * Bewusst ohne Fortschritt der anderen Person (community_feature3.md §6): Der
 * Block sagt, dass etwas gemeinsam ansteht, nicht wie es läuft.
 */
export type AppointmentBlock = {
    /** Unterscheidet sie im Raster von Gewohnheit und Kurs. */
    kind: 'appointment';
    /** Die Kennung der Verabredung — nicht die der fremden Gewohnheit. */
    id: number;
    habitId: number;
    title: string;
    /** Der Anker der fremden Gewohnheit: „nach dem Aufstehen". */
    anchor: string;
    /** Die fragende Person. */
    name: string;
    initial: string;
    /** Wo der Block im Stundenraster liegt, als Minute seit Mitternacht. */
    startMinute: number;
    durationMinutes: number | null;
    /** Ist die Stelle eine Uhrzeit oder nur eine Näherung? */
    exact: boolean;
    /** „17:00 – 17:20", wo es eine echte Spanne gibt. */
    timeRange: string | null;
    behaviorType: BehaviorType;
    /**
     * Der **eigene** Haken an dieser Zusage.
     *
     * Er hängt an der Verabredung, nicht an der fremden Gewohnheit: Wer
     * gefragt wurde, führt sie nicht, und ein Haken dort meldete die
     * Erfüllung der anderen Person.
     */
    completed: boolean;
    /** Nur am Tag selbst und im Nachtragefenster danach. */
    canComplete: boolean;
};
