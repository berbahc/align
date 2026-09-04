<?php

namespace App\Http\Requests;

use App\Enums\CourseKind;
use App\Models\Course;
use App\Models\Semester;
use App\Support\DayPlan;
use App\Support\SlotConflict;
use App\Support\Timetable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Einen Kurs in den Stundenplan eintragen.
 *
 * Es gelten dieselben Grenzen wie beim Planen überhaupt: Nichts liegt über
 * Mitternacht, und zwei Dinge liegen nicht übereinander. Ein Stundenplan, der
 * sich selbst widerspricht, wäre als Rahmen wertlos — die Rechnung, wo im Tag
 * noch Platz ist, führt ihn ungeprüft weiter.
 */
class StoreCourseRequest extends FormRequest
{
    /** Kürzeste und längste Veranstaltung in Minuten. */
    private const int MinimumMinutes = 30;

    private const int MaximumMinutes = 360;

    /** Frühester Anfang und spätestes Ende als Minute seit Mitternacht. */
    private const int EarliestStart = 300;   // 05:00

    private const int LatestEnd = 1439;      // 23:59

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'kind' => ['required', Rule::enum(CourseKind::class)],
            'weekday' => ['required', 'integer', 'between:1,7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_at.after' => 'Ein Kurs muss nach seinem Anfang enden.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            $this->validateLength(...),
            $this->validateWithinTheDay(...),
            $this->validateSlotIsFree(...),
            $this->validateNoHabitInTheWay(...),
            $this->validateCourseLimit(...),
        ];
    }

    /**
     * Das Semester, in das der Kurs gehört.
     *
     * Beim Anlegen ist das immer das laufende — es gibt nur eine Seite und auf
     * ihr nur ein Semester. Beim Ändern nimmt {@see UpdateCourseRequest} das
     * des Kurses, damit ein alter Kurs nicht ins neue Semester wandert.
     */
    public function semester(): ?Semester
    {
        return $this->user()?->currentSemester();
    }

    /**
     * Welcher Kurs bei der Überschneidungsprüfung nicht mitzählt.
     *
     * Beim Anlegen keiner; beim Ändern der Kurs selbst, sonst kollidierte er
     * mit sich, sobald man nur den Titel berichtigt.
     */
    protected function ignoredCourseId(): ?int
    {
        return null;
    }

    private function startMinute(): int
    {
        return DayPlan::toMinutes($this->string('starts_at')->toString());
    }

    private function endMinute(): int
    {
        return DayPlan::toMinutes($this->string('ends_at')->toString());
    }

    /**
     * Vorbei, sobald das Format nicht stimmt — sonst rechnete alles Weitere
     * mit einer Zeit, die es nicht gibt.
     */
    private function hasUsableTimes(Validator $validator): bool
    {
        return ! $validator->errors()->hasAny(['starts_at', 'ends_at', 'weekday']);
    }

    private function validateLength(Validator $validator): void
    {
        if (! $this->hasUsableTimes($validator)) {
            return;
        }

        $minutes = $this->endMinute() - $this->startMinute();

        if ($minutes < self::MinimumMinutes) {
            $validator->errors()->add('ends_at', sprintf(
                'Kürzer als %d Minuten ist keine Veranstaltung.',
                self::MinimumMinutes,
            ));

            return;
        }

        if ($minutes > self::MaximumMinutes) {
            $validator->errors()->add('ends_at', sprintf(
                'Länger als %d Stunden am Stück trag lieber als zwei Kurse ein.',
                intdiv(self::MaximumMinutes, 60),
            ));
        }
    }

    /**
     * Nichts reicht über Mitternacht.
     *
     * Dieselbe Grenze, die auch das Verschieben einer Gewohnheit zieht: Der
     * Kalender rechnet in Minuten seit Mitternacht, und ein Block, der darüber
     * hinausragt, läge am nächsten Morgen.
     */
    private function validateWithinTheDay(Validator $validator): void
    {
        if (! $this->hasUsableTimes($validator)) {
            return;
        }

        if ($this->startMinute() < self::EarliestStart) {
            $validator->errors()->add('starts_at', sprintf(
                'Vor %s Uhr fängt keine Veranstaltung an.',
                DayPlan::toTime(self::EarliestStart),
            ));
        }

        if ($this->endMinute() > self::LatestEnd) {
            $validator->errors()->add('ends_at', 'Ein Kurs kann nicht über Mitternacht gehen.');
        }
    }

    /**
     * Zwei Kurse am selben Wochentag dürfen sich nicht überschneiden.
     *
     * Derselbe halboffene Vergleich wie in {@see DayPlan::collisionWith()}:
     * Ein Kurs, der endet, wenn der nächste anfängt, ist keine Überschneidung.
     */
    private function validateSlotIsFree(Validator $validator): void
    {
        $semester = $this->semester();

        if ($semester === null || ! $this->hasUsableTimes($validator)) {
            return;
        }

        $from = $this->startMinute();
        $to = $this->endMinute();

        $others = $semester->courses()
            ->where('weekday', $this->integer('weekday'))
            ->when($this->ignoredCourseId(), fn ($query, int $id) => $query->whereKeyNot($id))
            ->get();

        foreach ($others as $other) {
            if ($from < $other->endMinute() && $to > $other->startMinute()) {
                $validator->errors()->add('starts_at', sprintf(
                    'Um diese Zeit läuft an dem Tag schon „%s".',
                    $other->title,
                ));

                return;
            }
        }
    }

    /**
     * An der Stelle darf auch keine Gewohnheit liegen.
     *
     * Die Richtung ist hier eine andere als sonst: Ein Kurs ist die Tatsache
     * und die Gewohnheit das Bewegliche — eigentlich müsste sie weichen. Sie
     * ungefragt zu verschieben wäre aber ein Eingriff in einen Plan, den sich
     * jemand vorgenommen hat, und sie stillschweigend überdecken zu lassen
     * hieße, zwei Dinge auf eine Minute zu legen.
     *
     * Also die dritte Möglichkeit: Der Kurs wartet, und der Satz sagt, was
     * zuerst zu tun ist. Die Reihenfolge bleibt damit dieselbe wie überall —
     * wer zuletzt kommt, sucht sich seinen Platz.
     */
    private function validateNoHabitInTheWay(Validator $validator): void
    {
        if (! $this->hasUsableTimes($validator) || $validator->errors()->has('starts_at')) {
            return;
        }

        $user = $this->user();

        if ($user === null) {
            return;
        }

        $conflict = SlotConflict::find(
            $user,
            [[
                'id' => 0,
                'title' => $this->string('title')->toString(),
                'from' => $this->startMinute(),
                'to' => $this->endMinute(),
            ]],
            [$this->integer('weekday')],
        );

        if ($conflict === null || Timetable::isCourseBlock($conflict['block'])) {
            return;
        }

        $validator->errors()->add('starts_at', SlotConflict::message(
            $conflict['block'],
            $conflict['date'],
            'Verschiebe die zuerst, dann passt der Kurs hier hinein.',
        ));
    }

    private function validateCourseLimit(Validator $validator): void
    {
        $semester = $this->semester();

        if ($semester === null || $this->ignoredCourseId() !== null) {
            return;
        }

        if ($semester->courses()->count() >= Course::MaxPerSemester) {
            $validator->errors()->add('title', sprintf(
                'Mehr als %d Kurse trägt ein Semesterplan nicht.',
                Course::MaxPerSemester,
            ));
        }
    }
}
