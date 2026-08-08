<?php

use App\Models\Habit;
use App\Models\HabitCompletion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Erfüllungen an aufeinanderfolgenden Tagen.
 *
 * Der Unique-Index auf (habit_id, completed_on) lässt denselben Tag nur einmal
 * zu — die Voreinstellung der Factory ist immer heute.
 */
function completions(Habit $habit, int $days): void
{
    foreach (range(0, $days - 1) as $offset) {
        HabitCompletion::factory()
            ->for($habit)
            ->on(Carbon::today()->subDays($offset))
            ->create();
    }
}

test('a habit can be ended and leaves the active list', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('habits.graduation.store', $habit))
        ->assertRedirect();

    expect($habit->refresh()->graduated_at)->not->toBeNull()
        ->and($user->habits()->active()->count())->toBe(0);
});

test('ending a habit keeps every completed day', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();
    completions($habit, 3);

    $this->actingAs($user)->post(route('habits.graduation.store', $habit));

    expect($habit->completions()->count())->toBe(3);
});

test('ending a habit frees a slot for a new one', function () {
    $user = User::factory()->create();
    $habits = Habit::factory()->for($user)->count(Habit::MaxActivePerUser)->create();

    $this->actingAs($user)->post(route('habits.graduation.store', $habits->first()));

    expect($user->habits()->active()->count())->toBe(Habit::MaxActivePerUser - 1);
});

test('ending a habit twice does not move the date', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->graduated()->create();
    $graduatedAt = $habit->graduated_at;

    $this->travel(1)->day();

    $this->actingAs($user)->post(route('habits.graduation.store', $habit));

    expect($habit->refresh()->graduated_at->timestamp)->toBe($graduatedAt->timestamp);
});

test('a habit of another user cannot be ended', function () {
    $habit = Habit::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('habits.graduation.store', $habit))
        ->assertForbidden();

    expect($habit->refresh()->graduated_at)->toBeNull();
});

test('an ended habit can be picked up again with its reminder intact', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->withReminder()->graduated()->create();

    $this->actingAs($user)
        ->delete(route('habits.graduation.destroy', $habit))
        ->assertRedirect();

    expect($habit->refresh()->graduated_at)->toBeNull()
        ->and($habit->reminder_enabled)->toBeTrue();
});

test('picking a habit up again is refused when all slots are taken', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->count(Habit::MaxActivePerUser)->create();
    $habit = Habit::factory()->for($user)->graduated()->create();

    $this->actingAs($user)
        ->delete(route('habits.graduation.destroy', $habit))
        ->assertSessionHasErrors('habit');

    expect($habit->refresh()->graduated_at)->not->toBeNull();
});

test('an ended habit no longer carries a reminder into the page', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->withReminder()->graduated()->create();

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('habitReminders', []));
});

test('the overview lists ended habits with their history', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->graduated()->create(['title' => 'Meditation']);
    completions($habit, 2);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 0)
            ->has('graduatedHabits', 1)
            ->where('graduatedHabits.0.title', 'Meditation')
            ->where('graduatedHabits.0.completionCount', 2));
});

test('a habit can be deleted for good and takes its completions with it', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->graduated()->create();
    completions($habit, 2);

    $this->actingAs($user)
        ->delete(route('habits.destroy', $habit))
        ->assertRedirect();

    expect(Habit::find($habit->id))->toBeNull()
        ->and(HabitCompletion::where('habit_id', $habit->id)->count())->toBe(0);
});

test('a habit of another user cannot be deleted', function () {
    $habit = Habit::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('habits.destroy', $habit))
        ->assertForbidden();

    expect(Habit::find($habit->id))->not->toBeNull();
});
