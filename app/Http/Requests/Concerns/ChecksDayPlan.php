<?php

namespace App\Http\Requests\Concerns;

use App\Models\Habit;
use App\Support\DayPlan;
use App\Support\PromiseLock;
use App\Support\SlotConflict;
use App\Support\Timetable;
use Illuminate\Validation\Validator;

/**
 * Eine feste Uhrzeit gegen den Tag prüfen, an jedem gewählten Wochentag.
 *
 * Nur der Anschluss ans Formular — gerechnet und formuliert wird in
 * {@see SlotConflict}, weil dieselbe Frage auch außerhalb eines Requests
 * gestellt wird: beim Ordnen des Tages, beim Wiederaufnehmen einer beendeten
 * Gewohnheit und beim Eintragen eines Kurses.
 */
trait ChecksDayPlan
{
    /**
     * Weist ab, was an einem der gewählten Tage schon belegt ist.
     *
     * @param  list<int>  $days  ISO-Wochentage
     * @param  Habit|null  $habit  Die Gewohnheit, die dort hin soll — beim Anlegen gibt es sie noch nicht
     * @param  int  $minutes  Ihre Dauer; nur nötig, solange sie noch keine hat
     */
    protected function validateSlotIsFree(
        Validator $validator,
        string $field,
        string $time,
        array $days,
        int $minutes,
        ?Habit $habit = null,
    ): void {
        $user = $this->user();

        if ($user === null || $validator->errors()->has($field)) {
            return;
        }

        // Was ausgemacht ist, rückt nicht: Eine dauerhaft geänderte Uhrzeit
        // zöge auch den Tag mit, für den schon jemand zugesagt hat, und die
        // andere Person läse weiter die alte ({@see PromiseLock}).
        if ($habit !== null) {
            $locked = PromiseLock::on($user, $habit, SlotConflict::datesFor($days, Timetable::for($user)));

            if ($locked !== null) {
                $validator->errors()->add(
                    $field,
                    PromiseLock::message($locked['appointment'], $locked['date'], $user),
                );

                return;
            }
        }

        $start = DayPlan::toMinutes($time);

        // Die eigene Spanne — und die der Gewohnheiten, die an ihr hängen. Eine
        // Kette rückt mit, und ein Nachfolger, der dabei in einem Kurs landet,
        // wäre genau der Widerspruch, den diese Prüfung verhindern soll.
        $spans = $habit?->spansFrom($start) ?? [[
            'id' => 0,
            'title' => '',
            'from' => $start,
            'to' => $start + $minutes,
        ]];

        $conflict = SlotConflict::find($user, $spans, $days, array_column($spans, 'id'));

        if ($conflict !== null) {
            $validator->errors()->add(
                $field,
                SlotConflict::message($conflict['block'], $conflict['date']),
            );
        }
    }
}
