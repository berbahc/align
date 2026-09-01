<?php

namespace App\Http\Requests;

use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use App\Http\Requests\Concerns\ChecksSituation;
use App\Http\Requests\Concerns\ChecksSleepWindow;
use App\Models\Habit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Die Planung einer Gewohnheit — beim Anlegen wie beim Bearbeiten dasselbe.
 *
 * Hier steht nur, was sich einstellen lässt: der Wann-Teil, die Dauer, der
 * Warum-Satz. **Was** die Gewohnheit ist, entscheidet der Katalog — die
 * Vorlage validiert {@see StoreHabitRequest}, und beim Bearbeiten steht sie
 * gar nicht zur Wahl: Eine Gewohnheit wechselt ihre Planung, nicht ihre
 * Identität.
 */
abstract class HabitFormRequest extends FormRequest
{
    use ChecksSituation, ChecksSleepWindow;

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
     * Die gewählte Planungsart — ungültige Eingaben fallen auf die Vorgabe
     * zurück, damit die Regeln unten nicht selbst noch prüfen müssen. Über die
     * Gültigkeit entscheidet die `Rule::enum`-Regel.
     */
    protected function scheduleType(): ScheduleType
    {
        return $this->enum('schedule_type', ScheduleType::class) ?? ScheduleType::Dynamic;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Der Wann-Teil hat drei Formen, die sich ausschließen: eine Situation,
        // eine Uhrzeit mit Wochentagen, oder der Anschluss an eine andere
        // Gewohnheit. Welche gilt, entscheidet `schedule_type`; die jeweils
        // anderen Hälften müssen leer bleiben.
        $type = $this->scheduleType();

        return [
            'schedule_type' => ['required', Rule::enum(ScheduleType::class)],
            'trigger_situation' => [
                Rule::requiredIf($type === ScheduleType::Dynamic),
                'nullable', 'string', 'max:120',
            ],
            'scheduled_time' => [
                Rule::requiredIf($type->hasClockTime()), 'nullable', 'date_format:H:i',
            ],
            'scheduled_days' => [
                Rule::requiredIf($type->hasClockTime()), 'nullable', 'array', 'min:1', 'max:7',
            ],
            'scheduled_days.*' => ['integer', 'between:1,7', 'distinct'],
            'chained_to_habit_id' => [
                Rule::requiredIf($type === ScheduleType::Chained), 'nullable', 'integer',
            ],
            'motivation' => ['nullable', 'string', 'max:200'],
            'smallest_step' => ['nullable', 'string', 'max:160'],
            // Die Dauer ist Pflicht: Der Katalog enthält nur planbare
            // Aktivitäten, und planbar heißt, sie belegen eine Spanne im Tag.
            // Ohne Dauer wüsste das Time-Blocking nicht, wann der Platz wieder
            // frei ist — und eine angehängte Gewohnheit hätte keinen Beginn.
            'target_amount' => ['required', 'numeric'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            $this->validateDuration(...),
            $this->validateChain(...),
            $this->validateFrame(...),
            $this->validateSituation(...),
        ];
    }

    /**
     * Die Situation muss frei sein — geprüft nur, wo es eine gibt.
     *
     * Eine feste Uhrzeit und eine Kette haben keinen Situationstext, gegen den
     * sich vergleichen ließe.
     */
    private function validateSituation(Validator $validator): void
    {
        if ($this->scheduleType() !== ScheduleType::Dynamic) {
            return;
        }

        $this->validateSituationIsFree($validator, $this->editedHabit());
    }

    /**
     * Der Rahmen gilt nur für feste Uhrzeiten.
     *
     * Eine Situation hat keine Uhr, gegen die sich prüfen ließe — „nach dem
     * Aufstehen" liegt per Definition im wachen Teil des Tages.
     */
    private function validateFrame(Validator $validator): void
    {
        if (! $this->scheduleType()->hasClockTime()) {
            return;
        }

        $this->validateSleepWindow($validator);
    }

    /**
     * Die Grenzen der Dauer stehen in {@see MeasureUnit::Minutes}, nicht hier —
     * damit sie mit denen des Steppers dieselben bleiben.
     */
    private function validateDuration(Validator $validator): void
    {
        if (! $this->filled('target_amount')) {
            return;
        }

        $unit = MeasureUnit::Minutes;
        $amount = $this->float('target_amount');

        if ($amount < $unit->min() || $amount > $unit->max()) {
            $validator->errors()->add('target_amount', sprintf(
                'Die Dauer muss zwischen %s und %s liegen.',
                $unit->format($unit->min()),
                $unit->format($unit->max()),
            ));
        }
    }

    /**
     * An wen sich anhängen lässt — und an wen nicht.
     *
     * Zwei Bedingungen, jede aus einem eigenen Grund:
     *
     * 1. **Die eigene Gewohnheit**, aktiv und nicht beendet. Eine fremde wäre
     *    ein Blick in einen fremden Tag, eine beendete ein Anschluss an etwas,
     *    das nicht mehr stattfindet.
     * 2. **Kein Zyklus.** Ohne diese Prüfung liefe die Rekursion in
     *    `Habit::startsAt()` gegen ihre Tiefengrenze, und die Kette hätte
     *    keinen Anfang mehr.
     */
    private function validateChain(Validator $validator): void
    {
        if ($this->scheduleType() !== ScheduleType::Chained) {
            return;
        }

        $previous = $this->user()->habits()
            ->active()
            ->find($this->integer('chained_to_habit_id'));

        if ($previous === null) {
            $validator->errors()->add(
                'chained_to_habit_id',
                'Diese Gewohnheit gibt es nicht mehr.',
            );

            return;
        }

        $edited = $this->editedHabit();

        if ($edited === null) {
            return;
        }

        if ($previous->is($edited) || $this->chainReaches($previous, $edited)) {
            $validator->errors()->add(
                'chained_to_habit_id',
                'Damit hinge die Gewohnheit an sich selbst.',
            );
        }
    }

    /**
     * Führt die Kette ab `$start` irgendwann auf `$target` zurück?
     */
    private function chainReaches(Habit $start, Habit $target): bool
    {
        $habit = $start;

        for ($depth = 0; $depth < Habit::MaxChainDepth; $depth++) {
            $next = $habit->chainedTo;

            if ($next === null) {
                return false;
            }

            if ($next->is($target)) {
                return true;
            }

            $habit = $next;
        }

        // Tiefer als erlaubt heißt: hier stimmt etwas nicht. Lieber abweisen
        // als eine Kette bauen, die sich nicht mehr auflösen lässt.
        return true;
    }

    /**
     * Die Gewohnheit, die gerade bearbeitet wird — beim Anlegen gibt es keine.
     */
    protected function editedHabit(): ?Habit
    {
        $habit = $this->route('habit');

        return $habit instanceof Habit ? $habit : null;
    }

    /**
     * Die validierten Planungs-Werte in der Form, die das Modell erwartet.
     *
     * Der nicht gewählte Zweig wird ausdrücklich auf `null` gesetzt, nicht
     * weggelassen — sonst bliebe beim Wechsel der Form ein Wert stehen, der
     * zur gewählten Art nicht mehr passt.
     *
     * Die Einheit der Dauer ist immer Minuten: Der Katalog kennt nur noch
     * Aktivitäten, die eine Spanne im Tag belegen — Seiten und Liter waren
     * ein Umfang, aber keine Zeit, und mit ihnen konnte das Time-Blocking
     * nicht rechnen.
     *
     * @return array{schedule_type: string, trigger_situation: string|null, scheduled_time: string|null, scheduled_days: list<int>|null, chained_to_habit_id: int|null, motivation: string|null, smallest_step: string|null, target_amount: float, target_unit: string}
     */
    public function habitAttributes(): array
    {
        $type = $this->scheduleType();

        /** @var list<int> $days */
        $days = array_values(array_unique(array_map(
            intval(...),
            $this->array('scheduled_days'),
        )));
        sort($days);

        return [
            'schedule_type' => $type->value,
            'trigger_situation' => $type === ScheduleType::Dynamic
                ? $this->string('trigger_situation')->trim()->toString()
                : null,
            'scheduled_time' => $type->hasClockTime()
                ? $this->string('scheduled_time')->toString()
                : null,
            'scheduled_days' => $type->hasClockTime() ? $days : null,
            'chained_to_habit_id' => $type === ScheduleType::Chained
                ? $this->integer('chained_to_habit_id')
                : null,
            'motivation' => $this->filled('motivation')
                ? $this->string('motivation')->trim()->toString()
                : null,
            'smallest_step' => $this->filled('smallest_step')
                ? $this->string('smallest_step')->trim()->toString()
                : null,
            'target_amount' => $this->float('target_amount'),
            'target_unit' => MeasureUnit::Minutes->value,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'template_key' => 'Gewohnheit',
            'schedule_type' => 'Art der Planung',
            'trigger_situation' => 'Auslöser',
            'scheduled_time' => 'Uhrzeit',
            'scheduled_days' => 'Wochentage',
            'motivation' => 'Grund',
            'smallest_step' => 'Erster Schritt',
            'target_amount' => 'Dauer',
            'chained_to_habit_id' => 'Vorherige Gewohnheit',
        ];
    }
}
