import { Form, Head } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { HabitWizard } from '@/components/habit-wizard';
import type { Direction } from '@/components/habit-wizard';
import { skip, store } from '@/routes/onboarding';

interface OnboardingProps {
    directions: Direction[];
    triggerSuggestions: string[];
}

export default function Onboarding({
    directions,
    triggerSuggestions,
}: OnboardingProps) {
    return (
        <>
            <Head title="Erste Gewohnheit" />

            <div className="flex min-h-screen flex-col bg-background">
                <header className="flex items-center justify-between gap-4 p-6">
                    <span className="flex items-center gap-2">
                        <AppLogoIcon className="size-5 fill-current text-primary" />
                        <span className="text-lg leading-none font-bold">
                            Align
                        </span>
                    </span>

                    {/* Überspringen ist gleichwertig sichtbar — die App fordert
                        nichts ein (Designsprache §1.5). */}
                    <Form {...skip.form()}>
                        <button
                            type="submit"
                            className="cursor-pointer text-sm text-muted-foreground transition-colors duration-200 hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            Später einrichten
                        </button>
                    </Form>
                </header>

                <main className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-6 pb-16">
                    <div className="mb-8">
                        <h1 className="text-[clamp(1.75rem,5vw,2rem)] leading-tight font-bold text-primary">
                            Fangen wir klein an.
                        </h1>
                        <p className="mt-2 text-[15px] leading-relaxed text-muted-foreground">
                            Eine einzige Gewohnheit reicht für den Anfang. Du
                            kannst später bis zu fünf gleichzeitig verfolgen.
                        </p>
                    </div>

                    <HabitWizard
                        directions={directions}
                        triggerSuggestions={triggerSuggestions}
                        action={store.url()}
                    />
                </main>
            </div>
        </>
    );
}
