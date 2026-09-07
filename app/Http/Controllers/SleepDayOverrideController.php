<?php

namespace App\Http\Controllers;

use App\Actions\CarryHabitsWithFrame;
use App\Enums\ShiftOrigin;
use App\Models\Habit;
use App\Models\HabitDayShift;
use App\Models\User;
use App\Support\DayPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * „Heute bin ich später aufgestanden."
 *
 * Der Schlafplan kennt Wochentage. Wer einmal verschläft, hatte deshalb nur
 * die Wahl, seinen Montag für immer umzustellen — wegen eines Morgens. Das ist
 * dieselbe Not, aus der {@see HabitDayShift} entstanden ist, eine
 * Ebene höher: Nicht die Gewohnheit liegt heute woanders, sondern der Tag
 * selbst.
 *
 * Die Ausnahme verschiebt den Rahmen, und mit ihm alles, was daran hängt.
 * Situative Gewohnheiten folgen von selbst — „nach dem Aufstehen" liest die
 * Aufstehzeit dieses Datums ({@see User::sleepWindowOn()}). Feste Uhrzeiten
 * bekommen einen Umzug für diesen einen Tag, und der trägt seine Herkunft
 * ({@see ShiftOrigin}), damit das Zurücknehmen weiß, was ihm gehört.
 */
class SleepDayOverrideController extends Controller
{
    /**
     * Was der andere Rahmen an diesem Tag verschieben würde.
     *
     * Wie bei der Schlaf-Seite: erst zeigen, dann übernehmen. Gerechnet wird
     * am gespeicherten Stand und danach zurückgerollt, damit die Vorschau
     * nichts anderes zeigen kann als das, was hinterher geschieht
     * ({@see SleepScheduleController::preview()}).
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $this->validated($request);
        $user = $request->user();
        $date = $validated['date'];

        $before = $user->sleepWindowsOn($date);

        DB::beginTransaction();

        try {
            $this->storeFrame($user, $date, $validated);

            $carried = (new CarryHabitsWithFrame)->forDay($user, $date, $before);
        } finally {
            DB::rollBack();
            $user->unsetRelation('sleepDayOverrides');
        }

        return response()->json([
            'moves' => array_map(fn (array $move): array => [
                'habitId' => $move['habit']->id,
                'title' => $move['habit']->title,
                'from' => DayPlan::toTime($move['from']),
                'to' => DayPlan::toTime($move['to']),
            ], $carried['moves']),
            'blocked' => array_map(fn (array $entry): array => [
                'habitId' => $entry['habit']->id,
                'title' => $entry['habit']->title,
                'reason' => $entry['reason'],
            ], $carried['blocked']),
        ]);
    }

    /**
     * Den Rahmen dieses einen Tages setzen.
     *
     * Der Rahmen gilt in jedem Fall — er beschreibt, wann jemand wach war, und
     * das weist die App nicht ab. `carry_habits` entscheidet nur darüber, ob
     * die festen Uhrzeiten mitgehen.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $user = $request->user();
        $date = $validated['date'];
        $carry = $request->boolean('carry_habits');

        $moved = DB::transaction(function () use ($user, $date, $validated, $carry): array {
            $before = $user->sleepWindowsOn($date);

            $this->storeFrame($user, $date, $validated);

            if ($carry === false) {
                return [];
            }

            // Die Aktion schreibt die Umzüge selbst — jeden sofort, damit der
            // nächste ihn sieht. Sonst landeten zwei auf demselben Platz.
            return (new CarryHabitsWithFrame)->forDay($user, $date, $before)['moves'];
        });

        return back()->with('success', $this->savedMessage($validated['wake_time'], $moved));
    }

    /**
     * Der Tag gilt wieder wie geplant.
     *
     * Mit der Ausnahme fallen nur die Umzüge, die aus ihr kamen. Was der
     * Nutzer an diesem Tag selbst gelegt hat, bleibt: Er hat es nicht
     * zurückgenommen, und eine Aufstehzeit zurückzusetzen ist keine Ansage
     * über den Rest des Tages.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $user = $request->user();
        $date = Carbon::createFromFormat('!Y-m-d', $validated['date']);

        DB::transaction(function () use ($user, $date): void {
            $user->sleepDayOverrides()->whereDate('on_date', $date)->delete();

            HabitDayShift::query()
                ->whereIn('habit_id', $user->habits()->select('id'))
                ->whereDate('shifted_on', $date)
                ->where('origin', ShiftOrigin::Frame)
                ->delete();
        });

        $user->unsetRelation('sleepDayOverrides');

        return back()->with('success', 'Dieser Tag läuft wieder nach deinem Schlafplan.');
    }

    /**
     * Die Zeile schreiben — oder löschen, wenn sie nichts mehr sagt.
     *
     * Gespeichert wird nur die Abweichung: Wer nur später aufsteht, lässt
     * seine Schlafenszeit leer und behält damit die aus dem Wochenplan, auch
     * wenn er den Plan später ändert. Wer beide zurücksetzt, hat keine
     * Ausnahme mehr — und eine Zeile, die nichts sagt, gehört nicht in die
     * Tabelle.
     *
     * @param  array{date: Carbon, wake_time: string|null, bedtime: string|null}  $validated
     */
    private function storeFrame(User $user, Carbon $date, array $validated): void
    {
        if ($validated['wake_time'] === null && $validated['bedtime'] === null) {
            $user->sleepDayOverrides()->whereDate('on_date', $date)->delete();
        } else {
            // Dieselbe Carbon-Falle wie bei den Umzügen: `on_date` ist ein
            // `date`-Cast, und eine Zeichenkette fände die eigene Zeile nicht.
            $user->sleepDayOverrides()->updateOrCreate(
                ['on_date' => $date],
                ['wake_time' => $validated['wake_time'], 'bedtime' => $validated['bedtime']],
            );
        }

        $user->unsetRelation('sleepDayOverrides');
    }

    /**
     * Der Satz danach.
     *
     * @param  list<array{habit: Habit, from: int, to: int}>  $moved
     */
    private function savedMessage(?string $wake, array $moved): string
    {
        $frame = $wake === null
            ? 'Dieser Tag hat einen eigenen Rahmen.'
            : sprintf('Dieser Tag beginnt um %s.', $wake);

        if ($moved === []) {
            return $frame;
        }

        if (count($moved) === 1) {
            return sprintf(
                '%s „%s" liegt heute um %s.',
                $frame,
                $moved[0]['habit']->title,
                DayPlan::toTime($moved[0]['to']),
            );
        }

        return sprintf('%s %d Gewohnheiten sind mitgezogen — nur heute.', $frame, count($moved));
    }

    /**
     * Die Regeln, die Vorschau und Speichern teilen.
     *
     * @return array{date: Carbon, wake_time: string|null, bedtime: string|null}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'wake_time' => ['nullable', 'date_format:H:i'],
            'bedtime' => ['nullable', 'date_format:H:i', 'different:wake_time'],
        ], [
            'bedtime.different' => 'Aufsteh- und Schlafenszeit können nicht dieselbe sein.',
        ]);

        $date = Carbon::createFromFormat('!Y-m-d', $validated['date']);

        // Ein vergangener Tag ist vorbei. Ihn umzuräumen änderte nichts mehr
        // an ihm und wäre nur eine Nachbesserung der eigenen Geschichte —
        // dieselbe Grenze wie in {@see HabitDayShiftController::move()}.
        if ($date->lessThan(Carbon::today())) {
            throw ValidationException::withMessages([
                'date' => 'Vergangene Tage lassen sich nicht mehr umlegen.',
            ]);
        }

        return [
            'date' => $date,
            'wake_time' => $validated['wake_time'] ?? null,
            'bedtime' => $validated['bedtime'] ?? null,
        ];
    }
}
