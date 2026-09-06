import { BEHAVIOR_ICONS, TEMPLATE_ICONS } from '@/lib/behavior-icons';
import type { BehaviorType } from '@/types';

/**
 * Das Zeichen einer Gewohnheit — Vorlage vor Verhaltensrichtung.
 *
 * Der Verhaltenstyp ist eine **fachliche** Einordnung: Er steuert die
 * Plateau-Schätzung und benennt der KI den Bereich ({@see BehaviorType}). Als
 * Bildsprache taugt er nicht — „Tagebuch schreiben", „Aufräumen", „Meditieren"
 * und „Woche planen" sind alle `other` und trugen deshalb alle denselben
 * Halbmond. Ein Mond über einem Tagebuch benennt nichts, und er ist ohnehin
 * schon für den Schlaf vergeben.
 *
 * Die Vorlage weiß dagegen genau, worum es geht. Sie entscheidet das Zeichen;
 * die Richtung bleibt der Rückfall für Gewohnheiten aus der Zeit der freien
 * Eingabe, die keine Vorlage haben.
 *
 * **Eine Komponente und keine Funktion, die eine Komponente zurückgibt.** Wer
 * `const Icon = habitIcon(habit)` schreibt, erzeugt bei jedem Render einen
 * neuen Komponententyp — React setzt dessen Zustand dann jedes Mal zurück.
 * Hier steht die Nachschlage-Tabelle innerhalb einer Komponente, die selbst
 * nur einmal deklariert wird.
 */
export function HabitGlyph({
    habit,
    className,
    strokeWidth = 1.5,
}: {
    habit: {
        /** Der Schlüssel aus `HabitTemplate`; null bei freier Eingabe. */
        templateKey?: string | null;
        behaviorType: BehaviorType;
    };
    className?: string;
    strokeWidth?: number;
}) {
    // Ein unbekannter Schlüssel fällt still auf die Richtung zurück, statt die
    // Zeile ohne Zeichen zu lassen.
    const Icon =
        TEMPLATE_ICONS[habit.templateKey ?? ''] ??
        BEHAVIOR_ICONS[habit.behaviorType];

    return (
        <Icon
            className={className}
            strokeWidth={strokeWidth}
            aria-hidden="true"
        />
    );
}
