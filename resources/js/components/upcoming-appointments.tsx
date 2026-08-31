import { router } from '@inertiajs/react';
import { PersonCircle } from '@/components/person-circle';
import { Card, CardContent } from '@/components/ui/card';
import { destroy } from '@/routes/appointments';
import type { UpcomingAppointment } from '@/types';

/**
 * Was mit jemandem ansteht — zugesagt oder gefragt.
 *
 * Ohne diese Liste war eine Verabredung für morgen unsichtbar: Sie erschien
 * erst an ihrem Tag, und wer zusagte, sah danach nichts mehr. Beide Seiten
 * mussten annehmen, es sei schiefgegangen.
 *
 * Zugesagt steht durchgezogen, gefragt gestrichelt — dieselbe Unterscheidung
 * wie im ganzen System (designsprache.md §7.3: gestrichelt heißt „noch nicht
 * festgelegt").
 *
 * Kein Fortschritt der anderen Person, kein Häkchen für sie, keine Historie.
 * Nach dem Tag verschwindet der Eintrag (§7).
 */
export function UpcomingAppointments({
    appointments,
    selfInitial,
}: {
    appointments: UpcomingAppointment[];
    selfInitial: string;
}) {
    if (appointments.length === 0) {
        return null;
    }

    return (
        <section className="flex flex-col gap-3">
            <h2 className="type-eyebrow text-muted-foreground">Zusammen</h2>

            {appointments.map((appointment) => {
                const pair = (
                    <span className="flex shrink-0 -space-x-2">
                        <PersonCircle initial={selfInitial} />
                        {appointment.accepted ? (
                            <PersonCircle
                                initial={appointment.initial}
                                className="ring-2 ring-background"
                            />
                        ) : (
                            <PersonCircle pending className="bg-card" />
                        )}
                    </span>
                );

                const body = (
                    <>
                        {pair}
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-[15px] font-semibold">
                                {appointment.title}
                            </p>
                            <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                {appointment.accepted
                                    ? `mit ${appointment.name}`
                                    : `${appointment.name} ist gefragt`}{' '}
                                · {appointment.day} · {appointment.anchor}
                            </p>
                        </div>

                        {/* Zurückziehen und Auflösen sind derselbe Weg — beides
                            löscht den Eintrag und hinterlässt keine Notiz. */}
                        <button
                            type="button"
                            onClick={() =>
                                router.delete(destroy.url(appointment.id), {
                                    preserveScroll: true,
                                })
                            }
                            className="shrink-0 cursor-pointer rounded-lg px-3 py-2 text-xs text-muted-foreground transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent hover:text-accent-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            {appointment.iAsked && !appointment.accepted
                                ? 'Zurückziehen'
                                : 'Absagen'}
                        </button>
                    </>
                );

                return appointment.accepted ? (
                    <Card key={appointment.id}>
                        <CardContent className="flex items-center gap-3">
                            {body}
                        </CardContent>
                    </Card>
                ) : (
                    <div
                        key={appointment.id}
                        className="flex items-center gap-3 rounded-2xl border-[1.5px] border-dashed border-sand bg-card p-4 sm:p-5"
                    >
                        {body}
                    </div>
                );
            })}
        </section>
    );
}
