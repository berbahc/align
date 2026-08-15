import { router } from '@inertiajs/react';
import { PersonCircle } from '@/components/person-circle';
import { Button } from '@/components/ui/button';
import { destroy, update } from '@/routes/appointments';
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
 */
export function AppointmentRequestNotice({
    requests,
    onAdopt,
}: {
    requests: AppointmentRequest[];
    /** Der dritte Weg: die Gewohnheit selbst führen, unabhängig vom Termin. */
    onAdopt: (request: AppointmentRequest) => void;
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

    return (
        <section role="status" className="flex flex-col gap-3">
            <h2 className="text-[11px] font-semibold tracking-[0.11em] text-muted-foreground uppercase">
                {requests.length === 1
                    ? 'Eine Verabredung'
                    : `${requests.length} Verabredungen`}
            </h2>

            {requests.map((request) => (
                <div
                    key={request.id}
                    className="rounded-2xl border-[1.5px] border-dashed border-sand bg-card p-4 sm:p-5"
                >
                    <div className="flex items-center gap-3">
                        <div className="flex -space-x-2">
                            <PersonCircle initial={request.initial} />
                            <PersonCircle pending />
                        </div>
                        <p className="min-w-0 text-[15px] font-semibold">
                            {request.name}
                            <span className="ml-1.5 font-normal text-muted-foreground">
                                fragt dich
                            </span>
                        </p>
                    </div>

                    <p className="mt-3 text-[15px] leading-relaxed">
                        {request.day === 'heute' ? 'Heute' : request.day}{' '}
                        zusammen{' '}
                        <span className="font-semibold">{request.title}</span>,{' '}
                        {request.anchor}?
                    </p>

                    <div className="mt-4 flex gap-3">
                        <Button
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
                        Die Frage nach dem einen Tag bleibt offen, wenn man
                        hier tippt. Es ist der Weg für „das will ich auch" —
                        bisher endete er mit dem einen Tag, und die Gewohnheit
                        blieb die der anderen Person. */}
                    <button
                        type="button"
                        onClick={() => onAdopt(request)}
                        className="mt-3 cursor-pointer rounded-lg px-1 py-1 text-xs text-muted-foreground underline underline-offset-4 transition-colors duration-200 hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        Selbst übernehmen
                    </button>
                </div>
            ))}
        </section>
    );
}
