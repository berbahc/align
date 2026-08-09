import { useForm } from '@inertiajs/react';
import { ArrowRight, BookOpen, Dumbbell, GlassWater, Moon } from 'lucide-react';
import { useState } from 'react';
import { AiSuggestion, AiSuggestionFailure } from '@/components/ai-suggestion';
import InputError from '@/components/input-error';
import { formatWeekdays, SchedulePicker } from '@/components/schedule-picker';
import type { ScheduleTypeOption } from '@/components/schedule-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { useSmallestStep } from '@/hooks/use-smallest-step';
import { cn } from '@/lib/utils';
import { suggestions } from '@/routes/habits/smallest-step';
import type { BehaviorType, ScheduleType, Weekday } from '@/types';

export interface Direction {
    value: BehaviorType;
    label: string;
    description: string;
    suggestions: string[];
}

const DIRECTION_ICONS: Record<BehaviorType, typeof Moon> = {
    movement: Dumbbell,
    learning: BookOpen,
    nutrition: GlassWater,
    other: Moon,
};

const STEP_COUNT = 5;

const EYEBROW = 'text-[11px] font-semibold tracking-[0.11em] uppercase';

const PRIMARY_BUTTON =
    'inline-flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-primary px-6 text-[15px] font-semibold text-primary-foreground transition-colors duration-200 hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:opacity-50';

/**
 * §5.5 — Auswahlkachel: Selektion ist ein 2px-Rahmen, die Füllung ändert sich
 * nicht. Ein bewusst leises Muster, das für alle Einfachauswahlen gilt.
 */
const CHOICE_TILE =
    'cursor-pointer rounded-[14px] border-2 bg-card text-left transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';

/** Ein neutraler Nachmittagstermin, von dem aus sich in beide Richtungen steppen lässt. */
const DEFAULT_TIME = '17:00';

export function HabitWizard({
    directions,
    triggerSuggestions,
    scheduleTypes,
    action,
}: {
    directions: Direction[];
    triggerSuggestions: string[];
    scheduleTypes: ScheduleTypeOption[];
    action: string;
}) {
    const [step, setStep] = useState(1);
    const [ownTitle, setOwnTitle] = useState(false);
    const [ownSituation, setOwnSituation] = useState(false);
    const [ownStep, setOwnStep] = useState(false);

    const suggestion = useSmallestStep();

    const { data, setData, post, processing, errors } = useForm({
        behavior_type: '' as BehaviorType | '',
        title: '',
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

    const canContinue = [
        data.behavior_type !== '',
        data.title.trim().length > 0,
        isFixed
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
     */
    function loadSuggestions() {
        void suggestion.load(suggestions.url(), {
            title: data.title,
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
            // Richtung ungültig — sonst bleibt er still stehen.
            title: '',
        }));
        setOwnTitle(false);
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
                            const Icon = DIRECTION_ICONS[candidate.value];
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
                        {direction.suggestions.map((suggestion) => {
                            const isSelected =
                                !ownTitle && data.title === suggestion;

                            return (
                                <button
                                    key={suggestion}
                                    type="button"
                                    aria-pressed={isSelected}
                                    onClick={() => {
                                        setOwnTitle(false);
                                        setData('title', suggestion);
                                    }}
                                    className={cn(
                                        CHOICE_TILE,
                                        'px-4 py-3 text-[15px]',
                                        isSelected
                                            ? 'border-primary'
                                            : 'border-border hover:border-secondary',
                                    )}
                                >
                                    {suggestion}
                                </button>
                            );
                        })}

                        <button
                            type="button"
                            aria-pressed={ownTitle}
                            onClick={() => {
                                setOwnTitle(true);
                                setData('title', '');
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
                        <div className="flex flex-col gap-2">
                            {triggerSuggestions.map((situation) => {
                                const isSelected =
                                    !ownSituation &&
                                    data.trigger_situation === situation;

                                return (
                                    <button
                                        key={situation}
                                        type="button"
                                        aria-pressed={isSelected}
                                        onClick={() => {
                                            setOwnSituation(false);
                                            setData(
                                                'trigger_situation',
                                                situation,
                                            );
                                        }}
                                        className={cn(
                                            CHOICE_TILE,
                                            'px-4 py-3 text-[15px]',
                                            isSelected
                                                ? 'border-primary'
                                                : 'border-border hover:border-secondary',
                                        )}
                                    >
                                        {situation}
                                    </button>
                                );
                            })}

                            <button
                                type="button"
                                aria-pressed={ownSituation}
                                onClick={() => {
                                    setOwnSituation(true);
                                    setData('trigger_situation', '');
                                }}
                                className={cn(
                                    CHOICE_TILE,
                                    'border-dashed px-4 py-3 text-[15px] text-muted-foreground',
                                    ownSituation
                                        ? 'border-primary'
                                        : 'border-border hover:border-secondary',
                                )}
                            >
                                Eigene Situation
                            </button>

                            {ownSituation && (
                                <div className="grid gap-2 pt-1">
                                    <Label
                                        htmlFor="trigger_situation"
                                        className="sr-only"
                                    >
                                        Eigene Situation
                                    </Label>
                                    <Input
                                        id="trigger_situation"
                                        name="trigger_situation"
                                        autoFocus
                                        maxLength={120}
                                        placeholder="z. B. wenn ich aus der Bib komme"
                                        value={data.trigger_situation}
                                        onChange={(event) =>
                                            setData(
                                                'trigger_situation',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                            )}
                        </div>
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
                                {isFixed ? 'Zeitpunkt' : 'Auslöser'}
                            </p>
                            <p className="mt-1 text-lg leading-snug font-semibold">
                                {isFixed
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
