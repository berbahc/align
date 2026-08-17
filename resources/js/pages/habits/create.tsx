import { Head, usePage } from '@inertiajs/react';
import { CompanionStep } from '@/components/companion-step';
import { HabitWizard } from '@/components/habit-wizard';
import type { Direction } from '@/components/habit-wizard';
import type { ScheduleTypeOption } from '@/components/schedule-picker';
import { dashboard } from '@/routes';
import { store } from '@/routes/habits';
import type {
    BusySlot,
    ChainCandidate,
    FriendshipPerson,
    MeasureUnitOption,
} from '@/types';

interface CreateHabitProps {
    directions: Direction[];
    triggerSuggestions: string[];
    scheduleTypes: ScheduleTypeOption[];
    measureUnits: MeasureUnitOption[];
    chainCandidates: ChainCandidate[];
    busySlots: BusySlot[];
    /** Der eigene Kreis, für den letzten Schritt. */
    friends: FriendshipPerson[];
}

export default function CreateHabit({
    directions,
    triggerSuggestions,
    scheduleTypes,
    measureUnits,
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
                <h1 className="mb-8 text-2xl leading-tight font-bold text-primary">
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
                        directions={directions}
                        triggerSuggestions={triggerSuggestions}
                        scheduleTypes={scheduleTypes}
                        measureUnits={measureUnits}
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
