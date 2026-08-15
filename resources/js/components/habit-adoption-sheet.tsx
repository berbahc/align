import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import InputError from '@/components/input-error';
import {
    formatWeekdays,
    SchedulePicker,
    SituationPicker,
} from '@/components/schedule-picker';
import type { ScheduleTypeOption } from '@/components/schedule-picker';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { BEHAVIOR_ICONS } from '@/lib/behavior-icons';
import { store } from '@/routes/habits/adoptions';
import type {
    BehaviorType,
    HabitBlueprint,
    ScheduleType,
    Weekday,
} from '@/types';

/** Ein neutraler Nachmittagstermin, von dem aus sich in beide Richtungen steppen lässt. */
const DEFAULT_TIME = '17:00';

const EYEBROW = 'text-[11px] font-semibold tracking-[0.11em] uppercase';

/**
 * Eine fremde Gewohnheit für sich selbst übernehmen.
 *
 * Wer gefragt wird, ob er einen Tag lang mitmacht, sieht dabei eine Gewohnheit,
 * die er selbst nicht führt — und manchmal ist die Antwort weder ja noch nein,
 * sondern „das will ich auch". Bisher endete das mit dem einen Tag.
 *
 * Was entsteht, ist eine **eigene** Gewohnheit, kein geteilter Eintrag: Sie
 * zählt gegen die eigenen fünf Plätze und beginnt bei Tag eins. Ein Eintrag,
 * den zwei Menschen teilen, wäre der Dauerstatus, den community_feature3.md §6
 * ausschließt — man sähe fortan, ob die andere Person heute abgehakt hat.
 *
 * Deshalb ist der Zeitpunkt hier auch änderbar und nicht bloß eine Anzeige:
 * Silas' Lauf um sechs ist selten der eigene, und eine Übernahme, die den
 * fremden Tagesablauf mitzwingt, wird nach drei Tagen wieder gelöscht. Der
 * Warum-Satz und der kleinste Schritt kommen dagegen gar nicht erst mit — sie
 * gehören zu einer Person, nicht zu einer Gewohnheit.
 */
export function HabitAdoptionSheet({
    blueprint,
    noticeId,
    scheduleTypes,
    triggerSuggestions,
    onOpenChange,
}: {
    /** Die Vorlage; null heißt zu. */
    blueprint: HabitBlueprint | null;
    /** Die Absage-Notiz, aus der heraus übernommen wird — sie verschwindet dann mit. */
    noticeId?: number;
    scheduleTypes: ScheduleTypeOption[];
    triggerSuggestions: string[];
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm({
            title: '',
            behavior_type: 'other' as BehaviorType,
            schedule_type: 'dynamic' as ScheduleType,
            trigger_situation: '',
            scheduled_time: DEFAULT_TIME,
            scheduled_days: [] as Weekday[],
            notice_id: noticeId,
        });

    // Die Vorlage steht erst fest, wenn das Sheet aufgeht — und sie ist bei
    // jeder Karte eine andere. Ohne diesen Abgleich trüge das Formular die
    // Werte der zuletzt geöffneten Gewohnheit.
    useEffect(() => {
        if (blueprint === null) {
            return;
        }

        clearErrors();
        setData({
            title: blueprint.title,
            behavior_type: blueprint.behaviorType,
            schedule_type: blueprint.scheduleType,
            trigger_situation: blueprint.triggerSituation ?? '',
            scheduled_time: blueprint.scheduledTime ?? DEFAULT_TIME,
            scheduled_days: blueprint.scheduledDays ?? [],
            notice_id: noticeId,
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [blueprint, noticeId]);

    const isFixed = data.schedule_type === 'fixed';
    const ready = isFixed
        ? data.scheduled_days.length > 0
        : data.trigger_situation.trim().length > 0;

    const Icon = BEHAVIOR_ICONS[data.behavior_type];

    function adopt() {
        post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    }

    return (
        <Sheet open={blueprint !== null} onOpenChange={onOpenChange}>
            <SheetContent side="bottom" className="mx-auto max-w-lg">
                <SheetHeader>
                    <SheetTitle>Selbst übernehmen</SheetTitle>
                    <SheetDescription>
                        Die Gewohnheit kommt in deine eigene Liste — mit deinen
                        Tagen und deinem Zeitpunkt. Der Verlauf der anderen
                        Person bleibt bei ihr; bei dir beginnt Tag eins.
                    </SheetDescription>
                </SheetHeader>

                <div className="flex max-h-[70vh] flex-col gap-5 overflow-y-auto px-4 pb-6">
                    <div className="flex items-center gap-3">
                        <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sand text-primary">
                            <Icon
                                className="size-5"
                                strokeWidth={1.5}
                                aria-hidden="true"
                            />
                        </span>
                        <span className="min-w-0">
                            <span className="block truncate text-[15px] leading-snug font-semibold">
                                {data.title}
                            </span>
                            <span className="mt-0.5 block text-xs text-muted-foreground">
                                {isFixed
                                    ? `${data.scheduled_time} · ${formatWeekdays(data.scheduled_days)}`
                                    : data.trigger_situation ||
                                      'noch kein Auslöser'}
                            </span>
                        </span>
                    </div>

                    {/* Die Grenze von fünf Gewohnheiten meldet sich am Titel,
                        der hier nicht änderbar ist — der Satz muss trotzdem
                        ankommen, sonst bliebe der Knopf grundlos wirkungslos. */}
                    <InputError message={errors.title} />

                    <div className="flex flex-col gap-3">
                        <p className={`${EYEBROW} text-muted-foreground`}>
                            Wann machst du das?
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
                    </div>

                    <button
                        type="button"
                        disabled={!ready || processing}
                        onClick={adopt}
                        className="inline-flex h-12 w-full cursor-pointer items-center justify-center rounded-xl bg-primary px-6 text-[15px] font-semibold text-primary-foreground transition-colors duration-200 hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Nehme ich mir vor
                    </button>
                </div>
            </SheetContent>
        </Sheet>
    );
}
