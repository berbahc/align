<?php

namespace App\Http\Requests\Concerns;

use App\Models\Habit;
use App\Support\DayPlan;
use App\Support\Timetable;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Eine feste Uhrzeit gegen den Tag prüfen, an jedem gewählten Wochentag.
 *
 * Time-Blocking heißt, dass jede Sache eine Spanne hat und zwei Spannen sich
 * nicht überschneiden. Bis hierher galt das nur beim Ziehen im Raster: Wer
 * einen Block auf eine Vorlesung zog, bekam eine Absage — wer dieselbe Uhrzeit
 * ins Formular tippte, kam durch. Der Plan widersprach sich damit selbst, und
 * zwar an der Stelle, an der die App ihr Versprechen einlöst.
 *
 * Ein Kurs wiegt dabei schwerer als eine Gewohnheit: Eine Vorlesung lässt sich
 * nicht verschieben, eine eigene Gewohnheit schon. Deshalb zwei Sätze statt
 * einem — der eine nennt einen Ausweg, den es gibt, der andere keinen, den es
 * nicht gibt.
 *
 * Geprüft wird an konkreten Daten und nicht an „montags": Der Schlafrahmen
 * hängt am Wochentag, die Ausnahmen des Stundenplans am Datum.
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

        if ($user === null || $days === [] || $validator->errors()->has($field)) {
            return;
        }

        $start = DayPlan::toMinutes($time);

        // Die eigene Spanne — und die der Gewohnheiten, die an ihr hängen. Eine
        // Kette rückt mit, und ein Nachfolger, der dabei in einer Vorlesung
        // landet, wäre genau der Widerspruch, den diese Prüfung verhindern soll.
        $spans = $habit?->spansFrom($start) ?? [[
            'id' => 0,
            'title' => '',
            'from' => $start,
            'to' => $start + $minutes,
        ]];

        $moving = array_column($spans, 'id');
        $dates = array_map($this->nextWeekday(...), $days);
        $timetable = Timetable::for($user);

        // Einmal laden, für alle sieben möglichen Tage. Die Tagesausnahmen
        // kommen in derselben Abfrage mit, damit `isScheduledOn()` und die
        // Platzierung sie sehen, ohne je Tag nachzuladen.
        $others = $user->habits()
            ->active()
            ->with([
                'chainedTo.chainedTo',
                'dayShifts' => fn ($query) => $query->whereIn(
                    'shifted_on',
                    array_map(fn (Carbon $date): string => $date->toDateString(), $dates),
                ),
            ])
            ->get();

        $others->each(fn (Habit $other) => $other->setRelation('user', $user));

        foreach ($dates as $date) {
            $plan = DayPlan::forDate(
                $others
                    ->filter(fn (Habit $other): bool => $other->isScheduledOn($date))
                    ->reject(fn (Habit $other): bool => in_array($other->id, $moving, strict: true))
                    ->values(),
                $date,
                $user->sleepWindows(),
                $timetable->blocksOn($date),
            );

            foreach ($spans as $span) {
                $conflict = $plan->collisionWith($span['from'], $span['to']);

                if ($conflict === null) {
                    continue;
                }

                $validator->errors()->add($field, $this->conflictMessage($conflict, $date));

                return;
            }
        }
    }

    /**
     * @param  array{id: int, title: string, from: int, to: int}  $conflict
     */
    private function conflictMessage(array $conflict, Carbon $date): string
    {
        $when = $this->weekdayLabel($date);

        if (Timetable::isCourseBlock($conflict)) {
            return sprintf(
                '%s läuft „%s" von %s bis %s. Such der Gewohnheit eine andere Zeit — die Vorlesung rückt nicht.',
                ucfirst($when),
                $conflict['title'],
                DayPlan::toTime($conflict['from']),
                DayPlan::toTime($conflict['to']),
            );
        }

        return sprintf(
            '„%s" liegt %s schon um %s. Verschiebe die zuerst, dann ist hier Platz.',
            $conflict['title'],
            $when,
            DayPlan::toTime($conflict['from']),
        );
    }

    /**
     * Der nächste Tag mit diesem Wochentag, heute eingeschlossen.
     */
    private function nextWeekday(int $weekday): Carbon
    {
        $day = Carbon::today();

        for ($step = 0; $step < 7; $step++) {
            if ($day->dayOfWeekIso === $weekday) {
                return $day;
            }

            $day->addDay();
        }

        return $day;
    }

    /**
     * „montags", „heute" — je nachdem, wie weit der Tag weg ist.
     */
    private function weekdayLabel(Carbon $date): string
    {
        if ($date->isSameDay(Carbon::today())) {
            return 'heute';
        }

        return mb_strtolower($date->settings(['locale' => 'de'])->isoFormat('dddd')).'s';
    }
}
