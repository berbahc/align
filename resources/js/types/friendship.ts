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
