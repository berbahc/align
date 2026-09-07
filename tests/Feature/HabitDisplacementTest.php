<?php

use App\Actions\RestoreDisplacedHabits;
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
        ->get(route('calendar'))
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

test('a course entered for a semester that has not started yet parks the habit already', function () {
    $user = User::factory()->create();
    Semester::factory()->for($user)->between(
        Carbon::today()->addMonth()->toDateString(),
        Carbon::today()->addMonths(5)->toDateString(),
    )->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('10:45', [1])->withMeasure(20)
        ->create(['title' => '20 Minuten spazieren']);

    $this->actingAs($user)->post(route('calendar.semester.courses.store'), [
        'title' => 'Mathe 1',
        'kind' => CourseKind::Vorlesung->value,
        'weekday' => 1,
        'starts_at' => '10:00',
        'ends_at' => '11:30',
    ])->assertSessionHasNoErrors();

    // Geparkt, obwohl der nächste Montag noch vor dem Semester liegt: Am ersten
    // Vorlesungsmontag läge sie sonst mitten im Kurs.
    expect($habit->fresh()->displaced_at)->not->toBeNull();
});

test('moving a course inside a future semester parks what lies under its new time', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->between(
        Carbon::today()->addMonth()->toDateString(),
        Carbon::today()->addMonths(5)->toDateString(),
    )->create();
    $course = Course::factory()->for($semester)->onWeekday(1)->at('08:00', '09:30')->create(['title' => 'Mathe 1']);
    $habit = Habit::factory()->for($user)->fixedSchedule('11:00', [1])->withMeasure(45)
        ->create(['title' => 'Krafttraining']);

    $this->actingAs($user)->put(route('calendar.semester.courses.update', $course), [
        'title' => 'Mathe 1',
        'kind' => CourseKind::Vorlesung->value,
        'weekday' => 1,
        'starts_at' => '10:00',
        'ends_at' => '11:30',
    ])->assertSessionHasNoErrors();

    expect($habit->fresh()->displaced_at)->not->toBeNull();
});

/** Ein Student, dessen Semester erst nächsten Monat anfängt — und schon einen Kurs hat. */
function studentBeforeTheSemester(): array
{
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->between(
        Carbon::today()->addMonth()->toDateString(),
        Carbon::today()->addMonths(5)->toDateString(),
    )->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('10:45', [1])->withMeasure(20)
        ->create(['title' => '20 Minuten spazieren', 'reminder_enabled' => true]);

    test()->actingAs($user)->post(route('calendar.semester.courses.store'), [
        'title' => 'Mathe 1',
        'kind' => CourseKind::Vorlesung->value,
        'weekday' => 1,
        'starts_at' => '10:00',
        'ends_at' => '11:30',
    ])->assertSessionHasNoErrors();

    $habit = $habit->fresh();
    $habit->setRelation('user', $user);

    return [$user, $semester, $habit];
}

test('before the semester starts, the habit keeps its place and the mark waits for the first day', function () {
    [$user, $semester, $habit] = studentBeforeTheSemester();

    $firstLectureMonday = Carbon::parse($semester->starts_on->toDateString());

    while ($firstLectureMonday->dayOfWeekIso !== 1) {
        $firstLectureMonday->addDay();
    }

    // Der Vermerk trägt den Semesterbeginn, nicht den Klick.
    expect($habit->displaced_at?->toDateString())->toBe($semester->starts_on->toDateString())
        // Nächsten Montag läuft sie noch — samt Erinnerung.
        ->and($habit->dayStartMinute(parkedMonday()))->toBe(645)
        ->and($habit->isDisplaced(parkedMonday()))->toBeFalse()
        ->and($habit->isDisplaced())->toBeFalse()
        ->and($habit->canRemind())->toBeTrue()
        ->and($habit->scheduleLabel(parkedMonday()))->not->toContain('braucht einen neuen Platz')
        // Am ersten Vorlesungsmontag nicht mehr.
        ->and($habit->dayStartMinute($firstLectureMonday))->toBeNull()
        ->and($habit->isDisplaced($firstLectureMonday))->toBeTrue();

    // Der Monat kündigt es an — mit dem Tag, ab dem es gilt. So kommt die
    // Änderung nicht über Nacht.
    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('displaced.0.title', '20 Minuten spazieren')
            ->where('displaced.0.from', $semester->starts_on->toDateString())
            ->where('displaced.0.fromLabel', $semester->starts_on->settings(['locale' => 'de'])->isoFormat('D. MMMM'))
            ->etc());
});

test('once the semester has started, the mark takes effect and the band appears', function () {
    [$user, $semester, $habit] = studentBeforeTheSemester();

    $this->travelTo(Carbon::parse($semester->starts_on->toDateString())->addDay()->setTime(9, 0));

    expect($habit->isDisplaced())->toBeTrue()
        ->and($habit->canRemind())->toBeFalse();

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('displaced.0.title', '20 Minuten spazieren')
            ->where('displaced.0.from', null)
            ->etc());

    $this->travelBack();
});

/**
 * Ist das Semester vorbei, gilt der Stundenplan nicht mehr — und was er
 * verdrängt hatte, kommt zurück, ohne dass jemand einen Kurs löschen müsste.
 */
test('once the semester is over, the parked habit comes back on its own', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->past()->create();
    Course::factory()->for($semester)->onWeekday(1)->at('10:00', '11:30')->create(['title' => 'Mathe 1']);
    $habit = Habit::factory()->for($user)->fixedSchedule('10:45', [1])->withMeasure(20)
        ->create(['title' => '20 Minuten spazieren', 'displaced_at' => Carbon::today()->subMonths(4)]);

    $this->artisan('habits:restore-displaced')->assertSuccessful();

    expect($habit->fresh()->displaced_at)->toBeNull();
});

test('opening the calendar brings a parked habit back once its place is free again', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->past()->create();
    Course::factory()->for($semester)->onWeekday(1)->at('10:00', '11:30')->create(['title' => 'Mathe 1']);
    $habit = Habit::factory()->for($user)->fixedSchedule('10:45', [1])->withMeasure(20)
        ->create(['title' => '20 Minuten spazieren', 'displaced_at' => Carbon::today()->subMonths(4)]);

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('displaced', [])->etc());

    expect($habit->fresh()->displaced_at)->toBeNull();
});

test('a parked habit stays parked while the semester still covers its course', function () {
    [$user, $habit] = studentWhoseHabitGetsCovered();

    $this->artisan('habits:restore-displaced')->assertSuccessful();

    expect($habit->fresh()->displaced_at)->not->toBeNull();
});

test('a course for a future semester parks every habit beneath it, not just the first', function () {
    $user = User::factory()->create();
    Semester::factory()->for($user)->between(
        Carbon::today()->addMonth()->toDateString(),
        Carbon::today()->addMonths(5)->toDateString(),
    )->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('10:15', [1])->withMeasure(20)->create(['title' => 'Spazieren']);
    $gym = Habit::factory()->for($user)->fixedSchedule('11:30', [1])->withMeasure(45)->create(['title' => 'Krafttraining']);

    $this->actingAs($user)->post(route('calendar.semester.courses.store'), [
        'title' => 'Mathe 1',
        'kind' => CourseKind::Vorlesung->value,
        'weekday' => 1,
        'starts_at' => '10:00',
        'ends_at' => '11:30',
    ])->assertSessionHasNoErrors();

    // Beide: die eine liegt im Kurs, die andere direkt dahinter — ohne die
    // Viertelstunde Luft, die jeder Hand gilt.
    expect($walk->fresh()->displaced_at)->not->toBeNull()
        ->and($gym->fresh()->displaced_at)->not->toBeNull();
});

/**
 * Eine Gewohnheit an einer Situation hat keine Uhrzeit — sie belegt im Tag die
 * Stunde ihres Ankers und wird darüber verdrängt wie jede andere. Beide
 * Rückwege filterten sie lange weg: Sie bekam nie einen Vorschlag und kam nie
 * von selbst zurück. Ein Drittel aller Gewohnheiten ist von dieser Art.
 */
function situationHabitUnderACourse(): array
{
    $user = User::factory()->create();
    Semester::factory()->for($user)->create();

    // „Nach dem Aufstehen" liegt zur Aufstehstunde: 07:00 bei der Vorgabe.
    // Die Vorlage gepinnt: Ohne sie würfelt die Factory eine, und mit ihr das
    // Tagesfenster — ein Vorschlag um 09:00 fiele durch das Fenster von
    // „Abendessen" und der Test wäre zu einem Fünftel rot.
    $habit = Habit::factory()->for($user)->withMeasure(30)->create([
        'title' => 'Spazieren gehen',
        'template_key' => HabitTemplate::Spazieren->value,
        'schedule_type' => ScheduleType::Dynamic,
        'trigger_situation' => 'nach dem Aufstehen',
        'scheduled_time' => null,
        'scheduled_days' => null,
    ]);

    test()->actingAs($user)->post(route('calendar.semester.courses.store'), [
        'title' => 'Frühseminar',
        'kind' => CourseKind::Vorlesung->value,
        'weekday' => 1,
        'starts_at' => '07:00',
        'ends_at' => '08:30',
    ])->assertSessionHasNoErrors();

    return [$user, $habit->fresh()];
}

test('a habit hanging on a situation is parked like any other', function () {
    [, $habit] = situationHabitUnderACourse();

    expect($habit->displaced_at)->not->toBeNull()
        ->and($habit->dayStartMinute(parkedMonday()))->toBeNull();
});

test('a parked situation habit comes back on its own once the course is gone', function () {
    [$user, $habit] = situationHabitUnderACourse();

    $user->currentSemester()->courses()->sole()->delete();

    app(RestoreDisplacedHabits::class)->handle($user);

    expect($habit->fresh()->displaced_at)->toBeNull();
});

/**
 * Ein Kurs im noch nicht begonnenen Semester lässt sich anlegen.
 *
 * Die Meldung nennt seit Kurzem den Tag, ab dem der Platz weg ist — und holte
 * ihn über eine Closure, die `?Carbon` versprach. `DisplaceHabits::effectiveFrom()`
 * gibt aber zweierlei zurück: `now()`, solange das Semester läuft, und
 * `starts_on->startOfDay()`, wenn es erst beginnt — und das ist eine
 * unveränderliche Instanz. Wer im September seinen Stundenplan eintrug, bekam
 * deshalb einen TypeError, **nachdem** der Kurs geschrieben war: Beim nächsten
 * Aufschlagen stand er da, die Meldung dazu nie.
 *
 * Die Tests trafen den Fall nie, weil ihre Semester schon liefen.
 */
test('a course in a semester that has not started yet can be created', function () {
    $user = User::factory()->create();
    $start = Carbon::today()->addWeeks(4)->next(Carbon::MONDAY);

    Semester::factory()->for($user)->create([
        'starts_on' => $start,
        'ends_on' => $start->copy()->addMonths(4),
    ]);

    Habit::factory()->for($user)->fixedSchedule('10:15', [1])->withMeasure(30)
        ->create(['title' => 'Vorlesung nachbereiten']);

    $this->actingAs($user)
        ->post(route('calendar.semester.courses.store'), [
            'title' => 'Mathe 1',
            'kind' => CourseKind::Vorlesung->value,
            'weekday' => 1,
            'starts_at' => '10:00',
            'ends_at' => '11:30',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($user->habits()->sole()->displaced_at?->toDateString())->toBe($start->toDateString());
});
