<?php

namespace App\Support;

use App\Enums\AppointmentConflictKind;
use App\Models\Appointment;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

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
 * **Der Stundenplan zählt mit.** Er tat es lange nur bei den Ausweichzeiten:
 * {@see options()} legte kein Fenster in eine Vorlesung, aber die Prüfung
 * darüber sah nur die eigenen Gewohnheiten. Wer um zehn Mathe hatte und für
 * zehn gefragt wurde, sagte zu und hatte danach zwei Dinge zur selben Zeit —
 * dieselbe Doppelbuchung, gegen die diese Klasse gebaut wurde, nur eine Tür
 * weiter. Eine Klasse mit zwei Maßstäben für denselben Tag ist einer zu viel.
 *
 * Der Unterschied zwischen den beiden Arten steckt nicht in der Rechnung,
 * sondern im Satz danach: Eine eigene Gewohnheit lässt sich für diesen einen
 * Tag verlegen, ein Kurs nicht. Deshalb trägt der Konflikt, ob er beweglich
 * ist — einen Ausweg anzubieten, den es nicht gibt, ist schlimmer als keiner
 * ({@see SlotConflict::message()} sagt dasselbe für die anderen Wege).
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
     * @param  array{title: string|null, from: int, to: int}  $span  Was im Weg liegt — bei der Nacht der Rahmen des Tages
     * @param  Habit|null  $habit  Dasselbe als Gewohnheit — null bei Kurs und Nacht
     * @param  array{from: int, to: int}  $window  Die Spanne der Verabredung, in Minuten seit Mitternacht
     * @param  Collection<int, Habit>  $habits  Der Tag der gefragten Person, einmal geladen
     */
    private function __construct(
        private readonly AppointmentConflictKind $kind,
        private readonly array $span,
        private readonly ?Habit $habit,
        private readonly array $window,
        private readonly Carbon $date,
        private readonly User $invitee,
        private readonly Collection $habits,
        private readonly Timetable $timetable,
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

        $habits = self::habitsOn($invitee, $date);

        // Dieselbe Sache zählt nicht gegen sich selbst: Wer zum Frühstück
        // zusagt und selbst Frühstück im Plan hat, hat keinen Konflikt,
        // sondern einmal Frühstück ({@see Appointment::replaces()}). Die
        // eigene Zeile fällt an dem Tag weg, also belegt sie auch nichts.
        $replaced = $appointment->replaces($invitee, $habits);

        if ($replaced !== null) {
            $habits = $habits->reject(fn (Habit $habit): bool => $habit->is($replaced))->values();

            // Die eigene Zeile zieht an diesem Tag auf die gemeinsame Zeit um
            // ({@see AppointmentController::update()}). Dauert sie länger als
            // die Verabredung, ragt sie darüber hinaus — geprüft wird deshalb,
            // was danach wirklich im Tag steht, und nicht nur die halbe Stunde
            // zu zweit.
            $window['to'] = max(
                $window['to'],
                $window['from'] + ($replaced->durationMinutes() ?? DayPlan::AssumedMinutes),
            );
        }

        $timetable = Timetable::for($invitee);
        $plan = DayPlan::forDate($habits, $date, $invitee->sleepWindowsOn($date), $timetable->blocksOn($date));

        // Zuerst der Rahmen, dann was darin liegt: Wer um drei Uhr nachts
        // gefragt wird, hat dort nichts stehen — und sagte deshalb zu. Der
        // Tagesplan kennt diese Grenze überall sonst
        // ({@see HabitDayShiftController::guard()}); eine Zusage war der eine
        // Weg, auf dem sie nicht galt.
        $frame = $plan->frame();

        if ($window['from'] < $frame['from'] || $window['to'] > self::lastMinuteOf($frame)) {
            return new self(
                AppointmentConflictKind::Night,
                ['title' => null, 'from' => $frame['from'], 'to' => self::lastMinuteOf($frame)],
                null,
                $window,
                $date,
                $invitee,
                $habits,
                $timetable,
            );
        }

        // Mit derselben Viertelstunde Luft wie überall sonst
        // ({@see DayPlan::collisionWith()}). Ohne sie ließ sich eine
        // Verabredung fünf Minuten hinter die eigene Gewohnheit legen: keine
        // Überschneidung, aber enger, als der Tagesplan es je zuließe — und
        // seit die Verabredung im Kalender liegt, meldet die
        // Überlappungs-Invariante genau das als Verstoß. Zwei Maßstäbe für
        // denselben Tag sind einer zu viel.
        foreach (self::blocksOn($habits, $timetable, $date) as $block) {
            if ($block['from'] < $window['to'] + DayPlan::BreatherMinutes
                && $window['from'] < $block['to'] + DayPlan::BreatherMinutes) {
                $istKurs = Timetable::isCourseBlock($block);

                return new self(
                    $istKurs ? AppointmentConflictKind::Course : AppointmentConflictKind::Habit,
                    $block,
                    // Der Kurs kommt nicht aus den Gewohnheiten und trägt eine
                    // negative Kennung; die Suche liefe dort ins Leere.
                    $istKurs ? null : $habits->firstWhere('id', $block['id']),
                    $window,
                    $date,
                    $invitee,
                    $habits,
                    $timetable,
                );
            }
        }

        return null;
    }

    /**
     * Die kollidierende eigene Gewohnheit — null bei Kurs und Nacht.
     */
    public function habit(): ?Habit
    {
        return $this->habit;
    }

    /**
     * Warum die Zusage nicht geht, in einem Satz.
     *
     * Der erste Satz sagt jedes Mal dasselbe: Die Zeit ist nicht zu haben. Der
     * zweite trennt die Fälle, denn nur bei der eigenen Gewohnheit gibt es
     * einen Weg vorbei.
     */
    public function message(): string
    {
        return match ($this->kind) {
            AppointmentConflictKind::Night => sprintf(
                'Um diese Zeit schläfst du. Dein Tag geht von %s bis %s Uhr.',
                DayPlan::toTime($this->span['from']),
                DayPlan::toTime($this->span['to']),
            ),
            AppointmentConflictKind::Course => sprintf(
                'Um diese Zeit läuft bei dir „%s" aus deinem Semesterplan. Ein Kurs rückt nicht.',
                $this->span['title'],
            ),
            AppointmentConflictKind::Habit => sprintf(
                'Um diese Zeit läuft bei dir schon „%s". Verschiebe sie für diesen Tag, dann kannst du zusagen.',
                $this->span['title'],
            ),
        };
    }

    /**
     * Die letzte Minute, bis zu der der Tag reicht.
     *
     * Wer nach Mitternacht ins Bett geht, hat einen Rahmen jenseits von 1440 —
     * die Verabredung liegt aber immer an ihrem eigenen Datum, und der
     * Anschlusstag gehört ihr nicht. Dieselbe Deckelung wie in
     * {@see HabitDayShiftController::guard()}.
     *
     * @param  array{from: int, to: int}  $frame
     */
    private static function lastMinuteOf(array $frame): int
    {
        return min($frame['to'], DayPlan::MinutesPerDay);
    }

    /**
     * Der Konflikt für die Oberfläche, samt Ausweichzeiten.
     *
     * `kind` entscheidet, was die Karte anbietet: bei einer eigenen Gewohnheit
     * die drei Ausweichzeiten, bei einem Kurs und in der Nacht den Satz, dass
     * es an diesem Tag nicht geht. Ohne das Feld müsste die Oberfläche aus
     * einer leeren Liste raten, welcher der drei Fälle vorliegt — und der
     * Grund ist genau das, was der Gefragte wissen will.
     *
     * `from` und `to` sind die Spanne dessen, was im Weg liegt — bei der Nacht
     * der Rahmen des eigenen Tages.
     *
     * @return array{kind: string, habitId: int|null, title: string|null, from: string, to: string, options: list<array{time: string, label: string}>}
     */
    public function present(): array
    {
        return [
            'kind' => $this->kind->value,
            'habitId' => $this->habit?->id,
            'title' => $this->span['title'],
            'from' => DayPlan::toTime($this->span['from']),
            'to' => DayPlan::toTime($this->span['to']),
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
     * Leer bei Kurs und Nacht: Da ist nichts, das rücken könnte.
     *
     * @return list<array{time: string, label: string}>
     */
    public function options(): array
    {
        if ($this->habit === null) {
            return [];
        }

        $minutes = $this->habit->durationMinutes() ?? DayPlan::AssumedMinutes;

        $plan = DayPlan::forDate(
            $this->habits,
            $this->date,
            // Mit der Ausnahme dieses einen Datums: Wer an dem Tag später
            // aufsteht, hat einen anderen Rahmen als an einem gewöhnlichen
            // Dienstag — und ein Ausweichfenster darin wäre eines, das es
            // nicht gibt.
            $this->invitee->sleepWindowsOn($this->date),
            [
                [
                    'id' => 0,
                    'title' => 'Verabredung',
                    'from' => $this->window['from'],
                    'to' => $this->window['to'],
                ],
                // Und der Stundenplan: Ein Ausweichfenster mitten in einer
                // Vorlesung wäre derselbe Konflikt in einer anderen Farbe.
                ...$this->timetable->blocksOn($this->date),
            ],
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
     * Alles, was an diesem Tag eine Uhrzeit belegt — in der Reihenfolge des Tages.
     *
     * Gewohnheiten und Kurse in derselben Form, damit die Prüfung darüber sie
     * nicht auseinanderhalten muss. Sortiert, weil sonst der Zufall der
     * Datenbank entschiede, welcher von zwei Konflikten auf der Karte steht —
     * genannt gehört der erste im Tag.
     *
     * @param  Collection<int, Habit>  $habits
     * @return list<array{id: int, title: string, from: int, to: int}>
     */
    private static function blocksOn(Collection $habits, Timetable $timetable, Carbon $date): array
    {
        $blocks = $habits
            ->map(function (Habit $habit) use ($date): ?array {
                $span = self::spanOf($habit, $date);

                return $span === null ? null : [
                    'id' => $habit->id,
                    'title' => $habit->title,
                    ...$span,
                ];
            })
            ->filter()
            ->concat($timetable->blocksOn($date))
            ->sortBy('from')
            ->values()
            ->all();

        /** @var list<array{id: int, title: string, from: int, to: int}> $blocks */
        return $blocks;
    }

    /**
     * Die Spanne der Verabredung — ihre eigene, festgehaltene Uhrzeit.
     *
     * Sie liest {@see Appointment::startMinute()} und nicht mehr die Uhr der
     * fragenden Gewohnheit. Der Unterschied ist keine Feinheit: Hängt die
     * Gewohnheit an einer Situation, hatte sie keine Uhrzeit, und diese
     * Methode gab `null` zurück — geprüft wurde dann **gar nichts**. Wer um
     * sieben eine Vorlesung hatte und für „nach dem Aufstehen" gefragt wurde,
     * sagte zu und saß gleichzeitig in zwei Räumen.
     *
     * @return array{from: int, to: int}
     */
    private static function windowOf(Appointment $appointment, Carbon $date): array
    {
        $from = $appointment->startMinute();

        return [
            'from' => $from,
            'to' => $from + ($appointment->habit->durationMinutes() ?? DayPlan::AssumedMinutes),
        ];
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
