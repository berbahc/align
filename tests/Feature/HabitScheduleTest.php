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

test('the wizard receives every way of anchoring a habit', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('habits.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('habits/create')
            // Situation, feste Uhrzeit, Anschluss an eine andere — und die
            // vierte Form, die gar keinen Platz im Tag hat.
            ->has('scheduleTypes', 4)
            // Die Situation steht vorn: sie ist die Empfehlung, nicht nur eine
            // von vier gleichrangigen Optionen (time-blocking.md).
            ->where('scheduleTypes.0.value', ScheduleType::Dynamic->value)
            ->where('scheduleTypes.3.value', ScheduleType::Opportunistic->value)
            ->has('scheduleTypes.0.description')
        );
});

test('creating a habit that is not due today says when it will be', function () {
    // Ein Samstag: die Mo–Fr-Gewohnheit steht heute nicht in der Tagesliste.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $this->actingAs(User::factory()->create())
        ->post(route('habits.store'), [
            'title' => 'Vorlesung nachbereiten',
            'behavior_type' => BehaviorType::Learning->value,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '17:00',
            'scheduled_days' => [1, 2, 3, 4, 5],
        ])
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('habitCreated.title', 'Vorlesung nachbereiten')
        ->assertInertiaFlash('habitCreated.when', 'am Montag um 17:00')
        ->assertInertiaFlash('habitCreated.scheduledToday', false);
});

test('a habit due today is confirmed as due today', function () {
    // Ein Montag.
    Carbon::setTestNow(Carbon::parse('2026-08-03'));

    $this->actingAs(User::factory()->create())
        ->post(route('habits.store'), [
            'title' => 'Vorlesung nachbereiten',
            'behavior_type' => BehaviorType::Learning->value,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '17:00',
            'scheduled_days' => [1, 2, 3, 4, 5],
        ])
        ->assertInertiaFlash('habitCreated.when', 'heute um 17:00')
        ->assertInertiaFlash('habitCreated.scheduledToday', true);
});

test('the day after today is named as tomorrow', function () {
    // Ein Sonntag — der nächste Mo–Fr-Termin ist der Folgetag.
    Carbon::setTestNow(Carbon::parse('2026-08-09'));

    $this->actingAs(User::factory()->create())
        ->post(route('habits.store'), [
            'title' => 'Vorlesung nachbereiten',
            'behavior_type' => BehaviorType::Learning->value,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '17:00',
            'scheduled_days' => [1, 2, 3, 4, 5],
        ])
        ->assertInertiaFlash('habitCreated.when', 'morgen um 17:00');
});

test('a situational habit counts from today', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('habits.store'), [
            'title' => '10 Minuten meditieren',
            'behavior_type' => BehaviorType::Other->value,
            'schedule_type' => ScheduleType::Dynamic->value,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertInertiaFlash('habitCreated.when', 'ab heute')
        ->assertInertiaFlash('habitCreated.scheduledToday', true);
});

test('the next occurrence of a fixed habit skips the days it is not planned for', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $habit = Habit::factory()->fixedSchedule(days: [1, 2, 3, 4, 5])->create();

    expect($habit->nextOccurrence()->toDateString())->toBe('2026-08-10');
});

test('a situational habit is due every day', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    expect(Habit::factory()->create()->nextOccurrence()->isToday())->toBeTrue();
});

test('the habits page lists what is due first, then the following days', function () {
    // Ein Samstag: heute steht nur die situative Gewohnheit an, die anderen
    // liegen am Montag und am Mittwoch.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('09:00', [3])->create([
        'title' => 'Am Mittwoch',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->fixedSchedule('09:00', [1])->create([
        'title' => 'Am Montag',
        'position' => 1,
    ]);
    Habit::factory()->for($user)->create([
        'title' => 'Heute',
        'trigger_situation' => 'nach dem Aufstehen',
        'position' => 2,
    ]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.title', 'Heute')
            ->where('habits.1.title', 'Am Montag')
            ->where('habits.2.title', 'Am Mittwoch')
            ->where('habits.0.nextOccurrence', 'heute')
            ->where('habits.1.nextOccurrence', 'am Montag')
            ->where('habits.2.nextOccurrence', 'am Mittwoch')
        );
});

test('within the same day the habits page reads from morning to evening', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-03'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('19:00', [1, 2, 3, 4, 5])->create([
        'title' => 'Abends',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->fixedSchedule('06:30', [1, 2, 3, 4, 5])->create([
        'title' => 'Früh',
        'position' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.title', 'Früh')
            ->where('habits.1.title', 'Abends')
        );
});

test('the habits page marks which habits belong in the today block', function () {
    // Ein Samstag: die Mo–Fr-Gewohnheit steht heute nicht an.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->create([
        'title' => 'Heute',
        'trigger_situation' => 'nach dem Aufstehen',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->fixedSchedule('09:00', [1, 2, 3, 4, 5])->create([
        'title' => 'Später',
        'position' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.dueToday', true)
            ->where('habits.1.dueToday', false)
        );
});

test('tomorrow is named as such instead of by its weekday', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('09:00', [7])->create();

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.nextOccurrence', 'morgen'));
});

test('a habit without a chosen weekday has no next occurrence and sorts last', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    // Über die Validierung ginge das nicht — im Bestand kann es die Zeile
    // trotzdem geben, und sie darf die Liste nicht anführen.
    Habit::factory()->for($user)->fixedSchedule('06:00', [])->create([
        'title' => 'Ohne Tag',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->create([
        'title' => 'Heute',
        'trigger_situation' => 'vor dem Schlafengehen',
        'position' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.title', 'Heute')
            ->where('habits.1.title', 'Ohne Tag')
            ->where('habits.1.nextOccurrence', null)
        );
});

test('the overview carries the limit so the interface never hardcodes it', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('maxActive', Habit::MaxActivePerUser));

    $this->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('maxActive', Habit::MaxActivePerUser));
});
