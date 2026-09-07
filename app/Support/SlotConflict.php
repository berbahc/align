<?php

namespace App\Support;

use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Liegt an dieser Stelle schon etwas?
 *
 * Time-Blocking heißt, dass jede Sache eine Spanne hat und zwei Spannen sich
 * nicht überschneiden. Die Frage stellt sich an fünf Stellen — beim Anlegen
 * einer Gewohnheit, beim Bearbeiten, beim Übernehmen eines KI-Vorschlags, beim
 * Ziehen im Raster und beim Eintragen eines Kurses —, und sie muss überall
 * dieselbe Antwort bekommen. Fünf Rechnungen wären fünf Wahrheiten über
 * denselben Tag.
 *
 * Der Unterschied zwischen den beiden Arten steckt nicht in der Rechnung,
 * sondern im Satz danach: Ein Kurs kommt von der Uni und rückt nicht, eine
 * eigene Gewohnheit schon. Einen Ausweg anzubieten, den es nicht gibt, ist
 * schlimmer als keiner — deshalb steht der Text hier und nicht bei den
 * Aufrufern.
 */
final class SlotConflict
{
    /**
     * Das Erste, was im Weg liegt — oder nichts.
     *
     * Geprüft wird an konkreten Daten und nicht an „montags": Der Stundenplan
     * hat Ausnahmen an einzelnen Daten, und der Schlafrahmen inzwischen auch.
     *
     * @param  list<array{id: int, title: string, from: int, to: int}>  $spans  Was hingelegt werden soll
     * @param  list<int>  $days  An welchen ISO-Wochentagen
     * @param  list<int>  $ignore  Gewohnheiten, die dabei nicht zählen — die bewegten selbst
     * @param  bool  $withTimetable  Zählt der Stundenplan mit? Nein, wenn er selbst der Prüfling ist
     * @param  bool  $spanIsCourse  Sind die Spannen Kurse? Die brauchen zu anderen Kursen keine Luft
     * @return array{block: array{id: int, title: string, from: int, to: int}, date: Carbon}|null
     */
    public static function find(
        User $user,
        array $spans,
        array $days,
        array $ignore = [],
        bool $withTimetable = true,
        bool $spanIsCourse = false,
    ): ?array {
        if ($spans === [] || $days === []) {
            return null;
        }

        // Wer den Stundenplan selbst prüft, darf ihn nicht als Gegner haben —
        // sonst kollidierte ein Kurs mit sich. Die Daten kommen trotzdem vom
        // Semester: Auch ein Kurs, der geändert wird, muss am ersten
        // Vorlesungstag frei sein, nicht nur nächste Woche.
        $semester = Timetable::for($user);
        $timetable = $withTimetable ? $semester : Timetable::none();

        return self::findOnDates($user, $spans, self::datesFor($days, $semester), $ignore, $timetable, $spanIsCourse);
    }

    /**
     * Dieselbe Frage an **einem** Datum.
     *
     * Für alles, was nicht jede Woche gilt: ein verlegter Kurs, ein Tag, an
     * dem etwas ausnahmsweise woanders liegt. Der Wochentag allein trüge die
     * Frage nicht — der 14. Oktober ist ein Dienstag, aber nicht jeder.
     *
     * @param  list<array{id: int, title: string, from: int, to: int}>  $spans
     * @param  list<int>  $ignore
     * @return array{block: array{id: int, title: string, from: int, to: int}, date: Carbon}|null
     */
    public static function findOn(
        User $user,
        array $spans,
        Carbon $date,
        array $ignore = [],
        bool $withTimetable = true,
        bool $spanIsCourse = false,
    ): ?array {
        if ($spans === []) {
            return null;
        }

        return self::findOnDates(
            $user,
            $spans,
            [$date],
            $ignore,
            $withTimetable ? Timetable::for($user) : Timetable::none(),
            $spanIsCourse,
        );
    }

    /**
     * Die gemeinsame Rechnung beider Einstiege.
     *
     * @param  list<array{id: int, title: string, from: int, to: int}>  $spans
     * @param  list<Carbon>  $dates
     * @param  list<int>  $ignore
     * @return array{block: array{id: int, title: string, from: int, to: int}, date: Carbon}|null
     */
    private static function findOnDates(
        User $user,
        array $spans,
        array $dates,
        array $ignore,
        Timetable $timetable,
        bool $spanIsCourse,
    ): ?array {
        // Einmal laden, für alle gefragten Tage. Die Tagesausnahmen kommen in
        // derselben Abfrage mit, damit die Platzierung sie sieht, ohne je Tag
        // nachzuladen.
        $habits = $user->habits()
            ->active()
            ->with([
                'chainedTo.chainedTo',
                'dayShifts' => fn ($query) => $query->whereIn(
                    'shifted_on',
                    array_map(fn (Carbon $date): string => $date->toDateString(), $dates),
                ),
            ])
            ->get();

        $habits->each(fn (Habit $habit) => $habit->setRelation('user', $user));

        foreach ($dates as $date) {
            $plan = DayPlan::forDate(
                $habits
                    ->filter(fn (Habit $habit): bool => $habit->isScheduledOn($date))
                    ->reject(fn (Habit $habit): bool => in_array($habit->id, $ignore, strict: true))
                    ->values(),
                $date,
                $user->sleepWindowsOn($date),
                $timetable->blocksOn($date),
            );

            foreach ($spans as $span) {
                $block = $plan->collisionWith($span['from'], $span['to'], null, $spanIsCourse);

                if ($block !== null) {
                    return ['block' => $block, 'date' => $date];
                }
            }
        }

        return null;
    }

    /**
     * Was im Weg liegt, und was sich dagegen tun lässt.
     *
     * Der Ausweg hängt an der Art des Blocks, nicht am Aufrufer — nur das
     * Ende darf ein Aufrufer ersetzen, wenn sein Zusammenhang ein anderes
     * verlangt („… dann lässt sie sich wieder aufnehmen").
     *
     * @param  array{id: int, title: string, from: int, to: int}  $block
     */
    public static function message(array $block, Carbon $date, ?string $remedy = null): string
    {
        $when = self::weekdayLabel($date);

        // Die Luft wird erklärt, nicht nur genannt — und mit ihr die Kanten:
        // Wer weiß, bis wann und ab wann Platz ist, muss nicht raten.
        $before = DayPlan::toTime($block['from'] - DayPlan::BreatherMinutes);
        $after = DayPlan::toTime($block['to'] + DayPlan::BreatherMinutes);

        if (Timetable::isCourseBlock($block)) {
            return sprintf(
                '%s läuft „%s" von %s bis %s aus deinem Semesterplan. Davor und danach hält Align eine Viertelstunde Luft — zum Hinkommen und Umschalten. Platz ist bis %s und wieder ab %s. %s',
                ucfirst($when),
                $block['title'],
                DayPlan::toTime($block['from']),
                DayPlan::toTime($block['to']),
                $before,
                $after,
                $remedy ?? 'Such eine andere Zeit — der Kurs rückt nicht.',
            );
        }

        return sprintf(
            '„%s" liegt %s schon um %s bis %s. Zwischen zwei Gewohnheiten hält Align eine Viertelstunde Luft — zum Umschalten. Platz ist bis %s und wieder ab %s. Soll sie direkt hinterher laufen, häng sie als „danach" an: Dann rückt sie mit, wenn die andere rückt. %s',
            $block['title'],
            $when,
            DayPlan::toTime($block['from']),
            DayPlan::toTime($block['to']),
            $before,
            $after,
            $remedy ?? 'Verschiebe die zuerst, dann ist hier Platz.',
        );
    }

    /**
     * An welchen Daten geprüft wird.
     *
     * Der nächste Termin jedes Wochentags — und, liegt das Semester noch vor
     * uns, zusätzlich sein erster Termin darin. Ein Kurs im Oktober ist im
     * September unsichtbar; die Gewohnheit, die man heute auf Montag zehn
     * legt, läge am ersten Vorlesungstag trotzdem mitten in ihm. Zwei Daten,
     * ein Wochentag: Beide müssen frei sein.
     *
     * @param  list<int>  $days
     * @return list<Carbon>
     */
    public static function datesFor(array $days, Timetable $timetable): array
    {
        $dates = [];

        foreach ($days as $day) {
            foreach ([self::nextWeekday($day), $timetable->firstDateOf($day)] as $date) {
                if ($date !== null && ! isset($dates[$date->toDateString()])) {
                    $dates[$date->toDateString()] = $date;
                }
            }
        }

        return array_values($dates);
    }

    /**
     * Der nächste Tag mit diesem Wochentag, heute eingeschlossen.
     */
    public static function nextWeekday(int $weekday): Carbon
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
    private static function weekdayLabel(Carbon $date): string
    {
        if ($date->isSameDay(Carbon::today())) {
            return 'heute';
        }

        return mb_strtolower($date->settings(['locale' => 'de'])->isoFormat('dddd')).'s';
    }
}
