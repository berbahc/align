<?php

namespace App\Http\Requests;

use App\Models\Course;
use App\Support\DayPlan;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Eine Ausnahme für genau ein Datum: Ausfall oder anderer Termin.
 *
 * Ohne Zeiten fällt der Kurs an dem Tag aus. Mit Zeiten findet er dann statt —
 * und liegt das Datum auf einem anderen Wochentag als der Kurs, ist es ein
 * Nachholtermin. Was es nicht geben darf, ist die vierte Kombination: ein
 * Ausfall an einem Tag, an dem der Kurs ohnehin nicht läuft. Das wäre eine
 * Zeile ohne Wirkung.
 */
class StoreCourseExceptionRequest extends FormRequest
{
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
            'on_date' => ['required', 'date_format:Y-m-d'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'required_with:starts_at', 'after:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_at.after' => 'Der Termin muss nach seinem Anfang enden.',
            'ends_at.required_with' => 'Ein Termin braucht Anfang und Ende.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            $this->validateWithinSemester(...),
            $this->validateCancellationLandsOnALectureDay(...),
            $this->validateWithinTheDay(...),
        ];
    }

    /**
     * Das gewählte Datum — null, solange es keins gibt.
     *
     * Heißt `onDate` und nicht `date`, weil `Request::date()` bei Laravel
     * bereits vergeben ist und eine andere Signatur hat.
     */
    public function onDate(): ?Carbon
    {
        $value = $this->string('on_date')->toString();

        if ($value === '') {
            return null;
        }

        // Die Regel `date_format` hat hier zwar schon gegriffen, ihre Fehler
        // halten die Prüfungen danach aber nicht auf. Ein „2026-02-30" ist
        // formatrichtig und trotzdem kein Tag — es käme als 2. März zurück und
        // beantwortete dann eine Frage über einen anderen Tag.
        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (InvalidFormatException) {
            return null;
        }

        return $date->format('Y-m-d') === $value ? $date : null;
    }

    /**
     * Der Kurs aus der Strecke.
     */
    public function course(): Course
    {
        /** @var Course $course */
        $course = $this->route('course');

        return $course;
    }

    public function isCancellation(): bool
    {
        return ! $this->filled('starts_at');
    }

    private function validateWithinSemester(Validator $validator): void
    {
        $date = $this->onDate();

        if ($date === null) {
            return;
        }

        if (! $this->course()->semester->covers($date)) {
            $validator->errors()->add(
                'on_date',
                'Dieser Tag liegt außerhalb des Semesters — dort findet ohnehin nichts statt.',
            );
        }
    }

    /**
     * Ein Ausfall braucht einen Tag, an dem etwas ausfallen kann.
     */
    private function validateCancellationLandsOnALectureDay(Validator $validator): void
    {
        $date = $this->onDate();

        if ($date === null || ! $this->isCancellation()) {
            return;
        }

        if ($date->dayOfWeekIso !== $this->course()->weekday) {
            $validator->errors()->add(
                'on_date',
                'An diesem Wochentag läuft der Kurs ohnehin nicht.',
            );
        }
    }

    /**
     * Auch ein Nachholtermin bleibt innerhalb eines Tages.
     */
    private function validateWithinTheDay(Validator $validator): void
    {
        if ($this->isCancellation() || $validator->errors()->hasAny(['starts_at', 'ends_at'])) {
            return;
        }

        if (DayPlan::toMinutes($this->string('ends_at')->toString()) > 1439) {
            $validator->errors()->add('ends_at', 'Ein Termin kann nicht über Mitternacht gehen.');
        }
    }
}
