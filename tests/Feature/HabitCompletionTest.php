<?php

use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

test('a habit can be ticked off for today', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('habits.completions.store', $habit))
        ->assertRedirect();

    expect($habit->completions()->whereDate('completed_on', Carbon::today())->exists())
        ->toBeTrue();
});

test('ticking off twice does not create a second entry', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)->post(route('habits.completions.store', $habit));
    $this->actingAs($user)->post(route('habits.completions.store', $habit));

    expect($habit->completions()->count())->toBe(1);
});

test('ticking off can be undone', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)->post(route('habits.completions.store', $habit));
    $this->actingAs($user)->delete(route('habits.completions.destroy', $habit));

    expect($habit->completions()->count())->toBe(0);
});

test('undoing today leaves earlier days untouched', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();
    $habit->completions()->create([
        'completed_on' => Carbon::yesterday(),
        'completed_at' => Carbon::yesterday()->setTime(7, 30),
    ]);

    $this->actingAs($user)->post(route('habits.completions.store', $habit));
    $this->actingAs($user)->delete(route('habits.completions.destroy', $habit));

    expect($habit->completions()->count())->toBe(1)
        ->and($habit->completions()->sole()->completed_on->isYesterday())->toBeTrue();
});

test('a habit belonging to someone else cannot be ticked off', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $habit = Habit::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->post(route('habits.completions.store', $habit))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('habits.completions.destroy', $habit))
        ->assertForbidden();

    expect($habit->completions()->count())->toBe(0);
});

test('the daily goal is the number of active habits', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->count(4)->create();
    Habit::factory()->for($user)->graduated()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('todayProgress.total', 4)
            ->where('todayProgress.completed', 0)
            ->where('todayProgress.percentage', 0)
        );
});

test('ticking off is reflected in the daily percentage', function () {
    $user = User::factory()->create();
    $habits = Habit::factory()->for($user)->count(4)->create();

    $this->actingAs($user)->post(route('habits.completions.store', $habits[0]));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('todayProgress.completed', 1)
            ->where('todayProgress.percentage', 25)
        );

    $this->actingAs($user)->post(route('habits.completions.store', $habits[1]));
    $this->actingAs($user)->post(route('habits.completions.store', $habits[2]));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('todayProgress.completed', 3)
            // 3/4 = 75 %, gerundet auf ganze Prozent.
            ->where('todayProgress.percentage', 75)
        );
});

test('a user without habits gets a daily goal of zero instead of a division by zero', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('todayProgress.total', 0)
            ->where('todayProgress.percentage', 0)
        );
});
