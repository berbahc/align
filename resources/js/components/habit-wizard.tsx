import { useForm } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useState } from 'react';
import { AiSuggestion, AiSuggestionFailure } from '@/components/ai-suggestion';
import { DurationPicker } from '@/components/duration-picker';
import InputError from '@/components/input-error';
import {
    formatWeekdays,
    SchedulePicker,
    SituationPicker,
} from '@/components/schedule-picker';
import type { ScheduleTypeOption } from '@/components/schedule-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { useSmallestStep } from '@/hooks/use-smallest-step';
import { CATEGORY_ICONS } from '@/lib/behavior-icons';
import {
    CHOICE_TILE,
    CHOICE_TILE_OFF,
    CHOICE_TILE_ON,
    PRIMARY_BUTTON,
    QUIET_BUTTON,
} from '@/lib/interaction';
import { outsideSleepWindow } from '@/lib/sleep';
import { cn } from '@/lib/utils';
import { suggestions } from '@/routes/habits/smallest-step';
import type {
    BusySlot,
    ChainCandidate,
    DurationLimits,
    HabitCategory,
    HabitCategoryOption,
    HabitTemplateOption,
    ScheduleType,
    SleepWindow,
    Weekday,
} from '@/types';

const STEP_COUNT = 5;

/** Ein neutraler Nachmittagstermin, von dem aus sich in beide Richtungen steppen lässt. */
const DEFAULT_TIME = '17:00';

/**
 * Der Assistent zum Anlegen — jede Gewohnheit kommt aus dem Katalog.
 *
 * Die freie Eingabe ist bewusst weg: Gewohnheiten unterscheiden sich zu
 * stark, als dass ein Formular allen gerecht würde — was sich ergibt, hat
 * keine Uhrzeit, was sich über den Tag verteilt, keine Dauer. Der Katalog
 * enthält nur planbare Aktivitäten, und deshalb hat hier jede Wahl eine
 * Dauer und einen Platz im Tag.
 */
export function HabitWizard({
    categories,
    triggerSuggestions,
    scheduleTypes,
    durationLimits,
    sleepWindows = [],
    chainCandidates = [],
    busySlots = [],
    action,
}: {
    categories: HabitCategoryOption[];
    triggerSuggestions: string[];
    scheduleTypes: ScheduleTypeOption[];
    durationLimits: DurationLimits;
    sleepWindows?: SleepWindow[];
    chainCandidates?: ChainCandidate[];
    busySlots?: BusySlot[];
    action: string;
}) {
    const [step, setStep] = useState(1);
    const [category, setCategory] = useState<HabitCategory | ''>('');
    const [ownStep, setOwnStep] = useState(false);

    const suggestion = useSmallestStep();

    const { data, setData, post, processing, errors } = useForm({
        template_key: '',
        target_amount: durationLimits.min,
        schedule_type: 'dynamic' as ScheduleType,
        trigger_situation: '',
        scheduled_time: DEFAULT_TIME,
        scheduled_days: [1, 2, 3, 4, 5] as Weekday[],
        chained_to_habit_id: null as number | null,
        motivation: '',
        smallest_step: '',
    });

    const chosenCategory = categories.find(
        (candidate) => candidate.value === category,
    );

    const template = categories
        .flatMap((candidate) => candidate.templates)
        .find((candidate) => candidate.key === data.template_key);

    const isFixed = data.schedule_type === 'fixed';

    // Der Rahmen aus dem Schlafplan: Was außerhalb läge, weist der Server ab
    // — dann darf der Schritt auch nicht weitergehen, der Hinweis steht schon
    // im Picker.
    const asleep = isFixed
        ? outsideSleepWindow(
              data.scheduled_time,
              data.scheduled_days,
              sleepWindows,
          )
        : null;

    const canContinue = [
        category !== '',
        data.template_key !== '',
        data.schedule_type === 'chained'
            ? data.chained_to_habit_id !== null
            : isFixed
              ? data.scheduled_days.length > 0 && asleep === null
              : data.trigger_situation.trim().length > 0,
        // Der kleinste Schritt ist überspringbar — bei ø 3,92 Schuldgefühl
        // darf hier kein weiteres Pflichtfeld entstehen.
        true,
    ][step - 1];

    /**
     * Holt die Vorschläge der KI für den kleinsten Schritt.
     *
     * Die Vorlage und die Dauer reisen mit, weil ein Schritt an ihnen hängt:
     * Ob jemand 10 oder 45 Minuten vorhat, ändert den sinnvollen ersten
     * Handgriff. Die Situation ebenso — „Leg die Schuhe an die Tür" passt zu
     * „wenn ich nach Hause komme", nicht zu „nach dem Aufstehen".
     */
    function loadSuggestions() {
        void suggestion.load(suggestions.url(), {
            template_key: data.template_key,
            target_amount: data.target_amount,
            trigger_situation: isFixed ? undefined : data.trigger_situation,
        });
    }

    /** Ein Schritt weiter — beim Eintritt in Schritt 4 fragt die KI mit. */
    function advance() {
        if (step === 3) {
            loadSuggestions();
        }

        setStep(step + 1);
    }

    function chooseCategory(value: HabitCategory) {
        setCategory(value);
        // Ein Kategoriewechsel macht die gewählte Vorlage ungültig — sonst
        // stünde eine Sport-Gewohnheit still unter „Uni & Lernen".
        setData((current) => ({
            ...current,
            template_key: '',
            target_amount: durationLimits.min,
        }));
    }

    /** Eine Vorlage setzt Titel und Dauer in einem Zug. */
    function chooseTemplate(candidate: HabitTemplateOption) {
        setData((current) => ({
            ...current,
            template_key: candidate.key,
            target_amount: candidate.defaultMinutes,
        }));
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();
        post(action);
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-8">
            <div className="flex items-center gap-2" aria-hidden="true">
                {Array.from({ length: STEP_COUNT }, (_, index) => (
                    <span
                        key={index}
                        className={cn(
                            'h-1 flex-1 rounded-full transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                            index < step ? 'bg-primary' : 'bg-sand',
                        )}
                    />
                ))}
            </div>

            {step === 1 && (
                <fieldset className="flex flex-col gap-4">
                    <legend className="sr-only">
                        Schritt 1 von {STEP_COUNT}: Bereich
                    </legend>
                    <p className={'type-eyebrow text-muted-foreground'}>
                        Schritt 1 von {STEP_COUNT}
                    </p>
                    <h2 className="type-heading">
                        Woran möchtest du arbeiten?
                    </h2>

                    <div className="grid gap-3 sm:grid-cols-2">
                        {categories.map((candidate) => {
                            const Icon = CATEGORY_ICONS[candidate.value];
                            const isSelected = category === candidate.value;

                            return (
                                <button
                                    key={candidate.value}
                                    type="button"
                                    aria-pressed={isSelected}
                                    onClick={() =>
                                        chooseCategory(candidate.value)
                                    }
                                    className={cn(
                                        CHOICE_TILE,
                                        'flex flex-col gap-2 px-4 py-4',
                                        isSelected
                                            ? CHOICE_TILE_ON
                                            : CHOICE_TILE_OFF,
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
                                    <span className="text-xs leading-relaxed text-muted-foreground">
                                        {candidate.description}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </fieldset>
            )}

            {step === 2 && chosenCategory && (
                <fieldset className="flex flex-col gap-4">
                    <legend className="sr-only">
                        Schritt 2 von {STEP_COUNT}: Gewohnheit
                    </legend>
                    <p className={'type-eyebrow text-muted-foreground'}>
                        Schritt 2 von {STEP_COUNT} · {chosenCategory.label}
                    </p>
                    <h2 className="type-heading">Womit fängst du an?</h2>
                    <p className="text-sm leading-relaxed text-muted-foreground">
                        Alles hier lässt sich fest im Tag einplanen. Klein
                        anfangen wirkt besser als groß planen — die Dauer
                        stellst du gleich darunter ein.
                    </p>

                    <div className="flex flex-col gap-2">
                        {chosenCategory.templates.map((candidate) => {
                            const isSelected =
                                data.template_key === candidate.key;

                            return (
                                <button
                                    key={candidate.key}
                                    type="button"
                                    aria-pressed={isSelected}
                                    onClick={() => chooseTemplate(candidate)}
                                    className={cn(
                                        CHOICE_TILE,
                                        'flex items-baseline justify-between gap-3 px-4 py-3 text-[15px]',
                                        isSelected
                                            ? CHOICE_TILE_ON
                                            : CHOICE_TILE_OFF,
                                    )}
                                >
                                    <span>{candidate.title}</span>
                                    {/* Die Dauer steht leiser als die
                                        Handlung: ein Startwert, keine Vorgabe. */}
                                    <span className="shrink-0 text-xs text-muted-foreground">
                                        {candidate.defaultMinutes} Min
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                    <InputError message={errors.template_key} />

                    {template !== undefined && (
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
                    )}
                </fieldset>
            )}

            {step === 3 && (
                <fieldset className="flex flex-col gap-4">
                    <legend className="sr-only">
                        Schritt 3 von {STEP_COUNT}: Auslöser
                    </legend>
                    <p className={'type-eyebrow text-muted-foreground'}>
                        Schritt 3 von {STEP_COUNT}
                    </p>
                    <h2 className="type-heading">Wann machst du das?</h2>
                    <p className="text-sm leading-relaxed text-muted-foreground">
                        Ein Moment im Tag trägt besser als eine Uhrzeit — eine
                        Situation löst das Verhalten von selbst aus. Für alles,
                        was ohnehin fest im Kalender steht, gibt es die Uhrzeit.
                    </p>

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
                        onDaysChange={(days) => setData('scheduled_days', days)}
                        chainCandidates={chainCandidates}
                        chainedTo={data.chained_to_habit_id}
                        onChainedToChange={(id) =>
                            setData('chained_to_habit_id', id)
                        }
                        busySlots={busySlots}
                        sleepWindows={sleepWindows}
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
                </fieldset>
            )}

            {step === 4 && (
                <fieldset className="flex flex-col gap-4">
                    <legend className="sr-only">
                        Schritt 4 von {STEP_COUNT}: Erster Schritt
                    </legend>
                    <p className={'type-eyebrow text-muted-foreground'}>
                        Schritt 4 von {STEP_COUNT}
                    </p>
                    <h2 className="type-heading">Womit fängt das an?</h2>
                    <p className="text-sm leading-relaxed text-muted-foreground">
                        Ein einziger Handgriff, der in einer Minute getan ist.
                        Er steht später unter deiner Gewohnheit — für die Tage,
                        an denen der Anfang das Schwere ist.
                    </p>

                    {suggestion.loading ? (
                        <AiSuggestion>
                            <div className="flex flex-col gap-2">
                                <Skeleton className="h-12 rounded-xl" />
                                <Skeleton className="h-12 rounded-xl" />
                                <Skeleton className="h-12 rounded-xl" />
                            </div>
                        </AiSuggestion>
                    ) : suggestion.failed ? (
                        <AiSuggestionFailure onRetry={loadSuggestions} />
                    ) : (
                        suggestion.steps.length > 0 && (
                            <AiSuggestion>
                                <div className="flex flex-col gap-2">
                                    {suggestion.steps.map((candidate) => {
                                        const isSelected =
                                            !ownStep &&
                                            data.smallest_step === candidate;

                                        return (
                                            <button
                                                key={candidate}
                                                type="button"
                                                aria-pressed={isSelected}
                                                onClick={() => {
                                                    setOwnStep(false);
                                                    setData(
                                                        'smallest_step',
                                                        candidate,
                                                    );
                                                }}
                                                className={cn(
                                                    CHOICE_TILE,
                                                    'px-4 py-3 text-[15px] leading-snug',
                                                    isSelected
                                                        ? 'border-primary'
                                                        : 'border-transparent hover:border-secondary',
                                                )}
                                            >
                                                {candidate}
                                            </button>
                                        );
                                    })}
                                </div>
                            </AiSuggestion>
                        )
                    )}

                    <div className="flex flex-col gap-2">
                        <button
                            type="button"
                            aria-pressed={ownStep}
                            onClick={() => {
                                setOwnStep(true);
                                setData('smallest_step', '');
                            }}
                            className={cn(
                                CHOICE_TILE,
                                'border-dashed px-4 py-3 text-[15px] text-muted-foreground',
                                ownStep
                                    ? 'border-primary'
                                    : 'border-border hover:border-secondary',
                            )}
                        >
                            Eigener Schritt
                        </button>

                        {ownStep && (
                            <div className="grid gap-2 pt-1">
                                <Label
                                    htmlFor="smallest_step"
                                    className="sr-only"
                                >
                                    Eigener erster Schritt
                                </Label>
                                <Input
                                    id="smallest_step"
                                    name="smallest_step"
                                    autoFocus
                                    maxLength={160}
                                    placeholder="z. B. Leg die Laufschuhe an die Tür"
                                    value={data.smallest_step}
                                    onChange={(event) =>
                                        setData(
                                            'smallest_step',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                        )}
                    </div>

                    <p className="text-xs leading-relaxed text-muted-foreground">
                        Du kannst diesen Schritt auch auslassen und einfach
                        weitergehen.
                    </p>

                    <InputError message={errors.smallest_step} />
                </fieldset>
            )}

            {step === 5 && (
                <fieldset className="flex flex-col gap-4">
                    <legend className="sr-only">
                        Schritt 5 von {STEP_COUNT}: Vorsatz bestätigen
                    </legend>
                    <p className={'type-eyebrow text-muted-foreground'}>
                        Schritt 5 von {STEP_COUNT}
                    </p>
                    <h2 className="type-heading">Dein Vorsatz</h2>

                    <div className="flex flex-col gap-3 rounded-2xl bg-card p-5">
                        <div>
                            <p className={'type-eyebrow text-muted-foreground'}>
                                {isFixed ? 'Zeitpunkt' : 'Auslöser'}
                            </p>
                            <p className="mt-1 text-lg leading-snug font-semibold">
                                {data.schedule_type === 'chained'
                                    ? `nach „${chainCandidates.find((candidate) => candidate.id === data.chained_to_habit_id)?.title ?? ''}"`
                                    : isFixed
                                      ? `${data.scheduled_time} Uhr · ${formatWeekdays(data.scheduled_days)}`
                                      : data.trigger_situation}
                            </p>
                        </div>
                        <div className="h-4 w-px self-center bg-sand" />
                        <div>
                            <p className={'type-eyebrow text-muted-foreground'}>
                                Gewohnheit
                            </p>
                            <p className="mt-1 text-lg leading-snug font-semibold text-primary">
                                {template?.title}
                                <span className="font-normal text-muted-foreground">
                                    {' · '}
                                    {data.target_amount} Min
                                </span>
                            </p>
                        </div>

                        {data.smallest_step.trim().length > 0 && (
                            <>
                                <div className="h-4 w-px self-center bg-sand" />
                                <div>
                                    <p
                                        className={
                                            'type-eyebrow text-muted-foreground'
                                        }
                                    >
                                        Erster Schritt
                                    </p>
                                    <p className="mt-1 text-[15px] leading-snug">
                                        {data.smallest_step}
                                    </p>
                                </div>
                            </>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="motivation">
                            Warum ist dir das wichtig?{' '}
                            <span className="font-normal text-muted-foreground">
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
                </fieldset>
            )}

            <div className="flex flex-col gap-3">
                {step < STEP_COUNT ? (
                    <button
                        type="button"
                        disabled={!canContinue}
                        onClick={advance}
                        className={PRIMARY_BUTTON}
                    >
                        Weiter
                        <ArrowRight className="size-4" aria-hidden="true" />
                    </button>
                ) : (
                    <>
                        {/* time-blocking.md: das ausdrückliche Commitment ist
                            der eine bewusste Willensakt, der die Wenn-Dann-
                            Planung überhaupt wirksam macht. */}
                        <button
                            type="submit"
                            disabled={processing}
                            className={PRIMARY_BUTTON}
                        >
                            {processing && <Spinner className="size-4" />}
                            Ich nehme mir das vor
                        </button>
                        <p
                            className={
                                'type-eyebrow text-center text-muted-foreground'
                            }
                        >
                            Du kannst das jederzeit ändern
                        </p>
                    </>
                )}

                {step > 1 && (
                    <button
                        type="button"
                        onClick={() => setStep(step - 1)}
                        className={QUIET_BUTTON}
                    >
                        Zurück
                    </button>
                )}
            </div>
        </form>
    );
}
