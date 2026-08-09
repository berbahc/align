import { Form, Head, router } from '@inertiajs/react';
import { FriendRequestNotice } from '@/components/friend-request-notice';
import InputError from '@/components/input-error';
import { PersonCircle } from '@/components/person-circle';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ToggleSwitch } from '@/components/ui/toggle-switch';
import { dashboard } from '@/routes';
import { availability } from '@/routes/appointments';
import { destroy, store } from '@/routes/friendships';
import type { FriendshipPerson } from '@/types';

const EYEBROW = 'text-[11px] font-semibold tracking-[0.11em] uppercase';

interface CommunityProps {
    /** Verabredungen zugelassen — Screen A5 der Mockups. */
    appointmentsEnabled: boolean;
    /** Der eigene Handle — steht hier, weil man ihn weitergeben muss. */
    username: string;
    friends: FriendshipPerson[];
    /** Anfragen an dich. `id` ist die Freundschaft, nicht die Person. */
    incoming: FriendshipPerson[];
    /** Anfragen, die du gestellt hast und die noch offen sind. */
    outgoing: FriendshipPerson[];
}

export default function Community({
    appointmentsEnabled,
    username,
    friends,
    incoming,
    outgoing,
}: CommunityProps) {
    function setAvailability(enabled: boolean) {
        router.put(availability.url(), { enabled }, { preserveScroll: true });
    }

    /**
     * Freundschaft beenden oder eigene Anfrage zurückziehen.
     *
     * Beides löscht denselben Eintrag — das Annehmen sitzt in
     * `FriendRequestNotice`, weil die Anfrage auch auf der Übersicht steht.
     */
    function remove(friendshipId: number) {
        router.delete(destroy.url(friendshipId), { preserveScroll: true });
    }

    return (
        <>
            <Head title="Community" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
                <header>
                    <h1 className="text-[clamp(1.75rem,4vw,2rem)] leading-tight font-bold text-primary">
                        Community
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Hier steht, mit wem du dich verabreden kannst. Was ihr
                        tut, sieht niemand — nur, dass ihr euch kennt.
                    </p>

                    {/* Den eigenen Handle sieht man sonst nirgends, muss ihn
                        aber weitergeben, um gefunden zu werden. */}
                    <p className="mt-3 text-sm text-muted-foreground">
                        Dein Name zum Weitergeben:{' '}
                        <span className="font-semibold text-foreground">
                            {username}
                        </span>
                    </p>
                </header>

                <FriendRequestNotice requests={incoming} />

                <section className="flex flex-col gap-3">
                    <h2 className={`${EYEBROW} text-muted-foreground`}>
                        Dein Kreis
                    </h2>

                    {friends.length === 0 ? (
                        <Card>
                            <CardContent>
                                <p className="text-[15px] font-semibold">
                                    Noch niemand.
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Verabredungen wirken mit Menschen, die dich
                                    kennen — nicht mit möglichst vielen.
                                </p>
                            </CardContent>
                        </Card>
                    ) : (
                        <Card>
                            <CardContent className="flex flex-col gap-1 p-2 sm:p-2">
                                {friends.map((friend) => (
                                    <div
                                        key={friend.id}
                                        className="flex items-center gap-3 rounded-xl px-3 py-2.5"
                                    >
                                        <PersonCircle
                                            initial={friend.initial}
                                        />
                                        <p className="min-w-0 flex-1 truncate text-[15px] font-semibold">
                                            {friend.name}
                                        </p>
                                        <button
                                            type="button"
                                            onClick={() => remove(friend.id)}
                                            className="cursor-pointer rounded-lg px-3 py-2 text-xs text-muted-foreground transition-colors duration-200 hover:bg-accent hover:text-accent-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                        >
                                            Entfernen
                                        </button>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </section>

                {appointmentsEnabled && (
                    <section className="flex flex-col gap-3">
                        <h2 className={`${EYEBROW} text-muted-foreground`}>
                            Jemanden fragen
                        </h2>

                        <Card>
                            <CardContent>
                                <Form
                                    action={store.url()}
                                    method="post"
                                    resetOnSuccess={['handle']}
                                    options={{ preserveScroll: true }}
                                    className="flex flex-col gap-3"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="flex flex-col gap-2 sm:flex-row">
                                                <div className="flex-1">
                                                    <Label
                                                        htmlFor="handle"
                                                        className="sr-only"
                                                    >
                                                        Username oder
                                                        E-Mail-Adresse
                                                    </Label>
                                                    {/* Ein Feld für beide
                                                        Formen: Das „@"
                                                        entscheidet, wonach
                                                        gesucht wird. */}
                                                    <Input
                                                        id="handle"
                                                        name="handle"
                                                        type="text"
                                                        autoComplete="off"
                                                        autoCapitalize="none"
                                                        spellCheck={false}
                                                        placeholder="Username oder E-Mail"
                                                        className="h-11"
                                                    />
                                                </div>
                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                    className="h-11 cursor-pointer rounded-xl px-6"
                                                >
                                                    Fragen
                                                </Button>
                                            </div>

                                            <InputError
                                                message={errors.handle}
                                            />

                                            <p className="text-xs text-muted-foreground">
                                                Der Name muss genau stimmen —
                                                Align sucht nicht nach
                                                Ähnlichem. Bei einer Absage
                                                siehst du nur das, ohne Grund
                                                und ohne Zähler.
                                            </p>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    </section>
                )}

                {outgoing.length > 0 && (
                    <section className="flex flex-col gap-3">
                        <h2 className={`${EYEBROW} text-muted-foreground`}>
                            Gefragt
                        </h2>

                        <Card>
                            <CardContent className="flex flex-col gap-1 p-2 sm:p-2">
                                {outgoing.map((person) => (
                                    <div
                                        key={person.id}
                                        className="flex items-center gap-3 rounded-xl px-3 py-2.5"
                                    >
                                        <PersonCircle pending />
                                        <p className="min-w-0 flex-1 truncate text-[15px]">
                                            <span className="font-semibold">
                                                {person.name}
                                            </span>
                                            <span className="ml-1.5 text-muted-foreground">
                                                antwortet, wann es passt
                                            </span>
                                        </p>
                                        <button
                                            type="button"
                                            onClick={() => remove(person.id)}
                                            className="cursor-pointer rounded-lg px-3 py-2 text-xs text-muted-foreground transition-colors duration-200 hover:bg-accent hover:text-accent-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                        >
                                            Zurückziehen
                                        </button>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </section>
                )}

                <section className="flex flex-col gap-3">
                    <h2 className={`${EYEBROW} text-muted-foreground`}>
                        Einstellungen
                    </h2>

                    <Card>
                        <CardContent className="flex items-center justify-between gap-4">
                            <div className="min-w-0">
                                <p className="text-[15px] font-semibold">
                                    Verabredungen zulassen
                                </p>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    Aus heißt: keine Anfragen, und an deinen
                                    Gewohnheiten erscheint kein Knopf dafür.
                                    Dein Kreis bleibt erhalten.
                                </p>
                            </div>
                            <ToggleSwitch
                                checked={appointmentsEnabled}
                                onChange={setAvailability}
                                label="Verabredungen zulassen"
                            />
                        </CardContent>
                    </Card>
                </section>
            </div>
        </>
    );
}

Community.layout = {
    breadcrumbs: [
        { title: 'Übersicht', href: dashboard() },
        { title: 'Community', href: '' },
    ],
};
