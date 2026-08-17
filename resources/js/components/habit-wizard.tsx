import { useForm } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useState } from 'react';
import { AiSuggestion, AiSuggestionFailure } from '@/components/ai-suggestion';
import InputError from '@/components/input-error';
import { MeasurePicker } from '@/components/measure-picker';
import {
    CHOICE_TILE,
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
import { BEHAVIOR_ICONS } from '@/lib/behavior-icons';
import { formatMeasure } from '@/lib/measure';
import { cn } from '@/lib/utils';
import { suggestions } from '@/routes/habits/smallest-step';
import type {
    BehaviorType,
    MeasureUnit,
    MeasureUnitOption,
    ScheduleType,
    Weekday,
} from '@/types';

/**
 * Ein Vorschlag aus dem Studienalltag — Handlung und Umfang getrennt.
 *
 * Spiegelt `BehaviorType::suggestions()`. Die Kachel zeigt beides und bleibt
 * damit so konkret wie vorher, nur ist die Zahl jetzt verstellbar. Wo kein
 * Umfang passt, steht `null`.
 */
export interface DirectionSuggestion {
    title: string;
    amount: number | null;
    unit: MeasureUnit | null;
    /**
     * Kann die Gewohnheit überhaupt eine Stelle im Tag haben?
     *
     * „Treppe statt Aufzug" kann es nicht — für sie steht Schritt 3 auf „Wenn
     * es sich ergibt", statt eine Uhrzeit zu erfinden. Vorgewählt, nicht
     * erzwungen.
     */
    plannable: boolean;
}

export interface Direction {
    value: BehaviorType;
    label: string;
    description: string;
    suggestions: DirectionSuggestion[];
}

const STEP_COUNT = 5;

const EYEBROW = 'text-[11px] font-semibold tracking-[0.11em] uppercase';

const PRIMARY_BUTTON =
    'inline-flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-primary px-6 text-[15px] font-semibold text-primary-foreground transition-colors duration-200 hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:opacity-50';

/** Ein neutraler Nachmittagstermin, von dem aus sich in beide Richtungen steppen lässt. */
const DEFAULT_TIME = '17:00';

export function HabitWizard({
    directions,
    triggerSuggestions,
    scheduleTypes,
    measureUnits,
    action,
}: {
    directions: Direction[];
    triggerSuggestions: string[];
    scheduleTypes: ScheduleTypeOption[];
    measureUnits: MeasureUnitOption[];
    action: string;
}) {
    const [step, setStep] = useState(1);
    const [ownTitle, setOwnTitle] = useState(false);
    const [ownStep, setOwnStep] = useState(false);

    const suggestion = useSmallestStep();

    const { data, setData, post, processing, errors } = useForm({
        behavior_type: '' as BehaviorType | '',
        title: '',
        target_amount: null as number | null,
        target_unit: '' as MeasureUnit | '',
        schedule_type: 'dynamic' as ScheduleType,
        trigger_situation: '',
        scheduled_time: DEFAULT_TIME,
        scheduled_days: [1, 2, 3, 4, 5] as Weekday[],
        motivation: '',
        smallest_step: '',
    });

    const direction = directions.find(
        (candidate) => candidate.value === data.behavior_type,
    );

    const isFixed = data.schedule_type === 'fixed';
    const isUnplanned = data.schedule_type === 'opportunistic';

    const measureLabel = formatMeasure(
        data.target_amount,
        data.target_unit === '' ? null : data.target_unit,
        measureUnits,
    );

    const canContinue = [
        data.behavior_type !== '',
        data.title.trim().length > 0,
        // Was sich ergibt, verlangt nichts: keine Situation, keine Uhrzeit.
        isUnplanned
            ? true
            : isFixed
              ? data.scheduled_days.length > 0
              : data.trigger_situation.trim().length > 0,
        // Der kleinste Schritt ist überspringbar — bei ø 3,92 Schuldgefühl
        // darf hier kein weiteres Pflichtfeld entstehen.
        true,
    ][step - 1];

    /**
     * Holt die Vorschläge der KI für den kleinsten Schritt.
     *
     * Die Situation reist mit, weil ein Schritt an ihr hängt: „Leg die Schuhe
     * an die Tür" passt zu „wenn ich nach Hause komme", nicht zu „nach dem
     * Aufstehen". Bei fester Uhrzeit gibt es keine — dann entscheidet der Titel.
     *
     * Der Umfang reist im Titel mit: Ob jemand 10 oder 45 Minuten vorhat, ändert,
     * was ein sinnvoller erster Handgriff ist.
     */
    function loadSuggestions() {
        void suggestion.load(suggestions.url(), {
            title:
                measureLabel === null
                    ? data.title
                    : `${data.title} · ${measureLabel}`,
            behavior_type: data.behavior_type,
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

    function chooseDirection(value: BehaviorType) {
        setData((current) => ({
            ...current,
            behavior_type: value,
            // Ein Richtungswechsel macht einen Vorschlag aus der alten
            // Richtung ungültig — sonst bleibt er still stehen. Der Umfang
            // gehörte zu ihm und geht mit: „2 Liter" hat nach dem Wechsel auf
            // „Bewegung" niemand mehr gemeint.
            title: '',
            target_amount: null,
            target_unit: '',
        }));
        setOwnTitle(false);
    }

    /**
     * Ein Vorschlag setzt Titel, Umfang und die Art der Planung in einem Zug.
     *
     * Für „Treppe statt Aufzug" steht danach „Wenn es sich ergibt" — der
     * Katalog weiß, dass diese Gewohnheit keinen Platz im Tag haben kann. Wer
     * widerspricht, stellt in Schritt 3 um; die Wahl bleibt offen.
     */
    function chooseSuggestion(candidate: DirectionSuggestion) {
        setOwnTitle(false);
        setData((current) => ({
            ...current,
            title: candidate.title,
            target_amount: candidate.amount,
            target_unit: candidate.unit ?? '',
            schedule_type: candidate.plannable ? 'dynamic' : 'opportunistic',
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
                            'h-1 flex-1 rounded-full transition-colors duration-200',
                            index < step ? 'bg-primary' : 'bg-sand',
                        )}
                    />
                ))}
            </div>

            {step === 1 && (
                <fieldset className="flex flex-col gap-4">
                    <legend className="sr-only">
                        Schritt 1 von {STEP_COUNT}: Richtung
                    </legend>
                    <p className={`${EYEBROW} text-muted-foreground`}>
                        Schritt 1 von {STEP_COUNT}
                    </p>
                    <h2 className="text-2xl leading-tight font-bold">
                        Woran möchtest du arbeiten?
                    </h2>

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
                                        chooseDirection(candidate.value)
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
                                    <span className="text-xs leading-relaxed text-muted-foreground">
                                        {candidate.description}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                    <InputError message={errors.behavior_type} />
                </fieldset>
            )}

            {step === 2 && direction && (
                <fieldset className="flex flex-col gap-4">
                    <legend className="sr-only">
                        Schritt 2 von {STEP_COUNT}: Gewohnheit
                    </legend>
                    <p className={`${EYEBROW} text-muted-foreground`}>
                        Schritt 2 von {STEP_COUNT} · {direction.label}
                    </p>
                    <h2 className="text-2xl leading-tight font-bold">
                        Womit fängst du an?
                    </h2>
                    <p className="text-sm leading-relaxed text-muted-foreground">
                        Klein anfangen wirkt besser als groß planen. Du kannst
                        später jederzeit mehr daraus machen.
                    </p>

                    <div className="flex flex-col gap-2">
                        {direction.suggestions.map((candidate) => {
                            const isSelected =
                                !ownTitle && data.title === candidate.title;
                            const suggested = formatMeasure(
                                candidate.amount,
                                candidate.unit,
                                measureUnits,
                            );

                            return (
                                <button
                                    key={candidate.title}
                                    type="button"
                                    aria-pressed={isSelected}
                                    onClick={() => chooseSuggestion(candidate)}
                                    className={cn(
                                        CHOICE_TILE,
                                        'flex items-baseline justify-between gap-3 px-4 py-3 text-[15px]',
                                        isSelected
                                            ? 'border-primary'
                                            : 'border-border hover:border-secondary',
                                    )}
                                >
                                    <span>{candidate.title}</span>
                                    {/* Der Umfang steht leiser als die Handlung:
                                        er ist ein Startwert, keine Vorgabe. */}
                                    {suggested !== null && (
                                        <span className="shrink-0 text-xs text-muted-foreground">
                                            {suggested}
                                        </span>
                                    )}
                                </button>
                            );
                        })}

                        <button
                            type="button"
                            aria-pressed={ownTitle}
                            onClick={() => {
                                setOwnTitle(true);
                                setData((current) => ({
                                    ...current,
                                    title: '',
                                    target_amount: null,
                                    target_unit: '',
                                    // Die Planbarkeit gehörte zum Vorschlag,
                                    // nicht zur eigenen Gewohnheit — für die
                                    // entscheidet Schritt 3 wieder von vorn.
                                    schedule_type: 'dynamic',
                                }));
                            }}
                            className={cn(
                                CHOICE_TILE,
                                'border-dashed px-4 py-3 text-[15px] text-muted-foreground',
                                ownTitle
                                    ? 'border-primary'
                                    : 'border-border hover:border-secondary',
                            )}
                        >
                            Etwas anderes
                        </button>

                        {ownTitle && (
                            <div className="grid gap-2 pt-1">
                                <Label htmlFor="title" className="sr-only">
                                    Eigene Gewohnheit
                                </Label>
                                <Input
                                    id="title"
                                    name="title"
                                    autoFocus
                                    maxLength={80}
                                    placeholder="z. B. Wäsche sortieren"
                                    value={data.title}
                                    onChange={(event) =>
                                        setData('title', event.target.value)
                                    }
                                />
                            </div>
                        )}
                    </div>
                    <InputError message={errors.title} />

                    {/* Der Umfang steht hier und nicht in einem eigenen Schritt:
                        „Klein anfangen wirkt besser als groß planen" steht
                        oben auf dieser Seite — hier wird es entschieden.
                        Sichtbar erst, wenn es eine Gewohnheit gibt, an der ein
                        Umfang hängen könnte. */}
                    {data.title.trim().length > 0 && (
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
                    )}
                </fieldset>
            )}

            {step === 3 && (
                <fieldset className="flex flex-col gap-4">
                    <legend className="sr-only">
                        Schritt 3 von {STEP_COUNT}: Auslöser
                    </legend>
                    <p className={`${EYEBROW} text-muted-foreground`}>
                        Schritt 3 von {STEP_COUNT}
                    </p>
                    <h2 className="text-2xl leading-tight font-bold">
                        Wann machst du das?
                    </h2>
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
                    <p className={`${EYEBROW} text-muted-foreground`}>
                        Schritt 4 von {STEP_COUNT}
                    </p>
                    <h2 className="text-2xl leading-tight font-bold">
                        Womit fängt das an?
                    </h2>
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
                                    placeholder="z. B. Stell das Glas ans Bett"
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
                    <p className={`${EYEBROW} text-muted-foreground`}>
                        Schritt 5 von {STEP_COUNT}
                    </p>
                    <h2 className="text-2xl leading-tight font-bold">
                        Dein Vorsatz
                    </h2>

                    <div className="flex flex-col gap-3 rounded-2xl bg-card p-5">
                        <div>
                            <p className={`${EYEBROW} text-muted-foreground`}>
                                {isUnplanned
                                    ? 'Gelegenheit'
                                    : isFixed
                                      ? 'Zeitpunkt'
                                      : 'Auslöser'}
                            </p>
                            <p className="mt-1 text-lg leading-snug font-semibold">
                                {isUnplanned
                                    ? 'wenn es sich ergibt'
                                    : isFixed
                                      ? `${data.scheduled_time} Uhr · ${formatWeekdays(data.scheduled_days)}`
                                      : data.trigger_situation}
                            </p>
                        </div>
                        <div className="h-4 w-px self-center bg-sand" />
                        <div>
                            <p className={`${EYEBROW} text-muted-foreground`}>
                                Gewohnheit
                            </p>
                            <p className="mt-1 text-lg leading-snug font-semibold text-primary">
                                {data.title}
                                {measureLabel !== null && (
                                    <span className="font-normal text-muted-foreground">
                                        {' · '}
                                        {measureLabel}
                                    </span>
                                )}
                            </p>
                        </div>

                        {data.smallest_step.trim().length > 0 && (
                            <>
                                <div className="h-4 w-px self-center bg-sand" />
                                <div>
                                    <p
                                        className={`${EYEBROW} text-muted-foreground`}
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
                            className={`${EYEBROW} text-center text-muted-foreground`}
                        >
                            Du kannst das jederzeit ändern
                        </p>
                    </>
                )}

                {step > 1 && (
                    <button
                        type="button"
                        onClick={() => setStep(step - 1)}
                        className="cursor-pointer text-sm text-muted-foreground transition-colors duration-200 hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        Zurück
                    </button>
                )}
            </div>
        </form>
    );
}
