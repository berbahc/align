import { Card, CardContent } from '@/components/ui/card';

/**
 * Platzhalter für einen Bereich, der noch keine Daten hat.
 *
 * Bewusst ohne gestrichelten Rahmen: „gestrichelt" ist in der Designsprache
 * §7.3 schon doppelt belegt (KI-Vorschlag und offene Gewohnheit). Eine dritte
 * Bedeutung würde das Zeichen entwerten.
 */
export function FeaturePlaceholder({
    title,
    description,
    planned,
    note,
}: {
    title: string;
    description: string;
    planned: string[];
    note?: string;
}) {
    return (
        <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 sm:p-6">
            <header>
                <p className="text-[11px] font-semibold tracking-[0.11em] text-muted-foreground uppercase">
                    In Arbeit
                </p>
                <h1 className="mt-2 text-[clamp(1.75rem,4vw,2rem)] leading-tight font-bold text-primary">
                    {title}
                </h1>
                <p className="mt-2 max-w-xl text-[15px] leading-relaxed text-muted-foreground">
                    {description}
                </p>
            </header>

            <Card className="gap-0 py-5">
                <CardContent className="px-5">
                    <p className="text-[11px] font-semibold tracking-[0.11em] text-muted-foreground uppercase">
                        Geplant
                    </p>

                    <ul className="mt-4 flex flex-col gap-3">
                        {planned.map((item) => (
                            <li key={item} className="flex items-start gap-3">
                                <span
                                    aria-hidden="true"
                                    className="mt-2 size-1.5 shrink-0 rounded-full bg-sand"
                                />
                                <span className="text-[15px] leading-relaxed">
                                    {item}
                                </span>
                            </li>
                        ))}
                    </ul>

                    {note && (
                        <p className="mt-5 border-t border-border pt-4 text-sm leading-relaxed text-muted-foreground">
                            {note}
                        </p>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
