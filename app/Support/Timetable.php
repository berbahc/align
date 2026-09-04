<?php

namespace App\Support;

use App\Models\Course;
use App\Models\CourseException;
use App\Models\Semester;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Der Stundenplan als Belegung eines Tages.
 *
 * Der Schlafplan sagt, wann der Tag anfängt und aufhört; dieser sagt, wann
 * darin nichts geht. Beide sind Rahmen, keine Vorsätze — ein Kurs wird nicht
 * abgehakt, er steht einfach im Weg.
 *
 * Die Klasse ist der **einzige** Ort, an dem aus wöchentlichen Kursen und
 * datierten Ausnahmen die Belegung eines konkreten Tages wird. Das ist keine
 * Ordnungsliebe, sondern die Bedingung dafür, dass die Rechnung aufgeht:
 * {@see DayPlan} nimmt Fremdblöcke von sechs verschiedenen Aufrufern entgegen,
 * und gäbe einer von ihnen eine andere Belegung heraus als die übrigen, säße
 * dieselbe Gewohnheit auf zwei Wegen an zwei Minuten.
 *
 * Einmal je Anfrage gebaut, beliebig oft je Datum befragt: Der Monat fragt
 * zweiundvierzigmal und kostet trotzdem keine zusätzliche Abfrage.
 */
final class Timetable
{
    /**
     * @param  array<int, list<Course>>  $byWeekday  Kurse nach ISO-Wochentag
     * @param  array<int, array<string, CourseException>>  $exceptions  Ausnahmen nach Kurs und „Y-m-d"
     * @param  array<string, list<array{course: Course, exception: CourseException}>>  $byDate  Dieselben Ausnahmen nach Datum
     * @param  array<string, list<array{course: Course, from: int, to: int, moved: bool}>>  $occurrences  Was schon ausgerechnet wurde
     */
    private function __construct(
        private readonly ?Semester $semester,
        private readonly array $byWeekday,
        private readonly array $exceptions,
        private readonly array $byDate,
        private array $occurrences = [],
    ) {}

    /**
     * Den Plan einer Person laden.
     *
     * Drei Abfragen: das Semester, seine Kurse, deren Ausnahmen. Danach fällt
     * keine mehr an, egal wie viele Tage gefragt werden.
     */
    public static function for(User $user): self
    {
        $semester = $user->currentSemester();

        if ($semester === null) {
            return self::none();
        }

        $courses = $semester->courses()->with('exceptions')->get();

        $byWeekday = [];
        $exceptions = [];
        $byDate = [];

        foreach ($courses as $course) {
            $byWeekday[$course->weekday][] = $course;

            foreach ($course->exceptions as $exception) {
                $key = $exception->on_date->toDateString();

                $exceptions[$course->id][$key] = $exception;
                $byDate[$key][] = ['course' => $course, 'exception' => $exception];
            }
        }

        return new self($semester, $byWeekday, $exceptions, $byDate);
    }

    /**
     * Ein leerer Plan.
     *
     * Der Weg der meisten Nutzer: Wer nicht studiert, hat kein Semester, und
     * jeder Aufrufer soll trotzdem einen Plan bekommen statt eines `null`, das
     * er sechsmal abfragen müsste.
     */
    public static function none(): self
    {
        return new self(null, [], [], []);
    }

    public function semester(): ?Semester
    {
        return $this->semester;
    }

    /**
     * Wie viele Kurse der Plan trägt.
     */
    /**
     * Der erste Termin dieses Wochentags, an dem der Stundenplan gilt.
     *
     * Heute oder später — und erst ab Semesterbeginn. Wer im September einen
     * Kurs für Oktober einträgt, hat ihn im September noch nicht im Tag; die
     * Kollisionsprüfung muss trotzdem dorthin schauen, sonst läge die
     * Gewohnheit am ersten Vorlesungstag mitten im Kurs. Null ohne Semester,
     * oder wenn der Wochentag darin keinen Termin mehr hat.
     */
    public function firstDateOf(int $weekday): ?Carbon
    {
        if ($this->semester === null) {
            return null;
        }

        $day = Carbon::today();

        if ($day->lt($this->semester->starts_on)) {
            $day = Carbon::parse($this->semester->starts_on->toDateString());
        }

        for ($step = 0; $step < 7; $step++) {
            if ($day->dayOfWeekIso === $weekday) {
                return $this->semester->covers($day) ? $day : null;
            }

            $day->addDay();
        }

        return null;
    }

    public function courseCount(): int
    {
        return array_sum(array_map(count(...), $this->byWeekday));
    }

    /**
     * Was der Stundenplan an diesem Tag belegt.
     *
     * In genau der Form, die {@see DayPlan} als Fremdblock erwartet. Die
     * Kennung ist negativ: Gewohnheiten tragen positive, die Verabredung die
     * 0 — zwei Blöcke mit derselben Kennung wären im Raster ein Schlüssel und
     * beim Verschieben ein falscher Nachfolger.
     *
     * @return list<array{id: int, title: string, from: int, to: int}>
     */
    public function blocksOn(CarbonInterface $date): array
    {
        return array_map(fn (array $occurrence): array => [
            'id' => -$occurrence['course']->id,
            'title' => $occurrence['course']->title,
            'from' => $occurrence['from'],
            'to' => $occurrence['to'],
        ], $this->occurrencesOn($date));
    }

    /**
     * Dieselbe Belegung für die Oberfläche — mit Art, Ort und lesbarer Spanne.
     *
     * @return list<array{kind: 'course', id: int, title: string, courseKind: string, kindLabel: string, startMinute: int, durationMinutes: int, timeRange: string, location: string|null, moved: bool}>
     */
    public function coursesOn(CarbonInterface $date): array
    {
        return array_map(function (array $occurrence): array {
            $course = $occurrence['course'];

            return [
                // Sagt dem Raster, welcher Art dieser Block ist. Das Gegenstück
                // steht in `CalendarController::block()`; fehlt es hier, hält
                // die Zeichnung den Kurs für eine Gewohnheit und sucht ein
                // Symbol, das es für ihn nicht gibt.
                'kind' => 'course',
                'id' => -$course->id,
                'title' => $course->title,
                'courseKind' => $course->kind->value,
                'kindLabel' => $course->kind->label(),
                'startMinute' => $occurrence['from'],
                'durationMinutes' => $occurrence['to'] - $occurrence['from'],
                'timeRange' => sprintf(
                    '%s – %s',
                    DayPlan::toTime($occurrence['from']),
                    DayPlan::toTime($occurrence['to']),
                ),
                'location' => $course->location,
                'moved' => $occurrence['moved'],
            ];
        }, $this->occurrencesOn($date));
    }

    /**
     * Die Vorlesungstage eines Zeitraums in einem Durchgang.
     *
     * Der Monat zeichnet zweiundvierzig Zellen. Je Zelle zu fragen, ob dort
     * etwas läuft, wäre in Ordnung — aber der Aufrufer soll gar nicht erst in
     * die Versuchung kommen, dafür etwas nachzuladen.
     *
     * @return array<string, true> Schlüssel „Y-m-d"
     */
    public function lectureDays(CarbonInterface $from, CarbonInterface $to): array
    {
        $days = [];

        for ($day = $from->copy(); $day->lessThanOrEqualTo($to); $day = $day->addDay()) {
            if ($this->occurrencesOn($day) !== []) {
                $days[$day->toDateString()] = true;
            }
        }

        return $days;
    }

    /**
     * Gehört dieser Fremdblock zum Stundenplan?
     *
     * Die eine Stelle, an der die Kennung gelesen wird — damit die Regel
     * „Kurse sind negativ" nicht an sechs Orten neu erfunden wird.
     *
     * @param  array{id: int, title: string, from: int, to: int}  $block
     */
    public static function isCourseBlock(array $block): bool
    {
        return $block['id'] < 0;
    }

    /**
     * Was an diesem Tag wirklich stattfindet.
     *
     * Drei Schichten übereinander: der wöchentliche Kurs, die Ausnahme für
     * dieses Datum, und die Nachholtermine, die von einem anderen Wochentag
     * hierher gezogen wurden.
     *
     * Verglichen wird über die Datumszeichenkette, nie über Carbon-Objekte:
     * In der Spalte steht ein Zeitstempel um Mitternacht, und über
     * Zeitzonengrenzen hinweg entschiede der über einen ganzen Tag.
     *
     * @return list<array{course: Course, from: int, to: int, moved: bool}>
     */
    private function occurrencesOn(CarbonInterface $date): array
    {
        $key = $date->toDateString();

        if (isset($this->occurrences[$key])) {
            return $this->occurrences[$key];
        }

        if ($this->semester === null || ! $this->semester->covers($date)) {
            return $this->occurrences[$key] = [];
        }

        $weekday = $date->dayOfWeekIso;
        $found = [];

        foreach ($this->byWeekday[$weekday] ?? [] as $course) {
            $exception = $this->exceptions[$course->id][$key] ?? null;

            if ($exception === null) {
                $found[] = [
                    'course' => $course,
                    'from' => $course->startMinute(),
                    'to' => $course->endMinute(),
                    'moved' => false,
                ];

                continue;
            }

            // Beide Zeiten fehlen: An diesem Tag findet nichts statt. Der Kurs
            // belegt dann auch keine Zeit — eine ausgefallene Vorlesung ist ein
            // freier Vormittag, kein blockierter.
            if ($exception->isCancellation()) {
                continue;
            }

            $found[] = [
                'course' => $course,
                'from' => DayPlan::toMinutes($exception->starts_at->format('H:i')),
                'to' => DayPlan::toMinutes($exception->ends_at->format('H:i')),
                'moved' => true,
            ];
        }

        // Nachholtermine: Ausnahmen, deren Kurs an einem anderen Wochentag
        // liegt. Sie kommen zu dem hinzu, was hier ohnehin läuft.
        foreach ($this->byDate[$key] ?? [] as $entry) {
            if ($entry['course']->weekday === $weekday || $entry['exception']->isCancellation()) {
                continue;
            }

            $found[] = [
                'course' => $entry['course'],
                'from' => DayPlan::toMinutes($entry['exception']->starts_at->format('H:i')),
                'to' => DayPlan::toMinutes($entry['exception']->ends_at->format('H:i')),
                'moved' => true,
            ];
        }

        usort($found, fn (array $a, array $b): int => $a['from'] <=> $b['from']);

        return $this->occurrences[$key] = $found;
    }
}
