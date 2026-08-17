import type { HabitBlueprint } from './habit';

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
    name: string;
    initial: string;
    title: string;
    anchor: string;
    day: string;
    /**
     * Die Gewohnheit als Vorlage — für den dritten Weg neben ja und nein.
     *
     * Wer gefragt wird, sieht hier eine Gewohnheit, die er selbst nicht führt.
     * Manchmal ist die Antwort „das will ich auch", und dafür braucht die
     * Übernahme eine Vorbelegung.
     */
    blueprint: HabitBlueprint;
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
};
