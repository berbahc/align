<?php

namespace App\Http\Requests;

use App\Enums\ScheduleType;
use App\Enums\SuggestionKind;
use App\Models\AiSuggestion;
use App\Models\Habit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Der neue Zeitpunkt einer Gewohnheit — von der KI vorgeschlagen, vom Nutzer
 * gewählt, hier geprüft.
 *
 * Die Regeln sind dieselben wie beim Anlegen. Ein Vorschlag der KI läuft nicht
 * ungeprüft in die Datenbank, nur weil er hübsch formuliert war: Was das
 * Formular nicht durfte, darf ein Modell erst recht nicht.
 *
 * Die Planungsart bleibt, wie sie ist. Eine situative Gewohnheit bekommt eine
 * andere Situation, eine feste eine andere Uhrzeit — der Wechsel zwischen
 * beiden ist keine Anpassung, sondern eine Entscheidung, die der Nutzer selbst
 * trifft.
 */
class AdjustHabitRequest extends FormRequest
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
        $isFixed = $this->habit()->schedule_type === ScheduleType::Fixed;

        return [
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

            // Welcher der angebotenen Zeitpunkte es geworden ist. Optional,
            // weil die Anpassung auch ohne Gedächtnis funktionieren muss:
            // Fällt die Zuordnung weg, ist der Vorschlag verloren — die
            // Gewohnheit umzustellen darf daran nicht scheitern.
            'suggestion_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Der übernommene Vorschlag — sofern er zu dieser Gewohnheit gehört.
     *
     * Bewusst keine `exists`-Regel: Eine fremde oder abgelaufene ID darf keine
     * Fehlermeldung auslösen, sondern nur ins Leere laufen. Der Nutzer hat
     * einen Zeitpunkt gewählt, und der gilt — ob die App ihn sich merken kann,
     * ist ihr Problem, nicht seins.
     */
    public function acceptedSuggestion(): ?AiSuggestion
    {
        $id = $this->integer('suggestion_id');

        if ($id < 1) {
            return null;
        }

        return AiSuggestion::query()
            ->whereKey($id)
            ->where('habit_id', $this->habit()->getKey())
            ->ofKind(SuggestionKind::Anchor)
            ->first();
    }

    /**
     * Die geprüften Werte in der Form, die das Modell erwartet.
     *
     * Der nicht gewählte Zweig bleibt unangetastet — anders als beim Anlegen
     * wird hier nichts auf `null` gesetzt, weil die Planungsart sich nicht
     * ändert.
     *
     * @return array{trigger_situation?: string, scheduled_time?: string, scheduled_days?: list<int>}
     */
    public function anchor(): array
    {
        if ($this->habit()->schedule_type !== ScheduleType::Fixed) {
            return [
                'trigger_situation' => $this->string('trigger_situation')->trim()->toString(),
            ];
        }

        /** @var list<int> $days */
        $days = array_values(array_unique(array_map(
            intval(...),
            $this->array('scheduled_days'),
        )));
        sort($days);

        return [
            'scheduled_time' => $this->string('scheduled_time')->toString(),
            'scheduled_days' => $days,
        ];
    }

    private function habit(): Habit
    {
        /** @var Habit $habit */
        $habit = $this->route('habit');

        return $habit;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'trigger_situation' => 'Auslöser',
            'scheduled_time' => 'Uhrzeit',
            'scheduled_days' => 'Wochentage',
        ];
    }
}
