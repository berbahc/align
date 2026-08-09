import { router } from '@inertiajs/react';
import { PersonCircle } from '@/components/person-circle';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { destroy, update } from '@/routes/friendships';
import type { FriendshipPerson } from '@/types';

/**
 * Offene Freundschaftsanfragen — auf der Übersicht wie im Community-Bereich.
 *
 * Mockup A2 setzt die Anfrage oben auf den Home-Screen, über die Gewohnheiten.
 * Das ist der einzige Weg, auf dem jemand von ihr erfährt: Es gibt keine Mail,
 * keine Systembenachrichtigung, und community_feature3.md §5 schließt das
 * Nachfassen ausdrücklich aus (Ngocanh: einseitiges Motivieren demotiviert
 * beide Seiten).
 *
 * Deshalb blendet sich der Hinweis auch **nicht von selbst aus**. Er trägt eine
 * Auskunft samt Entscheidung, kein Lob — dieselbe Begründung, aus der die
 * FlashNotice stehen bleibt. Ein Hinweis, der nach vier Sekunden verschwindet,
 * verlöre genau die Information, für die es sonst keinen zweiten Kanal gibt.
 *
 * Kein Modal: Ein Hinweis, der den Bildschirm sperrt, ist eine Forderung.
 */
export function FriendRequestNotice({
    requests,
    className,
}: {
    requests: FriendshipPerson[];
    className?: string;
}) {
    if (requests.length === 0) {
        return null;
    }

    function answer(friendshipId: number, accepted: boolean) {
        const options = { preserveScroll: true };

        if (accepted) {
            router.patch(update.url(friendshipId), {}, options);

            return;
        }

        router.delete(destroy.url(friendshipId), options);
    }

    return (
        <section role="status" className={cn('flex flex-col gap-3', className)}>
            <h2 className="text-[11px] font-semibold tracking-[0.11em] text-muted-foreground uppercase">
                {requests.length === 1
                    ? 'Eine Anfrage'
                    : `${requests.length} Anfragen`}
            </h2>

            {/* Gestrichelt `sand`: designsprache.md §7.3 — vom Menschen
                angelegt und noch offen. Nicht `accent` gestrichelt, das ist
                der KI vorbehalten. */}
            {requests.map((person) => (
                <div
                    key={person.id}
                    className="rounded-2xl border-[1.5px] border-dashed border-sand bg-card p-4 sm:p-5"
                >
                    <div className="flex items-center gap-3">
                        <div className="flex -space-x-2">
                            <PersonCircle initial={person.initial} />
                            <PersonCircle pending />
                        </div>
                        <p className="min-w-0 text-[15px] font-semibold">
                            {person.name}
                            <span className="ml-1.5 font-normal text-muted-foreground">
                                fragt dich
                            </span>
                        </p>
                    </div>

                    {/* Beide Knöpfe gleich breit — §7.2 Regel 1. Ein grauer
                        Sekundärknopf neben einem farbigen wäre eine
                        Empfehlung, keine Wahl. */}
                    <div className="mt-4 flex gap-3">
                        <Button
                            onClick={() => answer(person.id, true)}
                            className="h-11 flex-1 cursor-pointer rounded-xl"
                        >
                            Passt mir
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => answer(person.id, false)}
                            className="h-11 flex-1 cursor-pointer rounded-xl border-primary text-primary"
                        >
                            Lieber nicht
                        </Button>
                    </div>
                </div>
            ))}
        </section>
    );
}
