import { Form, Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AppointmentNotice } from '@/components/appointment-notice';
import { AppointmentRequestNotice } from '@/components/appointment-request-notice';
import { FriendRequestNotice } from '@/components/friend-request-notice';
import { HabitAdoptionSheet } from '@/components/habit-adoption-sheet';
import InputError from '@/components/input-error';
import { PersonCircle } from '@/components/person-circle';
import type { ScheduleTypeOption } from '@/components/schedule-picker';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ToggleSwitch } from '@/components/ui/toggle-switch';
import { UpcomingAppointments } from '@/components/upcoming-appointments';
import { dashboard } from '@/routes';
import { destroy as dismissNotice } from '@/routes/appointment-notices';
import { availability } from '@/routes/appointments';
import { destroy, store } from '@/routes/friendships';
import type {
    AppointmentNotice as Notice,
    AppointmentRequest,
    FriendshipPerson,
    HabitBlueprint,
    UpcomingAppointment,
} from '@/types';

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
    /** Offene Verabredungs-Anfragen an dich, mit beiden Knöpfen. */
    appointmentRequests: AppointmentRequest[];
    /** Absagen, die einmal erscheinen und beim Wegklicken verschwinden. */
    appointmentNotices: Notice[];
    /** Was in der kommenden Woche mit jemandem ansteht. */
    upcomingAppointments: UpcomingAppointment[];
    /** Für das Übernehmen einer fremden Gewohnheit — dieselbe Wahl wie beim Anlegen. */
    scheduleTypes: ScheduleTypeOption[];
    triggerSuggestions: string[];
}

export default function Community({
    appointmentsEnabled,
    username,
    friends,
    incoming,
    outgoing,
    appointmentRequests,
    appointmentNotices,
    upcomingAppointments,
    scheduleTypes,
    triggerSuggestions,
}: CommunityProps) {
    const { auth } = usePage().props;
    const selfInitial = (auth.user?.name.charAt(0) ?? '').toUpperCase();

    // Welche fremde Gewohnheit gerade zum Übernehmen offen steht, und aus
    // welcher Absage heraus — die Notiz verschwindet dann mit.
    const [adopting, setAdopting] = useState<{
        blueprint: HabitBlueprint;
        noticeId?: number;
    } | null>(null);

    /**
     * „Mach ich trotzdem" auf dieser Seite.
     *
     * Anders als auf der Übersicht gibt es hier keine Habit-Zeile, auf die
     * verwiesen werden könnte — die Notiz verschwindet, und der Weg führt
     * dorthin, wo die Gewohnheit steht.
     */
    function carryOn(notice: Notice) {
        router.delete(dismissNotice.url(notice.id), {
            onSuccess: () => router.visit(dashboard()),
        });
    }

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
                        Hier steht, mit wem du dich verabreden kannst und was
                        gerade ausgemacht ist. Was ihr tut, sieht niemand — nur,
                        dass ihr euch kennt.
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
                <AppointmentRequestNotice
                    requests={appointmentRequests}
                    onAdopt={(request) =>
                        setAdopting({ blueprint: request.blueprint })
                    }
                />

                {/* Über allem, was noch steht: Eine Absage erklärt die Lücke,
                    die man sonst weiter unten vergeblich sucht. */}
                <AppointmentNotice
                    notices={appointmentNotices}
                    onAdopt={(notice) =>
                        notice.blueprint !== null &&
                        setAdopting({
                            blueprint: notice.blueprint,
                            noticeId: notice.id,
                        })
                    }
                    onCarryOn={carryOn}
                />

                {/* Über dem Kreis, nicht darunter: Was ausgemacht ist, ist der
                    lebendige Teil dieser Seite — die Namensliste steht
                    darunter, weil sie sich selten ändert. Anders als auf der
                    Übersicht bleibt hier auch stehen, was heute an der eigenen
                    Gewohnheit hängt; diese Seite hat keine Habit-Zeile, die es
                    sonst trüge. */}
                <UpcomingAppointments
                    appointments={upcomingAppointments}
                    selfInitial={selfInitial}
                />

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

            <HabitAdoptionSheet
                blueprint={adopting?.blueprint ?? null}
                noticeId={adopting?.noticeId}
                scheduleTypes={scheduleTypes}
                triggerSuggestions={triggerSuggestions}
                onOpenChange={(open) => !open && setAdopting(null)}
            />
        </>
    );
}

Community.layout = {
    breadcrumbs: [
        { title: 'Übersicht', href: dashboard() },
        { title: 'Community', href: '' },
    ],
};
