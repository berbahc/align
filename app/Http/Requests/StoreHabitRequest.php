<?php

namespace App\Http\Requests;

use App\Enums\BehaviorType;
use App\Enums\ScheduleType;
use App\Models\Habit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHabitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Fehlt die Art der Planung, ist sie situativ.
     *
     * Das ist keine Bequemlichkeit, sondern die Voreinstellung des Systems:
     * time-blocking.md macht die Situation zum Standard, die feste Uhrzeit
     * zur ausdrücklich gewählten Ausnahme.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('schedule_type')) {
            $this->merge(['schedule_type' => ScheduleType::Dynamic->value]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // `behavior_type` ist die im ersten Schritt gewählte Richtung.
        // Der Wann-Teil hat zwei sich ausschließende Formen: entweder eine
        // Situation oder eine Uhrzeit mit Wochentagen. Welche gilt, entscheidet
        // `schedule_type` — die jeweils andere Hälfte muss leer bleiben.
        $isFixed = $this->enum('schedule_type', ScheduleType::class) === ScheduleType::Fixed;

        return [
            'behavior_type' => ['required', Rule::enum(BehaviorType::class)],
            'schedule_type' => ['required', Rule::enum(ScheduleType::class)],
            'title' => ['required', 'string', 'max:80'],
            'trigger_situation' => [
                Rule::requiredIf(! $isFixed), 'nullable', 'string', 'max:120',
            ],
            'scheduled_time' => [
                Rule::requiredIf($isFixed), 'nullable', 'date_format:H:i',
            ],
            'scheduled_days' => [
                Rule::requiredIf($isFixed), 'nullable', 'array', 'min:1', 'max:7',
            ],
            'scheduled_days.*' => ['integer', 'between:1,7', 'distinct'],
            'motivation' => ['nullable', 'string', 'max:200'],
            'focus_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
        ];
    }

    /**
     * Die validierten Werte in der Form, die CreateHabit erwartet.
     *
     * Der nicht gewählte Zweig wird ausdrücklich auf `null` gesetzt, nicht
     * weggelassen — sonst bliebe beim Wechsel der Form ein Wert stehen, der
     * zur gewählten Art nicht mehr passt.
     *
     * @return array{title: string, behavior_type: string, schedule_type: string, trigger_situation: string|null, scheduled_time: string|null, scheduled_days: list<int>|null, motivation: string|null, focus_minutes: int|null}
     */
    public function habitAttributes(): array
    {
        $isFixed = $this->enum('schedule_type', ScheduleType::class) === ScheduleType::Fixed;

        /** @var list<int> $days */
        $days = array_values(array_unique(array_map(
            intval(...),
            $this->array('scheduled_days'),
        )));
        sort($days);

        return [
            'title' => $this->string('title')->trim()->toString(),
            'behavior_type' => $this->string('behavior_type')->toString(),
            'schedule_type' => $this->string('schedule_type')->toString(),
            'trigger_situation' => $isFixed
                ? null
                : $this->string('trigger_situation')->trim()->toString(),
            'scheduled_time' => $isFixed
                ? $this->string('scheduled_time')->toString()
                : null,
            'scheduled_days' => $isFixed ? $days : null,
            'motivation' => $this->filled('motivation')
                ? $this->string('motivation')->trim()->toString()
                : null,
            'focus_minutes' => $this->filled('focus_minutes')
                ? $this->integer('focus_minutes')
                : null,
        ];
    }

    /**
     * progress-tracking.md begrenzt auf 5 gleichzeitig aktive Gewohnheiten.
     * Die Grenze gehört in die Validierung, nicht nur ins Frontend — sonst
     * lässt sie sich mit einem direkten Request umgehen.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $active = $this->user()->habits()->active()->count();

                if ($active >= Habit::MaxActivePerUser) {
                    $validator->errors()->add('title', sprintf(
                        'Du verfolgst bereits %d Gewohnheiten. Markiere zuerst eine als gefestigt, um Platz zu schaffen.',
                        Habit::MaxActivePerUser,
                    ));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'Gewohnheit',
            'behavior_type' => 'Richtung',
            'schedule_type' => 'Art der Planung',
            'trigger_situation' => 'Auslöser',
            'scheduled_time' => 'Uhrzeit',
            'scheduled_days' => 'Wochentage',
            'motivation' => 'Grund',
        ];
    }
}
