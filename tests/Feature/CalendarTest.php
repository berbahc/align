<?php

use App\Enums\BehaviorType;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

test('without a date the calendar shows today', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('calendar')
            ->where('date', Carbon::today()->toDateString())
            ->where('isToday', true)
        );
});

test('a date shows that day instead', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create();

    $yesterday = Carbon::yesterday()->toDateString();

    $this->actingAs($user)
        ->get(route('calendar', ['date' => $yesterday]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('date', $yesterday)
            ->where('isToday', false)
        );
});

test('a nonsense date is rejected instead of guessed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('calendar', ['date' => 'irgendwann']))
        ->assertSessionHasErrors('date');
});

test('a Monday to Friday habit is absent on Saturday', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5])->create();

    // Ein fester Samstag, damit der Test nicht vom Wochentag des Laufs abhängt.
    $saturday = Carbon::today()->startOfWeek()->addDays(5);

    $this->actingAs($user)
        ->get(route('calendar', ['date' => $saturday->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('blocks', 0));
});

test('a habit does not appear on days before it existed', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create(['created_at' => Carbon::today()]);

    $this->actingAs($user)
        ->get(route('calendar', ['date' => Carbon::today()->subDays(3)->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('blocks', 0));
});

/**
 * Der Tag, an dem sie lief, hat stattgefunden — ihn nachträglich zu leeren wäre
 * eine Geschichtsfälschung.
 */
test('a graduated habit stays visible on the days it was still running', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create([
        'title' => 'Morgentraining',
        'created_at' => Carbon::today()->subDays(10),
        'graduated_at' => Carbon::today()->subDays(2),
    ]);

    $this->actingAs($user)
        ->get(route('calendar', ['date' => Carbon::today()->subDays(5)->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('blocks', 1)
            ->where('blocks.0.title', 'Morgentraining')
            ->where('blocks.0.graduated', true)
        );

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('blocks', 0));
});

test('the blocks are ordered along the day, not by creation', function () {
    $user = User::factory()->create();

    // Absichtlich in umgekehrter Tagesreihenfolge angelegt.
    Habit::factory()->for($user)->create([
        'title' => 'Lesen',
        'trigger_situation' => 'vor dem Schlafengehen',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->fixedSchedule('17:00', [1, 2, 3, 4, 5, 6, 7])->create([
        'title' => 'Laufen',
        'position' => 1,
    ]);
    Habit::factory()->for($user)->create([
        'title' => 'Wasser trinken',
        'trigger_situation' => 'nach dem Aufstehen',
        'position' => 2,
    ]);

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.title', 'Wasser trinken')
            ->where('blocks.1.title', 'Laufen')
            ->where('blocks.2.title', 'Lesen')
            ->etc()
        );
});

test('a habit with a made up situation lands in the middle of the day', function () {
    $habit = Habit::factory()->make(['trigger_situation' => 'wenn ich aus der Bib komme']);

    expect($habit->dayAnchorHour())->toBe(Habit::UnknownAnchorHour);
});

test('a completed day carries its tick', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create([
        'created_at' => Carbon::today()->subDays(3),
    ]);
    $habit->completions()->create([
        'completed_on' => Carbon::yesterday(),
        'completed_at' => Carbon::yesterday()->setTime(20, 0),
    ]);

    $this->actingAs($user)
        ->get(route('calendar', ['date' => Carbon::yesterday()->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('blocks.0.completed', true));

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('blocks.0.completed', false));
});

test('backdating is offered inside the window and withdrawn outside it', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create(['created_at' => Carbon::today()->subDays(30)]);

    $this->actingAs($user);

    $inside = Carbon::today()->subDays(Habit::WeekOverviewDays - 1);
    $outside = $inside->copy()->subDay();

    $this->get(route('calendar', ['date' => $inside->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canComplete', true));

    $this->get(route('calendar', ['date' => $outside->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canComplete', false));
});

test('a future day cannot be ticked off', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('calendar', ['date' => Carbon::tomorrow()->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canComplete', false));
});

/**
 * Ein Pfeil, der in leere Tage führt, verspricht etwas, das er nicht hält.
 */
test('the way back ends at the first habit', function () {
    $user = User::factory()->create();
    $start = Carbon::today()->subDays(3);
    Habit::factory()->for($user)->create(['created_at' => $start]);

    $this->actingAs($user);

    $this->get(route('calendar', ['date' => $start->copy()->addDay()->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('previousDate', $start->toDateString())
        );

    $this->get(route('calendar', ['date' => $start->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('previousDate', null));
});

test('a user without habits gets no way back at all', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('previousDate', null)
            ->has('blocks', 0)
        );
});

test('the calendar shows only the habits of the signed in user', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create(['title' => 'Eigene Gewohnheit']);
    Habit::factory()->create(['title' => 'Fremde Gewohnheit']);

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('blocks', 1)
            ->where('blocks.0.title', 'Eigene Gewohnheit')
            ->etc()
        );
});

test('the smallest step travels to the calendar', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create([
        'behavior_type' => BehaviorType::Movement,
        'smallest_step' => 'Zieh die Laufschuhe an.',
    ]);

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.smallestStep', 'Zieh die Laufschuhe an.')
            ->where('blocks.0.behaviorType', 'movement')
            ->etc()
        );
});

test('ticking off from the calendar lands on the shown day', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create(['created_at' => Carbon::today()->subDays(10)]);

    $twoDaysAgo = Carbon::today()->subDays(2);

    $this->actingAs($user)
        ->post(route('habits.completions.store', $habit), [
            'completed_on' => $twoDaysAgo->toDateString(),
        ])
        ->assertRedirect();

    expect($habit->completions()->sole()->completed_on->isSameDay($twoDaysAgo))->toBeTrue();
});
