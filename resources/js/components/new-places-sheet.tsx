import { Link, router } from '@inertiajs/react';
import { ArrowRight, Check } from 'lucide-react';
import { useEffect, useState } from 'react';
import { AiMascot } from '@/components/ai-mascot';
import type { MascotState } from '@/components/ai-mascot';
import { AiSuggestionFailure } from '@/components/ai-suggestion';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { useNewPlaces } from '@/hooks/use-new-places';
import {
    BOTTOM_SHEET,
    OUTLINE_BUTTON,
    PRIMARY_BUTTON,
    QUIET_LINK,
} from '@/lib/interaction';
import { cn } from '@/lib/utils';
import { day as calendarDay } from '@/routes/calendar';
import { store, suggestions } from '@/routes/calendar/semester/places';

/** Beide Knöpfe gleich breit — ein kleinerer Ablehn-Knopf wäre eine Empfehlung, keine Wahl. */
const ACTION_BUTTON = 'flex-1 justify-center';

/**
 * Die KI holt die Routine über den Semesterwechsel.
 *
 * Die Schale von „Tag ordnen" — Vorher durchgestrichen, Pfeil, Nachher fett —,
 * aber mit der Einzelwahl der Anpassung: Jeder Platz hat seinen eigenen Haken,
 * und alle stehen vorab auf Ja. Angenommen wird, was den Haken behält.
 *
 * Was keinen Vorschlag hat, steht darunter — nicht wählbar, nicht verschwiegen.
 * Die Person legt es selbst hin; das Sheet sagt, warum.
 */
export function NewPlacesSheet({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const suggestion = useNewPlaces();
    /**
     * Was die Figur im Kopf gerade tut.
     *
     * Kein eigener Zustand — dieselben zwei Flags, die auch die Skelette und
     * die Absage steuern. Was die KI tut, steht damit an genau einer Stelle.
     */
    const mascotState: MascotState = suggestion.loading
        ? 'thinking'
        : suggestion.failed
          ? 'stumped'
          : 'speaking';
    const [declined, setDeclined] = useState<Set<number>>(new Set());
    const [refusal, setRefusal] = useState<string | null>(null);
    const [applying, setApplying] = useState(false);

    // Beim Öffnen fragen, nicht beim Rendern — und jedes Öffnen fragt neu, weil
    // sich seit dem letzten Mal der Plan geändert haben kann.
    useEffect(() => {
        if (!open) {
            return;
        }

        void suggestion.load(suggestions.url());
        // `suggestion.load` ist bei jedem Rendern neu — es in die
        // Abhängigkeiten zu nehmen hieße, bei jedem Zustand neu zu fragen.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    // Abgewählt wird die Vorschlagskennung, nicht die Gewohnheit: Jeder
    // Aufruf legt neue Vorschläge an, alte Abwahlen treffen darum nichts mehr
    // — ohne dass ein Effekt sie zurücksetzen müsste.
    const chosen = suggestion.places.filter(
        (place) => !declined.has(place.suggestionId),
    );

    /** Schließen räumt die Abweisung weg — sie gehörte zu diesem Versuch. */
    function close(next: boolean) {
        if (!next) {
            setRefusal(null);
        }

        onOpenChange(next);
    }

    function toggle(suggestionId: number) {
        setDeclined((current) => {
            const next = new Set(current);

            if (next.has(suggestionId)) {
                next.delete(suggestionId);
            } else {
                next.add(suggestionId);
            }

            return next;
        });
    }

    function apply() {
        setApplying(true);
        setRefusal(null);

        router.post(
            store.url(),
            {
                places: chosen.map((place) => ({
                    id: place.id,
                    time: place.time,
                    days: place.days,
                    suggestion_id: place.suggestionId,
                })),
            },
            {
                preserveScroll: true,
                onSuccess: () => close(false),
                onError: (errors) =>
                    setRefusal(
                        Object.values(errors)[0] ??
                            'Das ließ sich gerade nicht übernehmen.',
                    ),
                onFinish: () => setApplying(false),
            },
        );
    }

    return (
        <Sheet open={open} onOpenChange={close}>
            <SheetContent side="bottom" className={BOTTOM_SHEET}>
                <SheetHeader className="flex-row items-start gap-3 p-0">
                    {/* Die Figur steht neben dem Text, nicht davor:
                        Sie ist hier kein Aufzählungszeichen, sondern
                        der Gegenüber, der gerade überlegt oder
                        antwortet. Der Zustand kommt aus demselben
                        `loading`/`failed`, das die Skelette steuert. */}
                    <AiMascot
                        state={mascotState}
                        className="mt-0.5 size-9 shrink-0 text-primary"
                    />

                    <div className="flex min-w-0 flex-col gap-2">
                        <SheetTitle className="type-eyebrow text-left text-primary">
                            Neue Plätze
                        </SheetTitle>
                        <SheetDescription className="text-left text-sm leading-relaxed text-foreground">
                            {suggestion.refusal ??
                                suggestion.reason ??
                                (suggestion.loading ? (
                                    <Skeleton
                                        as="span"
                                        className="inline-block h-5 w-3/4 align-middle"
                                    />
                                ) : (
                                    'Wo deine Gewohnheiten jetzt Platz hätten, so nah wie möglich an der alten Zeit.'
                                ))}
                        </SheetDescription>
                    </div>
                </SheetHeader>

                <div className="mt-5 flex flex-col gap-2">
                    {suggestion.loading &&
                        [0, 1, 2].map((index) => (
                            <Skeleton
                                key={index}
                                className="h-20 rounded-[14px]"
                            />
                        ))}

                    {suggestion.failed && (
                        <AiSuggestionFailure
                            onRetry={() =>
                                void suggestion.load(suggestions.url())
                            }
                        />
                    )}

                    {suggestion.places.map((place) => {
                        const on = !declined.has(place.suggestionId);

                        return (
                            <div
                                key={place.suggestionId}
                                className="flex flex-col gap-1"
                            >
                                <button
                                    type="button"
                                    onClick={() => toggle(place.suggestionId)}
                                    aria-pressed={on}
                                    className={cn(
                                        'flex w-full cursor-pointer items-start gap-3 rounded-[14px] border-2 bg-card px-4 py-3 text-left transition-[border-color,scale] duration-[var(--duration-press)] ease-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.97]',
                                        on
                                            ? 'border-primary'
                                            : 'border-border hover:border-secondary',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full border-2 transition-colors duration-[var(--duration-press)] ease-out',
                                            on
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-input',
                                        )}
                                        aria-hidden="true"
                                    >
                                        {on && (
                                            <Check
                                                className="size-3.5"
                                                strokeWidth={3}
                                            />
                                        )}
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block text-[15px] leading-snug font-semibold">
                                            {place.title}
                                        </span>
                                        <span className="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                                            <span className="line-through">
                                                {place.previousLabel.replace(
                                                    'braucht einen neuen Platz · lief bisher ',
                                                    '',
                                                )}
                                            </span>
                                            <ArrowRight
                                                className="size-3"
                                                aria-hidden="true"
                                            />
                                            <span className="font-semibold text-foreground">
                                                {place.label}
                                            </span>
                                        </span>
                                        <span className="mt-1 block text-sm leading-relaxed text-muted-foreground">
                                            {place.reason}
                                        </span>
                                    </span>
                                </button>
                                {/* Der Weg in den Tag: Dort liegt der Vorschlag
                                gestrichelt neben den Kursen, um die es geht —
                                und lässt sich übernehmen oder selbst
                                einordnen. */}
                                <Link
                                    href={calendarDay(place.previewDate, {
                                        query: {
                                            suggestion: place.suggestionId,
                                        },
                                    })}
                                    className={`${QUIET_LINK} self-end text-xs`}
                                >
                                    Im Tag ansehen
                                </Link>
                            </div>
                        );
                    })}
                </div>

                {suggestion.unplaced.length > 0 && (
                    <section className="mt-5 border-t border-border pt-4">
                        <h3 className="type-eyebrow text-muted-foreground">
                            Die legst du selbst hin
                        </h3>
                        <ul className="mt-2 flex flex-col gap-2">
                            {suggestion.unplaced.map((habit) => (
                                <li key={habit.id} className="text-sm">
                                    <span className="font-semibold">
                                        {habit.title}
                                    </span>
                                    <span className="block text-muted-foreground">
                                        {habit.message}
                                    </span>
                                </li>
                            ))}
                        </ul>
                        <p className="mt-2 text-xs leading-relaxed text-muted-foreground">
                            Tippe sie im Tag an. Dort findest du ihre
                            Einstellungen.
                        </p>
                    </section>
                )}

                {refusal !== null && (
                    <p
                        role="alert"
                        className="mt-4 rounded-[14px] border border-primary/25 bg-accent px-4 py-3 text-sm leading-relaxed"
                    >
                        {refusal}
                    </p>
                )}

                <div className="mt-6 flex gap-2">
                    <button
                        type="button"
                        onClick={() => close(false)}
                        className={`${OUTLINE_BUTTON} ${ACTION_BUTTON}`}
                    >
                        Lass so
                    </button>
                    <button
                        type="button"
                        onClick={apply}
                        disabled={chosen.length === 0 || applying}
                        className={`${PRIMARY_BUTTON} ${ACTION_BUTTON} w-auto`}
                    >
                        {chosen.length === 1
                            ? 'Einen übernehmen'
                            : `${chosen.length} übernehmen`}
                    </button>
                </div>
            </SheetContent>
        </Sheet>
    );
}
