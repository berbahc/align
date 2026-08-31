import { Form, Head, useForm } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { HabitWizard } from '@/components/habit-wizard';
import InputError from '@/components/input-error';
import type { ScheduleTypeOption } from '@/components/schedule-picker';
import { TimeStepper } from '@/components/time-stepper';
import { Spinner } from '@/components/ui/spinner';
import { PRIMARY_BUTTON, QUIET_BUTTON } from '@/lib/interaction';
import { skip, sleep, store } from '@/routes/onboarding';
import type { DurationLimits, HabitCategoryOption, SleepWindow } from '@/types';

interface OnboardingProps {
    /** Gibt es schon einen gespeicherten Rahmen? Dann beginnt die Gewohnheit. */
    hasSleepSchedule: boolean;
    defaultWakeTime: string;
    defaultBedtime: string;
    categories: HabitCategoryOption[];
    triggerSuggestions: string[];
    scheduleTypes: ScheduleTypeOption[];
    durationLimits: DurationLimits;
    sleepWindows: SleepWindow[];
}

/**
 * Der erste Schritt in der App — in zwei Stufen.
 *
 * Zuerst der Rahmen: Aufsteh- und Schlafenszeit spannen den Tag auf, in dem
 * alles Weitere geplant wird. Ein Paar für alle Tage reicht hier; je
 * Wochentag verfeinern geht später unter „Schlaf". Danach die erste
 * Gewohnheit, aus dem Katalog.
 */
export default function Onboarding({
    hasSleepSchedule,
    defaultWakeTime,
    defaultBedtime,
    categories,
    triggerSuggestions,
    scheduleTypes,
    durationLimits,
    sleepWindows,
}: OnboardingProps) {
    const frame = useForm({
        wake_time: defaultWakeTime,
        bedtime: defaultBedtime,
    });

    function submitFrame(event: React.FormEvent) {
        event.preventDefault();
        frame.post(sleep.url());
    }

    return (
        <>
            <Head
                title={hasSleepSchedule ? 'Erste Gewohnheit' : 'Dein Rahmen'}
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
                        nichts ein (Designsprache §1.5). */}
                    <Form {...skip.form()}>
                        <button type="submit" className={QUIET_BUTTON}>
                            Später einrichten
                        </button>
                    </Form>
                </header>

                <main className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-6 pb-16">
                    {hasSleepSchedule ? (
                        <>
                            <div className="mb-8">
                                <h1 className="type-title text-primary">
                                    Fangen wir klein an.
                                </h1>
                                <p className="mt-2 text-[15px] leading-relaxed text-muted-foreground">
                                    Eine einzige Gewohnheit reicht für den
                                    Anfang. Du kannst später bis zu fünf
                                    gleichzeitig verfolgen.
                                </p>
                            </div>

                            <HabitWizard
                                categories={categories}
                                triggerSuggestions={triggerSuggestions}
                                scheduleTypes={scheduleTypes}
                                durationLimits={durationLimits}
                                sleepWindows={sleepWindows}
                                action={store.url()}
                            />
                        </>
                    ) : (
                        <>
                            <div className="mb-8">
                                <h1 className="type-title text-primary">
                                    Wann beginnt dein Tag?
                                </h1>
                                <p className="mt-2 text-[15px] leading-relaxed text-muted-foreground">
                                    Aufstehen und Schlafen sind der Rahmen, in
                                    dem deine Gewohnheiten Platz finden. Du
                                    kannst später jedem Wochentag eigene Zeiten
                                    geben.
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
                        </>
                    )}
                </main>
            </div>
        </>
    );
}
