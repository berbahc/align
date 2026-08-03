import { Head } from '@inertiajs/react';
import { FeaturePlaceholder } from '@/components/feature-placeholder';
import { dashboard } from '@/routes';

export default function Community() {
    return (
        <>
            <Head title="Community" />

            <FeaturePlaceholder
                title="Community"
                description="Gewohnheiten gemeinsam aufbauen — soziale Verbindlichkeit als Verstärker, nicht als Druckmittel."
                planned={[
                    'Eine Gewohnheit mit ein bis drei Freunden teilen',
                    'Sich für einen konkreten Tag zum gemeinsamen Ausführen verabreden',
                    'Mit einer Reaktion auf den Fortschritt anderer antworten',
                    'Gemeinsame Gewohnheiten in kleinen Gruppen, etwa einer WG oder Lerngruppe',
                ]}
                note="Ohne Rangliste, ohne Punktestand, ohne Abzeichen. Geteilt wird nur ein einfaches Signal — wer einen Tag auslässt, bleibt für andere unsichtbar."
            />
        </>
    );
}

Community.layout = {
    breadcrumbs: [
        { title: 'Übersicht', href: dashboard() },
        { title: 'Community', href: '' },
    ],
};
