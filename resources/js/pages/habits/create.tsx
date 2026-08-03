import { Head } from '@inertiajs/react';
import { HabitWizard } from '@/components/habit-wizard';
import type { Direction } from '@/components/habit-wizard';
import { dashboard } from '@/routes';
import { store } from '@/routes/habits';

interface CreateHabitProps {
    directions: Direction[];
    triggerSuggestions: string[];
}

export default function CreateHabit({
    directions,
    triggerSuggestions,
}: CreateHabitProps) {
    return (
        <>
            <Head title="Neue Gewohnheit" />

            <div className="mx-auto w-full max-w-md p-4 sm:p-6">
                <h1 className="mb-8 text-2xl leading-tight font-bold text-primary">
                    Neue Gewohnheit
                </h1>

                <HabitWizard
                    directions={directions}
                    triggerSuggestions={triggerSuggestions}
                    action={store.url()}
                />
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
