<?php

namespace App\Actions;

use App\Enums\SuggestionKind;
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
     * @param  array{title: string, behavior_type: string, schedule_type: string, trigger_situation: string|null, scheduled_time: string|null, scheduled_days: list<int>|null, motivation: string|null, smallest_step: string|null, focus_minutes: int|null}  $attributes
     */
    public function handle(User $user, array $attributes): Habit
    {
        $habit = $user->habits()->create([
            ...$attributes,
            'position' => ($user->habits()->max('position') ?? -1) + 1,
            'committed_at' => now(),
        ]);

        $this->claimSuggestedStep($user, $habit);

        return $habit;
    }

    /**
     * Verbindet den übernommenen Vorschlag mit der Gewohnheit, die daraus wurde.
     *
     * Die Starthilfe schlägt Schritte vor, bevor es die Gewohnheit gibt — ihre
     * Zeilen im Gedächtnis stehen deshalb zunächst ohne `habit_id` da. Hier
     * bekommen sie eine, und der gewählte Schritt gilt als angenommen.
     *
     * Verglichen wird **wortgleich**, und das ist keine Bequemlichkeit: Das
     * Feld im Wizard ist editierbar. Wer den Vorschlag umformuliert hat, hat
     * ihn nicht übernommen, sondern etwas Eigenes geschrieben — und die KI darf
     * sich das nicht als Erfolg anrechnen.
     */
    private function claimSuggestedStep(User $user, Habit $habit): void
    {
        if ($habit->smallest_step === null) {
            return;
        }

        $user->aiSuggestions()
            ->ofKind(SuggestionKind::SmallestStep)
            ->notTaken()
            ->whereNull('habit_id')
            ->where('label', $habit->smallest_step)
            ->latest()
            ->first()
            ?->forceFill([
                'habit_id' => $habit->getKey(),
                'accepted_at' => now(),
            ])->save();
    }
}
