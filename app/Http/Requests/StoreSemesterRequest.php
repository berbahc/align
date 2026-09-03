<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Das Semester anlegen oder seinen Zeitraum ändern.
 *
 * Der Zeitraum ist die einzige Angabe, die etwas entscheidet: Außerhalb von
 * ihm belegen die Kurse keine Zeit mehr. Deshalb steht er unter Grenzen, die
 * ein vertipptes Jahr abfangen — ein Semester, das drei Tage oder drei Jahre
 * dauert, ist keins.
 */
class StoreSemesterRequest extends FormRequest
{
    /** Kürzestes und längstes Semester in Tagen. */
    private const int MinimumDays = 30;

    private const int MaximumDays = 400;

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
            'title' => ['required', 'string', 'max:60'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after:starts_on'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_on.after' => 'Das Semester muss nach seinem Anfang enden.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [$this->validateLength(...)];
    }

    private function validateLength(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $days = $this->date('starts_on')->diffInDays($this->date('ends_on'));

        if ($days < self::MinimumDays || $days > self::MaximumDays) {
            $validator->errors()->add('ends_on', sprintf(
                'Ein Semester dauert zwischen %d Tagen und %d Tagen.',
                self::MinimumDays,
                self::MaximumDays,
            ));
        }
    }
}
