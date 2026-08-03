import { Head } from '@inertiajs/react';
import { FeaturePlaceholder } from '@/components/feature-placeholder';
import { dashboard } from '@/routes';

export default function Journey() {
    return (
        <>
            <Head title="Verlauf" />

            <FeaturePlaceholder
                title="Verlauf"
                description="Die Langzeitsicht: nicht ob du heute abgehakt hast, sondern wie weit eine Gewohnheit auf ihrem Weg zur Automatisierung ist."
                planned={[
                    'Pro Gewohnheit eine Kurve, die sich einem Plateau annähert',
                    'Phasenanzeige: Aufbau, Festigung, Gewohnheit',
                    'Plateau-Schätzung auf Basis deiner bisherigen Konsistenz',
                    'Meilensteine nach 30, 66 und 100 Tagen',
                ]}
                note="Die Kurve zeigt nur deine eigene Entwicklung. Habitbildung schwankt laut Forschung zwischen 18 und 254 Tagen — ein Vergleich mit anderen wäre irreführend."
            />
        </>
    );
}

Journey.layout = {
    breadcrumbs: [
        { title: 'Übersicht', href: dashboard() },
        { title: 'Verlauf', href: '' },
    ],
};
