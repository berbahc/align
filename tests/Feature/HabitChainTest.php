<?php

use App\Enums\BehaviorType;
use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\HabitCompletion;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Das Domino-Prinzip: eine Gewohnheit hängt an einer anderen.
 *
 * Hier zahlt sich die Dauer aus — sie sagt, wann die vorige fertig ist, und
 * damit, wann die nächste anfängt. Alissa im Interview beschreibt genau das:
 * „Wenn ich dann im Bett bin, kann ich es direkt machen."
 */
function chainedTo(Habit $previous, array $attributes = []): Habit
{
    return Habit::factory()->for($previous->user)->withoutMeasure()->create([
        'schedule_type' => ScheduleType::Chained,
        'chained_to_habit_id' => $previous->id,
        'trigger_situation' => null,
        ...$attributes,
    ]);
}

test('a chained habit starts where the previous one ends', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('17:00')->withMeasure(20)->create([
        'title' => 'Spazieren gehen',
    ]);

    $read = chainedTo($walk, ['title' => 'Lesen']);

    expect($read->startsAt()?->format('H:i'))->toBe('17:20')
        ->and($read->scheduleLabel())->toBe('nach „Spazieren gehen"')
        ->and($read->timeRangeLabel())->toBe('ab 17:20');
});

test('the chain adds up over more than one link', function () {
    $user = User::factory()->create();

    $first = Habit::factory()->for($user)->fixedSchedule('07:00')->withMeasure(15)->create();
    $second = chainedTo($first, ['target_amount' => 10, 'target_unit' => MeasureUnit::Minutes]);
    $third = chainedTo($second);

    expect($second->startsAt()?->format('H:i'))->toBe('07:15')
        ->and($second->timeRangeLabel())->toBe('07:15 – 07:25')
        ->and($third->startsAt()?->format('H:i'))->toBe('07:25');
});

test('without a duration the chained habit inherits the plain start', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('17:00')->withoutMeasure()->create();

    // Gleichzeitig anzufangen ist falsch — aber weniger falsch als eine
    // erfundene Länge.
    expect(chainedTo($walk)->startsAt()?->format('H:i'))->toBe('17:00');
});

test('a chained habit runs on the days of the one it hangs on', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('17:00', [1, 3, 5])->create();
    $read = chainedTo($walk);

    $monday = Carbon::parse('2026-08-17');
    $tuesday = Carbon::parse('2026-08-18');

    expect($read->isScheduledOn($monday))->toBeTrue()
        ->and($read->isScheduledOn($tuesday))->toBeFalse()
        // Dieselbe Stunde wie der Vorgänger, damit sie direkt darunter sortiert.
        ->and($read->dayAnchorHour())->toBe(17)
        // Ohne eigene Uhrzeit gibt es nichts zu erinnern.
        ->and($read->canRemind())->toBeFalse();
});

test('a habit cannot hang on itself, not even around a corner', function () {
    $user = User::factory()->create();
    $first = Habit::factory()->for($user)->create(['trigger_situation' => 'nach dem Aufstehen']);
    $second = chainedTo($first);

    $form = [
        'behavior_type' => BehaviorType::Movement->value,
        'title' => $first->title,
        'schedule_type' => ScheduleType::Chained->value,
    ];

    // Direkt auf sich selbst.
    $this->actingAs($user)
        ->put(route('habits.update', $first), [...$form, 'chained_to_habit_id' => $first->id])
        ->assertSessionHasErrors('chained_to_habit_id');

    // Und über das Glied, das schon an ihr hängt.
    $this->actingAs($user)
        ->put(route('habits.update', $first), [...$form, 'chained_to_habit_id' => $second->id])
        ->assertSessionHasErrors('chained_to_habit_id');

    expect($first->refresh()->schedule_type)->toBe(ScheduleType::Dynamic);
});

test('nothing can hang on a habit that has no place in the day itself', function () {
    $user = User::factory()->create();
    $whenever = Habit::factory()->for($user)->create([
        'title' => 'Treppe statt Aufzug',
        'schedule_type' => ScheduleType::Opportunistic,
        'trigger_situation' => null,
    ]);

    $this->actingAs($user)->post(route('habits.store'), [
        'behavior_type' => BehaviorType::Movement->value,
        'title' => 'Danach etwas trinken',
        'schedule_type' => ScheduleType::Chained->value,
        'chained_to_habit_id' => $whenever->id,
    ])->assertSessionHasErrors('chained_to_habit_id');
});

test('a stranger habit is no anchor', function () {
    $user = User::factory()->create();
    $strangers = Habit::factory()->create();

    $this->actingAs($user)->post(route('habits.store'), [
        'behavior_type' => BehaviorType::Movement->value,
        'title' => 'Etwas',
        'schedule_type' => ScheduleType::Chained->value,
        'chained_to_habit_id' => $strangers->id,
    ])->assertSessionHasErrors('chained_to_habit_id');
});

/**
 * Dieselbe Haltung wie bei der abgesagten Verabredung: Die Gewohnheit überlebt
 * das, woran sie hing — samt Verlauf.
 */
test('the successor inherits the anchor when the previous habit is ended', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('17:00', [1, 2, 3])->create();
    $read = chainedTo($walk);

    HabitCompletion::factory()->for($read)->create([
        'completed_on' => Carbon::today()->subDay(),
    ]);

    $this->actingAs($user)->post(route('habits.graduation.store', $walk));

    $read->refresh();

    expect($read->schedule_type)->toBe(ScheduleType::Fixed)
        ->and($read->scheduled_time?->format('H:i'))->toBe('17:00')
        ->and($read->scheduled_days)->toBe([1, 2, 3])
        ->and($read->chained_to_habit_id)->toBeNull()
        ->and($read->completions()->count())->toBe(1);
});

test('the successor inherits the situation when the previous habit is deleted', function () {
    $user = User::factory()->create();
    $morning = Habit::factory()->for($user)->graduated()->create([
        'trigger_situation' => 'nach dem Aufstehen',
    ]);
    $read = chainedTo($morning);

    $this->actingAs($user)->delete(route('habits.destroy', $morning));

    $read->refresh();

    expect($read->schedule_type)->toBe(ScheduleType::Dynamic)
        ->and($read->trigger_situation)->toBe('nach dem Aufstehen')
        ->and($read->chained_to_habit_id)->toBeNull();
});

test('a chain closes up when a middle link falls away', function () {
    $user = User::factory()->create();
    $first = Habit::factory()->for($user)->fixedSchedule('07:00')->create();
    $second = chainedTo($first);
    $third = chainedTo($second);

    $this->actingAs($user)->post(route('habits.graduation.store', $second));

    $third->refresh();

    expect($third->schedule_type)->toBe(ScheduleType::Chained)
        ->and($third->chained_to_habit_id)->toBe($first->id);
});

test('a chain cannot be handed to someone else, only its anchor', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('17:00', [1, 2])->withMeasure(20)->create();
    $read = chainedTo($walk, ['title' => 'Lesen']);

    $blueprint = $read->blueprint();

    // „nach dem Spaziergang" meint meinen Spaziergang — den hat die andere
    // Person nicht.
    expect($blueprint['scheduleType'])->toBe(ScheduleType::Fixed->value)
        ->and($blueprint['scheduledTime'])->toBe('17:00')
        ->and($blueprint['scheduledDays'])->toBe([1, 2])
        ->and($blueprint['title'])->toBe('Lesen');
});

test('the wizard is offered only habits that can carry a chain', function () {
    $user = User::factory()->create();

    Habit::factory()->for($user)->create(['title' => 'Aufstehen']);
    Habit::factory()->for($user)->create([
        'title' => 'Treppe statt Aufzug',
        'schedule_type' => ScheduleType::Opportunistic,
        'trigger_situation' => null,
    ]);
    Habit::factory()->for($user)->graduated()->create(['title' => 'Beendetes']);

    $candidates = collect(
        $this->actingAs($user)->get(route('habits.create'))
            ->viewData('page')['props']['chainCandidates']
    )->pluck('title');

    expect($candidates)->toHaveCount(1)
        ->and($candidates->first())->toContain('Aufstehen');
});

test('the busy slots name the window a fixed habit occupies', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('17:00', [1, 2, 3, 4, 5])
        ->withMeasure(20)->create(['title' => 'Spazieren gehen']);

    $slots = $this->actingAs($user)->get(route('habits.create'))
        ->viewData('page')['props']['busySlots'];

    expect($slots)->toHaveCount(1)
        ->and($slots[0]['title'])->toBe('Spazieren gehen')
        ->and($slots[0]['from'])->toBe('17:00')
        ->and($slots[0]['to'])->toBe('17:20')
        ->and($slots[0]['days'])->toBe([1, 2, 3, 4, 5]);
});
