<?php

namespace App\Http\Requests;

use App\Enums\BehaviorType;
use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Was eine Gewohnheit ausmacht — beim Anlegen wie beim Bearbeiten dasselbe.
 *
 * Die Felder sind in beiden Fällen identisch; nur die Umstände unterscheiden
 * sich. {@see StoreHabitRequest} setzt zusätzlich die Grenze von fünf aktiven
 * Gewohnheiten durch, {@see UpdateHabitRequest} nicht — beim Bearbeiten zählt
 * die Gewohnheit ja bereits mit und würde sich selbst blockieren.
 */
abstract class HabitFormRequest extends FormRequest
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
            'smallest_step' => ['nullable', 'string', 'max:160'],
            // Menge und Einheit sind ein Paar: eine Zahl ohne Einheit ist
            // bedeutungslos, eine Einheit ohne Zahl leer. Beide zusammen sind
            // freiwillig — nicht jede Gewohnheit misst sich.
            'target_amount' => ['nullable', 'required_with:target_unit', 'numeric'],
            'target_unit' => ['nullable', 'required_with:target_amount', Rule::enum(MeasureUnit::class)],
        ];
    }

    /**
     * Die Grenzen des Umfangs hängen an der Einheit, nicht am Feld.
     *
     * 240 Minuten sind ein langer, aber denkbarer Lerntag; 240 Liter sind ein
     * Vertipper. Die Werte stehen in {@see MeasureUnit} und nicht hier, damit
     * sie mit denen des Steppers dieselben bleiben.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $unit = $this->enum('target_unit', MeasureUnit::class);

                if ($unit === null || ! $this->filled('target_amount')) {
                    return;
                }

                $amount = $this->float('target_amount');

                if ($amount < $unit->min() || $amount > $unit->max()) {
                    $validator->errors()->add('target_amount', sprintf(
                        'Der Umfang muss zwischen %s und %s liegen.',
                        $unit->format($unit->min()),
                        $unit->format($unit->max()),
                    ));
                }
            },
        ];
    }

    /**
     * Die validierten Werte in der Form, die CreateHabit erwartet.
     *
     * Der nicht gewählte Zweig wird ausdrücklich auf `null` gesetzt, nicht
     * weggelassen — sonst bliebe beim Wechsel der Form ein Wert stehen, der
     * zur gewählten Art nicht mehr passt. Aus demselben Grund fällt der Umfang
     * ganz weg, sobald eine seiner beiden Hälften fehlt.
     *
     * @return array{title: string, behavior_type: string, schedule_type: string, trigger_situation: string|null, scheduled_time: string|null, scheduled_days: list<int>|null, motivation: string|null, smallest_step: string|null, target_amount: float|null, target_unit: string|null}
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

        $hasMeasure = $this->filled('target_amount') && $this->filled('target_unit');

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
            'smallest_step' => $this->filled('smallest_step')
                ? $this->string('smallest_step')->trim()->toString()
                : null,
            'target_amount' => $hasMeasure ? $this->float('target_amount') : null,
            'target_unit' => $hasMeasure ? $this->string('target_unit')->toString() : null,
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
            'smallest_step' => 'Erster Schritt',
            'target_amount' => 'Umfang',
            'target_unit' => 'Einheit',
        ];
    }
}
