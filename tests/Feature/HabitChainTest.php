<?php

use App\Enums\HabitTemplate;
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

test('a chained habit starts a short breath after the previous one ends', function () {
    // „Danach" heißt nicht „in derselben Minute" — aber in der Kette reicht
    // das Atemholen: fünf Minuten statt der Viertelstunde, die zwischen zwei
    // unabhängigen Blöcken liegt. Wer „danach" plant, ist schon dabei.
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('17:00')->withMeasure(20)->create([
        'title' => 'Spazieren gehen',
    ]);

    $read = chainedTo($walk, ['title' => 'Lesen']);

    expect($read->startsAt()?->format('H:i'))->toBe('17:25')
        ->and($read->scheduleLabel())->toBe('nach „Spazieren gehen"')
        ->and($read->timeRangeLabel())->toBe('ab 17:25')
        ->and($walk->followerStartsAt()?->format('H:i'))->toBe('17:25');
});

test('the chain adds up over more than one link', function () {
    $user = User::factory()->create();

    $first = Habit::factory()->for($user)->fixedSchedule('07:00')->withMeasure(15)->create();
    $second = chainedTo($first, ['target_amount' => 10, 'target_unit' => MeasureUnit::Minutes]);
    $third = chainedTo($second);

    expect($second->startsAt()?->format('H:i'))->toBe('07:20')
        ->and($second->timeRangeLabel())->toBe('07:20 – 07:30')
        ->and($third->startsAt()?->format('H:i'))->toBe('07:35');
});

test('without a duration the chained habit assumes a quarter hour, plus the breath', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('17:00')->withoutMeasure()->create();

    // Dieselbe Annahme wie im Tag (`DayPlan::AssumedMinutes`) — der Block
    // ohne Dauer belegt eine Viertelstunde, dann kommt die Luft.
    expect(chainedTo($walk)->startsAt()?->format('H:i'))->toBe('17:20');
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
        'schedule_type' => ScheduleType::Chained->value,
        'target_amount' => 20,
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

test('a stranger habit is no anchor', function () {
    $user = User::factory()->create();
    $strangers = Habit::factory()->create();

    $this->actingAs($user)->post(route('habits.store'), [
        'template_key' => HabitTemplate::Lesen->value,
        'target_amount' => 20,
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

/**
 * Eine Kette darf seltener laufen als ihr Vorgänger — nur nie öfter.
 *
 * „Immer wenn die andere läuft" war bisher die einzige Möglichkeit. Wer
 * montags, mittwochs und freitags joggt, will danach aber vielleicht nur
 * mittwochs dehnen; an einem Tag ohne Joggen gäbe es dagegen kein „danach".
 */
test('a chain may run on a subset of its anchor days', function () {
    $user = User::factory()->create();
    $joggen = Habit::factory()->for($user)->fromTemplate(HabitTemplate::Joggen)
        ->fixedSchedule('07:30', [1, 3, 5])->withMeasure(30)->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Dehnen->value,
            'target_amount' => 10,
            'schedule_type' => ScheduleType::Chained->value,
            'chained_to_habit_id' => $joggen->id,
            'scheduled_days' => [3],
        ])
        ->assertSessionHasNoErrors();

    $dehnen = $user->habits()->where('template_key', HabitTemplate::Dehnen->value)->sole();

    expect($dehnen->scheduled_days)->toBe([3])
        ->and($dehnen->activeWeekdays())->toBe([3])
        ->and($dehnen->isScheduledOn(Carbon::today()->next(Carbon::WEDNESDAY)))->toBeTrue()
        // Der Vorgänger läuft montags, die Kette nicht.
        ->and($dehnen->isScheduledOn(Carbon::today()->next(Carbon::MONDAY)))->toBeFalse();
});

test('a chain cannot run on a day its anchor does not', function () {
    $user = User::factory()->create();
    $joggen = Habit::factory()->for($user)->fromTemplate(HabitTemplate::Joggen)
        ->fixedSchedule('07:30', [1, 3, 5])->withMeasure(30)->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Dehnen->value,
            'target_amount' => 10,
            'schedule_type' => ScheduleType::Chained->value,
            'chained_to_habit_id' => $joggen->id,
            'scheduled_days' => [2, 3],
        ])
        ->assertSessionHasErrors([
            'scheduled_days' => '„Joggen gehen" läuft Di nicht. Wähl aus den Tagen, an denen sie stattfindet.',
        ]);

    expect($user->habits()->count())->toBe(1);
});

/**
 * Ohne eigene Tage folgt die Kette ihrem Vorgänger — auch später noch.
 *
 * Die Spalte bleibt dafür leer statt „alle sieben" zu tragen: Ändert der
 * Vorgänger seine Tage, wandert die Kette mit, statt auf einem Stand von
 * damals stehen zu bleiben.
 */
test('a chain without own days follows its anchor', function () {
    $user = User::factory()->create();
    $joggen = Habit::factory()->for($user)->fromTemplate(HabitTemplate::Joggen)
        ->fixedSchedule('07:30', [1, 3, 5])->withMeasure(30)->create();
    $dehnen = Habit::factory()->for($user)->fromTemplate(HabitTemplate::Dehnen)
        ->withMeasure(10)->create([
            'schedule_type' => ScheduleType::Chained,
            'chained_to_habit_id' => $joggen->id,
            'trigger_situation' => null,
            'scheduled_time' => null,
            'scheduled_days' => null,
        ]);

    expect($dehnen->activeWeekdays())->toBe([1, 3, 5]);

    $joggen->update(['scheduled_days' => [2, 4]]);

    expect($dehnen->fresh()->activeWeekdays())->toBe([2, 4]);
});
