import { Head, Link, useForm } from '@inertiajs/react';
import type { Direction } from '@/components/habit-wizard';
import InputError from '@/components/input-error';
import { MeasurePicker } from '@/components/measure-picker';
import {
    CHOICE_TILE,
    SchedulePicker,
    SituationPicker,
} from '@/components/schedule-picker';
import type { ScheduleTypeOption } from '@/components/schedule-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { BEHAVIOR_ICONS } from '@/lib/behavior-icons';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index, update } from '@/routes/habits';
import type {
    BehaviorType,
    MeasureUnit,
    MeasureUnitOption,
    ScheduleType,
    Weekday,
} from '@/types';

const EYEBROW = 'text-[11px] font-semibold tracking-[0.11em] uppercase';

const PRIMARY_BUTTON =
    'inline-flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-primary px-6 text-[15px] font-semibold text-primary-foreground transition-colors duration-200 hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:opacity-50';

/** Die Gewohnheit, wie der Server sie zum Vorbelegen schickt. */
interface EditableHabit {
    id: number;
    title: string;
    behaviorType: BehaviorType;
    targetAmount: number | null;
    targetUnit: MeasureUnit | null;
    scheduleType: ScheduleType;
    triggerSituation: string | null;
    scheduledTime: string | null;
    scheduledDays: Weekday[] | null;
    smallestStep: string | null;
    motivation: string | null;
}

interface EditHabitProps {
    habit: EditableHabit;
    directions: Direction[];
    triggerSuggestions: string[];
    scheduleTypes: ScheduleTypeOption[];
    measureUnits: MeasureUnitOption[];
}

/** Ein neutraler Nachmittagstermin, falls die Gewohnheit noch keinen hat. */
const DEFAULT_TIME = '17:00';

/**
 * Eine bestehende Gewohnheit ändern — alles auf einer Seite.
 *
 * Flach statt in fünf Schritten: Der Wizard führt jemanden, der noch nicht
 * weiß, was er will. Wer etwas ändert, weiß es — für ihn wäre die Führung ein
 * Umweg, und er müsste sich durch vier Schritte klicken, um im fünften ein Wort
 * zu ändern.
 *
 * Der Verlauf bleibt unberührt. Genau darin liegt der Sinn: Bis hierher blieb
 * nur Beenden und Neuanlegen, und das kostete jedes Mal die Serie.
 */
export default function EditHabit({
    habit,
    directions,
    triggerSuggestions,
    scheduleTypes,
    measureUnits,
}: EditHabitProps) {
    const { data, setData, put, processing, errors } = useForm({
        behavior_type: habit.behaviorType,
        title: habit.title,
        target_amount: habit.targetAmount,
        target_unit: (habit.targetUnit ?? '') as MeasureUnit | '',
        schedule_type: habit.scheduleType,
        trigger_situation: habit.triggerSituation ?? '',
        scheduled_time: habit.scheduledTime ?? DEFAULT_TIME,
        scheduled_days: habit.scheduledDays ?? ([1, 2, 3, 4, 5] as Weekday[]),
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
                <h1 className="mb-8 text-2xl leading-tight font-bold text-primary">
                    Gewohnheit bearbeiten
                </h1>

                <form onSubmit={submit} className="flex flex-col gap-8">
                    <fieldset className="flex flex-col gap-3">
                        <legend className={`${EYEBROW} text-muted-foreground`}>
                            Richtung
                        </legend>

                        <div className="grid gap-3 sm:grid-cols-2">
                            {directions.map((candidate) => {
                                const Icon = BEHAVIOR_ICONS[candidate.value];
                                const isSelected =
                                    data.behavior_type === candidate.value;

                                return (
                                    <button
                                        key={candidate.value}
                                        type="button"
                                        aria-pressed={isSelected}
                                        onClick={() =>
                                            setData(
                                                'behavior_type',
                                                candidate.value,
                                            )
                                        }
                                        className={cn(
                                            CHOICE_TILE,
                                            'flex flex-col gap-2 px-4 py-4',
                                            isSelected
                                                ? 'border-primary'
                                                : 'border-border hover:border-secondary',
                                        )}
                                    >
                                        <Icon
                                            className="size-6 text-primary"
                                            strokeWidth={1.5}
                                            aria-hidden="true"
                                        />
                                        <span className="text-[15px] font-semibold">
                                            {candidate.label}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                        <InputError message={errors.behavior_type} />
                    </fieldset>

                    {/* Freies Feld statt der Vorschlagskacheln aus dem Wizard:
                        Wer seinen Titel schon hat, soll ihn nicht versehentlich
                        gegen einen fremden tauschen. */}
                    <div className="flex flex-col gap-2">
                        <Label htmlFor="title" className={EYEBROW}>
                            Gewohnheit
                        </Label>
                        <Input
                            id="title"
                            name="title"
                            maxLength={80}
                            value={data.title}
                            onChange={(event) =>
                                setData('title', event.target.value)
                            }
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="flex flex-col gap-2">
                        <p className={`${EYEBROW} text-muted-foreground`}>
                            Umfang{' '}
                            <span className="font-normal normal-case">
                                (optional)
                            </span>
                        </p>
                        <MeasurePicker
                            units={measureUnits}
                            amount={data.target_amount}
                            unit={data.target_unit}
                            onChange={(amount, unit) =>
                                setData((current) => ({
                                    ...current,
                                    target_amount: amount,
                                    target_unit: unit,
                                }))
                            }
                        />
                        <InputError message={errors.target_amount} />
                    </div>

                    <fieldset className="flex flex-col gap-3">
                        <legend className={`${EYEBROW} text-muted-foreground`}>
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
                        >
                            <SituationPicker
                                suggestions={triggerSuggestions}
                                value={data.trigger_situation}
                                onChange={(value) =>
                                    setData('trigger_situation', value)
                                }
                            />
                        </SchedulePicker>

                        <InputError message={errors.trigger_situation} />
                        <InputError message={errors.scheduled_time} />
                        <InputError message={errors.scheduled_days} />

                        {/* Beobachtend statt belehrend: der Satz erklärt die
                            Folge, bevor sie eintritt, statt sie hinterher zu
                            melden. */}
                        {!isFixed && (
                            <p className="text-xs leading-relaxed text-muted-foreground">
                                Ohne feste Uhrzeit gibt es nichts zu erinnern —
                                eine gesetzte Erinnerung wird beim Speichern
                                abgeschaltet.
                            </p>
                        )}
                    </fieldset>

                    <div className="flex flex-col gap-2">
                        <Label htmlFor="smallest_step" className={EYEBROW}>
                            Erster Schritt{' '}
                            <span className="font-normal text-muted-foreground normal-case">
                                (optional)
                            </span>
                        </Label>
                        <Input
                            id="smallest_step"
                            name="smallest_step"
                            maxLength={160}
                            placeholder="z. B. Stell das Glas ans Bett"
                            value={data.smallest_step}
                            onChange={(event) =>
                                setData('smallest_step', event.target.value)
                            }
                        />
                        <InputError message={errors.smallest_step} />
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label htmlFor="motivation" className={EYEBROW}>
                            Warum dir das wichtig ist{' '}
                            <span className="font-normal text-muted-foreground normal-case">
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
                            className="text-center text-sm text-muted-foreground transition-colors duration-200 hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            Abbrechen
                        </Link>

                        {/* Die Sorge, die diesen Weg bisher verstellt hat: Was
                            schon geschafft ist, bleibt. */}
                        <p
                            className={`${EYEBROW} text-center text-muted-foreground`}
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
