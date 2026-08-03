import { Head } from '@inertiajs/react';
import { FeaturePlaceholder } from '@/components/feature-placeholder';
import { dashboard } from '@/routes';

export default function HabitsIndex() {
    return (
        <>
            <Head title="Gewohnheiten" />

            <FeaturePlaceholder
                title="Gewohnheiten"
                description="Der Ort, an dem deine Gewohnheiten entstehen und sich verändern — Auslöser, Reihenfolge und was sich schon gefestigt hat."
                planned={[
                    'Alle aktiven Gewohnheiten mit ihrem Auslöser bearbeiten',
                    'Reihenfolge der Tagesliste anpassen',
                    'Gefestigte Gewohnheiten archivieren, um Platz für neue zu schaffen',
                    'Gewohnheiten aneinanderketten: die eine wird zum Auslöser der nächsten',
                ]}
                note={
                    'Neue Gewohnheiten kannst du schon jetzt über „Neu hinzufügen“ auf der Übersicht anlegen.'
                }
            />
        </>
    );
}

HabitsIndex.layout = {
    breadcrumbs: [
        { title: 'Übersicht', href: dashboard() },
        { title: 'Gewohnheiten', href: '' },
    ],
};
