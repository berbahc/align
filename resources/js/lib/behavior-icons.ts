import {
    BookOpen,
    Dumbbell,
    GlassWater,
    GraduationCap,
    Home,
    Leaf,
    Moon,
} from 'lucide-react';
import type { BehaviorType, HabitCategory } from '@/types';

/**
 * Das Zeichen einer Verhaltensrichtung — eine Quelle für die ganze App.
 *
 * Dieselbe Richtung darf nicht je nach Bildschirm ein anderes Icon tragen:
 * in der Tagesliste, im Kalender und im Archiv steht sie für dieselbe
 * Gewohnheit.
 */
export const BEHAVIOR_ICONS: Record<BehaviorType, typeof Moon> = {
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
export const CATEGORY_ICONS: Record<HabitCategory, typeof Moon> = {
    sport: Dumbbell,
    uni: GraduationCap,
    alltag: Home,
    erholung: Leaf,
};
