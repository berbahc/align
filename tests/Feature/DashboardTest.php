<?php

use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard lists the active habits of the current user', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create(['title' => 'Morgentraining', 'position' => 0]);
    Habit::factory()->for($user)->create(['title' => '10 Seiten lesen', 'position' => 1]);
    Habit::factory()->for($user)->graduated()->create(['title' => 'Trinken']);
    Habit::factory()->create(['title' => 'Fremde Gewohnheit']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->has('habits', 2)
            ->where('habits.0.title', 'Morgentraining')
            ->where('habits.1.title', '10 Seiten lesen')
        );
});

test('a habit completed today is delivered with its completion time', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();
    $habit->completions()->create([
        'completed_on' => Carbon::today(),
        'completed_at' => Carbon::today()->setTime(7, 30),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.completedAt', '07:30')
        );
});

test('a habit not completed today is delivered as open', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();
    $habit->completions()->create([
        'completed_on' => Carbon::yesterday(),
        'completed_at' => Carbon::yesterday()->setTime(7, 30),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.completedAt', null)
        );
});

test('the consistency rate counts the full 30 day window including today', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();
    $habit->forceFill(['created_at' => Carbon::today()->subDays(60)])->save();

    // Der letzte Tag des Fensters ist die Stelle, an der ein String-Vergleich
    // gegen "Y-m-d" den Datensatz lexikografisch ausschließen würde.
    foreach (range(0, 29) as $daysAgo) {
        $date = Carbon::today()->subDays($daysAgo);
        $habit->completions()->create([
            'completed_on' => $date,
            'completed_at' => $date->copy()->setTime(7, 30),
        ]);
    }

    expect($habit->consistencyRate())->toBe(100);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('consistency', 100));
});

test('a user without habits gets no consistency rate instead of zero percent', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 0)
            ->where('consistency', null)
        );
});
