import { Head, Link, usePage } from '@inertiajs/react';
import { CompanionStep } from '@/components/companion-step';
import { HabitWizard } from '@/components/habit-wizard';
import type { ScheduleTypeOption } from '@/components/schedule-picker';
import { OUTLINE_BUTTON } from '@/lib/interaction';
import { dashboard } from '@/routes';
import { index as habitsIndex, store } from '@/routes/habits';
import { store as adopt } from '@/routes/habits/adoptions';
import type {
    BusySlot,
    ChainCandidate,
    DurationLimits,
    FriendshipPerson,
    HabitAdoption,
    HabitCategoryOption,
    SituationChoice,
    SleepWindow,
} from '@/types';

interface CreateHabitProps {
    /** Wie viele gerade laufen — und wie viele höchstens dürfen. */
    activeCount: number;
    maxActive: number;
    categories: HabitCategoryOption[];
    triggerSuggestions: SituationChoice[];
    scheduleTypes: ScheduleTypeOption[];
    durationLimits: DurationLimits;
    sleepWindows: SleepWindow[];
    chainCandidates: ChainCandidate[];
    busySlots: BusySlot[];
    /** Der eigene Kreis, für den letzten Schritt. */
    friends: FriendshipPerson[];
    /**
     * Gesetzt, wenn der Weg aus einer Anfrage oder einer Absage kommt.
     *
     * Übernehmen ist kein eigener Assistent: Es ist derselbe, nur mit
     * feststehender Vorlage — und am Ende ist eine Anfrage beantwortet.
     */
    adoption: HabitAdoption | null;
}

export default function CreateHabit({
    activeCount,
    maxActive,
    categories,
    triggerSuggestions,
    scheduleTypes,
    durationLimits,
    sleepWindows,
    chainCandidates,
    busySlots,
    friends,
    adoption,
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

    const heading = adoption === null ? 'Neue Gewohnheit' : 'Selbst übernehmen';

    // Fünf sind die Grenze — und die steht hier, bevor jemand fünf Schritte
    // durchläuft und die Absage erst hinter dem letzten Knopf bekommt.
    const full = activeCount >= maxActive;

    return (
        <>
            <Head title={asking ? 'Zusammen angehen?' : heading} />

            <div className="mx-auto w-full max-w-md p-4 sm:p-6">
                <h1 className="type-heading mb-8 text-primary">
                    {asking ? 'Fast fertig' : heading}
                </h1>

                {asking ? (
                    <CompanionStep
                        habitId={created.id}
                        title={created.title}
                        anchor={created.anchor}
                        friends={friends}
                        days={created.days}
                    />
                ) : full ? (
                    <div
                        role="status"
                        className="flex flex-col gap-4 rounded-xl border border-primary/25 bg-accent px-4 py-4"
                    >
                        <p className="text-sm leading-relaxed text-foreground">
                            <span className="font-semibold">
                                {activeCount} von {maxActive} aktiv
                            </span>
                            . Alle Plätze sind belegt. Mehr als {maxActive} auf
                            einmal trägt niemand durch; das ist ein Schutz, kein
                            Verbot. Läuft eine schon von allein, markiere sie
                            als gefestigt. Dann ist hier Platz für eine neue.
                        </p>
                        <Link
                            href={habitsIndex()}
                            className={`${OUTLINE_BUTTON} self-start`}
                        >
                            Zu deinen Gewohnheiten
                        </Link>
                    </div>
                ) : (
                    <HabitWizard
                        categories={categories}
                        triggerSuggestions={triggerSuggestions}
                        scheduleTypes={scheduleTypes}
                        durationLimits={durationLimits}
                        sleepWindows={sleepWindows}
                        chainCandidates={chainCandidates}
                        busySlots={busySlots}
                        adoption={adoption}
                        action={adoption === null ? store.url() : adopt.url()}
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
