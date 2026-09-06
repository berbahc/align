import {
    Bed,
    Bike,
    BookOpen,
    BookText,
    Brain,
    CalendarRange,
    ChefHat,
    Croissant,
    Dumbbell,
    Footprints,
    GlassWater,
    GraduationCap,
    Home,
    Layers,
    Leaf,
    Moon,
    NotebookPen,
    Salad,
    Soup,
    Sparkles,
    StretchHorizontal,
    Timer,
} from 'lucide-react';
import type { BehaviorType, HabitCategory } from '@/types';

/**
 * Das Zeichen einer Verhaltensrichtung — eine Quelle für die ganze App.
 *
 * Dieselbe Richtung darf nicht je nach Bildschirm ein anderes Icon tragen:
 * in der Tagesliste, im Kalender und im Archiv steht sie für dieselbe
 * Gewohnheit.
 */
/** Der Typ eines Zeichens aus dem Set — damit ihn niemand über den Umweg
 * einer Nachschlagetabelle ausdrücken muss. */
export type HabitIcon = typeof Moon;

export const BEHAVIOR_ICONS: Record<BehaviorType, HabitIcon> = {
    nutrition: GlassWater,
    movement: Dumbbell,
    learning: BookOpen,
    other: Moon,
};

/**
 * Das Zeichen einer Katalog-Kategorie — für die Kacheln des Wizards.
 *
 * Kategorien sortieren nach Lebensbereich, nicht nach Verhaltensart: „Uni"
 * bekommt den Hut, nicht das Buch — das Buch gehört der Richtung „Lernen",
 * die auch außerhalb der Uni vorkommt.
 */
export const CATEGORY_ICONS: Record<HabitCategory, HabitIcon> = {
    sport: Dumbbell,
    uni: GraduationCap,
    alltag: Home,
    erholung: Leaf,
};

/**
 * Das Zeichen einer Katalog-Vorlage — genauer als die Verhaltensrichtung.
 *
 * Der Verhaltenstyp ist eine **fachliche** Einordnung: Er steuert die
 * Plateau-Schätzung und benennt der KI den Bereich ({@see BehaviorType}). Als
 * Bildsprache taugt er nicht — „Tagebuch schreiben", „Aufräumen", „Meditieren"
 * und „Woche planen" sind alle `other` und trugen deshalb alle denselben
 * Halbmond. Ein Mond über einem Tagebuch benennt nichts; er ist außerdem schon
 * für den Schlaf vergeben.
 *
 * Die Vorlage weiß dagegen genau, worum es geht. Sie entscheidet das Zeichen,
 * die Richtung bleibt der Rückfall für Gewohnheiten aus der Zeit der freien
 * Eingabe — die haben keine Vorlage und behalten ihr bisheriges Zeichen.
 *
 * Die Schlüssel stammen aus `HabitTemplate` (PHP); ein unbekannter Schlüssel
 * fällt still auf die Richtung zurück, statt die Zeile zu sprengen.
 */
export const TEMPLATE_ICONS: Record<string, HabitIcon> = {
    // Sport & Bewegung
    joggen: Footprints,
    krafttraining: Dumbbell,
    spazieren: Footprints,
    dehnen: StretchHorizontal,
    fahrrad: Bike,

    // Uni & Lernen
    'vorlesung-nachbereiten': BookOpen,
    'fokussiert-lernen': Brain,
    karteikarten: Layers,
    'uni-tag-planen': CalendarRange,

    // Haushalt & Alltag
    'essen-vorkochen': ChefHat,
    aufraeumen: Sparkles,
    fruehstuecken: Croissant,
    mittagessen: Salad,
    abendessen: Soup,
    'woche-planen': CalendarRange,

    // Erholung & Achtsamkeit
    meditieren: Timer,
    tagebuch: NotebookPen,
    lesen: BookText,
    'offline-abend': Bed,
};
