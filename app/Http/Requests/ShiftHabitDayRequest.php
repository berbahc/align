<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ChecksSleepWindow;
use App\Models\Habit;
use App\Support\DayPlan;
use App\Support\PromiseLock;
use App\Support\SlotConflict;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Eine Gewohnheit für genau einen Tag woanders hinlegen.
 *
 * Es gelten dieselben Grenzen wie beim Planen überhaupt: Der Rahmen des
 * Schlafplans und die Regel, dass zwei Gewohnheiten nicht übereinanderliegen.
 * Eine Ausnahme darf nicht erlauben, was die Regel verbietet — sonst wäre sie
 * ein zweiter, schwächerer Plan.
 */
class ShiftHabitDayRequest extends FormRequest
{
    use ChecksSleepWindow;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Der Wochentag des gewählten Datums, damit die geteilte Schlafprüfung
     * dieselbe Form vorfindet wie im Formular.
     */
    protected function prepareForValidation(): void
    {
        $date = $this->shiftedOn();

        if ($date !== null) {
            $this->merge(['scheduled_days' => [$date->dayOfWeekIso]]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Vergangenes lässt sich nicht mehr umlegen — der Tag ist gelaufen.
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'scheduled_time' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            $this->validateSleepWindow(...),
            $this->validateHabitHasATime(...),
            $this->validateSlotIsFree(...),
        ];
    }

    /**
     * Das gewählte Datum — null, solange es keins gibt.
     *
     * Heißt `shiftedOn` und nicht `date`: `Request::date()` ist bei Laravel
     * bereits vergeben und hat eine andere Signatur — PHP verweigert die
     * Klasse sonst.
     */
    public function shiftedOn(): ?Carbon
    {
        $value = $this->string('date')->toString();

        return $value === '' ? null : Carbon::parse($value)->startOfDay();
    }

    /**
     * Die Gewohnheit aus der Strecke.
     */
    public function habit(): Habit
    {
        /** @var Habit $habit */
        $habit = $this->route('habit');

        return $habit;
    }

    /**
     * Was keine Uhrzeit hat, lässt sich nicht verrücken.
     *
     * „Nach dem Aufstehen" liegt nirgends — eine Ausnahme dafür wäre eine
     * erfundene Uhrzeit, und die hätte die Gewohnheit nie gehabt.
     */
    private function validateHabitHasATime(Validator $validator): void
    {
        if ($this->habit()->startsAt() === null) {
            $validator->errors()->add(
                'scheduled_time',
                'Diese Gewohnheit hängt an einer Situation, nicht an einer Uhrzeit — sie lässt sich nicht verschieben.',
            );
        }
    }

    /**
     * An der neuen Stelle muss Platz sein.
     *
     * Über {@see SlotConflict::findOn()} und damit über dieselbe Rechnung wie
     * überall sonst — samt der Viertelstunde Luft. Vorher stand hier ein
     * eigener Vergleich ohne sie: Über diesen Weg ließ sich ein Block Rücken
     * an Rücken an eine Vorlesung legen, während jeder andere Weg das abwies.
     *
     * Geprüft wird die ganze Kette ({@see Habit::spansFrom()}), nicht nur der
     * bewegte Block: Die Nachfolger rutschen mit, und einer davon darf nicht
     * in einer Vorlesung landen. Sie selbst zählen dabei nicht als Hindernis —
     * von ihrer alten Stelle sollen sie ja gerade weg.
     */
    private function validateSlotIsFree(Validator $validator): void
    {
        $date = $this->shiftedOn();

        if ($date === null || ! $this->filled('scheduled_time')) {
            return;
        }

        $habit = $this->habit();

        // Was ausgemacht ist, rückt nicht: Steht an diesem Tag eine Zusage an
        // dieser Gewohnheit, liest die andere Person ihre Uhrzeit — und die
        // steht fest ({@see PromiseLock}).
        $locked = PromiseLock::on($this->user(), $habit, [$date]);

        if ($locked !== null) {
            $validator->errors()->add(
                'scheduled_time',
                PromiseLock::message($locked['appointment'], $locked['date'], $this->user()),
            );

            return;
        }
        $spans = $habit->spansFrom(DayPlan::toMinutes($this->string('scheduled_time')->toString()));

        $conflict = SlotConflict::findOn(
            $this->user(),
            $spans,
            $date,
            array_column($habit->spansFrom(0), 'id'),
        );

        if ($conflict !== null) {
            $validator->errors()->add(
                'scheduled_time',
                SlotConflict::message($conflict['block'], $conflict['date']),
            );
        }
    }
}
