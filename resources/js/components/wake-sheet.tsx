import { router } from '@inertiajs/react';
import { useState } from 'react';
import { FrameCarryList } from '@/components/frame-carry-list';
import { TimeStepper } from '@/components/time-stepper';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { isQuiet, useFrameCarry } from '@/hooks/use-frame-carry';
import { BOTTOM_SHEET, QUIET_BUTTON } from '@/lib/interaction';
import { destroy, preview, store } from '@/routes/sleep/days';

const SHEET_ACTION =
    'inline-flex h-12 flex-1 cursor-pointer items-center justify-center rounded-xl px-4 text-center text-[15px] font-semibold transition-[background-color,scale] duration-[var(--duration-press)] ease-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:pointer-events-none disabled:opacity-50 motion-safe:active:scale-[0.97]';

/**
 * „Heute bin ich später aufgestanden."
 *
 * Der Schlafplan kennt Wochentage, dieser Weg kennt Tage. Er ändert nichts am
 * Rhythmus — er sagt, wie dieser eine Morgen wirklich angefangen hat, und
 * verschiebt den Rahmen mit.
 *
 * Was daran hängt, zieht mit: Situative Gewohnheiten von selbst, feste nach
 * Rückfrage. Die Vorschau steht deshalb im Sheet und nicht dahinter — wer die
 * Aufstehzeit dreht, soll sehen, was das mit seinem Tag macht, bevor er es
 * tut.
 *
 * Der Aufrufer hängt einen Schlüssel an Tag, Uhrzeit und Offenheit — ein
 * Sheet, das die Zeit von vorgestern zeigte, wäre eine Frage nach etwas
 * anderem. Über den Schlüssel statt über einen Effekt: Der Zustand wird beim
 * Öffnen neu gesetzt, nicht nachträglich korrigiert.
 */
export function WakeSheet({
    open,
    date,
    wakeTime,
    overridden,
    onOpenChange,
}: {
    open: boolean;
    /** Der Tag, um den es geht — „YYYY-MM-DD". */
    date: string;
    /** Die Aufstehzeit, die gerade für diesen Tag gilt. */
    wakeTime: string;
    /** Hat dieser Tag schon einen eigenen Rahmen? Dann gibt es einen Rückweg. */
    overridden: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [time, setTime] = useState(wakeTime);
    const [saving, setSaving] = useState(false);
    const carry = useFrameCarry({});

    /**
     * Erst fragen, dann speichern.
     *
     * Zieht nichts mit — und das ist der Normalfall, weil situative
     * Gewohnheiten dem Rahmen ohnehin folgen —, wird direkt gespeichert. Ein
     * Zwischenschritt, der „nichts passiert" meldet, ist ein Klick ohne
     * Auskunft.
     */
    function ask() {
        setSaving(true);

        void carry.ask(preview.url(), { date, wake_time: time }, (result) => {
            if (result === null || isQuiet(result)) {
                save(false);
            } else {
                setSaving(false);
            }
        });
    }

    function save(carryHabits: boolean) {
        setSaving(true);

        router.post(
            store.url(),
            { date, wake_time: time, carry_habits: carryHabits },
            {
                preserveScroll: true,
                onFinish: () => {
                    setSaving(false);
                    onOpenChange(false);
                },
            },
        );
    }

    function undo() {
        setSaving(true);

        router.delete(destroy.url(), {
            data: { date },
            preserveScroll: true,
            onFinish: () => {
                setSaving(false);
                onOpenChange(false);
            },
        });
    }

    const decision =
        carry.preview !== null && !isQuiet(carry.preview)
            ? carry.preview
            : null;

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="bottom" className={BOTTOM_SHEET}>
                <SheetHeader className="gap-2 p-0">
                    <SheetTitle className="type-eyebrow text-left text-primary">
                        Nur dieser Tag
                    </SheetTitle>
                    <SheetDescription className="text-left text-[15px] leading-relaxed text-foreground">
                        {decision === null
                            ? 'Wann bist du aufgestanden? Dein Tag beginnt dann hier — dein Schlafplan bleibt, wie er ist.'
                            : 'Diese festen Uhrzeiten liegen dann vor deinem Tag. Sie können um dieselbe Zeit mitrücken — nur heute.'}
                    </SheetDescription>
                </SheetHeader>

                {decision === null ? (
                    <div className="mt-6 flex justify-center">
                        <TimeStepper
                            value={time}
                            onChange={setTime}
                            label="Aufstehzeit an diesem Tag"
                        />
                    </div>
                ) : (
                    <div className="mt-5">
                        <FrameCarryList
                            moves={decision.moves}
                            blocked={decision.blocked}
                        />
                    </div>
                )}

                <div className="mt-6 flex flex-col gap-3 sm:flex-row">
                    {decision === null ? (
                        <button
                            type="button"
                            onClick={ask}
                            disabled={
                                saving || carry.asking || time === wakeTime
                            }
                            className={`${SHEET_ACTION} bg-primary text-primary-foreground hover:bg-primary/90`}
                        >
                            {(saving || carry.asking) && (
                                <Spinner className="mr-2 size-4" />
                            )}
                            Tag hier beginnen
                        </button>
                    ) : (
                        <>
                            {decision.moves.length > 0 && (
                                <button
                                    type="button"
                                    onClick={() => save(true)}
                                    disabled={saving}
                                    className={`${SHEET_ACTION} bg-primary text-primary-foreground hover:bg-primary/90`}
                                >
                                    Mitnehmen
                                </button>
                            )}
                            <button
                                type="button"
                                onClick={() => save(false)}
                                disabled={saving}
                                className={`${SHEET_ACTION} border border-primary text-primary hover:bg-accent`}
                            >
                                Nur den Rahmen
                            </button>
                        </>
                    )}
                </div>

                {/* Der Rückweg steht nur da, wenn es einen gibt. */}
                {overridden && decision === null && (
                    <button
                        type="button"
                        onClick={undo}
                        disabled={saving}
                        className={`${QUIET_BUTTON} mt-4 self-center`}
                    >
                        Wieder nach Schlafplan
                    </button>
                )}
            </SheetContent>
        </Sheet>
    );
}
