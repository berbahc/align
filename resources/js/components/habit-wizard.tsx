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
import { findConflict } from '@/lib/slots';
import { cn } from '@/lib/utils';
import { suggestions } from '@/routes/habits/smallest-step';
import type {
    BusySlot,
    ChainCandidate,
    DurationLimits,
    HabitAdoption,
    HabitCategory,
    HabitCategoryOption,
    HabitTemplateOption,
    ScheduleType,
    SituationChoice,
    SleepWindow,
    Weekday,
} from '@/types';

/**
 * Die Schritte des Assistenten, in ihrer Reihenfolge.
 *
 * Beim Übernehmen fällt der erste weg: Der Bereich ist keine Frage mehr, wenn
 * die Gewohnheit schon feststeht. Alles andere bleibt — die Dauer, der
 * Zeitpunkt, der erste Schritt, der Vorsatz. Es ist dasselbe Anlegen.
 */
const STEPS: number[] = [1, 2, 3, 4, 5];
const ADOPTION_STEPS: number[] = [2, 3, 4, 5];

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
    adoption = null,
    action,
}: {
    categories: HabitCategoryOption[];
    triggerSuggestions: SituationChoice[];
    scheduleTypes: ScheduleTypeOption[];
    durationLimits: DurationLimits;
    sleepWindows?: SleepWindow[];
    chainCandidates?: ChainCandidate[];
    busySlots?: BusySlot[];
    /** Gesetzt, wenn eine fremde Gewohnheit zur eigenen wird — sonst null. */
    adoption?: HabitAdoption | null;
    action: string;
}) {
    const blueprint = adoption?.blueprint ?? null;
    const steps = adoption === null ? STEPS : ADOPTION_STEPS;

    const [step, setStep] = useState<number>(steps[0]!);
    const [category, setCategory] = useState<HabitCategory | ''>(
        // Beim Übernehmen steht die Vorlage fest, und mit ihr der Bereich.
        categories.find((candidate) =>
            candidate.templates.some(
                (option) => option.key === blueprint?.templateKey,
            ),
        )?.value ?? '',
    );
    const [ownStep, setOwnStep] = useState(false);

    const suggestion = useSmallestStep();

    const { data, setData, post, processing, errors } = useForm({
        template_key: blueprint?.templateKey ?? '',
        // Die Dauer der anderen Person ist ein Startwert, keine Vorgabe: Sie
        // lässt sich hier ändern wie bei jeder neuen Gewohnheit.
        target_amount: blueprint?.durationMinutes ?? durationLimits.min,
        schedule_type: blueprint?.scheduleType ?? ('dynamic' as ScheduleType),
        trigger_situation: blueprint?.triggerSituation ?? '',
        scheduled_time: blueprint?.scheduledTime ?? DEFAULT_TIME,
        // Täglich als Vorgabe, für beide Anker: So verhielten sich Situationen
        // schon immer, und eine Gewohnheit, die man aufbauen will, hat jeden
        // Tag den besten Grund zu laufen. Wer seltener will, wählt ab.
        scheduled_days: (blueprint?.scheduledDays ?? [
            1, 2, 3, 4, 5, 6, 7,
        ]) as Weekday[],
        chained_to_habit_id: null as number | null,
        motivation: '',
        smallest_step: '',
        // Was mit dem Anlegen beantwortet ist: die Absage-Notiz und die offene
        // Anfrage, aus der heraus übernommen wird.
        notice_id: adoption?.noticeId ?? null,
        appointment_id: adoption?.appointmentId ?? null,
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

    // Was an dieser Stelle schon liegt — mit der Viertelstunde Luft, die
    // der Server verlangt. Der Hinweis steht im Picker; hier hält er den
    // Schritt an, damit die Absage nicht erst nach dem letzten Knopf kommt.
    const blocked = isFixed
        ? findConflict(
              data.scheduled_time,
              data.scheduled_days,
              busySlots,
              data.target_amount,
          )
        : null;

    const canContinue = {
        1: category !== '',
        2: data.template_key !== '',
        3:
            data.schedule_type === 'chained'
                ? data.chained_to_habit_id !== null
                : isFixed
                  ? data.scheduled_days.length > 0 &&
                    asleep === null &&
                    blocked === null
                  : data.trigger_situation.trim().length > 0 &&
                    data.scheduled_days.length > 0,
        // Der kleinste Schritt ist überspringbar — bei ø 3,92 Schuldgefühl
        // darf hier kein weiteres Pflichtfeld entstehen.
        4: true,
        5: true,
    }[step];

    const position = steps.indexOf(step);
    const isLast = position === steps.length - 1;

    /** „Schritt 2 von 4" — beim Übernehmen sind es vier, sonst fünf. */
    const stepLabel = `Schritt ${position + 1} von ${steps.length}`;

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

        setStep(steps[position + 1] ?? step);
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

    /**
     * Wohin ein Fehler des Servers gehört — der Schritt, in dem das Feld steht.
     *
     * Der Wizard steht beim Abschicken auf dem letzten Schritt, die Uhrzeit
     * aber auf dem dritten. Käme die Absage dort an, wo niemand hinschaut,
     * sähe es aus, als ginge der Knopf einfach nicht.
     */
    const STEP_OF_FIELD: Record<string, number> = {
        template_key: 2,
        target_amount: 2,
        schedule_type: 3,
        trigger_situation: 3,
        scheduled_time: 3,
        scheduled_days: 3,
        chained_to_habit_id: 3,
        smallest_step: 4,
        motivation: 5,
    };

    function submit(event: React.FormEvent) {
        event.preventDefault();
        post(action, {
            onError: (failed) => {
                const target = Math.min(
                    ...Object.keys(failed).map(
                        (field) => STEP_OF_FIELD[field] ?? step,
                    ),
                );

                if (steps.includes(target)) {
                    setStep(target);
                }
            },
        });
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-8">
            <div className="flex items-center gap-2" aria-hidden="true">
                {steps.map((candidate, index) => (
                    <span
                        key={candidate}
                        className={cn(
                            'h-1 flex-1 rounded-full transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                            index <= position ? 'bg-primary' : 'bg-sand',
                        )}
                    />
                ))}
            </div>

            {step === 1 && (
                <fieldset className="flex flex-col gap-4">
                    <legend className="sr-only">{stepLabel}: Bereich</legend>
                    <p className={'type-eyebrow text-muted-foreground'}>
                        {stepLabel}
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
                                </button>
                            );
                        })}
                    </div>
                </fieldset>
            )}

            {step === 2 && chosenCategory && (
                <fieldset className="flex flex-col gap-4">
                    <legend className="sr-only">{stepLabel}: Gewohnheit</legend>
                    <p className={'type-eyebrow text-muted-foreground'}>
                        {stepLabel}
                    </p>
                    {/* Der Bereich steht als Überschrift, nicht als Zusatz in
                        der Zeile darüber: Er ist die Antwort auf Schritt 1 und
                        sagt, worin hier gewählt wird. */}
                    <h2 className="type-heading">{chosenCategory.label}</h2>

                    {/* Beim Übernehmen ist die Gewohnheit keine Wahl mehr —
                        sie steht schon fest. Der Satz darunter sagt, was das
                        heißt: Der Verlauf der anderen Person bleibt bei ihr. */}
                    {adoption !== null && (
                        <div className="flex flex-col gap-1 rounded-2xl bg-card p-4">
                            <p className="text-[15px] font-semibold">
                                {blueprint?.title}
                            </p>
                            <p className="text-xs leading-relaxed text-muted-foreground">
                                Bei dir beginnt Tag eins.
                            </p>
                        </div>
                    )}

                    <div
                        className={cn(
                            'flex flex-col gap-2',
                            adoption !== null && 'hidden',
                        )}
                    >
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
                    <legend className="sr-only">{stepLabel}: Auslöser</legend>
                    <p className={'type-eyebrow text-muted-foreground'}>
                        {stepLabel}
                    </p>
                    <h2 className="type-heading">Wann machst du das?</h2>
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
                        durationMinutes={data.target_amount}
                        sleepWindows={sleepWindows}
                    >
                        <SituationPicker
                            suggestions={triggerSuggestions}
                            value={data.trigger_situation}
                            onChange={(value) =>
                                setData('trigger_situation', value)
                            }
                            days={data.scheduled_days}
                            onDaysChange={(days) =>
                                setData('scheduled_days', days)
                            }
                        />
                    </SchedulePicker>

                    <InputError message={errors.trigger_situation} />
                    <InputError message={errors.scheduled_time} />
                    <InputError message={errors.scheduled_days} />
                    <InputError message={errors.chained_to_habit_id} />
                    <InputError message={errors.schedule_type} />
                </fieldset>
            )}

            {step === 4 && (
                <fieldset className="flex flex-col gap-4">
                    <legend className="sr-only">
                        {stepLabel}: Erster Schritt
                    </legend>
                    <p className={'type-eyebrow text-muted-foreground'}>
                        {stepLabel}
                    </p>
                    <h2 className="type-heading">Womit fängt das an?</h2>
                    <p className="text-sm leading-relaxed text-muted-foreground">
                        Ein einziger Handgriff, der in einer Minute getan ist.
                    </p>

                    {suggestion.loading ? (
                        <AiSuggestion state="thinking">
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
                            <AiSuggestion state="speaking">
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
                                'hollow px-4 py-3 text-[15px] text-muted-foreground',
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

                    <InputError message={errors.smallest_step} />
                </fieldset>
            )}

            {step === 5 && (
                <fieldset className="flex flex-col gap-4">
                    <legend className="sr-only">
                        {stepLabel}: Vorsatz bestätigen
                    </legend>
                    <p className={'type-eyebrow text-muted-foreground'}>
                        {stepLabel}
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
                                      : /* Die Tage gehören auch zur Situation
                                           — außer sie sind alle sieben, dann
                                           sagt der Moment schon alles. */
                                        data.scheduled_days.length === 7
                                        ? data.trigger_situation
                                        : `${data.trigger_situation} · ${formatWeekdays(data.scheduled_days)}`}
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
                {!isLast ? (
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

                {position > 0 && (
                    <button
                        type="button"
                        onClick={() => setStep(steps[position - 1] ?? step)}
                        className={QUIET_BUTTON}
                    >
                        Zurück
                    </button>
                )}
            </div>
        </form>
    );
}
