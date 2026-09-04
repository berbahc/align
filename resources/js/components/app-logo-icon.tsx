import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * Align-Bildmarke — die App-Icon-Kachel.
 *
 * Die Dateinamen sagen, **worauf** die Kachel gehört, nicht wie sie aussieht:
 * `logo-on-light` ist die dunkle Kachel für den hellen Modus,
 * `logo-on-dark` die helle für den dunklen. Vorher hießen sie
 * `logo-light`/`logo-dark`, und das war zweideutig — „hell" konnte die Kachel
 * meinen oder den Modus. Gezeigt wurde jeweils die gleichfarbige, und die
 * Marke verschwand im Hintergrund.
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
                src="/logo-on-light.webp"
                alt="Align"
                className={cn('block dark:hidden', className)}
                {...props}
            />
            <img
                src="/logo-on-dark.webp"
                alt="Align"
                className={cn('hidden dark:block', className)}
                {...props}
            />
        </>
    );
}
