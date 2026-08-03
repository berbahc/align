<?php

namespace App\Actions;

use App\Models\Habit;
use App\Models\User;

class CreateHabit
{
    /**
     * Legt eine Gewohnheit am Ende der Tagesliste an.
     *
     * `behavior_type` kommt aus der Richtung, die der Nutzer im ersten Schritt
     * gewählt hat — es gibt keine Kategoriefrage im Nachhinein.
     *
     * `committed_at` wird gesetzt, weil der letzte Schritt des Ablaufs das
     * ausdrückliche „Ich nehme mir das vor" ist. time-blocking.md: dieser eine
     * bewusste Willensakt ist laut Gollwitzer die Voraussetzung dafür, dass die
     * Wenn-Dann-Planung überhaupt wirkt.
     *
     * @param  array{title: string, behavior_type: string, trigger_situation: string, motivation: string|null, focus_minutes: int|null}  $attributes
     */
    public function handle(User $user, array $attributes): Habit
    {
        return $user->habits()->create([
            ...$attributes,
            'position' => ($user->habits()->max('position') ?? -1) + 1,
            'committed_at' => now(),
        ]);
    }
}
