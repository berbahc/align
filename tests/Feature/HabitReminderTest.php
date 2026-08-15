<?php

use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

test('a reminder can be switched on for a single fixed habit', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule()->create();

    $this->actingAs($user)
        ->patch(route('habits.reminder.update', $habit), ['enabled' => true]);

    expect($habit->refresh()->reminder_enabled)->toBeTrue();
});

test('a reminder can be switched off again', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->withReminder()->create();

    $this->actingAs($user)
        ->patch(route('habits.reminder.update', $habit), ['enabled' => false]);

    expect($habit->refresh()->reminder_enabled)->toBeFalse();
});

test('a dynamic habit cannot carry a reminder', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('habits.reminder.update', $habit), ['enabled' => true])
        ->assertSessionHasErrors('enabled');

    expect($habit->refresh()->reminder_enabled)->toBeFalse();
});

test('a habit of another user stays untouched', function () {
    $habit = Habit::factory()->fixedSchedule()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('habits.reminder.update', $habit), ['enabled' => true])
        ->assertForbidden();

    expect($habit->refresh()->reminder_enabled)->toBeFalse();
});

test('the bulk switch reaches every fixed habit at once', function () {
    $user = User::factory()->create();
    $morning = Habit::factory()->for($user)->fixedSchedule('07:00')->create();
    $evening = Habit::factory()->for($user)->fixedSchedule('20:00')->create();

    $this->actingAs($user)
        ->put(route('habits.reminders.update-all'), ['enabled' => true]);

    expect($morning->refresh()->reminder_enabled)->toBeTrue()
        ->and($evening->refresh()->reminder_enabled)->toBeTrue();
});

test('the bulk switch passes over habits without a fixed time', function () {
    $user = User::factory()->create();
    $situational = Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('habits.reminders.update-all'), ['enabled' => true]);

    // Übergangen, nicht abgewiesen: der Sammelschalter meint „alle, für die
    // das gilt" — eine Situation kann sich nicht von selbst melden.
    expect($situational->refresh()->reminder_enabled)->toBeFalse();
});

test('the bulk switch leaves other users alone', function () {
    $stranger = Habit::factory()->fixedSchedule()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('habits.reminders.update-all'), ['enabled' => true]);

    expect($stranger->refresh()->reminder_enabled)->toBeFalse();
});

test('the habits page reports which habits can be reminded', function () {
    // Ein Montag: beide Gewohnheiten stehen heute an, damit die Reihenfolge
    // der Liste an der Tageszeit hängt und nicht am Wochentag.
    Carbon::setTestNow(Carbon::parse('2026-08-03'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule()->create(['title' => 'Lesen', 'position' => 0]);
    Habit::factory()->for($user)->create(['title' => 'Spazieren', 'trigger_situation' => 'vor dem Schlafengehen', 'position' => 1]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('habits/index')
            ->has('habits', 2)
            ->where('habits.0.canRemind', true)
            ->where('habits.1.canRemind', false)
            ->where('habits.0.scheduleLabel', '17:00 · Mo–Fr')
        );
});

test('active reminders are shared with every page so they fire anywhere', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->withReminder()->create(['title' => 'Lesen']);
    Habit::factory()->for($user)->fixedSchedule()->create(['title' => 'Ohne Erinnerung']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habitReminders', 1)
            ->where('habitReminders.0.title', 'Lesen')
            ->where('habitReminders.0.scheduledTime', '17:00')
            ->where('habitReminders.0.completedToday', false)
        );
});
