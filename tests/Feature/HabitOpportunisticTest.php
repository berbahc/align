<?php

use App\Enums\BehaviorType;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\HabitCompletion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Gewohnheiten ohne Platz im Tag.
 *
 * „Treppe statt Aufzug" hängt an einer Gelegenheit, die auftaucht, wann sie
 * will. Bis hierher landete sie über `UnknownAnchorHour` mittags im Kalender,
 * als wäre sie für 12 Uhr geplant, und wurde gemessen wie eine tägliche
 * Verpflichtung. Beides war erfunden.
 */
function opportunisticHabit(User $user, array $attributes = []): Habit
{
    return Habit::factory()->for($user)->withoutMeasure()->create([
        'title' => 'Treppe statt Aufzug',
        'schedule_type' => ScheduleType::Opportunistic,
        'trigger_situation' => null,
        ...$attributes,
    ]);
}

test('an opportunistic habit can be created without a situation or a time', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('habits.store'), [
        'behavior_type' => BehaviorType::Movement->value,
        'title' => 'Treppe statt Aufzug',
        'schedule_type' => ScheduleType::Opportunistic->value,
    ])->assertSessionHasNoErrors();

    $habit = $user->habits()->sole();

    expect($habit->schedule_type)->toBe(ScheduleType::Opportunistic)
        ->and($habit->trigger_situation)->toBeNull()
        ->and($habit->scheduled_time)->toBeNull()
        ->and($habit->scheduled_days)->toBeNull();
});

test('it names itself instead of falling through to an empty string', function () {
    $habit = opportunisticHabit(User::factory()->create());

    // Ohne eigenen Zweig fiele scheduleLabel() auf `(string) null` — und der
    // leere String stünde wortlos im Kalender, im Verzeichnis und im Prompt.
    expect($habit->scheduleLabel())->toBe('wenn es sich ergibt')
        ->and($habit->scheduleLabel())->not->toBe('');
});

test('it has no place on the day axis but stands below it', function () {
    $user = User::factory()->create();
    opportunisticHabit($user);

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar')
            ->has('blocks', 0)
            ->has('whenever', 1)
            ->where('whenever.0.title', 'Treppe statt Aufzug')
            ->where('whenever.0.anchor', 'wenn es sich ergibt')
            // Ohne Zeitpunkt gibt es keinen besseren Zeitpunkt.
            ->where('whenever.0.adjustable', false)
        );
});

test('it is available on every day, including a saturday', function () {
    $habit = opportunisticHabit(User::factory()->create());
    $saturday = Carbon::parse('2026-08-15');

    expect($habit->isAvailableOn($saturday))->toBeTrue()
        // Verfügbar heißt nicht vorgesehen — daran hängt jede Messung.
        ->and($habit->isScheduledOn($saturday))->toBeFalse()
        ->and($habit->dayAnchorHour())->toBeNull();
});

test('it can be ticked off on the day it comes up', function () {
    $user = User::factory()->create();
    $habit = opportunisticHabit($user);

    $this->actingAs($user)
        ->post(route('habits.completions.store', $habit), [
            'completed_on' => Carbon::today()->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    expect($habit->completions()->count())->toBe(1);
});

/**
 * Der Kern: Wer an keinem Aufzug vorbeikam, hat nichts versäumt.
 */
test('it is never measured as a rate and never counted as missed', function () {
    $user = User::factory()->create();
    $habit = opportunisticHabit($user, ['created_at' => Carbon::today()->subDays(30)]);

    HabitCompletion::factory()->for($habit)->create([
        'completed_on' => Carbon::today()->subDay(),
    ]);

    $habit->load('completions');

    expect($habit->consistencyRate())->toBeNull()
        ->and($habit->recentMisses())->toBe([])
        ->and($habit->scheduledDaysBetween(Carbon::today()->subDays(29), Carbon::today()))->toBe(0);
});

test('the dashboard average is untouched by an opportunistic habit', function () {
    $user = User::factory()->create();

    $planned = Habit::factory()->for($user)->create([
        'created_at' => Carbon::today()->subDays(29),
    ]);
    HabitCompletion::factory()->for($planned)->create([
        'completed_on' => Carbon::today(),
    ]);

    $before = $this->actingAs($user)->get(route('dashboard'))
        ->viewData('page')['props']['consistency'];

    opportunisticHabit($user, ['created_at' => Carbon::today()->subDays(29)]);

    $after = $this->actingAs($user)->get(route('dashboard'))
        ->viewData('page')['props']['consistency'];

    // Ohne den Nullnenner hätte die spontane Gewohnheit 30 Tage Soll
    // hinzugefügt und den Schnitt aller Gewohnheiten heruntergezogen.
    expect($after)->toBe($before);
});

test('the habit list shows a count where there can be no streak', function () {
    $user = User::factory()->create();
    $habit = opportunisticHabit($user, ['created_at' => Carbon::today()->subDays(20)]);

    foreach ([1, 4, 9] as $offset) {
        HabitCompletion::factory()->for($habit)->create([
            'completed_on' => Carbon::today()->subDays($offset),
        ]);
    }

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('habits.0.streak', null)
            ->where('habits.0.recentCount', '3× in 30 Tagen')
            // Ohne Uhrzeit keine Erinnerung — der Schalter bleibt gesperrt.
            ->where('habits.0.canRemind', false)
        );
});

test('there is no better time for something that has no time', function () {
    $user = User::factory()->create();
    $habit = opportunisticHabit($user);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertNotFound();

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), [
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertNotFound();
});

test('a reminder cannot be switched on, not even in bulk', function () {
    $user = User::factory()->create();
    $habit = opportunisticHabit($user);

    $this->actingAs($user)
        ->patch(route('habits.reminder.update', $habit), ['enabled' => true])
        ->assertSessionHasErrors('enabled');

    $this->actingAs($user)
        ->put(route('habits.reminders.update-all'), ['enabled' => true]);

    expect($habit->refresh()->reminder_enabled)->toBeFalse();
});

test('the catalog marks what cannot be planned', function () {
    $plannability = collect(BehaviorType::Movement->suggestions())
        ->pluck('plannable', 'title');

    expect($plannability['Treppe statt Aufzug'])->toBeFalse()
        ->and($plannability['Eine Station früher aussteigen'])->toBeFalse()
        ->and($plannability['Spazieren gehen'])->toBeTrue();
});
