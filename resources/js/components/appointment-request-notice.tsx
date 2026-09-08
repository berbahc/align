import { Link, router } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { PersonCircle } from '@/components/person-circle';
import { Button } from '@/components/ui/button';
import { QUIET_LINK } from '@/lib/interaction';
import { capitaliseDay } from '@/lib/utils';
import { destroy, update } from '@/routes/appointments';
import { create } from '@/routes/habits';
import { store as shift } from '@/routes/habits/shifts';
import type { AppointmentRequest } from '@/types';

/**
 * Screen A2 — eine Verabredung wurde vorgeschlagen.
 *
 * Die heikelste Stelle des Features ist die **Absage**: Sie muss so leicht
 * sein wie die Zusage. Deshalb sind beide Knöpfe gleich breit (§7.2 Regel 1),
 * es gibt kein Begründungsfeld, und die Absage hinterlässt keinen Eintrag.
 * Sobald man sich rechtfertigen muss, wird aus einer Verabredung eine Schuld —
 * bei ø 3,92 Schuldgefühl der schnellste Weg, das Feature toxisch zu machen.
 *
 * Der zweite Kreis bleibt offen und gestrichelt: Die Verabredung ist visuell
 * unvollständig, solange nicht zugestimmt wurde.
 *
 * **Fragt dieselbe Person mehrmals, steht ihr Name einmal darüber.** Vorher
 * trug jede Anfrage ihre eigene Kopfzeile, und bei zwei Fragen stand „Test2
 * fragt dich" zweimal untereinander — dieselbe Person, dasselbe Bild, zweimal.
 * Zusammengefasst wird nur die Kopfzeile: Es bleiben zwei Fragen mit je
 * eigenem Ja und Nein, getrennt durch eine Linie. Sie zu einer Entscheidung zu
 * verschmelzen wäre falsch — es sind verschiedene Gewohnheiten an
 * verschiedenen Tagen.
 */
export function AppointmentRequestNotice({
    requests,
}: {
    requests: AppointmentRequest[];
}) {
    if (requests.length === 0) {
        return null;
    }

    function answer(appointmentId: number, accepted: boolean) {
        const options = { preserveScroll: true };

        if (accepted) {
            router.patch(update.url(appointmentId), {}, options);

            return;
        }

        router.delete(destroy.url(appointmentId), options);
    }

    /**
     * Die eigene Gewohnheit für diesen einen Tag woanders hinlegen.
     *
     * Nicht für immer: Wer am Mittwoch mitläuft, liest am Donnerstag wieder
     * um halb acht. Die Gewohnheit selbst bleibt unberührt.
     */
    function makeRoom(request: AppointmentRequest, time: string) {
        const habitId = request.conflict?.habitId;

        if (!habitId) {
            return;
        }

        router.post(
            shift.url(habitId),
            { date: request.date, scheduled_time: time },
            { preserveScroll: true },
        );
    }

    /**
     * Die Anfragen nach fragender Person, in der Reihenfolge ihres ersten
     * Auftretens. Eine `Map` hält diese Reihenfolge zu, ein Objekt nicht
     * verlässlich.
     */
    const groups = new Map<number, AppointmentRequest[]>();

    for (const request of requests) {
        const group = groups.get(request.requesterId);

        if (group) {
            group.push(request);
        } else {
            groups.set(request.requesterId, [request]);
        }
    }

    return (
        <section role="status" className="flex flex-col gap-3">
            <h2 className="type-eyebrow text-muted-foreground">
                {requests.length === 1
                    ? 'Eine Verabredung'
                    : `${requests.length} Verabredungen`}
            </h2>

            {[...groups.values()].map((group) => (
                /* Dieselbe Mulde wie die Freundschaftsanfrage: eine offene
                   Frage wartet, sie liegt nicht über dem Blatt. */
                <div
                    key={group[0].requesterId}
                    className="hollow rounded-2xl border-[1.5px] p-4 sm:p-5"
                >
                    {/* Einmal, egal wie viele Fragen darunter stehen. */}
                    <div className="flex items-center gap-3">
                        <div className="flex -space-x-2">
                            <PersonCircle initial={group[0].initial} />
                            <PersonCircle pending />
                        </div>
                        <p className="min-w-0 text-[15px] font-semibold">
                            {group[0].name}
                            {/* Ohne Zahl: Wie viele es sind, steht schon in
                                der Überschrift, und darunter stehen sie
                                einzeln. Ein drittes Mal wäre dieselbe
                                Wiederholung, die diese Karte gerade
                                abgeschafft hat. */}
                            <span className="ml-1.5 font-normal text-muted-foreground">
                                fragt dich
                            </span>
                        </p>
                    </div>

                    {group.map((request, index) => (
                        <div
                            key={request.id}
                            /* Die Linie trennt zwei Entscheidungen. Ohne sie
                               läsen die beiden Knopfpaare wie zwei Wege durch
                               dieselbe Frage. */
                            className={
                                index === 0
                                    ? 'mt-3'
                                    : 'mt-5 border-t border-sand pt-5'
                            }
                        >
                            <p className="text-[15px] leading-relaxed">
                                {capitaliseDay(request.day)} zusammen{' '}
                                <span className="font-semibold">
                                    {request.title}
                                </span>
                                , {request.anchor}?
                            </p>

                            {/* Time-Blocking heißt, dass zwei Spannen sich nicht
                                überschneiden. Wer zur selben Zeit schon etwas vorhat,
                                soll das vor der Zusage sehen — und den eigenen Tag
                                dafür einmal umstellen können, statt seine Gewohnheit
                                für immer zu verlegen oder doppelt zu buchen. */}
                            {request.conflict !== null && (
                                <div className="mt-3 flex flex-col gap-3 rounded-2xl bg-sand/50 p-3">
                                    <p className="flex items-start gap-2 text-xs leading-relaxed text-muted-foreground">
                                        <TriangleAlert
                                            className="mt-0.5 size-4 shrink-0 text-primary"
                                            strokeWidth={1.5}
                                            aria-hidden="true"
                                        />
                                        {/* In einem Stück und nicht aus drei
                                            Ausdrücken zusammengesetzt: JSX
                                            schluckt Leerzeichen an
                                            Zeilenenden, und ein Satz, dessen
                                            Lücken von der Einrückung
                                            abhängen, bricht beim nächsten
                                            Formatierer. */}
                                        <span>
                                            {
                                                {
                                                    habit: `Um diese Zeit läuft bei dir schon „${request.conflict.title}" von ${request.conflict.from} bis ${request.conflict.to}.`,
                                                    course: `Um diese Zeit läuft bei dir „${request.conflict.title}" von ${request.conflict.from} bis ${request.conflict.to} — aus deinem Semesterplan.`,
                                                    night: `Um diese Zeit schläfst du. Dein Tag geht von ${request.conflict.from} bis ${request.conflict.to} Uhr.`,
                                                }[request.conflict.kind]
                                            }
                                        </span>
                                    </p>

                                    {request.conflict.kind !== 'habit' ? (
                                        /* Weder ein Kurs noch die Nacht rücken.
                                           Drei Ausweichzeiten anzubieten, von
                                           denen keine etwas bewirkt, wäre
                                           schlimmer als keine — §1.5, benannt
                                           wird, was gilt. */
                                        <p className="text-xs leading-relaxed text-muted-foreground">
                                            {request.conflict.kind === 'course'
                                                ? 'Ein Kurs rückt nicht. An diesem Tag geht es deshalb nicht.'
                                                : 'An diesem Tag geht es deshalb nicht.'}
                                        </p>
                                    ) : request.conflict.options.length > 0 ? (
                                        <div className="flex flex-col gap-2">
                                            <p className="type-eyebrow text-muted-foreground">
                                                An diesem Tag stattdessen
                                            </p>
                                            <div className="flex flex-wrap gap-2">
                                                {request.conflict.options.map(
                                                    (option) => (
                                                        <button
                                                            key={option.time}
                                                            type="button"
                                                            onClick={() =>
                                                                makeRoom(
                                                                    request,
                                                                    option.time,
                                                                )
                                                            }
                                                            className="h-10 shrink-0 cursor-pointer rounded-full border border-primary px-4 text-xs font-semibold text-primary transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                                        >
                                                            {option.label}
                                                        </button>
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    ) : (
                                        /* §1.5 — benannt wird, was gilt: An diesem Tag
                                           ist kein Platz mehr, und das ist keine
                                           Aufforderung, irgendetwas zu ändern. */
                                        <p className="text-xs leading-relaxed text-muted-foreground">
                                            An diesem Tag ist sonst nirgends
                                            Platz für „{request.conflict.title}
                                            ".
                                        </p>
                                    )}
                                </div>
                            )}

                            {/* Was die Zusage aus dem eigenen Tag nimmt.
                                Steht vor den Knöpfen, weil es die Antwort
                                mitentscheidet — hinterher im Kalender wäre es
                                eine Überraschung. */}
                            {request.replaces !== null && (
                                <p className="mt-3 text-xs leading-relaxed text-muted-foreground">
                                    {request.replaces.moment === request.anchor
                                        ? `Dein „${request.replaces.title}" um ${request.replaces.moment} macht ihr an dem Tag zusammen, statt zweimal. Ein Haken zählt für beides.`
                                        : `Dein „${request.replaces.title}" um ${request.replaces.moment} rückt an dem Tag auf ${request.anchor} — ihr macht es zusammen, statt zweimal. Ein Haken zählt für beides.`}
                                </p>
                            )}

                            <div className="mt-4 flex gap-3">
                                <Button
                                    disabled={request.conflict !== null}
                                    onClick={() => answer(request.id, true)}
                                    className="h-11 flex-1 cursor-pointer rounded-xl"
                                >
                                    Passt mir
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => answer(request.id, false)}
                                    className="h-11 flex-1 cursor-pointer rounded-xl border-primary text-primary"
                                >
                                    Lieber nicht
                                </Button>
                            </div>

                            {/* Leiser und getrennt, weil es keine dritte Antwort ist:
                                Es ist der Weg für „das will ich auch" — und weil man
                                damit ohnehin mitmacht, ist die Frage danach
                                beantwortet. Geplant wird im selben Assistenten wie
                                jede neue Gewohnheit. */}
                            {request.blueprint.templateKey !== null && (
                                <Link
                                    href={create.url({
                                        query: { appointment: request.id },
                                    })}
                                    className={`${QUIET_LINK} mt-3 inline-block text-xs`}
                                >
                                    Selbst übernehmen
                                </Link>
                            )}
                        </div>
                    ))}
                </div>
            ))}
        </section>
    );
}
