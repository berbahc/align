import { createInertiaApp, router } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * Vorgeladene Seiten nach jedem Seitenwechsel verwerfen.
 *
 * Die Sidebar lädt ihre Ziele mit `prefetch` vor und hält die Antwort bis zu
 * 30 Sekunden. Darin stecken auch die geteilten Eigenschaften — unter anderem
 * `habitReminders`. Wer eine Gewohnheit abhakt und dann die Seite wechselt,
 * bekäme sonst eine Antwort ausgeliefert, die vor dem Abhaken geholt wurde,
 * und der Hinweis stünde dort weiterhin.
 *
 * Der Vorteil des Vorladens bleibt: Es geschieht beim Überfahren des Links,
 * also nach diesem Leeren und vor dem Klick.
 */
router.on('navigate', () => router.flushAll());

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            // Das Onboarding läuft ohne Sidebar — ein einziger Ablauf, keine
            // Navigation, die davon ablenkt.
            case name === 'onboarding':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
