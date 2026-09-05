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
    // Gleicher Anker für beide: die Tagesliste sortiert nach Tageszeit, hier
    // soll aber geprüft werden, wer überhaupt in ihr steht.
    Habit::factory()->for($user)->create(['title' => 'Morgentraining', 'trigger_situation' => 'nach dem Aufstehen', 'position' => 0]);
    Habit::factory()->for($user)->create(['title' => '10 Seiten lesen', 'trigger_situation' => 'nach dem Aufstehen', 'position' => 1]);
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

test('the daily list runs from morning to evening, not by creation order', function () {
    // Ein Montag, damit auch die Mo–Fr-Gewohnheit heute ansteht.
    Carbon::setTestNow(Carbon::parse('2026-08-03'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->create([
        'title' => 'Abendritual',
        'trigger_situation' => 'vor dem Schlafengehen',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->fixedSchedule('07:30', [1, 2, 3, 4, 5])->create([
        'title' => 'Morgentraining',
        'position' => 1,
    ]);
    Habit::factory()->for($user)->create([
        'title' => 'Mittagspause',
        'trigger_situation' => 'nach dem Mittagessen',
        'position' => 2,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.title', 'Morgentraining')
            ->where('habits.1.title', 'Mittagspause')
            ->where('habits.2.title', 'Abendritual')
        );
});

test('habits anchored to the same hour keep their own order', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-03'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->create([
        'title' => 'Zuerst angelegt',
        'trigger_situation' => 'nach dem Aufstehen',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->create([
        'title' => 'Danach angelegt',
        'trigger_situation' => 'nach dem Aufstehen',
        'position' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.title', 'Zuerst angelegt')
            ->where('habits.1.title', 'Danach angelegt')
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

test('an empty day is told apart from an empty list', function () {
    // Ein Samstag: die Mo–Fr-Gewohnheit steht heute nicht an, es gibt sie aber.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5])->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 0)
            ->where('activeCount', 1)
        );
});

test('a user without habits is reported as empty on both counts', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->graduated()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 0)
            ->where('activeCount', 0)
        );
});

test('a habit is hidden on a day it is not scheduled for', function () {
    // Ein Samstag — die Mo–Fr-Gewohnheit steht heute nicht an.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5])->create(['title' => 'Lesen']);
    Habit::factory()->for($user)->create(['title' => 'Trinken']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 1)
            ->where('habits.0.title', 'Trinken')
            // Das Tagesziel zählt nur, was heute vorgesehen ist — sonst wäre
            // der Tag von vornherein unerfüllbar.
            ->where('todayProgress.total', 1)
        );
});

test('a weekday habit reaches 100 percent without being punished for the weekend', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5])->create();
    $habit->forceFill(['created_at' => Carbon::today()->subDays(60)])->save();

    // Jeden vorgesehenen Tag der letzten 30 erfüllt — und keinen weiteren.
    foreach (range(0, 29) as $daysAgo) {
        $date = Carbon::today()->subDays($daysAgo);

        if (! $habit->isScheduledOn($date)) {
            continue;
        }

        $habit->completions()->create([
            'completed_on' => $date,
            'completed_at' => $date->copy()->setTime(17, 0),
        ]);
    }

    expect($habit->consistencyRate())->toBe(100);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('consistency', 100));
});

test('the schedule label carries the time and days of a fixed habit', function () {
    // Ein Montag, damit die Gewohnheit in der Tagesliste auftaucht.
    Carbon::setTestNow(Carbon::parse('2026-08-03'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('07:30', [1, 2, 3, 4, 5])->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.scheduleLabel', '07:30 · Mo–Fr')
            // Dieselbe Auskunft getrennt: Die Uhr steht auf der Übersicht in
            // einer eigenen Spalte, die Wiederholung in der Nebenzeile. Ohne
            // die Trennung lief beides in derselben Kette aus Punkten mit und
            // zwei gleichnamige Gewohnheiten am selben Tag waren nicht
            // auseinanderzuhalten.
            ->where('habits.0.timeLabel', '07:30')
            ->where('habits.0.repeatLabel', 'Mo–Fr')
        );
});

test('a habit anchored to a situation leaves the clock column empty', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-03'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->create(['trigger_situation' => 'nach dem Aufstehen']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Die abgeleitete Stunde gehört nicht in die Spalte: Sie wäre eine
            // Festlegung, die niemand getroffen hat. Der Zeitpunkt steht
            // stattdessen in der Nebenzeile.
            ->where('habits.0.timeLabel', null)
            ->where('habits.0.repeatLabel', 'nach dem Aufstehen')
        );
});
