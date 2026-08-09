import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * Align-Bildmarke — die App-Icon-Kachel, hell und dunkel.
 *
 * Umgeschaltet wird über die `.dark`-Klasse, nicht über `prefers-color-scheme`:
 * `use-appearance` kennt drei Zustände (hell/dunkel/system) und setzt die Klasse
 * auf `<html>`. Eine Media Query würde die ausdrückliche Wahl übergehen und im
 * hellen Modus auf dunklem System das falsche Bild zeigen.
 *
 * Beide Varianten stehen im Markup, eine davon ist `display: none` — so liegt der
 * Wechsel beim Stylesheet und kostet keinen Renderdurchgang. Screenreader lesen
 * nur die sichtbare.
 */
export default function AppLogoIcon({
    className,
    ...props
}: ComponentProps<'img'>) {
    return (
        <>
            <img
                src="/logo-light.webp"
                alt="Align"
                className={cn('block dark:hidden', className)}
                {...props}
            />
            <img
                src="/logo-dark.webp"
                alt="Align"
                className={cn('hidden dark:block', className)}
                {...props}
            />
        </>
    );
}
