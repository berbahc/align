import { cn } from '@/lib/utils';

/**
 * Ein/Aus-Schalter.
 *
 * Bewusst kein Radix-Baustein: `@radix-ui/react-switch` gehört nicht zu den
 * Abhängigkeiten dieses Projekts, und ein `role="switch"`-Button trägt dieselbe
 * Bedeutung für Screenreader.
 *
 * Im An-Zustand `primary` — nach §2.2 die eine Farbe, die „aktiv" bedeutet.
 * Der Übergang bleibt bei 200 ms ohne Federn (§6).
 */
export function ToggleSwitch({
    checked,
    onChange,
    disabled = false,
    label,
    id,
}: {
    checked: boolean;
    onChange: (checked: boolean) => void;
    disabled?: boolean;
    /** Beschriftung für Screenreader, wenn daneben kein sichtbarer Text steht. */
    label?: string;
    id?: string;
}) {
    return (
        <button
            type="button"
            role="switch"
            id={id}
            aria-checked={checked}
            aria-label={label}
            disabled={disabled}
            onClick={() => onChange(!checked)}
            className={cn(
                'inline-flex h-7 w-12 shrink-0 cursor-pointer items-center rounded-full p-0.5 transition-colors duration-200',
                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
                'disabled:cursor-not-allowed disabled:opacity-40',
                checked ? 'bg-primary' : 'bg-sand',
            )}
        >
            <span
                className={cn(
                    'size-6 rounded-full bg-white shadow-sm transition-transform duration-200',
                    checked ? 'translate-x-5' : 'translate-x-0',
                )}
            />
        </button>
    );
}
