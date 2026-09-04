<?php

use App\Ai\Agents\SuggestBetterAnchor;
use App\Enums\HabitTemplate;
use App\Enums\ScheduleType;
use App\Models\Course;
use App\Models\Habit;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Eine Gewohnheit darf nicht dort liegen, wo schon etwas liegt.
 *
 * Bis hierher galt das nur beim Ziehen im Raster: Wer einen Block auf eine
 * Vorlesung zog, bekam eine Absage — wer dieselbe Uhrzeit ins Formular tippte,
 * kam durch. Der Plan widersprach sich damit an genau der Stelle, an der die
 * App ihr Versprechen einlöst: Time-Blocking heißt, um das Feste herum zu
 * planen.
 *
 * Geprüft werden hier die drei Wege, auf denen eine Uhrzeit gesetzt wird —
 * anlegen, bearbeiten, einen KI-Vorschlag übernehmen.
 */
function studentWithCourse(string $from = '10:00', string $to = '11:30', int $weekday = 1): User
{
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->create();

    Course::factory()->for($semester)->onWeekday($weekday)->at($from, $to)->create([
        'title' => 'Mathe 1',
    ]);

    return $user;
}

/** Die Felder, die das Formular für eine feste Uhrzeit schickt. */
function fixedHabitPayload(string $time, array $days, int $minutes = 30): array
{
    return [
        'template_key' => HabitTemplate::Lesen->value,
        'target_amount' => $minutes,
        'schedule_type' => ScheduleType::Fixed->value,
        'scheduled_time' => $time,
        'scheduled_days' => $days,
    ];
}

test('a new habit cannot be planned into a lecture', function () {
    $user = studentWithCourse();

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('10:30', [1]))
        ->assertSessionHasErrors('scheduled_time');

    $message = session('errors')->first('scheduled_time');

    expect($message)->toContain('Mathe 1')
        // Ein Kurs rückt nicht — der Satz darf nichts anderes anbieten.
        ->toContain('der Kurs rückt nicht')
        ->not->toContain('Verschiebe die zuerst')
        ->and($user->habits()->count())->toBe(0);
});

test('a habit on a weekday without lectures is planned as before', function () {
    $user = studentWithCourse(weekday: 1);

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('10:30', [3]))
        ->assertSessionHasNoErrors();

    expect($user->habits()->count())->toBe(1);
});

test('a habit that starts when the lecture ends is allowed', function () {
    $user = studentWithCourse('10:00', '11:30');

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('11:30', [1]))
        ->assertSessionHasNoErrors();

    expect($user->habits()->sole()->scheduled_time->format('H:i'))->toBe('11:30');
});

test('one colliding weekday out of five is enough to refuse', function () {
    $user = studentWithCourse(weekday: 4);

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('10:30', [1, 2, 3, 4, 5]))
        ->assertSessionHasErrors('scheduled_time');

    // Der Tag steht im Satz, damit man weiß, welcher der fünf klemmt.
    expect(session('errors')->first('scheduled_time'))->toContain('Donnerstags');
});

/**
 * Der Fall, den die App vorher nur als Hinweis kannte — im Raster war er
 * längst eine Sperre.
 */
test('two habits cannot share the same span either', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('17:00', [1])->withMeasure(30)
        ->create(['title' => 'Joggen gehen']);

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('17:15', [1]))
        ->assertSessionHasErrors('scheduled_time');

    expect(session('errors')->first('scheduled_time'))
        ->toContain('Joggen gehen')
        // Eine eigene Gewohnheit lässt sich verschieben — hier gibt es einen Ausweg.
        ->toContain('Verschiebe die zuerst');
});

test('a habit keeps its own time when only its duration changes', function () {
    $user = studentWithCourse();
    $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

    $this->actingAs($user)
        ->put(route('habits.update', $habit), [
            'target_amount' => 45,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '14:00',
            'scheduled_days' => [1],
        ])
        ->assertSessionHasNoErrors();

    expect($habit->fresh()->durationMinutes())->toBe(45);
});

test('an edit into a lecture is refused', function () {
    $user = studentWithCourse();
    $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

    $this->actingAs($user)
        ->put(route('habits.update', $habit), [
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '10:15',
            'scheduled_days' => [1],
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect($habit->fresh()->scheduled_time->format('H:i'))->toBe('14:00');
});

/**
 * Eine Kette rückt mit. Der Vorgänger passt hier noch vor die Vorlesung — sein
 * Nachfolger läge mitten darin, und das zählt.
 */
test('a follower that would land in a lecture stops the move', function () {
    $user = studentWithCourse('10:00', '11:30');

    $first = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)
        ->create(['title' => 'Joggen gehen']);
    Habit::factory()->for($user)->withMeasure(30)->create([
        'title' => 'Dehnen',
        'schedule_type' => ScheduleType::Chained,
        'trigger_situation' => null,
        'chained_to_habit_id' => $first->id,
    ]);

    $this->actingAs($user)
        ->put(route('habits.update', $first), [
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '09:30',
            'scheduled_days' => [1],
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect($first->fresh()->scheduled_time->format('H:i'))->toBe('14:00');
});

/**
 * Eine Situation hat keinen Zeitpunkt, mit dem sich kollidieren ließe. Sie
 * abzuweisen hieße, eine Genauigkeit zu behaupten, die sie nicht hat.
 */
test('a situational habit is not measured against the timetable', function () {
    $user = studentWithCourse();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Lesen->value,
            'target_amount' => 30,
            'trigger_situation' => 'nach der Vorlesung',
        ])
        ->assertSessionHasNoErrors();

    expect($user->habits()->count())->toBe(1);
});

/**
 * Zwischen dem Vorschlag und dem Übernehmen liegt eine Entscheidung — und in
 * der Zeit kann ein Kurs dazugekommen sein.
 */
test('an accepted suggestion is measured against the timetable too', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['situation' => '', 'time' => '10:30', 'days' => [1], 'reason' => 'Passt.']],
    ]]);

    $user = studentWithCourse();
    $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create([
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), [
            'scheduled_time' => '10:30',
            'scheduled_days' => [1],
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect($habit->fresh()->scheduled_time->format('H:i'))->toBe('14:00');
});

test('a lecture outside the semester blocks nothing', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->past()->create();
    Course::factory()->for($semester)->onWeekday(1)->at('10:00', '11:30')->create();

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('10:30', [1]))
        ->assertSessionHasNoErrors();

    expect($user->habits()->count())->toBe(1);
});

/**
 * Der Weg über die Verabredung: Wer Platz für jemanden macht, wählt eine neue
 * Zeit — und dabei gilt dasselbe.
 */
test('making room for an appointment cannot land in a lecture either', function () {
    $user = studentWithCourse('10:00', '11:30');
    $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

    $monday = Carbon::today()->next(Carbon::MONDAY);

    $this->actingAs($user)
        ->post(route('habits.shifts.store', $habit), [
            'date' => $monday->toDateString(),
            'scheduled_time' => '10:30',
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect(session('errors')->first('scheduled_time'))
        ->toContain('Mathe 1')
        ->toContain('der Kurs rückt nicht')
        ->and($habit->dayShifts()->count())->toBe(0);
});

/**
 * Und die Gegenprobe: Bei einer eigenen Gewohnheit bleibt der Ausweg stehen,
 * denn den gibt es dort.
 */
test('a habit in the way is named without pretending a lecture could move', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('10:00', [1])->withMeasure(60)
        ->create(['title' => 'Essen vorkochen']);
    $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

    $monday = Carbon::today()->next(Carbon::MONDAY);

    $this->actingAs($user)
        ->post(route('habits.shifts.store', $habit), [
            'date' => $monday->toDateString(),
            'scheduled_time' => '10:30',
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect(session('errors')->first('scheduled_time'))
        ->toContain('Essen vorkochen')
        ->not->toContain('rückt nicht');
});
