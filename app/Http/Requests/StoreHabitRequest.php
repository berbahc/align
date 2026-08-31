<?php

namespace App\Http\Requests;

use App\Enums\HabitTemplate;
use App\Models\Habit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Eine neue Gewohnheit anlegen — immer aus einer Vorlage des Katalogs.
 *
 * Die Planungs-Felder stehen in {@see HabitFormRequest}; hier kommt dazu, was
 * ausschließlich beim Anlegen gilt: die Vorlage und die Grenze von fünf
 * aktiven Gewohnheiten.
 *
 * Es gibt keinen Titel im Request. Was die Gewohnheit ist, sagt die Vorlage —
 * Titel, Verhaltenstyp und Kategorie werden serverseitig aus ihr abgeleitet.
 * Ein Titel-Feld daneben wäre die freie Eingabe durch die Hintertür.
 */
class StoreHabitRequest extends HabitFormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'template_key' => ['required', Rule::enum(HabitTemplate::class)],
        ];
    }

    /**
     * Die gewählte Vorlage — nach der Validierung immer vorhanden.
     */
    protected function template(): HabitTemplate
    {
        /** @var HabitTemplate $template */
        $template = $this->enum('template_key', HabitTemplate::class);

        return $template;
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
            ...parent::after(),
            function (Validator $validator): void {
                $active = $this->user()->habits()->active()->count();

                if ($active >= Habit::MaxActivePerUser) {
                    $validator->errors()->add('template_key', sprintf(
                        'Du verfolgst bereits %d Gewohnheiten. Markiere zuerst eine als gefestigt, um Platz zu schaffen.',
                        Habit::MaxActivePerUser,
                    ));
                }
            },
        ];
    }

    /**
     * Planung plus Identität: Was die Gewohnheit ist, kommt aus der Vorlage.
     *
     * @return array{title: string, template_key: string, behavior_type: string, schedule_type: string, trigger_situation: string|null, scheduled_time: string|null, scheduled_days: list<int>|null, chained_to_habit_id: int|null, motivation: string|null, smallest_step: string|null, target_amount: float, target_unit: string}
     */
    public function habitAttributes(): array
    {
        $template = $this->template();

        return [
            ...parent::habitAttributes(),
            'title' => $template->title(),
            'template_key' => $template->value,
            'behavior_type' => $template->behaviorType()->value,
        ];
    }
}
