<?php

namespace App\Actions;

use App\Enums\ScheduleType;
use App\Models\Habit;

/**
 * Die Nachfolger einer Gewohnheit erben ihren Anker, wenn sie wegfällt.
 *
 * Wird eine Gewohnheit beendet oder gelöscht, hängen ihre Nachfolger an etwas,
 * das nicht mehr stattfindet. Sie deshalb mitzunehmen wäre falsch: „nach dem
 * Spaziergang lesen" heißt nicht, dass das Lesen am Spaziergang hängt — es
 * hatte nur seinen Platz dahinter.
 *
 * Also rückt die Kette zusammen. Der Nachfolger übernimmt genau den Anker, den
 * die weggefallene Gewohnheit hatte: ihre Uhrzeit, ihre Situation, oder ihren
 * eigenen Vorgänger. Er behält dabei seinen Verlauf und seine Serie.
 *
 * Dasselbe Muster wie bei der abgesagten Verabredung — die Gewohnheit überlebt
 * das, woran sie hing.
 */
class ReleaseChainedHabits
{
    public function handle(Habit $habit): void
    {
        $successors = $habit->chainedHabits()->get();

        if ($successors->isEmpty()) {
            return;
        }

        // Hing die weggefallene Gewohnheit selbst an einer, rutschen die
        // Nachfolger eine Stelle weiter nach vorn statt aus der Kette heraus.
        $inherited = $habit->schedule_type === ScheduleType::Chained
            ? [
                'schedule_type' => ScheduleType::Chained,
                'chained_to_habit_id' => $habit->chained_to_habit_id,
                'trigger_situation' => null,
                'scheduled_time' => null,
                'scheduled_days' => null,
            ]
            : [
                'schedule_type' => $habit->schedule_type,
                'chained_to_habit_id' => null,
                'trigger_situation' => $habit->trigger_situation,
                'scheduled_time' => $habit->scheduled_time?->format('H:i'),
                'scheduled_days' => $habit->scheduled_days,
            ];

        // Nur der erste erbt den Platz; die übrigen hängen sich an ihn. Zwei
        // Gewohnheiten mit demselben Anker begännen zur selben Minute — und
        // eine Kette ist eine Reihe, kein Fächer.
        //
        // Neu entstehen kann so etwas nicht mehr, der Validator lässt es nicht
        // zu. Zeilen aus der Zeit davor gibt es aber, und die sollen beim
        // Auflösen in eine Reihe fallen statt aufeinander.
        $previous = null;

        foreach ($successors->sortBy('position') as $successor) {
            $successor->update($previous === null ? $inherited : [
                'schedule_type' => ScheduleType::Chained,
                'chained_to_habit_id' => $previous->id,
                'trigger_situation' => null,
                'scheduled_time' => null,
                'scheduled_days' => null,
            ]);

            $previous = $successor;
        }
    }
}
