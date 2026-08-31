import { router } from '@inertiajs/react';
import { useState } from 'react';
import { PersonCircle } from '@/components/person-circle';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { store } from '@/routes/appointments';
import type { AppointmentDay, FriendshipPerson } from '@/types';

/**
 * Der letzte, freiwillige Schritt beim Anlegen: zu zweit angehen?
 *
 * Er kommt **nach** dem Speichern. Die Gewohnheit steht bereits, dieser Schritt
 * kann sie nicht mehr verhindern — das ist der ganze Grund für seine Stellung.
 * Ngocanh nennt Überforderung als Blocker fürs Anfangen; eine Entscheidung über
 * eine andere Person darf deshalb nicht zwischen Vorsatz und Speichern stehen.
 *
 * Nichts ist vorausgewählt, und „Später" steht gleichwertig daneben. Becker
 * nennt soziale Verbindlichkeit eine der wirksamsten Starthilfen — ein Angebot
 * also, keine Erwartung.
 *
 * Die Verabredung gilt für einen einzelnen Tag, nicht für die Gewohnheit. Eine
 * wiederkehrende wäre faktisch der gemeinsame Kalender, der mit Top-2 46 %
 * abgelehnt wurde (community_feature3.md §9).
 *
 * Zur Wahl stehen die nächsten Termine **dieser** Gewohnheit, nicht die
 * nächsten drei Kalendertage: Eine Mo–Fr-Gewohnheit, samstags angelegt, ließe
 * sich sonst nur für Tage verabreden, an denen sie gar nicht stattfindet.
 */
export function CompanionStep({
    habitId,
    title,
    anchor,
    friends,
    days,
}: {
    habitId: number;
    title: string;
    anchor: string;
    friends: FriendshipPerson[];
    days: AppointmentDay[];
}) {
    const [friendId, setFriendId] = useState<number | null>(null);
    const [day, setDay] = useState<string | null>(null);
    const [sending, setSending] = useState(false);

    const chosenFriend = friends.find((friend) => friend.id === friendId);
    const chosenDay = days.find((option) => option.value === day);
    const ready = chosenFriend !== undefined && chosenDay !== undefined;

    function ask() {
        if (!ready) {
            return;
        }

        setSending(true);

        router.post(
            store.url(habitId),
            { friend_id: chosenFriend.id, scheduled_for: chosenDay.value },
            {
                onFinish: () => setSending(false),
                onSuccess: () => router.visit(dashboard.url()),
            },
        );
    }

    return (
        <div className="flex flex-col gap-6">
            <div>
                <p className="type-eyebrow">Noch eine Möglichkeit</p>
                <h2 className="type-subheading mt-2">
                    Willst du „{title}" mit jemandem zusammen angehen?
                </h2>
                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                    Für einen einzelnen Tag, {anchor}. Das ist keine Verabredung
                    auf Dauer — und du kannst es jederzeit später noch tun.
                </p>
            </div>

            <section>
                <h3 className="type-eyebrow">Wen fragst du?</h3>

                <div className="mt-3 flex flex-wrap gap-3">
                    {friends.map((friend) => (
                        <button
                            key={friend.id}
                            type="button"
                            onClick={() =>
                                setFriendId(
                                    friendId === friend.id ? null : friend.id,
                                )
                            }
                            aria-pressed={friendId === friend.id}
                            className={cn(
                                'flex w-24 cursor-pointer flex-col items-center gap-2 rounded-2xl border-2 bg-card px-2 py-3 transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
                                friendId === friend.id
                                    ? 'border-primary'
                                    : 'border-transparent hover:bg-accent',
                            )}
                        >
                            <PersonCircle initial={friend.initial} />
                            <span className="w-full truncate text-center text-xs">
                                {friend.name}
                            </span>
                        </button>
                    ))}
                </div>
            </section>

            <section>
                <h3 className="type-eyebrow">Wann?</h3>

                {/* Die Tage kommen aus der Gewohnheit, nicht aus dem Kalender.
                    Wer selten übt, hat entsprechend weniger zur Wahl — der
                    Satz sagt, warum, statt eine leere Stelle zu lassen. */}
                {days.length < 3 && (
                    <p className="mt-2 text-xs text-muted-foreground">
                        Öfter steht „{title}" in dieser Woche nicht an.
                    </p>
                )}

                <div className="mt-3 flex flex-wrap gap-3">
                    {days.map((option) => (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() =>
                                setDay(
                                    day === option.value ? null : option.value,
                                )
                            }
                            aria-pressed={day === option.value}
                            className={cn(
                                'h-11 min-w-24 cursor-pointer rounded-xl border-2 bg-card px-4 text-sm transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
                                day === option.value
                                    ? 'border-primary'
                                    : 'border-transparent hover:bg-accent',
                            )}
                        >
                            {option.label}
                        </button>
                    ))}
                </div>
            </section>

            {ready && (
                <div className="rounded-2xl bg-card p-4">
                    <p className="text-[15px] font-semibold">
                        {chosenFriend.name} · {chosenDay.label}
                    </p>
                    <p className="mt-0.5 text-xs text-muted-foreground">
                        {anchor}
                    </p>
                </div>
            )}

            {/* „Später" ist kein kleinerer Knopf: Bei ø 3,92 Schuldgefühl darf
                der Ausweg nicht wie das schlechtere Ende aussehen. */}
            <div className="flex flex-col gap-3 sm:flex-row">
                <button
                    type="button"
                    onClick={ask}
                    disabled={!ready || sending}
                    className="h-12 flex-1 cursor-pointer rounded-xl bg-primary text-[15px] font-semibold text-primary-foreground transition-colors duration-[var(--duration-press)] ease-out hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Fragen
                </button>
                <button
                    type="button"
                    onClick={() => router.visit(dashboard.url())}
                    className="h-12 flex-1 cursor-pointer rounded-xl border border-primary text-[15px] font-semibold text-primary transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                >
                    Später
                </button>
            </div>

            {ready && (
                <p className="text-xs leading-relaxed text-muted-foreground">
                    {chosenFriend.name} bekommt eine Anfrage. Bei einer Absage
                    siehst du nur das — ohne Grund, ohne Zähler.
                </p>
            )}
        </div>
    );
}
