<?php

use App\Enums\BehaviorType;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

test('a habit can be anchored to a fixed time on chosen weekdays', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'title' => '10 Seiten lesen',
            'behavior_type' => BehaviorType::Learning->value,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '17:00',
            'scheduled_days' => [1, 2, 3, 4, 5],
        ])
        ->assertRedirect(route('dashboard'));

    $habit = $user->habits()->sole();

    expect($habit->schedule_type)->toBe(ScheduleType::Fixed)
        ->and($habit->scheduled_time->format('H:i'))->toBe('17:00')
        ->and($habit->scheduled_days)->toBe([1, 2, 3, 4, 5])
        // Die Situation gehört zum anderen Zweig und darf nicht stehenbleiben.
        ->and($habit->trigger_situation)->toBeNull();
});

test('the situation stays the default when no schedule type is sent', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('habits.store'), [
        'title' => '10 Seiten lesen',
        'behavior_type' => BehaviorType::Learning->value,
        'trigger_situation' => 'vor dem Schlafengehen',
    ])->assertRedirect(route('dashboard'));

    expect($user->habits()->sole())
        ->schedule_type->toBe(ScheduleType::Dynamic)
        ->trigger_situation->toBe('vor dem Schlafengehen')
        ->scheduled_time->toBeNull();
});

test('a fixed habit without weekdays is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'title' => '10 Seiten lesen',
            'behavior_type' => BehaviorType::Learning->value,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '17:00',
        ])
        ->assertSessionHasErrors('scheduled_days');

    expect($user->habits()->count())->toBe(0);
});

test('a dynamic habit without a situation is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'title' => '10 Seiten lesen',
            'behavior_type' => BehaviorType::Learning->value,
            'schedule_type' => ScheduleType::Dynamic->value,
        ])
        ->assertSessionHasErrors('trigger_situation');
});

test('a fixed habit is only scheduled on its own weekdays', function () {
    // 2026-08-03 ist ein Montag, 2026-08-08 ein Samstag.
    $habit = Habit::factory()->fixedSchedule(days: [1, 2, 3, 4, 5])->make();

    expect($habit->isScheduledOn(Carbon::parse('2026-08-03')))->toBeTrue()
        ->and($habit->isScheduledOn(Carbon::parse('2026-08-08')))->toBeFalse();
});

test('a dynamic habit is scheduled on every day', function () {
    $habit = Habit::factory()->make();

    expect($habit->isScheduledOn(Carbon::parse('2026-08-03')))->toBeTrue()
        ->and($habit->isScheduledOn(Carbon::parse('2026-08-08')))->toBeTrue();
});

test('only fixed habits can carry a reminder', function () {
    expect(Habit::factory()->fixedSchedule()->make()->canRemind())->toBeTrue()
        ->and(Habit::factory()->make()->canRemind())->toBeFalse();
});

test('the schedule label folds consecutive weekdays into a span', function (array $days, string $expected) {
    $habit = Habit::factory()->fixedSchedule('17:00', $days)->make();

    expect($habit->scheduleLabel())->toBe("17:00 · {$expected}");
})->with([
    'weekdays fold' => [[1, 2, 3, 4, 5], 'Mo–Fr'],
    'all seven read as daily' => [[1, 2, 3, 4, 5, 6, 7], 'täglich'],
    // Zwei aufeinanderfolgende Tage bleiben eine Aufzählung — „Mo–Di" wäre
    // länger als „Mo, Di" und läse sich als Spanne über nichts.
    'two in a row stay listed' => [[1, 2], 'Mo, Di'],
    'gaps stay listed' => [[1, 3, 5], 'Mo, Mi, Fr'],
    'a span next to a single day' => [[1, 2, 3, 6], 'Mo–Mi, Sa'],
    'a single day' => [[7], 'So'],
]);

test('a dynamic habit shows its situation as the schedule label', function () {
    $habit = Habit::factory()->make(['trigger_situation' => 'nach dem Aufstehen']);

    expect($habit->scheduleLabel())->toBe('nach dem Aufstehen');
});

test('the wizard receives both ways of anchoring a habit', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('habits.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('habits/create')
            ->has('scheduleTypes', 2)
            ->where('scheduleTypes.0.value', ScheduleType::Dynamic->value)
        );
});
