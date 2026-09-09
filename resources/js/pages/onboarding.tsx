import { Form, Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { HabitWizard } from '@/components/habit-wizard';
import InputError from '@/components/input-error';
import { OnboardingIntro } from '@/components/onboarding-intro';
import type { ScheduleTypeOption } from '@/components/schedule-picker';
import { TimeStepper } from '@/components/time-stepper';
import { Spinner } from '@/components/ui/spinner';
import { PRIMARY_BUTTON, QUIET_BUTTON } from '@/lib/interaction';
import { cn } from '@/lib/utils';
import { intro, skip, sleep, store } from '@/routes/onboarding';
import type {
    DurationLimits,
    HabitCategoryOption,
    SituationChoice,
    SleepWindow,
} from '@/types';

interface OnboardingProps {
    /** Lief der Auftakt schon? Erst danach beginnt das Einrichten. */
    hasSeenIntro: boolean;
    /** Gibt es schon einen gespeicherten Rahmen? Dann beginnt die Gewohnheit. */
    hasSleepSchedule: boolean;
    defaultWakeTime: string;
    defaultBedtime: string;
    categories: HabitCategoryOption[];
    triggerSuggestions: SituationChoice[];
    scheduleTypes: ScheduleTypeOption[];
    durationLimits: DurationLimits;
    sleepWindows: SleepWindow[];
}

/**
 * Der erste Schritt in der App — in drei Stufen.
 *
 * Zuerst der Auftakt: sieben Bilder, die zeigen, woran Vorsätze im Studium
 * scheitern und was Align dagegen tut. Er steht vorn, weil er die Frage
 * beantwortet, die alle folgenden erst sinnvoll macht — warum eine App nach
 * der Aufstehzeit fragt, bevor sie nach einer Gewohnheit fragt.
 *
 * Dann der Rahmen: Aufsteh- und Schlafenszeit spannen den Tag auf, in dem
 * alles Weitere geplant wird. Ein Paar für alle Tage reicht hier; je
 * Wochentag verfeinern geht später unter „Schlaf". Danach die erste
 * Gewohnheit, aus dem Katalog.
 *
 * Zwischen Film und Rahmen gibt es keinen Schnitt: Das letzte Bild wird zur
 * ersten Frage. Deshalb wechselt die Stufe hier im Browser, und der Server
 * erfährt erst danach davon — eine Antwort, die neu rendert, wäre genau der
 * Schnitt, den es nicht geben soll.
 */
export default function Onboarding({
    hasSeenIntro,
    hasSleepSchedule,
    defaultWakeTime,
    defaultBedtime,
    categories,
    triggerSuggestions,
    scheduleTypes,
    durationLimits,
    sleepWindows,
}: OnboardingProps) {
    const [introDone, setIntroDone] = useState(hasSeenIntro);
    /**
     * Kommt der Rahmen gerade aus dem Film? Dann fährt er wie eine Szene ein.
     *
     * Nur beim Übergang und nicht bei jedem Aufruf: Wer die Seite neu lädt,
     * kommt aus keinem Film und soll auch nicht so behandelt werden.
     */
    const [fromIntro, setFromIntro] = useState(false);
    /** Geht es zurück in den Film? Dann steigt er bei seinem letzten Bild ein. */
    const [replayingIntro, setReplayingIntro] = useState(false);
    /**
     * Der Rahmen wird noch einmal geöffnet, obwohl er schon steht.
     *
     * Sonst führt der erste Schritt des Assistenten nirgendwohin zurück: Der
     * Server sagt „Rahmen gespeichert", und die Seite zeigt ab da nur noch
     * den Assistenten. Wer die Aufstehzeit vertippt hat, käme nicht mehr
     * heran, ohne den ganzen Ablauf zu verlassen.
     */
    const [reopenFrame, setReopenFrame] = useState(false);

    const frame = useForm({
        wake_time: defaultWakeTime,
        bedtime: defaultBedtime,
    });

    /**
     * Der Auftakt ist durch — die Seite wechselt sofort, die Notiz geht
     * nebenher raus.
     *
     * `preserveState` hält den Stand, den der Browser gerade zeigt: Die
     * Antwort setzt nur `hasSeenIntro`, und darauf muss niemand warten.
     */
    function finishIntro() {
        setIntroDone(true);
        setFromIntro(true);
        setReplayingIntro(false);
        router.post(
            intro.url(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    /**
     * Zurück in den Film.
     *
     * Nur im Browser und ohne den Server zu fragen: Gesehen bleibt gesehen,
     * hier geht es um dieselbe Sitzung. Der Film steigt bei seinem letzten
     * Bild wieder ein, damit der Weg zurück so kurz ist wie der Weg hin.
     */
    function replayIntro() {
        setFromIntro(false);
        setReplayingIntro(true);
        setIntroDone(false);
    }

    /**
     * Zurück aus dem Rahmen in den Assistenten, ohne etwas zu speichern.
     *
     * Nur, wenn der Rahmen schon stand: Wer ihn gerade zum ersten Mal
     * ausfüllt, hat dahinter noch nichts, wohin er zurückkönnte.
     */
    function closeFrame() {
        setReopenFrame(false);
    }

    function submitFrame(event: React.FormEvent) {
        event.preventDefault();
        // `updateOrCreate` je Wochentag: Ein zweites Absenden korrigiert den
        // Rahmen, es legt keinen zweiten an.
        frame.post(sleep.url(), {
            onSuccess: () => setReopenFrame(false),
        });
    }

    return (
        <>
            <Head
                title={
                    !introDone
                        ? 'Willkommen'
                        : hasSleepSchedule
                          ? 'Erste Gewohnheit'
                          : 'Dein Rahmen'
                }
            />

            <div className="flex min-h-screen flex-col bg-background">
                <header className="flex items-center justify-between gap-4 p-6">
                    <span className="flex items-center gap-2">
                        <AppLogoIcon className="size-7" />
                        <span className="text-lg leading-none font-bold">
                            Align
                        </span>
                    </span>

                    {/* Überspringen ist gleichwertig sichtbar — die App fordert
                        nichts ein (Designsprache §1.5).

                        Genau ein Ausgang je Bild: Während des Films führt er
                        aus dem Film, danach aus dem ganzen Ablauf. Beide
                        nebeneinander wären eine Frage, die niemand gestellt
                        hat — „überspringen" wohin? */}
                    {introDone ? (
                        <Form {...skip.form()}>
                            <button type="submit" className={QUIET_BUTTON}>
                                Später einrichten
                            </button>
                        </Form>
                    ) : (
                        <button
                            type="button"
                            onClick={finishIntro}
                            className={QUIET_BUTTON}
                        >
                            Überspringen
                        </button>
                    )}
                </header>

                {/* Der Auftakt darf auf dem großen Schirm die Breite
                    nehmen, alles danach nicht: Der Assistent ist für eine
                    schmale Spalte gebaut, und eine Kachelreihe über die ganze
                    Seite wäre eine andere App. */}
                <main
                    className={cn(
                        'mx-auto flex w-full flex-1 flex-col justify-center px-6 pb-16',
                        introDone ? 'max-w-md' : 'max-w-md lg:max-w-6xl',
                    )}
                >
                    {!introDone ? (
                        <OnboardingIntro
                            onDone={finishIntro}
                            startAtEnd={replayingIntro}
                        />
                    ) : hasSleepSchedule && !reopenFrame ? (
                        <>
                            <div className="mb-8">
                                <h1 className="type-title text-primary">
                                    Fangen wir klein an.
                                </h1>
                            </div>

                            <HabitWizard
                                categories={categories}
                                triggerSuggestions={triggerSuggestions}
                                scheduleTypes={scheduleTypes}
                                durationLimits={durationLimits}
                                sleepWindows={sleepWindows}
                                action={store.url()}
                                onBackFromStart={() => setReopenFrame(true)}
                            />
                        </>
                    ) : (
                        <div
                            className={cn(
                                'flex flex-col',
                                fromIntro && 'intro-entering',
                            )}
                            style={{ '--intro-dir': 1 } as React.CSSProperties}
                        >
                            {/* Der Weg zurück. Er steht über der Frage und
                                nicht neben dem Ausgang oben: Zurück ist keine
                                Alternative zum Abbrechen, sondern ein Schritt
                                in demselben Ablauf.

                                Wohin er führt, hängt davon ab, woher man
                                kommt — aus dem Film oder aus dem Assistenten,
                                der den Rahmen noch einmal aufgemacht hat. */}
                            <button
                                type="button"
                                onClick={reopenFrame ? closeFrame : replayIntro}
                                className={cn(
                                    QUIET_BUTTON,
                                    'mb-6 flex items-center gap-1.5 self-start',
                                )}
                            >
                                <ArrowLeft
                                    className="size-3.5"
                                    aria-hidden="true"
                                />
                                {reopenFrame
                                    ? 'Zurück zur Gewohnheit'
                                    : 'Zurück zum Film'}
                            </button>

                            <div className="mb-8">
                                <h1 className="type-title text-primary">
                                    Wann beginnt dein Tag?
                                </h1>
                                <p className="mt-2 text-[15px] leading-relaxed text-muted-foreground">
                                    Gilt zunächst für alle Tage. Je Wochentag
                                    einstellbar ist es später.
                                </p>
                            </div>

                            <form
                                onSubmit={submitFrame}
                                className="flex flex-col gap-6"
                            >
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="flex flex-col items-center gap-3 rounded-2xl bg-card p-5">
                                        <p
                                            className={
                                                'type-eyebrow text-muted-foreground'
                                            }
                                        >
                                            Aufstehen
                                        </p>
                                        <TimeStepper
                                            value={frame.data.wake_time}
                                            onChange={(value) =>
                                                frame.setData(
                                                    'wake_time',
                                                    value,
                                                )
                                            }
                                            label="Aufstehzeit"
                                            size="compact"
                                        />
                                    </div>
                                    <div className="flex flex-col items-center gap-3 rounded-2xl bg-card p-5">
                                        <p
                                            className={
                                                'type-eyebrow text-muted-foreground'
                                            }
                                        >
                                            Schlafen
                                        </p>
                                        <TimeStepper
                                            value={frame.data.bedtime}
                                            onChange={(value) =>
                                                frame.setData('bedtime', value)
                                            }
                                            label="Schlafenszeit"
                                            size="compact"
                                        />
                                    </div>
                                </div>

                                <InputError
                                    message={
                                        frame.errors.wake_time ??
                                        frame.errors.bedtime
                                    }
                                />

                                <button
                                    type="submit"
                                    disabled={frame.processing}
                                    className={PRIMARY_BUTTON}
                                >
                                    {frame.processing && (
                                        <Spinner className="size-4" />
                                    )}
                                    Weiter
                                    <ArrowRight
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </button>
                            </form>
                        </div>
                    )}
                </main>
            </div>
        </>
    );
}
