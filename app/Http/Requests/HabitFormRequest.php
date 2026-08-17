<?php

namespace App\Http\Requests;

use App\Enums\BehaviorType;
use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use App\Models\Habit;
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
        // `behavior_type` ist die im ersten Schritt gewählte Richtung.
        // Der Wann-Teil hat drei Formen, die sich ausschließen: eine Situation,
        // eine Uhrzeit mit Wochentagen — oder gar keinen Platz im Tag. Welche
        // gilt, entscheidet `schedule_type`; die jeweils anderen Hälften müssen
        // leer bleiben. Was sich ergibt, verlangt von beiden nichts.
        $type = $this->scheduleType();

        return [
            'behavior_type' => ['required', Rule::enum(BehaviorType::class)],
            'schedule_type' => ['required', Rule::enum(ScheduleType::class)],
            'title' => ['required', 'string', 'max:80'],
            'trigger_situation' => [
                Rule::requiredIf($type->isPlanned() && ! $type->hasClockTime()),
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
            $this->validateChain(...),
        ];
    }

    /**
     * An wen sich anhängen lässt — und an wen nicht.
     *
     * Drei Bedingungen, jede aus einem eigenen Grund:
     *
     * 1. **Die eigene Gewohnheit**, aktiv und nicht beendet. Eine fremde wäre
     *    ein Blick in einen fremden Tag, eine beendete ein Anschluss an etwas,
     *    das nicht mehr stattfindet.
     * 2. **Etwas mit Platz im Tag.** Was sich ergibt, hat keine Stelle — und
     *    kann deshalb auch keine weitergeben.
     * 3. **Kein Zyklus.** Ohne diese Prüfung liefe die Rekursion in
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

        if (! $previous->schedule_type->isPlanned()) {
            $validator->errors()->add('chained_to_habit_id', sprintf(
                '„%s" hat selbst keinen festen Platz im Tag — daran lässt sich nichts anschließen.',
                $previous->title,
            ));

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
     * Die validierten Werte in der Form, die CreateHabit erwartet.
     *
     * Der nicht gewählte Zweig wird ausdrücklich auf `null` gesetzt, nicht
     * weggelassen — sonst bliebe beim Wechsel der Form ein Wert stehen, der
     * zur gewählten Art nicht mehr passt. Aus demselben Grund fällt der Umfang
     * ganz weg, sobald eine seiner beiden Hälften fehlt.
     *
     * @return array{title: string, behavior_type: string, schedule_type: string, trigger_situation: string|null, scheduled_time: string|null, scheduled_days: list<int>|null, chained_to_habit_id: int|null, motivation: string|null, smallest_step: string|null, target_amount: float|null, target_unit: string|null}
     */
    public function habitAttributes(): array
    {
        $type = $this->scheduleType();
        $wantsSituation = $type->isPlanned() && ! $type->hasClockTime();

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
            'schedule_type' => $type->value,
            'trigger_situation' => $wantsSituation
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
            'chained_to_habit_id' => 'Vorherige Gewohnheit',
        ];
    }
}
