import { Head, Link, useForm } from '@inertiajs/react';
import { DurationPicker } from '@/components/duration-picker';
import { HabitGlyph } from '@/components/habit-glyph';
import InputError from '@/components/input-error';
import { SchedulePicker, SituationPicker } from '@/components/schedule-picker';
import type { ScheduleTypeOption } from '@/components/schedule-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { PRIMARY_BUTTON, QUIET_BUTTON } from '@/lib/interaction';
import { dashboard } from '@/routes';
import { index, update } from '@/routes/habits';
import type {
    BehaviorType,
    BusySlot,
    ChainCandidate,
    DurationLimits,
    ScheduleType,
    SituationChoice,
    SleepWindow,
    Weekday,
} from '@/types';

/** Die Gewohnheit, wie der Server sie zum Vorbelegen schickt. */
interface EditableHabit {
    id: number;
    title: string;
    /** Der Katalog-Bereich; null bei Gewohnheiten aus der Zeit der freien Eingabe. */
    categoryLabel: string | null;
    /** Der Schlüssel der Katalog-Vorlage — entscheidet das Zeichen. */
    templateKey: string | null;
    behaviorType: BehaviorType;
    /** Die Dauer in Minuten; null, wenn die alte Zeile keinen Minuten-Umfang trug. */
    durationMinutes: number | null;
    scheduleType: ScheduleType;
    triggerSituation: string | null;
    scheduledTime: string | null;
    /** Abweichende Uhrzeiten je Wochentag — leer, wenn alle dieselbe haben. */
    scheduledTimes: Partial<Record<Weekday, string>>;
    scheduledDays: Weekday[] | null;
    chainedToHabitId: number | null;
    smallestStep: string | null;
    motivation: string | null;
}

interface EditHabitProps {
    habit: EditableHabit;
    triggerSuggestions: SituationChoice[];
    scheduleTypes: ScheduleTypeOption[];
    durationLimits: DurationLimits;
    sleepWindows: SleepWindow[];
    chainCandidates: ChainCandidate[];
    busySlots: BusySlot[];
}

/** Ein neutraler Nachmittagstermin, falls die Gewohnheit noch keinen hat. */
const DEFAULT_TIME = '17:00';

/**
 * Eine bestehende Gewohnheit ändern — alles auf einer Seite.
 *
 * Bearbeitet wird die Planung, nicht die Identität: Was die Gewohnheit ist,
 * steht im Katalog fest — sie wechselt ihren Zeitpunkt, ihre Dauer, ihren
 * ersten Schritt, aber nicht ihren Namen. Wer etwas anderes will, legt etwas
 * anderes an.
 *
 * Der Verlauf bleibt unberührt. Genau darin liegt der Sinn: Bis hierher blieb
 * nur Beenden und Neuanlegen, und das kostete jedes Mal die Serie.
 */
export default function EditHabit({
    habit,
    triggerSuggestions,
    scheduleTypes,
    durationLimits,
    sleepWindows,
    chainCandidates,
    busySlots,
}: EditHabitProps) {
    const { data, setData, put, processing, errors } = useForm({
        target_amount: habit.durationMinutes ?? durationLimits.min,
        schedule_type: habit.scheduleType,
        trigger_situation: habit.triggerSituation ?? '',
        scheduled_time: habit.scheduledTime ?? DEFAULT_TIME,
        // Ohne eigene Tage lief sie täglich — das ist die Altlast der
        // situativen Gewohnheiten, und das Formular muss sie so zeigen, wie sie
        // wirklich läuft.
        scheduled_days:
            habit.scheduledDays ?? ([1, 2, 3, 4, 5, 6, 7] as Weekday[]),
        // Leer heißt: jeden Tag zur selben Uhrzeit.
        scheduled_times: (habit.scheduledTimes ?? {}) as Partial<
            Record<Weekday, string>
        >,
        chained_to_habit_id: habit.chainedToHabitId,
        motivation: habit.motivation ?? '',
        smallest_step: habit.smallestStep ?? '',
    });

    const isFixed = data.schedule_type === 'fixed';

    function submit(event: React.FormEvent) {
        event.preventDefault();
        put(update.url(habit.id));
    }

    return (
        <>
            <Head title="Gewohnheit bearbeiten" />

            <div className="mx-auto w-full max-w-md p-4 sm:p-6">
                <h1 className="type-heading mb-8 text-primary">
                    Gewohnheit bearbeiten
                </h1>

                <form onSubmit={submit} className="flex flex-col gap-8">
                    {/* Die Identität wird gezeigt, nicht bearbeitet: Titel und
                        Bereich kommen aus dem Katalog und bleiben, was sie
                        sind. */}
                    <div className="flex items-center gap-3 rounded-2xl bg-card p-4">
                        <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sand text-primary">
                            <HabitGlyph habit={habit} className="size-5" />
                        </span>
                        <span className="min-w-0">
                            <span className="block truncate text-[15px] leading-snug font-semibold">
                                {habit.title}
                            </span>
                            {habit.categoryLabel !== null && (
                                <span className="mt-0.5 block text-xs text-muted-foreground">
                                    {habit.categoryLabel}
                                </span>
                            )}
                        </span>
                    </div>

                    <div className="flex flex-col gap-2">
                        <p className={'type-eyebrow text-muted-foreground'}>
                            Dauer
                        </p>
                        <DurationPicker
                            minutes={data.target_amount}
                            limits={durationLimits}
                            onChange={(minutes) =>
                                setData('target_amount', minutes)
                            }
                        />
                        <InputError message={errors.target_amount} />
                    </div>

                    <fieldset className="flex flex-col gap-3">
                        <legend
                            className={'type-eyebrow text-muted-foreground'}
                        >
                            Wann
                        </legend>

                        <SchedulePicker
                            scheduleTypes={scheduleTypes}
                            scheduleType={data.schedule_type}
                            onScheduleTypeChange={(value) =>
                                setData('schedule_type', value)
                            }
                            time={data.scheduled_time}
                            onTimeChange={(value) =>
                                setData('scheduled_time', value)
                            }
                            days={data.scheduled_days}
                            onDaysChange={(days) =>
                                setData('scheduled_days', days)
                            }
                            times={data.scheduled_times}
                            onTimesChange={(times) =>
                                setData('scheduled_times', times)
                            }
                            chainCandidates={chainCandidates}
                            chainedTo={data.chained_to_habit_id}
                            onChainedToChange={(id) =>
                                setData('chained_to_habit_id', id)
                            }
                            busySlots={busySlots}
                            durationMinutes={data.target_amount}
                            sleepWindows={sleepWindows}
                        >
                            <SituationPicker
                                suggestions={triggerSuggestions}
                                days={data.scheduled_days}
                                onDaysChange={(days) =>
                                    setData('scheduled_days', days)
                                }
                                value={data.trigger_situation}
                                onChange={(value) =>
                                    setData('trigger_situation', value)
                                }
                            />
                        </SchedulePicker>

                        <InputError message={errors.trigger_situation} />
                        <InputError message={errors.scheduled_time} />
                        <InputError message={errors.scheduled_days} />
                        <InputError message={errors.chained_to_habit_id} />

                        {/* Beobachtend statt belehrend: der Satz erklärt die
                            Folge, bevor sie eintritt, statt sie hinterher zu
                            melden. */}
                        {!isFixed && (
                            <p className="text-xs leading-relaxed text-muted-foreground">
                                Eine gesetzte Erinnerung wird beim Speichern
                                abgeschaltet.
                            </p>
                        )}
                    </fieldset>

                    <div className="flex flex-col gap-2">
                        <Label htmlFor="smallest_step" className="type-eyebrow">
                            Erster Schritt{' '}
                            <span className="font-normal tracking-normal text-muted-foreground normal-case">
                                (optional)
                            </span>
                        </Label>
                        <Input
                            id="smallest_step"
                            name="smallest_step"
                            maxLength={160}
                            placeholder="z. B. Leg die Laufschuhe an die Tür"
                            value={data.smallest_step}
                            onChange={(event) =>
                                setData('smallest_step', event.target.value)
                            }
                        />
                        <InputError message={errors.smallest_step} />
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label htmlFor="motivation" className="type-eyebrow">
                            Warum dir das wichtig ist{' '}
                            <span className="font-normal tracking-normal text-muted-foreground normal-case">
                                (optional)
                            </span>
                        </Label>
                        <Input
                            id="motivation"
                            name="motivation"
                            maxLength={200}
                            placeholder="z. B. damit ich abends runterkomme"
                            value={data.motivation}
                            onChange={(event) =>
                                setData('motivation', event.target.value)
                            }
                        />
                        <InputError message={errors.motivation} />
                    </div>

                    <div className="flex flex-col gap-3">
                        <button
                            type="submit"
                            disabled={processing}
                            className={PRIMARY_BUTTON}
                        >
                            {processing && <Spinner className="size-4" />}
                            Änderungen speichern
                        </button>

                        <Link
                            href={index()}
                            className={`${QUIET_BUTTON} text-center`}
                        >
                            Abbrechen
                        </Link>

                        {/* Die Sorge, die diesen Weg bisher verstellt hat: Was
                            schon geschafft ist, bleibt. */}
                        <p
                            className={
                                'type-eyebrow text-center text-muted-foreground'
                            }
                        >
                            Dein Verlauf bleibt erhalten
                        </p>
                    </div>
                </form>
            </div>
        </>
    );
}

EditHabit.layout = {
    breadcrumbs: [
        { title: 'Übersicht', href: dashboard() },
        { title: 'Gewohnheiten', href: index() },
        { title: 'Bearbeiten', href: '' },
    ],
};
