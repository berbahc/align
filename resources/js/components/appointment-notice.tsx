import { router } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { destroy } from '@/routes/appointment-notices';
import type { AppointmentNotice as Notice } from '@/types';

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
    className,
}: {
    notices: Notice[];
    className?: string;
}) {
    if (notices.length === 0) {
        return null;
    }

    return (
        <section role="status" className={cn('flex flex-col gap-3', className)}>
            {notices.map((notice) => (
                <Card key={notice.id}>
                    <CardContent className="flex items-center gap-4">
                        <div className="min-w-0 flex-1">
                            <p className="text-[15px] font-semibold">
                                {notice.message}
                            </p>
                            <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                {notice.detail}
                            </p>
                        </div>

                        {/* „Alles gut" statt „Schließen": Das Wegklicken ist
                            die Antwort auf eine Absage, nicht das Abräumen
                            einer Systemmeldung. */}
                        <button
                            type="button"
                            onClick={() =>
                                router.delete(destroy.url(notice.id), {
                                    preserveScroll: true,
                                })
                            }
                            className="shrink-0 cursor-pointer rounded-lg px-3 py-2 text-xs text-muted-foreground transition-colors duration-200 hover:bg-accent hover:text-accent-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            Alles gut
                        </button>
                    </CardContent>
                </Card>
            ))}
        </section>
    );
}
