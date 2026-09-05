<?php

namespace App\Actions;

use App\Enums\SuggestionKind;
use App\Models\AiSuggestion;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Schreibt fest, was die KI gerade vorgeschlagen hat.
 *
 * Eine Zeile je Vorschlag, alle offen: Wer drei Zeitpunkte angeboten bekommt,
 * hat drei Entscheidungen vor sich, und höchstens eine davon fällt positiv aus.
 * Was offen bleibt, gilt als nicht genommen — die App erfasst keinen
 * ausdrücklichen Ablehn-Tap, weil „Lass so" nicht mehr kosten darf als
 * „Übernehmen" (ki-assistent-design.md §2).
 */
class RememberSuggestions
{
    /**
     * Merkt sich die vorgeschlagenen kleinsten Schritte.
     *
     * `$habit` ist null, wenn der Vorschlag im Anlege-Ablauf entsteht — dort
     * gibt es die Gewohnheit noch nicht. {@see CreateHabit} holt die
     * Verknüpfung nach, sobald ein Schritt unverändert übernommen wird.
     *
     * @param  list<string>  $steps
     * @return Collection<int, AiSuggestion>
     */
    public function steps(User $user, array $steps, ?Habit $habit = null): Collection
    {
        return collect($steps)->map(fn (string $step): AiSuggestion => $user->aiSuggestions()->create([
            'habit_id' => $habit?->getKey(),
            'kind' => SuggestionKind::SmallestStep,
            'label' => $step,
        ]));
    }

    /**
     * Merkt sich die vorgeschlagenen Zeitpunkte.
     *
     * Der `label` entsteht über {@see Habit::anchorLabel()} und sieht damit
     * genauso aus wie ein bestehender Anker („17:00 · Mo–Fr"). Das ist keine
     * Kosmetik: Er landet später wieder im Prompt, und dort muss ein
     * vorgeschlagener Zeitpunkt in derselben Sprache stehen wie ein
     * bestehender, sonst liest das Modell zwei verschiedene Dinge.
     *
     * @param  list<array{situation?: string, time?: string, days?: list<int>, chainToId?: int, chainToTitle?: string, reason: string}>  $alternatives
     * @return Collection<int, AiSuggestion>
     */
    public function anchors(User $user, Habit $habit, array $alternatives): Collection
    {
        return collect($alternatives)->map(fn (array $alternative): AiSuggestion => $user->aiSuggestions()->create([
            'habit_id' => $habit->getKey(),
            'kind' => SuggestionKind::Anchor,
            // Eine Kette heißt, wie der Kalender sie nennt — der Vorschlag
            // landet später wieder im Prompt und muss dort dieselbe Sprache
            // sprechen wie ein bestehender Anker.
            'label' => isset($alternative['chainToTitle'])
                ? sprintf('nach „%s"', $alternative['chainToTitle'])
                : Habit::anchorLabel(
                    situation: $alternative['situation'] ?? null,
                    time: $alternative['time'] ?? null,
                    days: $alternative['days'] ?? null,
                ),
            'payload' => $alternative,
        ]));
    }
}
