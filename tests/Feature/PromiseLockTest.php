<?php

use App\Enums\HabitTemplate;
use App\Enums\ScheduleType;
use App\Models\Appointment;
use App\Models\Friendship;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Was ausgemacht ist, rückt nicht.
 *
 * Die Uhrzeit einer Verabredung steht fest, sobald gefragt wurde — genau
 * damit beide Seiten dieselbe lesen. Wer danach seine Gewohnheit verschiebt,
 * bricht das auf: Sein Block wandert, die Verabredung bleibt, und die andere
 * Person erfährt nichts davon. Zwei Kalender, zwei Uhrzeiten, beide überzeugt.
 *
 * Drei Wege führten dorthin, jeder mit einer anderen Geste. Der vierte —
 * der Schlafplan — lässt sich nicht verriegeln, weil er den ganzen Tag
 * betrifft; dort hält eine Ausnahme die Gewohnheit fest.
 *
 * @param  bool  $sharedHabit  Führt die gefragte Seite dieselbe Sache selbst?
 * @return array{0: User, 1: User, 2: Habit, 3: Appointment, 4: Habit|null}
 */
function promised(bool $situational = false, bool $sharedHabit = false): array
{
    Carbon::setTestNow(Carbon::parse('2026-09-07 05:00'));

    $berkay = User::factory()->create(['name' => 'Berkay']);
    $aylin = User::factory()->create(['name' => 'Aylin']);

    Friendship::factory()->accepted()->create([
        'requester_id' => $berkay->id,
        'addressee_id' => $aylin->id,
    ]);

    $berkay->sleepSchedules()->create([
        'weekday' => 1,
        'wake_time' => '07:00',
        'bedtime' => '23:00',
        'alarm_enabled' => false,
    ]);

    $factory = Habit::factory()->for($berkay)
        ->fromTemplate(HabitTemplate::Fruehstuecken)
        ->withMeasure(30);

    $his = $situational
        ? $factory->create(['trigger_situation' => 'nach dem Aufstehen'])
        : $factory->fixedSchedule('08:00', [1, 2, 3, 4, 5])->create();

    // Vor der Zusage angelegt, nicht danach: `accepted_at` ist nicht
    // ausfüllbar, ein Zurücksetzen liefe ins Leere, und die zweite Zusage
    // fiele in die Berechtigung statt in die Prüfung.
    $hers = $sharedHabit
        ? Habit::factory()->for($aylin)
            ->fromTemplate(HabitTemplate::Fruehstuecken)
            ->fixedSchedule('09:00', [1, 2, 3, 4, 5])
            ->withMeasure(30)
            ->create()
        : null;

    test()->actingAs($berkay->refresh())->post(route('appointments.store', $his), [
        'friend_id' => $aylin->id,
        'scheduled_for' => Carbon::today()->toDateString(),
    ])->assertSessionHasNoErrors();

    $appointment = Appointment::query()->sole();

    test()->actingAs($aylin)->patch(route('appointments.update', $appointment))
        ->assertSessionHasNoErrors();

    return [$berkay, $aylin, $his, $appointment, $hers];
}

test('the asker cannot slide the day away from under a promise', function () {
    [$berkay, , $his] = promised();

    $this->actingAs($berkay)
        ->from(route('calendar.day', Carbon::today()->toDateString()))
        ->post(route('habits.shifts.store', $his), [
            'date' => Carbon::today()->toDateString(),
            'scheduled_time' => '11:00',
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect($his->refresh()->startsAt(Carbon::today())?->format('H:i'))->toBe('08:00');
});

test('dragging it in the grid is refused too, today and for good', function () {
    [$berkay, , $his] = promised();

    foreach (['today', 'always'] as $scope) {
        $this->actingAs($berkay)
            ->from(route('calendar.day', Carbon::today()->toDateString()))
            ->put(route('habits.shifts.move', $his), [
                'date' => Carbon::today()->toDateString(),
                'start_minute' => 660,
                'scope' => $scope,
            ])
            ->assertSessionHasErrors('start_minute');
    }

    expect($his->refresh()->scheduled_time->format('H:i'))->toBe('08:00');
});

test('editing the habit cannot move the promised time either', function () {
    [$berkay, , $his] = promised();

    $this->actingAs($berkay)
        ->from(route('habits.edit', $his))
        ->put(route('habits.update', $his), [
            'template_key' => HabitTemplate::Fruehstuecken->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '11:00',
            'scheduled_days' => [1, 2, 3, 4, 5],
        ])
        ->assertSessionHasErrors();

    expect($his->refresh()->scheduled_time->format('H:i'))->toBe('08:00');
});

test('the invited side cannot move its replaced row either', function () {
    // Aylin führt selbst Frühstücken; ihre Zeile ist die, die an diesem Tag
    // in Berkays Kalender steht. Sie zu verschieben hieße, die Verabredung zu
    // verschieben.
    [, $aylin, , , $hers] = promised(sharedHabit: true);

    $this->actingAs($aylin)
        ->from(route('calendar.day', Carbon::today()->toDateString()))
        ->post(route('habits.shifts.store', $hers), [
            'date' => Carbon::today()->toDateString(),
            'scheduled_time' => '13:00',
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect($hers->refresh()->startsAt(Carbon::today())?->format('H:i'))->toBe('08:00');
});

test('taking back the exception is refused while the promise stands', function () {
    [, $aylin, , , $hers] = promised(sharedHabit: true);

    // Die Ausnahme wegzunehmen spränge zurück auf neun — auch das verschiebt.
    $this->actingAs($aylin)
        ->from(route('calendar.day', Carbon::today()->toDateString()))
        ->delete(route('habits.shifts.destroy', $hers), [
            'date' => Carbon::today()->toDateString(),
        ])
        ->assertSessionHasErrors();

    expect($hers->refresh()->startsAt(Carbon::today())?->format('H:i'))->toBe('08:00');
});

test('a situation is held in place when the sleep plan moves', function () {
    [$berkay, , $his, $appointment] = promised(situational: true);

    expect($appointment->starts_at->format('H:i'))->toBe('07:00');

    // Der Schlafplan lässt sich nicht verriegeln — er betrifft den ganzen Tag,
    // nicht diese eine Gewohnheit. Stattdessen hält eine Ausnahme sie fest:
    // Berkay steht jetzt um zehn auf, das gemeinsame Frühstück bleibt um
    // sieben.
    $berkay->sleepSchedules()->where('weekday', 1)->update(['wake_time' => '10:00']);

    $fresh = $his->fresh();
    $fresh->setRelation('user', $berkay->fresh());

    expect($fresh->dayStartMinute(Carbon::today()))->toBe($appointment->startMinute())
        ->and($fresh->dayStartMinute(Carbon::today()))->toBe(7 * 60);
});

test('saying no gives the day back', function () {
    [$berkay, $aylin, $his, $appointment] = promised(situational: true);

    $this->actingAs($aylin)->delete(route('appointments.destroy', $appointment));

    // Ohne Verabredung gilt wieder der Schlafplan.
    expect($his->dayShifts()->whereDate('shifted_on', Carbon::today())->exists())->toBeFalse();
});

test('and the lock is gone with it', function () {
    [$berkay, $aylin, $his, $appointment] = promised();

    $this->actingAs($aylin)->delete(route('appointments.destroy', $appointment));

    $this->actingAs($berkay)
        ->from(route('calendar.day', Carbon::today()->toDateString()))
        ->post(route('habits.shifts.store', $his), [
            'date' => Carbon::today()->toDateString(),
            'scheduled_time' => '11:00',
        ])
        ->assertSessionHasNoErrors();
});
