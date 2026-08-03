<?php

namespace App\Http\Requests;

use App\Enums\BehaviorType;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // `behavior_type` ist die im ersten Schritt gewählte Richtung.
        return [
            'behavior_type' => ['required', Rule::enum(BehaviorType::class)],
            'title' => ['required', 'string', 'max:80'],
            'trigger_situation' => ['required', 'string', 'max:120'],
            'motivation' => ['nullable', 'string', 'max:200'],
            'focus_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
        ];
    }

    /**
     * Die validierten Werte in der Form, die CreateHabit erwartet.
     *
     * @return array{title: string, behavior_type: string, trigger_situation: string, motivation: string|null, focus_minutes: int|null}
     */
    public function habitAttributes(): array
    {
        return [
            'title' => $this->string('title')->trim()->toString(),
            'behavior_type' => $this->string('behavior_type')->toString(),
            'trigger_situation' => $this->string('trigger_situation')->trim()->toString(),
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
            'trigger_situation' => 'Auslöser',
            'motivation' => 'Grund',
        ];
    }
}
