<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Passt die Verabredung überhaupt in den Tag der gefragten Person?
 *
 * Bis hierher war „Passt mir" eine Zusage ins Blaue: Wer um 7:30 gefragt wurde
 * und selbst um 7:30 liest, sagte zu und hatte danach zwei Dinge zur selben
 * Zeit. Time-Blocking heißt aber, dass jede Gewohnheit eine Spanne hat und
 * zwei Spannen sich nicht überschneiden — sonst ist der Plan keiner.
 *
 * Diese Klasse rechnet das aus und liefert im Konfliktfall gleich mit, wohin
 * die eigene Gewohnheit an diesem einen Tag rücken könnte. Sie entscheidet
 * nichts: Sie sagt, was ist, und die freien Fenster kommen aus derselben
 * Rechnung, die auch die KI-Vorschläge begrenzt.
 *
 * Verglichen wird nur gegen echte Uhrzeiten. Eine Situation („nach dem
 * Aufstehen") hat keinen Zeitpunkt, mit dem sich kollidieren ließe — sie
 * deshalb zu verschieben hieße, eine Uhrzeit zu erfinden, die es nie gab.
 */
final class AppointmentFit
{
    /**
     * Wie viele Ausweichzeiten die Karte anbietet.
     *
     * Drei, wie überall in diesem Feature: Mehr Wahl wäre eine Terminfindung,
     * und die gehört laut community_feature3.md §9 nicht in die App.
     */
    private const int OptionCount = 3;

    /**
     * @param  array{from: int, to: int}  $window  Die Spanne der Verabredung, in Minuten seit Mitternacht
     * @param  Collection<int, Habit>  $habits  Der Tag der gefragten Person, einmal geladen
     */
    private function __construct(
        private readonly Habit $habit,
        private readonly array $window,
        private readonly Carbon $date,
        private readonly User $invitee,
        private readonly Collection $habits,
    ) {}

    /**
     * Der Konflikt — oder nichts, wenn der Tag Platz hat.
     */
    public static function conflict(Appointment $appointment, User $invitee): ?self
    {
        // Als veränderliches Carbon: Die Datums-Casts liefern hier
        // CarbonImmutable, und die übrige Codebasis rechnet mit dem
        // Illuminate-Typ.
        $date = Carbon::parse($appointment->scheduled_for)->startOfDay();
        $window = self::windowOf($appointment, $date);

        if ($window === null) {
            return null;
        }

        $habits = self::habitsOn($invitee, $date);

        $clash = $habits
            ->first(function (Habit $habit) use ($date, $window): bool {
                $span = self::spanOf($habit, $date);

                return $span !== null
                    && $span['from'] < $window['to']
                    && $span['to'] > $window['from'];
            });

        return $clash === null ? null : new self($clash, $window, $date, $invitee, $habits);
    }

    /**
     * Die kollidierende eigene Gewohnheit.
     */
    public function habit(): Habit
    {
        return $this->habit;
    }

    /**
     * Der Konflikt für die Oberfläche, samt Ausweichzeiten.
     *
     * @return array{habitId: int, title: string, from: string, to: string, options: list<array{time: string, label: string}>}
     */
    public function present(): array
    {
        /** @var array{from: int, to: int} $span */
        $span = self::spanOf($this->habit, $this->date);

        return [
            'habitId' => $this->habit->id,
            'title' => $this->habit->title,
            'from' => DayPlan::toTime($span['from']),
            'to' => DayPlan::toTime($span['to']),
            'options' => $this->options(),
        ];
    }

    /**
     * Wohin die eigene Gewohnheit an diesem Tag rücken könnte.
     *
     * Gerechnet gegen den eigenen Tag **plus** die Verabredung: Ein Fenster,
     * das genau dort läge, wo gleich gemeinsam gelaufen wird, wäre kein
     * Ausweg, sondern derselbe Konflikt an anderer Stelle.
     *
     * @return list<array{time: string, label: string}>
     */
    public function options(): array
    {
        $minutes = $this->habit->durationMinutes() ?? DayPlan::AssumedMinutes;

        $plan = DayPlan::forDate(
            $this->habits,
            $this->date,
            $this->invitee->sleepWindows(),
            [[
                'id' => 0,
                'title' => 'Verabredung',
                'from' => $this->window['from'],
                'to' => $this->window['to'],
            ]],
        );

        return array_map(
            fn (array $free): array => [
                'time' => DayPlan::toTime($free['from']),
                'label' => DayPlan::toTime($free['from']).' – '.DayPlan::toTime($free['from'] + $minutes),
            ],
            array_slice($plan->freeWindows($minutes, $this->habit), 0, self::OptionCount),
        );
    }

    /**
     * Die Spanne der Verabredung — die der fragenden Gewohnheit an diesem Tag.
     *
     * Die Verabredung erfindet keine eigene Zeit (community_feature3.md §4);
     * ohne Uhrzeit auf der fragenden Seite gibt es deshalb nichts zu prüfen.
     *
     * @return array{from: int, to: int}|null
     */
    private static function windowOf(Appointment $appointment, Carbon $date): ?array
    {
        return self::spanOf($appointment->habit, $date);
    }

    /**
     * Die belegte Spanne einer Gewohnheit an einem Tag, in Minuten.
     *
     * @return array{from: int, to: int}|null
     */
    private static function spanOf(Habit $habit, Carbon $date): ?array
    {
        $start = $habit->startsAt($date);

        if ($start === null) {
            return null;
        }

        $from = DayPlan::toMinutes($start->format('H:i'));

        return [
            'from' => $from,
            'to' => $from + ($habit->durationMinutes() ?? DayPlan::AssumedMinutes),
        ];
    }

    /**
     * Die Gewohnheiten, die an diesem Tag im Tag der Person stehen.
     *
     * Mit geladener Kette und geladenen Verschiebungen: Beide entscheiden
     * mit, wo ein Block wirklich liegt, und ohne sie fragte jede Zeile die
     * Datenbank ein weiteres Mal.
     *
     * @return Collection<int, Habit>
     */
    private static function habitsOn(User $user, Carbon $date): Collection
    {
        $habits = $user->habits()->active()->with(['chainedTo.chainedTo', 'dayShifts'])->get();
        $habits->each(fn (Habit $habit) => $habit->setRelation('user', $user));

        return $habits->filter(fn (Habit $habit): bool => $habit->isScheduledOn($date))->values();
    }
}
