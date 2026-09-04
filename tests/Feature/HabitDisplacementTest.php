<?php

use App\Enums\CourseKind;
use App\Enums\HabitTemplate;
use App\Enums\ScheduleType;
use App\Models\Course;
use App\Models\Habit;
use App\Models\Semester;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\Timetable;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Der Semesterwechsel darf die Routine nicht kosten.
 *
 * Ein neuer Kurs, der auf einer Gewohnheit landet, wird eingetragen — und die
 * Gewohnheit wird geparkt: Sie verliert ihren Platz im Tag, nicht sich selbst.
 * Diese Tests prüfen den geparkten Zustand an jeder Stelle, an der er wirkt —
 * und vor allem dort, wo er vergessen werden könnte.
 */
function parkedMonday(): Carbon
{
    return Carbon::today()->next(Carbon::MONDAY);
}

/** Ein Student mit Semester und einer Gewohnheit um zehn — und dann kommt Mathe. */
function studentWhoseHabitGetsCovered(): array
{
    $user = User::factory()->create();
    Semester::factory()->for($user)->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('10:15', [1])->withMeasure(30)
        ->create(['title' => 'Vorlesung nachbereiten', 'reminder_enabled' => true]);

    test()->actingAs($user)->post(route('calendar.semester.courses.store'), [
        'title' => 'Mathe 1',
        'kind' => CourseKind::Vorlesung->value,
        'weekday' => 1,
        'starts_at' => '10:00',
        'ends_at' => '11:30',
    ])->assertSessionHasNoErrors();

    return [$user, $habit->fresh()];
}

function occupiedTitles(User $user, Carbon $date): array
{
    $habits = $user->habits()->active()->with('chainedTo.chainedTo')->get();
    $habits->each(fn (Habit $habit) => $habit->setRelation('user', $user));

    return array_column(DayPlan::forDate(
        $habits->filter(fn (Habit $habit): bool => $habit->isScheduledOn($date))->values(),
        $date,
        $user->sleepWindows(),
        Timetable::for($user)->blocksOn($date),
    )->occupied(), 'title');
}

test('a parked habit keeps its time as a memory but occupies nothing', function () {
    [$user, $habit] = studentWhoseHabitGetsCovered();

    expect($habit->isDisplaced())->toBeTrue()
        ->and($habit->scheduled_time->format('H:i'))->toBe('10:15')
        ->and($habit->dayStartMinute(parkedMonday()))->toBeNull()
        ->and(occupiedTitles($user, parkedMonday()))->toBe(['Mathe 1']);
});

test('a parked habit shows in the day without a place', function () {
    [$user, $habit] = studentWhoseHabitGetsCovered();

    $this->actingAs($user)
        ->get(route('calendar.day', ['date' => parkedMonday()->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.id', $habit->id)
            ->where('blocks.0.startMinute', null)
            ->where('blocks.0.anchor', 'braucht einen neuen Platz · lief bisher 10:15')
            ->etc());
});

test('a parked habit no longer rings', function () {
    [$user, $habit] = studentWhoseHabitGetsCovered();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('habitReminders', []));

    expect($habit->canRemind())->toBeFalse();
});

test('a parked habit is grouped as needing a place, not as due today', function () {
    Carbon::setTestNow(parkedMonday()->setHour(8));
    [$user, $habit] = studentWhoseHabitGetsCovered();

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.id', $habit->id)
            ->where('habits.0.group', 'displaced')
            ->etc());

    Carbon::setTestNow();
});

test('a chained follower of a parked anchor loses its place too, without its own mark', function () {
    $user = User::factory()->create();
    Semester::factory()->for($user)->create();
    $anchor = Habit::factory()->for($user)->fixedSchedule('10:15', [1])->withMeasure(30)->create(['title' => 'Anker']);
    $follower = Habit::factory()->for($user)->withMeasure(20)->create([
        'title' => 'Dehnen',
        'schedule_type' => ScheduleType::Chained,
        'trigger_situation' => null,
        'chained_to_habit_id' => $anchor->id,
    ]);

    $this->actingAs($user)->post(route('calendar.semester.courses.store'), [
        'title' => 'Mathe 1', 'kind' => CourseKind::Vorlesung->value,
        'weekday' => 1, 'starts_at' => '10:00', 'ends_at' => '11:30',
    ])->assertSessionHasNoErrors();

    $follower = $follower->fresh();

    expect($anchor->fresh()->displaced_at)->not->toBeNull()
        ->and($follower->displaced_at)->toBeNull()
        ->and($follower->isDisplaced())->toBeTrue()
        ->and($follower->dayStartMinute(parkedMonday()))->toBeNull()
        ->and($follower->scheduleLabel())->toBe('braucht einen neuen Platz · lief bisher 10:15');
});

test('two habits under one course are both parked', function () {
    $user = User::factory()->create();
    Semester::factory()->for($user)->create();
    $first = Habit::factory()->for($user)->fixedSchedule('10:00', [1])->withMeasure(30)->create();
    $second = Habit::factory()->for($user)->fixedSchedule('11:00', [1])->withMeasure(20)->create();

    $this->actingAs($user)->post(route('calendar.semester.courses.store'), [
        'title' => 'Mathe 1', 'kind' => CourseKind::Vorlesung->value,
        'weekday' => 1, 'starts_at' => '10:00', 'ends_at' => '11:30',
    ])->assertSessionHasNoErrors();

    expect($first->fresh()->displaced_at)->not->toBeNull()
        ->and($second->fresh()->displaced_at)->not->toBeNull();
});

test('deleting the course gives the habit its place back — if it is still free', function () {
    [$user, $habit] = studentWhoseHabitGetsCovered();

    $this->actingAs($user)->delete(route('calendar.semester.courses.destroy', Course::sole()));

    expect($habit->fresh()->displaced_at)->toBeNull()
        ->and(occupiedTitles($user, parkedMonday()))->toBe(['Vorlesung nachbereiten']);
});

test('deleting the course leaves the habit parked when something else took its place', function () {
    [$user, $habit] = studentWhoseHabitGetsCovered();

    // Inzwischen liegt eine andere Gewohnheit dort.
    Habit::factory()->for($user)->fixedSchedule('10:15', [1])->withMeasure(30)->create(['title' => 'Lesen']);

    $this->actingAs($user)->delete(route('calendar.semester.courses.destroy', Course::sole()));

    expect($habit->fresh()->displaced_at)->not->toBeNull();
});

test('a single cancelled date does not bring the habit back', function () {
    [$user, $habit] = studentWhoseHabitGetsCovered();

    $this->actingAs($user)->post(route('calendar.semester.courses.exceptions.store', Course::sole()), [
        'on_date' => parkedMonday()->toDateString(),
    ])->assertSessionHasNoErrors();

    // Die Woche gilt weiter — ein Ausfall ist kein Umzug.
    expect($habit->fresh()->displaced_at)->not->toBeNull();
});

test('a parked habit takes its place again when it is given a new time by hand', function () {
    [$user, $habit] = studentWhoseHabitGetsCovered();

    $this->actingAs($user)->put(route('habits.update', $habit), [
        'target_amount' => 30,
        'schedule_type' => ScheduleType::Fixed->value,
        'scheduled_time' => '14:00',
        'scheduled_days' => [1],
    ])->assertSessionHasNoErrors();

    expect($habit->fresh()->displaced_at)->toBeNull()
        ->and(occupiedTitles($user, parkedMonday()))->toBe(['Mathe 1', 'Vorlesung nachbereiten']);
});

test('the day order leaves parked habits alone', function () {
    [$user, $habit] = studentWhoseHabitGetsCovered();
    $other = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

    $this->actingAs($user)->post(route('calendar.order.store'), [
        'date' => parkedMonday()->toDateString(),
        'order' => [
            ['id' => $habit->id, 'time' => '16:00'],
            ['id' => $other->id, 'time' => '17:00'],
        ],
    ]);

    // Die geparkte stand nicht zur Wahl — sie bleibt, wie sie war.
    expect($habit->fresh()->displaced_at)->not->toBeNull()
        ->and($habit->fresh()->scheduled_time->format('H:i'))->toBe('10:15');
});

test('graduating a parked anchor hands the follower a parked time, not a live one', function () {
    $user = User::factory()->create();
    Semester::factory()->for($user)->create();
    $anchor = Habit::factory()->for($user)->fixedSchedule('10:15', [1])->withMeasure(30)->create();
    $follower = Habit::factory()->for($user)->withMeasure(20)->create([
        'schedule_type' => ScheduleType::Chained, 'trigger_situation' => null,
        'chained_to_habit_id' => $anchor->id,
    ]);

    $this->actingAs($user)->post(route('calendar.semester.courses.store'), [
        'title' => 'Mathe 1', 'kind' => CourseKind::Vorlesung->value,
        'weekday' => 1, 'starts_at' => '10:00', 'ends_at' => '11:30',
    ]);

    $this->actingAs($user)->post(route('habits.graduation.store', $anchor));

    $follower = $follower->fresh();

    expect($follower->scheduled_time->format('H:i'))->toBe('10:15')
        ->and($follower->displaced_at)->not->toBeNull()
        ->and(occupiedTitles($user, parkedMonday()))->toBe(['Mathe 1']);
});

test('the semester page lists what lost its place', function () {
    [$user, $habit] = studentWhoseHabitGetsCovered();

    $this->actingAs($user)
        ->get(route('calendar.semester'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('displaced.0.id', $habit->id)
            ->where('displaced.0.previousTime', '10:15')
            ->etc());
});

test('only the words that name a time of day carry a band', function () {
    $banded = array_filter(HabitTemplate::cases(), fn (HabitTemplate $template): bool => $template->dayBand() !== null);

    expect(array_values(array_map(fn (HabitTemplate $template): string => $template->value, $banded)))
        ->toBe(['uni-tag-planen', 'fruehstuecken', 'mittagessen', 'abendessen', 'offline-abend']);

    foreach ($banded as $template) {
        $band = $template->dayBand();
        expect($band['from'])->toBeLessThan($band['to'])
            ->and($band['from'])->toBeGreaterThanOrEqual(0)
            ->and($band['to'])->toBeLessThanOrEqual(1440);
    }

    expect(HabitTemplate::Mittagessen->dayBand())->toBe(['from' => 660, 'to' => 900])
        ->and(Habit::factory()->make(['template_key' => null])->dayBand())->toBeNull();
});
