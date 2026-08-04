import type { SVGAttributes } from 'react';
import { useId } from 'react';

/**
 * Align-Wortmarke. Farben kommen ausschließlich aus den Theme-Tokens
 * (--olive/--olive-mid/--gold/--sand), damit die Marke Light und Dark Mode
 * ohne Sonderfall mitgeht. Die Grundlinie greift den Fortschrittsbalken der
 * Designsprache §10 auf, der Punkt rechts die abgehakte Gewohnheit.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    const gradientId = useId();

    return (
        <svg
            viewBox="0 0 116 34"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            role="img"
            aria-label="Align"
            {...props}
        >
            <defs>
                <linearGradient
                    id={gradientId}
                    x1="2"
                    y1="32"
                    x2="114"
                    y2="2"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop offset="0" stopColor="var(--olive)" />
                    <stop offset="0.55" stopColor="var(--olive-mid)" />
                    <stop offset="1" stopColor="var(--gold)" />
                </linearGradient>
            </defs>

            {/* Obere Hilfslinie — Ausrichtungsmotiv, bewusst kurz und leise. */}
            <rect x="2" y="0" width="38" height="2" rx="1" fill="var(--sand)" />

            <text
                x="2"
                y="26"
                textLength="97"
                lengthAdjust="spacing"
                fontFamily="var(--font-sans), 'Sora', ui-sans-serif, system-ui, sans-serif"
                fontSize="30"
                fontWeight="600"
                letterSpacing="-0.5"
                fill={`url(#${gradientId})`}
            >
                Align
            </text>

            {/* Grundlinie: Spur in Sand, gefüllter Anteil im Marken-Verlauf. */}
            <rect
                x="2"
                y="31"
                width="112"
                height="3"
                rx="1.5"
                fill="var(--sand)"
            />
            <rect
                x="2"
                y="31"
                width="70"
                height="3"
                rx="1.5"
                fill={`url(#${gradientId})`}
            />

            {/* Abschlusspunkt auf x-Höhe des Wortes. */}
            <circle cx="109" cy="21" r="4" fill="var(--gold)" />
        </svg>
    );
}
