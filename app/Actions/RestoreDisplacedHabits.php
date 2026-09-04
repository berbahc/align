<?php

namespace App\Actions;

use App\Models\Habit;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\SlotConflict;

/**
 * Holt geparkte Gewohnheiten an ihren alten Platz zurück, wo er frei ist.
 *
 * Der Anlass ist ein Kurs, der verschwindet oder umzieht: Was ihn verdrängt
 * hat, ist weg — die Gewohnheit kann zurück. Aber nur dorthin, wo inzwischen
 * nichts anderes liegt. Geprüft wird vor dem Zurückholen, nicht danach; sonst
 * käme der Überlapp durch die Hintertür, die diese Runde gerade geschlossen
 * hat.
 */
class RestoreDisplacedHabits
{
    /**
     * @return list<Habit> Was seinen alten Platz zurückbekommen hat
     */
    public function handle(User $user): array
    {
        $restored = [];

        $parked = $user->habits()
            ->active()
            ->whereNotNull('displaced_at')
            ->whereNotNull('scheduled_time')
            ->get();

        foreach ($parked as $habit) {
            $start = DayPlan::toMinutes($habit->scheduled_time->format('H:i'));
            $spans = $habit->spansFrom($start);

            $conflict = SlotConflict::find(
                $user,
                $spans,
                $habit->scheduled_days ?? [1, 2, 3, 4, 5, 6, 7],
                array_column($spans, 'id'),
            );

            if ($conflict !== null) {
                continue;
            }

            $habit->takeAPlace();
            $restored[] = $habit;
        }

        return $restored;
    }
}
