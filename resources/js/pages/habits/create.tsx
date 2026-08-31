import { Head, usePage } from '@inertiajs/react';
import { CompanionStep } from '@/components/companion-step';
import { HabitWizard } from '@/components/habit-wizard';
import type { ScheduleTypeOption } from '@/components/schedule-picker';
import { dashboard } from '@/routes';
import { store } from '@/routes/habits';
import type {
    BusySlot,
    ChainCandidate,
    DurationLimits,
    FriendshipPerson,
    HabitCategoryOption,
    SleepWindow,
} from '@/types';

interface CreateHabitProps {
    categories: HabitCategoryOption[];
    triggerSuggestions: string[];
    scheduleTypes: ScheduleTypeOption[];
    durationLimits: DurationLimits;
    sleepWindows: SleepWindow[];
    chainCandidates: ChainCandidate[];
    busySlots: BusySlot[];
    /** Der eigene Kreis, für den letzten Schritt. */
    friends: FriendshipPerson[];
}

export default function CreateHabit({
    categories,
    triggerSuggestions,
    scheduleTypes,
    durationLimits,
    sleepWindows,
    chainCandidates,
    busySlots,
    friends,
}: CreateHabitProps) {
    const { flash } = usePage();
    const created = flash.habitCreated;

    /**
     * Nach dem Speichern führt der Weg zurück auf diese Seite — dann steht
     * hier nicht mehr der Assistent, sondern die Frage nach der Begleitung.
     *
     * Der Controller schickt nur dann zurück, wenn es überhaupt jemanden zu
     * fragen gibt; ohne Kreis geht es direkt weiter zur Übersicht.
     */
    const asking = created !== undefined && friends.length > 0;

    return (
        <>
            <Head title={asking ? 'Zusammen angehen?' : 'Neue Gewohnheit'} />

            <div className="mx-auto w-full max-w-md p-4 sm:p-6">
                <h1 className="type-heading mb-8 text-primary">
                    {asking ? 'Fast fertig' : 'Neue Gewohnheit'}
                </h1>

                {asking ? (
                    <CompanionStep
                        habitId={created.id}
                        title={created.title}
                        anchor={created.anchor}
                        friends={friends}
                        days={created.days}
                    />
                ) : (
                    <HabitWizard
                        categories={categories}
                        triggerSuggestions={triggerSuggestions}
                        scheduleTypes={scheduleTypes}
                        durationLimits={durationLimits}
                        sleepWindows={sleepWindows}
                        chainCandidates={chainCandidates}
                        busySlots={busySlots}
                        action={store.url()}
                    />
                )}
            </div>
        </>
    );
}

CreateHabit.layout = {
    breadcrumbs: [
        { title: 'Übersicht', href: dashboard() },
        { title: 'Neue Gewohnheit', href: '' },
    ],
};
