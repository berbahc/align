<?php

namespace App\Enums;

/**
 * Die Kategorie, unter der eine Gewohnheit im Katalog steht.
 *
 * Der Katalog ersetzt die freie Eingabe: Gewohnheiten unterscheiden sich zu
 * stark, als dass ein Formular allen gerecht würde — was sich ergibt („Treppe
 * statt Aufzug") hat keine Uhrzeit, was sich über den Tag verteilt („Wasser
 * trinken") keine Dauer. Beides passt nicht in ein Time-Blocking, das mit
 * Zeitfenstern plant. Übrig bleiben planbare Aktivitäten, und die lassen sich
 * in vier Bereiche des Studienalltags sortieren.
 *
 * Die Kategorie ist die Einstiegsfrage des Ablaufs und sonst nichts: Für die
 * Plateau-Schätzung aus habit-journey.md zählt weiterhin {@see BehaviorType},
 * der an der einzelnen Vorlage hängt — „Essen vorkochen" (Ernährung) und
 * „Aufräumen" (Sonstiges) stehen beide unter Alltag, bilden sich aber
 * verschieden schnell.
 */
enum HabitCategory: string
{
    case Sport = 'sport';
    case Uni = 'uni';
    case Alltag = 'alltag';
    case Erholung = 'erholung';

    /**
     * Die Kategorien samt ihrer Vorlagen — die eine Quelle des Katalogs für
     * die Oberfläche.
     *
     * @return list<array{value: string, label: string, templates: list<array{key: string, title: string, defaultMinutes: int}>}>
     */
    public static function options(): array
    {
        return array_map(fn (self $category): array => [
            'value' => $category->value,
            'label' => $category->label(),
            'templates' => array_map(fn (HabitTemplate $template): array => [
                'key' => $template->value,
                'title' => $template->title(),
                'defaultMinutes' => $template->defaultMinutes(),
            ], $category->templates()),
        ], self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Sport => 'Sport & Bewegung',
            self::Uni => 'Uni & Lernen',
            self::Alltag => 'Haushalt & Alltag',
            self::Erholung => 'Erholung & Achtsamkeit',
        };
    }

    /**
     * Die Vorlagen dieser Kategorie, in der Reihenfolge der Anzeige.
     *
     * @return list<HabitTemplate>
     */
    public function templates(): array
    {
        return array_values(array_filter(
            HabitTemplate::cases(),
            fn (HabitTemplate $template): bool => $template->category() === $this,
        ));
    }
}
