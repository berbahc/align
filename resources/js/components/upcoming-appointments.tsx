import { router } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { PersonCircle } from '@/components/person-circle';
import { SectionHeading } from '@/components/section-heading';
import { Card, CardContent } from '@/components/ui/card';
import { capitaliseDay, cn } from '@/lib/utils';
import { destroy } from '@/routes/appointments';
import {
    destroy as undone,
    store as done,
} from '@/routes/appointments/completion';
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
 *
 * **Der eigene Haken steht sehr wohl da.** Wer zusagt, macht mit und hat
 * danach dasselbe getan wie die andere Person — bis hierher konnte er es
 * nirgends abhaken, weil die Gewohnheit der fragenden Seite gehört. Der Haken
 * hängt deshalb an der Verabredung und meldet nur den eigenen Teil; die
 * fragende Seite erfährt davon nichts (§6).
 */
export function UpcomingAppointments({
    appointments,
    selfInitial,
    onRepeat,
}: {
    appointments: UpcomingAppointment[];
    selfInitial: string;
    /**
     * Dieselbe Person nochmal fragen — `community_feature3.md` §7.
     *
     * Steht erst, wenn der eigene Anteil erledigt ist, und nur, wenn es eine
     * eigene Gewohnheit gibt, auf der sich das wiederholen ließe. Wer die
     * Gewohnheit nicht führt, wird gefragt, statt zu fragen.
     *
     * Optional, weil der Community-Bereich das Verabredungs-Sheet nicht
     * montiert: Dort bleibt es beim Weg zurück. Der Weg nach vorn steht auf
     * der Übersicht, wo der Tag ohnehin gelesen wird.
     */
    onRepeat?: (appointment: UpcomingAppointment) => void;
}) {
    if (appointments.length === 0) {
        return null;
    }

    return (
        <section
            aria-labelledby="verabredungen"
            className="flex flex-col gap-3"
        >
            {/* Vorher stand hier nur „ZUSAMMEN" in Kleinversalien. Auf der
                Übersicht steht darüber aber schon eine Gewohnheit mit
                derselben Person und demselben Titel — und nichts sagte, was
                der Unterschied ist. Er ist der Tag: Was heute ansteht, trägt
                sein Doppel-Zeichen oben in der Zeile, alles Weitere steht
                hier. Deshalb nennt die Überschrift den Bereich und die Karten
                führen mit dem Tag. */}
            <SectionHeading
                id="verabredungen"
                title="Verabredungen"
                hint="Was du mit jemandem ausgemacht hast, mit Tag und Uhrzeit."
            />

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

                function toggle() {
                    const options = { preserveScroll: true };

                    if (appointment.completed) {
                        router.delete(undone.url(appointment.id), options);

                        return;
                    }

                    router.post(done.url(appointment.id), {}, options);
                }

                const body = (
                    <>
                        {pair}
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-[15px] font-semibold">
                                {appointment.title}
                            </p>
                            <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                {/* Groß geschrieben, weil der Tag hier den
                                    Satz anführt: „heute", „nächsten Dienstag"
                                    kommen klein aus dem Server, wo sie mitten
                                    in einer Zeile stehen. */}
                                <span className="font-semibold text-foreground">
                                    {capitaliseDay(appointment.day)}
                                </span>{' '}
                                · {appointment.anchor} ·{' '}
                                {appointment.accepted
                                    ? `mit ${appointment.name}`
                                    : `${appointment.name} ist gefragt`}
                            </p>
                        </div>

                        {/* Der eigene Haken: nur für die gefragte Seite, und
                            nur am Tag selbst. Wer selbst gefragt hat, hakt
                            seine Gewohnheit in ihrer Zeile ab — hier wäre das
                            ein zweiter Haken für dieselbe Sache. */}
                        {appointment.canComplete && (
                            <button
                                type="button"
                                onClick={toggle}
                                aria-pressed={appointment.completed === true}
                                aria-label={
                                    appointment.completed
                                        ? `${appointment.title} als noch offen markieren`
                                        : `${appointment.title} als erledigt markieren`
                                }
                                className="group/check flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            >
                                <span className="transition-transform duration-[var(--duration-press)] ease-out motion-safe:group-active/check:scale-90">
                                    <span
                                        className={cn(
                                            'flex size-7 items-center justify-center rounded-full transition-[background-color,border-color] duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                            appointment.completed
                                                ? 'bg-primary'
                                                : 'border-2 border-dashed border-sand',
                                        )}
                                    >
                                        {appointment.completed && (
                                            <Check
                                                className="size-4 text-primary-foreground duration-[var(--duration-pop)] ease-[var(--ease-pop)] motion-safe:animate-in motion-safe:zoom-in-50"
                                                strokeWidth={2.5}
                                                aria-hidden="true"
                                            />
                                        )}
                                    </span>
                                </span>
                            </button>
                        )}

                        {/* Nach dem eigenen Haken tritt der Weg nach vorn an
                            die Stelle des Wegs zurück: Absagen ergibt für
                            etwas, das gerade stattgefunden hat, keinen Sinn
                            mehr. §7 — die Wiederholung entsteht jedes Mal
                            neu, statt einmal vage vereinbart zu werden. */}
                        {appointment.repeatHabitId !== null && onRepeat ? (
                            <button
                                type="button"
                                onClick={() => onRepeat(appointment)}
                                className="shrink-0 cursor-pointer rounded-lg px-3 py-2 text-xs font-semibold text-primary transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            >
                                Nochmal ausmachen?
                            </button>
                        ) : (
                            /* Zurückziehen und Auflösen sind derselbe Weg —
                               beides löscht den Eintrag und hinterlässt keine
                               Notiz. */
                            <button
                                type="button"
                                onClick={() =>
                                    router.delete(destroy.url(appointment.id), {
                                        preserveScroll: true,
                                    })
                                }
                                // Derselbe Ton wie „Nochmal ausmachen?", das an
                                // genau dieser Stelle steht: Zwei Knöpfe, die
                                // sich denselben Platz teilen, dürfen nicht wie
                                // zwei Arten von Knopf aussehen.
                                className="shrink-0 cursor-pointer rounded-lg px-3 py-2 text-xs font-semibold text-primary transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            >
                                {appointment.iAsked && !appointment.accepted
                                    ? 'Zurückziehen'
                                    : 'Absagen'}
                            </button>
                        )}
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
