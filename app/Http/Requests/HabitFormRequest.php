<?php

namespace App\Http\Requests;

use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use App\Http\Requests\Concerns\ChecksDayPlan;
use App\Http\Requests\Concerns\ChecksSituation;
use App\Http\Requests\Concerns\ChecksSleepWindow;
use App\Models\Habit;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\SlotConflict;
use App\Support\Timetable;
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
    use ChecksDayPlan, ChecksSituation, ChecksSleepWindow;

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
            $this->validateSituationSlot(...),
            $this->validateSlot(...),
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
     * Auch eine Situation braucht Platz — irgendwo in ihrer Spanne.
     *
     * „Nach dem Frühstück" ist kein Termin um acht, sondern irgendwann am
     * Vormittag. Der Tagesplan legt die Gewohnheit deshalb an den ersten
     * freien Fleck darin ({@see DayPlan::placements()}), statt sie stur auf
     * die Stunde zu setzen und mit dem zu kollidieren, was dort liegt.
     *
     * Abgewiesen wird nur, was gar nicht mehr hineinpasst: Wenn die ganze
     * Spanne an einem ihrer Tage voll ist, gäbe es keine Stelle mehr, an die
     * sie ausweichen könnte — und sie läge übereinander, was dieser Kalender
     * nirgends zulässt.
     */
    private function validateSituationSlot(Validator $validator): void
    {
        if ($this->scheduleType() !== ScheduleType::Dynamic) {
            return;
        }

        $user = $this->user();
        $situation = $this->string('trigger_situation')->trim()->toString();

        if ($user === null || $situation === '' || $validator->errors()->has('trigger_situation')) {
            return;
        }

        $minutes = (int) round($this->float('target_amount'));
        $edited = $this->editedHabit();
        $probe = $this->probeFor($user, $situation, $minutes);
        $window = $probe->situationWindow();

        if ($window === null) {
            return;
        }

        $habits = $user->habits()->active()->with('chainedTo.chainedTo')->get();
        $habits->each(fn (Habit $other) => $other->setRelation('user', $user));

        $timetable = Timetable::for($user);

        foreach (range(1, 7) as $weekday) {
            $date = SlotConflict::nextWeekday($weekday);

            $plan = DayPlan::forDate(
                $habits
                    ->filter(fn (Habit $other): bool => $other->isScheduledOn($date))
                    ->reject(fn (Habit $other): bool => $edited !== null && $other->is($edited))
                    ->values(),
                $date,
                $user->sleepWindows(),
                $timetable->blocksOn($date),
            );

            if ($this->fitsInWindow($plan, $probe->situationWindow($date) ?? $window, $minutes)) {
                continue;
            }

            $validator->errors()->add('trigger_situation', sprintf(
                'Rund um „%s" ist an diesem Tag nichts mehr frei — der Kalender legt die Gewohnheit dort irgendwo zwischen %s und %s hin, und %d Minuten passen nirgends mehr dazwischen. Wähl eine andere Situation, oder mach anderswo Platz.',
                $situation,
                DayPlan::toTime($window['from']),
                DayPlan::toTime($window['to']),
                $minutes,
            ));

            return;
        }
    }

    /**
     * Passt die Dauer irgendwo in diese Spanne?
     *
     * Über die freien Fenster des Tages, damit die Atempause und der
     * Schlafrahmen gelten, ohne dass es dafür eine zweite Rechnung gibt.
     *
     * @param  array{from: int, to: int}  $window
     */
    private function fitsInWindow(DayPlan $plan, array $window, int $minutes): bool
    {
        foreach ($plan->freeWindows($minutes) as $free) {
            $from = max($free['from'], $window['from']);
            $to = min($free['to'], $window['to']);

            if ($to - $from >= $minutes) {
                return true;
            }
        }

        return false;
    }

    /**
     * Eine Gewohnheit, wie sie mit den eingegebenen Werten aussähe.
     *
     * Damit die Spanne aus derselben Rechnung kommt wie im Tag: Die beiden
     * Situationen am Tagesrand hängen am Schlafplan und an der Dauer, und
     * zwei Rechnungen dafür wären zwei Wahrheiten über dieselbe Stelle.
     */
    private function probeFor(User $user, string $situation, int $minutes): Habit
    {
        $probe = new Habit([
            'schedule_type' => ScheduleType::Dynamic,
            'trigger_situation' => $situation,
            'target_amount' => $minutes,
            'target_unit' => MeasureUnit::Minutes->value,
        ]);

        $probe->setRelation('user', $user);

        return $probe;
    }

    /**
     * An der gewählten Uhrzeit muss Platz sein — an jedem gewählten Tag.
     *
     * Für feste Uhrzeiten. Eine Kette hat ihren Zeitpunkt erst, wenn ihr
     * Vorgänger einen hat; sie wird über ihn geprüft, nicht selbst.
     */
    private function validateSlot(Validator $validator): void
    {
        if (! $this->scheduleType()->hasClockTime() || ! $this->filled('scheduled_time')) {
            return;
        }

        /** @var list<int> $days */
        $days = array_values(array_unique(array_map(intval(...), $this->array('scheduled_days'))));

        $this->validateSlotIsFree(
            $validator,
            'scheduled_time',
            $this->string('scheduled_time')->toString(),
            $days,
            (int) round($this->float('target_amount')),
            $this->editedHabit(),
        );
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
            // Beim Anlegen gibt es noch keine eigene Kennung, die auszunehmen
            // wäre — der Zyklus-Fall kann hier ohnehin nicht eintreten.
            if ($previous->chainedHabits()->active()->exists()) {
                $validator->errors()->add(
                    'chained_to_habit_id',
                    sprintf('An „%s" hängt schon eine Gewohnheit. Häng deine an die letzte der Reihe.', $previous->title),
                );
            }

            return;
        }

        if ($previous->is($edited) || $this->chainReaches($previous, $edited)) {
            $validator->errors()->add(
                'chained_to_habit_id',
                'Damit hinge die Gewohnheit an sich selbst.',
            );

            return;
        }

        // Eine Kette ist eine Reihe, kein Fächer. Hängen zwei Gewohnheiten an
        // derselben, beginnen beide, wenn die vorige endet — zwei Dinge auf
        // einer Minute. {@see Habit::spansFrom()} folgt ohnehin nur der ersten;
        // die Regel schreibt also fest, wovon die Rechnung längst ausgeht.
        if ($previous->chainedHabits()->active()->whereKeyNot($edited->id)->exists()) {
            $validator->errors()->add(
                'chained_to_habit_id',
                sprintf('An „%s" hängt schon eine Gewohnheit. Häng deine an die letzte der Reihe.', $previous->title),
            );

            return;
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
