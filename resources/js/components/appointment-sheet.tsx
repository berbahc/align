import { router } from '@inertiajs/react';
import { useState } from 'react';
import { PersonCircle } from '@/components/person-circle';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { capitaliseDay, cn } from '@/lib/utils';
import { store } from '@/routes/appointments';
import type { AppointmentDay, FriendshipPerson, Habit } from '@/types';

/**
 * Screen A1 — eine Verabredung vorschlagen.
 *
 * Drei Taps, ein Ergebnis: wer, wann, fragen. Kein Kalender, keine
 * Uhrzeitwahl, keine Terminfindung — Danial: „Ich will, dass die App simpel
 * ist. Wir Menschen haben eine richtig kurze Aufmerksamkeitsspanne."
 *
 * Der Zeitpunkt kommt aus dem Anker der Gewohnheit. Die Verabredung erfindet
 * keine Zeitlogik, sie nutzt die vorhandene — genau darum ist sie **kein**
 * gemeinsamer Kalender (Top-2 46 %, die härteste Ablehnung nach den Bildern):
 * Es wird kein Zeitraum abgeglichen, sondern ein Anker geteilt.
 *
 * Das gilt auch für die Tage: Angeboten werden die nächsten Termine dieser
 * Gewohnheit, nicht die nächsten drei Kalendertage.
 *
 * Die Auswahl folgt §5.5: 2 px Rahmen in `primary`, **Füllung unverändert**.
 * Kein Farbblock, kein Häkchen — dasselbe leise Muster wie beim Mood-Selector.
 */
export function AppointmentSheet({
    habit,
    friends,
    days,
    preselect = null,
    onOpenChange,
}: {
    habit: Habit | null;
    friends: FriendshipPerson[];
    days: AppointmentDay[];
    /**
     * Wer schon vorgewählt ist — der Weg „Nochmal ausmachen?".
     *
     * Ein Startwert, keine Setzung: Die Auswahl bleibt änderbar wie immer.
     * `community_feature3.md` §7 will die Wiederholung als neue
     * Einzelentscheidung, nicht als Abo — deshalb wird hier nur der Weg
     * verkürzt, nicht die Entscheidung abgenommen.
     */
    preselect?: number | null;
    onOpenChange: (open: boolean) => void;
}) {
    const [friendId, setFriendId] = useState<number | null>(preselect);
    const [day, setDay] = useState<string | null>(null);

    // Beim Wechsel der Gewohnheit gilt die neue Vorwahl. Ohne das bliebe die
    // Person der zuvor geöffneten Verabredung stehen.
    const [seen, setSeen] = useState<number | null>(habit?.id ?? null);

    if (habit !== null && habit.id !== seen) {
        setSeen(habit.id);
        setFriendId(preselect);
        setDay(null);
    }

    const chosenFriend = friends.find((friend) => friend.id === friendId);
    const chosenDay = days.find((option) => option.value === day);
    const ready = chosenFriend !== undefined && chosenDay !== undefined;

    function ask() {
        if (habit === null || !ready) {
            return;
        }

        router.post(
            store.url(habit.id),
            { friend_id: chosenFriend.id, scheduled_for: chosenDay.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setFriendId(preselect);
                    setDay(null);
                    onOpenChange(false);
                },
            },
        );
    }

    return (
        <Sheet open={habit !== null} onOpenChange={onOpenChange}>
            <SheetContent side="bottom" className="mx-auto max-w-lg">
                <SheetHeader>
                    <SheetTitle>{habit?.title}</SheetTitle>
                    <SheetDescription>{habit?.scheduleLabel}</SheetDescription>
                </SheetHeader>

                <div className="flex flex-col gap-6 px-4 pb-6">
                    {friends.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Dein Kreis ist noch leer. Unter „Community" fügst du
                            jemanden hinzu.
                        </p>
                    ) : (
                        <>
                            <section>
                                <h3 className="type-eyebrow text-muted-foreground">
                                    Wen fragst du?
                                </h3>

                                <div className="mt-3 flex flex-wrap gap-3">
                                    {friends.map((friend) => (
                                        <button
                                            key={friend.id}
                                            type="button"
                                            onClick={() =>
                                                setFriendId(friend.id)
                                            }
                                            aria-pressed={
                                                friendId === friend.id
                                            }
                                            className={cn(
                                                'flex w-24 cursor-pointer flex-col items-center gap-2 rounded-2xl border-2 bg-card px-2 py-3 transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
                                                friendId === friend.id
                                                    ? 'border-primary'
                                                    : 'border-transparent hover:bg-accent',
                                            )}
                                        >
                                            <PersonCircle
                                                initial={friend.initial}
                                            />
                                            <span className="w-full truncate text-center text-xs">
                                                {friend.name}
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            </section>

                            <section>
                                <h3 className="type-eyebrow text-muted-foreground">
                                    Wann?
                                </h3>

                                {/* Zur Wahl stehen die nächsten Termine dieser
                                    Gewohnheit. Wer selten übt, hat weniger als
                                    drei — der Satz sagt, warum. */}
                                {days.length < 3 && (
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        Öfter steht „{habit?.title}" in dieser
                                        Woche nicht an.
                                    </p>
                                )}

                                <div className="mt-3 flex flex-wrap gap-3">
                                    {days.map((option) => (
                                        <button
                                            key={option.value}
                                            type="button"
                                            onClick={() => setDay(option.value)}
                                            aria-pressed={day === option.value}
                                            className={cn(
                                                'h-11 min-w-24 cursor-pointer rounded-xl border-2 bg-card px-4 text-sm transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
                                                day === option.value
                                                    ? 'border-primary'
                                                    : 'border-transparent hover:bg-accent',
                                            )}
                                        >
                                            {/* Auf dem Knopf steht der Tag für
                                                sich — „morgen" klein ist eine
                                                Satzmitte ohne Satz. Vom Server
                                                kommt er klein, weil dieselbe
                                                Zeile anderswo mitten in einen
                                                Satz läuft. */}
                                            {capitaliseDay(option.label)}
                                        </button>
                                    ))}
                                </div>
                            </section>

                            {/* Die Zusammenfassung erscheint erst, wenn beide
                                Teile stehen — vorher wäre sie ein Satz mit
                                Lücken. */}
                            {ready && (
                                <div className="rounded-2xl bg-card p-4">
                                    <p className="text-[15px] font-semibold">
                                        {chosenFriend.name} ·{' '}
                                        {capitaliseDay(chosenDay.label)}
                                    </p>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        {habit?.scheduleLabel}
                                    </p>
                                </div>
                            )}

                            <div>
                                <button
                                    type="button"
                                    onClick={ask}
                                    disabled={!ready}
                                    className="h-12 w-full cursor-pointer rounded-xl bg-primary text-[15px] font-semibold text-primary-foreground transition-colors duration-[var(--duration-press)] ease-out hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Fragen
                                </button>

                                <p className="type-eyebrow mt-3 text-center text-muted-foreground">
                                    Nur für diesen einen Tag
                                </p>

                                {/* Wörtlich aus Screen A1 — die Zusage über
                                    die Folgen einer Absage steht vor dem
                                    Fragen, nicht danach. */}
                                <p className="mt-3 text-xs leading-relaxed text-muted-foreground">
                                    {chosenFriend?.name ?? 'Die Person'} bekommt
                                    eine Anfrage. Bei einer Absage siehst du nur
                                    das — ohne Grund, ohne Zähler.
                                </p>
                            </div>
                        </>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}
