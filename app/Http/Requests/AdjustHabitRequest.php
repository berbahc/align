<?php

namespace App\Http\Requests;

use App\Enums\ScheduleType;
use App\Enums\SuggestionKind;
use App\Http\Requests\Concerns\ChecksSituation;
use App\Http\Requests\Concerns\ChecksSleepWindow;
use App\Models\AiSuggestion;
use App\Models\Habit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Der neue Zeitpunkt einer Gewohnheit — von der KI vorgeschlagen, vom Nutzer
 * gewählt, hier geprüft.
 *
 * Die Regeln sind dieselben wie beim Anlegen. Ein Vorschlag der KI läuft nicht
 * ungeprüft in die Datenbank, nur weil er hübsch formuliert war: Was das
 * Formular nicht durfte, darf ein Modell erst recht nicht.
 *
 * **Die Planungsart darf sich dabei ändern**, und das war vorher anders. Die
 * Strecke kannte zwei Arten — Uhrzeit oder nicht —, es gibt aber drei: Eine
 * gekettete Gewohnheit fiel in den situativen Zweig, bekam eine
 * `trigger_situation` und behielt sichtbar ihren alten Anker, weil die Spalte
 * bei ihr niemand liest. Verschoben wurde nichts, gesperrt dafür ein Moment,
 * den sie gar nicht belegte.
 *
 * Welche Form gilt, entscheidet deshalb allein, **was** geschickt wurde: eine
 * Uhrzeit macht die Gewohnheit fest, eine Situation situativ, ein Vorgänger
 * gekettet. Wer ein freies Zeitfenster wählt, löst seine Gewohnheit damit aus
 * ihrer Kette — genau das ist das Verschieben.
 */
class AdjustHabitRequest extends FormRequest
{
    use ChecksSituation, ChecksSleepWindow;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Auch ein Vorschlag der KI bleibt im Rahmen des Tages.
     *
     * Der Agent kennt ihn und bietet nichts außerhalb an — aber diese Route
     * lässt sich auch von Hand ansprechen, und die Grenze gehört an die
     * Stelle, an der geschrieben wird.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $type = $this->targetType();

                if ($type === null) {
                    // §1.5 — benennt, was fehlt, ohne Vorwurf. Der Fehler hängt
                    // am Auslöser, weil das der Regelfall ist: Die meisten
                    // Gewohnheiten hängen an einem Moment.
                    $validator->errors()->add(
                        'trigger_situation',
                        'Ohne einen neuen Zeitpunkt gibt es nichts zu verschieben.',
                    );

                    return;
                }

                match ($type) {
                    ScheduleType::Fixed => $this->validateSleepWindow($validator),
                    // Auch ein Vorschlag der KI darf keinen Moment doppelt
                    // belegen — die eigene Gewohnheit zählt dabei nicht mit.
                    ScheduleType::Dynamic => $this->validateSituationIsFree($validator, $this->habit()),
                    ScheduleType::Chained => $this->validateChain($validator),
                };
            },
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'trigger_situation' => ['nullable', 'string', 'max:120'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            // Eine Uhrzeit ohne Wochentage wäre ein Zeitpunkt ohne Tag. Jeder
            // Fenster-Vorschlag bringt sie mit, und der Rückweg trägt die
            // bisherigen zurück — es gibt keinen Weg hierher, der sie nicht
            // kennt.
            'scheduled_days' => [
                Rule::requiredIf($this->filled('scheduled_time')),
                'nullable', 'array', 'min:1', 'max:7',
            ],
            'scheduled_days.*' => ['integer', 'between:1,7', 'distinct'],
            // Nur der Rückweg schickt das: Die KI hängt keine Gewohnheit an
            // eine andere — eine Kette entsteht im Formular, nicht aus einem
            // Vorschlag.
            'chained_to_habit_id' => ['nullable', 'integer'],

            // Welcher der angebotenen Zeitpunkte es geworden ist. Optional,
            // weil die Anpassung auch ohne Gedächtnis funktionieren muss:
            // Fällt die Zuordnung weg, ist der Vorschlag verloren — die
            // Gewohnheit umzustellen darf daran nicht scheitern.
            'suggestion_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Welche Planungsart aus dem Gewählten folgt — `null`, wenn nichts kam.
     *
     * Die Reihenfolge ist keine Rangfolge, sondern eine feste Lesart: Die
     * Oberfläche schickt immer genau eine der drei Formen, und was von Hand
     * kommt, soll nicht davon abhängen, in welcher Reihenfolge die Felder im
     * Request stehen.
     */
    public function targetType(): ?ScheduleType
    {
        return match (true) {
            $this->filled('scheduled_time') => ScheduleType::Fixed,
            $this->filled('trigger_situation') => ScheduleType::Dynamic,
            $this->filled('chained_to_habit_id') => ScheduleType::Chained,
            default => null,
        };
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
     * Immer alle vier Anker-Spalten, drei davon leer: Eine Gewohnheit hat genau
     * einen Zeitpunkt. Ein Rest aus der vorigen Planungsart wäre kein harmloser
     * Altwert — er belegte einen Moment oder eine Spanne, die diese Gewohnheit
     * nicht mehr hat.
     *
     * @return array{schedule_type: ScheduleType, trigger_situation: string|null, scheduled_time: string|null, scheduled_days: list<int>|null, chained_to_habit_id: int|null}
     */
    public function anchor(): array
    {
        $empty = [
            'trigger_situation' => null,
            'scheduled_time' => null,
            'scheduled_days' => null,
            'chained_to_habit_id' => null,
        ];

        return match ($this->targetType()) {
            ScheduleType::Fixed => [
                ...$empty,
                'schedule_type' => ScheduleType::Fixed,
                'scheduled_time' => $this->string('scheduled_time')->toString(),
                'scheduled_days' => $this->days(),
            ],
            ScheduleType::Chained => [
                ...$empty,
                'schedule_type' => ScheduleType::Chained,
                'chained_to_habit_id' => $this->integer('chained_to_habit_id'),
            ],
            default => [
                ...$empty,
                'schedule_type' => ScheduleType::Dynamic,
                'trigger_situation' => $this->string('trigger_situation')->trim()->toString(),
            ],
        };
    }

    /**
     * @return list<int>
     */
    private function days(): array
    {
        /** @var list<int> $days */
        $days = array_values(array_unique(array_map(
            intval(...),
            $this->array('scheduled_days'),
        )));
        sort($days);

        return $days;
    }

    /**
     * Der Rückweg darf keine Kette bauen, die es so nie gab.
     *
     * Dieselben Grenzen wie im Formular: Die vorige Gewohnheit muss es geben,
     * sie muss der eigenen Person gehören, und sie darf nicht über Umwege auf
     * die eigene zurückführen.
     */
    private function validateChain(Validator $validator): void
    {
        $previous = $this->user()->habits()
            ->active()
            ->find($this->integer('chained_to_habit_id'));

        if ($previous === null) {
            $validator->errors()->add('chained_to_habit_id', 'Diese Gewohnheit gibt es nicht mehr.');

            return;
        }

        $habit = $this->habit();

        if ($previous->is($habit) || $this->chainReaches($previous, $habit)) {
            $validator->errors()->add('chained_to_habit_id', 'Damit hinge die Gewohnheit an sich selbst.');
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

        return true;
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
            'chained_to_habit_id' => 'Vorherige Gewohnheit',
        ];
    }
}
