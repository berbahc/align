<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ChecksSleepWindow;
use App\Models\Habit;
use App\Support\DayPlan;
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
     * Dieselbe Rechnung wie überall: der Rahmen des Tages, die belegten
     * Fenster, eine Atempause dazwischen. Die eigene bisherige Stelle zählt
     * nicht — von dort soll sie ja gerade weg.
     */
    private function validateSlotIsFree(Validator $validator): void
    {
        $date = $this->shiftedOn();

        if ($date === null || ! $this->filled('scheduled_time')) {
            return;
        }

        $habit = $this->habit();
        $user = $this->user();

        $habits = $user->habits()->active()->with(['chainedTo.chainedTo', 'dayShifts'])->get();
        $habits->each(fn (Habit $other) => $other->setRelation('user', $user));

        $plan = DayPlan::forDate(
            $habits->filter(fn (Habit $other): bool => $other->isScheduledOn($date))->values(),
            $date,
            $user->sleepWindows(),
        );

        $from = DayPlan::toMinutes($this->string('scheduled_time')->toString());
        $to = $from + ($habit->durationMinutes() ?? DayPlan::AssumedMinutes);

        foreach ($plan->occupied($habit) as $block) {
            if ($from < $block['to'] && $to > $block['from']) {
                $validator->errors()->add('scheduled_time', sprintf(
                    'Um diese Zeit läuft an dem Tag schon „%s".',
                    $block['title'],
                ));

                return;
            }
        }
    }
}
