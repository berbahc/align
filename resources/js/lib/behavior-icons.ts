import { BookOpen, Dumbbell, GlassWater, Moon } from 'lucide-react';
import type { BehaviorType } from '@/types';

/**
 * Das Zeichen einer Verhaltensrichtung — eine Quelle für die ganze App.
 *
 * Dieselbe Kategorie darf nicht je nach Bildschirm ein anderes Icon tragen:
 * im Wizard steht sie für die Wahl, in der Tagesliste und im Kalender für
 * dieselbe Gewohnheit wieder.
 */
export const BEHAVIOR_ICONS: Record<BehaviorType, typeof Moon> = {
    nutrition: GlassWater,
    movement: Dumbbell,
    learning: BookOpen,
    other: Moon,
};
