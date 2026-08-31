import { router } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { destroy } from '@/routes/appointment-notices';
import type { AppointmentNotice as Notice } from '@/types';

/**
 * §5.6 Outline-Variante: transparent, 1 px primary. Kein gefüllter Knopf —
 * weitermachen ist ein Angebot, keine Aufforderung.
 */
const ACTION =
    'h-10 shrink-0 cursor-pointer rounded-full border border-primary px-4 text-xs font-semibold text-primary transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';

/**
 * Eine Absage, die einmal ankommt — Übersicht wie Community-Bereich.
 *
 * community_feature3.md §5 verlangt sie ausdrücklich: Beim Fragenden soll
 * „Passt Silas diesmal nicht" erscheinen statt einer Lücke, wo eben noch eine
 * Verabredung stand. Ohne sie erfährt niemand, ob abgesagt oder nie hingesehen
 * wurde — und wer eine Zusage verliert, steht am Tag allein da.
 *
 * Kein Rot, kein Warnzeichen: §1.4 hält fest, dass in der ganzen App keine
 * Alarmfarbe existiert, auch nicht für eine Absage. Es ist eine Feststellung,
 * keine Meldung — deshalb dieselbe ruhige Karte wie überall, ohne Rahmen und
 * ohne Akzent.
 *
 * Kein Grund, kein Zähler, keine Historie (§9): Warum jemand nicht kann, steht
 * hier nicht und wird nirgends gespeichert. Wegklicken löscht die Zeile.
 */
export function AppointmentNotice({
    notices,
    onAdopt,
    onCarryOn,
    className,
}: {
    notices: Notice[];
    /** Die Gewohnheit gehört der anderen Person — sie lässt sich übernehmen. */
    onAdopt: (notice: Notice) => void;
    /** Sie gehört einem selbst — sie läuft weiter, mit oder ohne Begleitung. */
    onCarryOn: (notice: Notice) => void;
    className?: string;
}) {
    if (notices.length === 0) {
        return null;
    }

    return (
        <section role="status" className={cn('flex flex-col gap-3', className)}>
            {notices.map((notice) => (
                <Card key={notice.id}>
                    <CardContent className="flex flex-col gap-4">
                        <div className="min-w-0">
                            <p className="text-[15px] font-semibold">
                                {notice.message}
                            </p>
                            <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                {notice.detail}
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            {/* Die Absage betrifft den einen Tag, nicht die
                                Gewohnheit. Wem sie gehört, der macht sie
                                weiter; wer nur eingeladen war, kann sie zu
                                seiner machen. Beides steht vor „Alles gut" —
                                die Absage ist ein Ende, das keines sein muss. */}
                            {notice.habitId !== null && (
                                <button
                                    type="button"
                                    onClick={() => onCarryOn(notice)}
                                    className={ACTION}
                                >
                                    Mach ich trotzdem
                                </button>
                            )}

                            {notice.blueprint !== null && (
                                <button
                                    type="button"
                                    onClick={() => onAdopt(notice)}
                                    className={ACTION}
                                >
                                    Selbst übernehmen
                                </button>
                            )}

                            {/* „Alles gut" statt „Schließen": Das Wegklicken
                                ist die Antwort auf eine Absage, nicht das
                                Abräumen einer Systemmeldung. */}
                            <button
                                type="button"
                                onClick={() =>
                                    router.delete(destroy.url(notice.id), {
                                        preserveScroll: true,
                                    })
                                }
                                className="ml-auto shrink-0 cursor-pointer rounded-lg px-3 py-2 text-xs text-muted-foreground transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent hover:text-accent-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            >
                                Alles gut
                            </button>
                        </div>
                    </CardContent>
                </Card>
            ))}
        </section>
    );
}
