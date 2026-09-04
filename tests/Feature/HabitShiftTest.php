<?php

use App\Enums\ScheduleType;
use App\Models\Course;
use App\Models\CourseException;
use App\Models\Habit;
use App\Models\Semester;
use App\Models\User;
use App\Support\DayPlan;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Einen Block im Tag mit der Hand verschieben.
 *
 * Zwei Reichweiten, zwei völlig verschiedene Dinge: „heute mache ich das
 * später" ist keine Planänderung, „ab jetzt immer um zwei" schon. Die Geste
 * sieht in beiden Fällen gleich aus — deshalb fragt die App hinterher, und
 * deshalb prüfen die Tests hier vor allem, dass die beiden Wege nicht
 * ineinanderlaufen.
 */
function shiftable(User $user, array $attributes = []): Habit
{
    return Habit::factory()->for($user)->withMeasure(30)->create([
        'title' => 'Joggen gehen',
        'trigger_situation' => 'nach dem Mittagessen',
        ...$attributes,
    ]);
}

test('a shift for today leaves the habit itself alone', function () {
    $user = User::factory()->create();
    $habit = shiftable($user);

    $this->actingAs($user)
        ->put(route('habits.shifts.move', $habit), [
            'date' => Carbon::today()->toDateString(),
            'start_minute' => 14 * 60,
            'scope' => 'today',
        ])
        ->assertRedirect();

    expect($habit->fresh())
        // Der Auslöser bleibt: „heute später" heißt nicht „ab jetzt anders".
        ->trigger_situation->toBe('nach dem Mittagessen')
        ->schedule_type->toBe(ScheduleType::Dynamic)
        ->scheduled_time->toBeNull()
        ->and($habit->dayShifts()->sole()->scheduled_time->format('H:i'))->toBe('14:00');
});

test('the shifted block lies at its new place — and only on that day', function () {
    $user = User::factory()->create();
    $habit = shiftable($user);

    $this->actingAs($user)->put(route('habits.shifts.move', $habit), [
        'date' => Carbon::today()->toDateString(),
        'start_minute' => 14 * 60,
        'scope' => 'today',
    ]);

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.startMinute', 14 * 60)
            // Auf die Uhr gelegt heißt: für heute eine Zusage, keine Gegend.
            ->where('blocks.0.exact', true)
            ->where('blocks.0.shifted', true)
        );

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::tomorrow()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.startMinute', 13 * 60)
            ->where('blocks.0.exact', false)
            ->where('blocks.0.shifted', false)
        );
});

test('a permanent shift makes a situational habit fixed and drops its trigger', function () {
    $user = User::factory()->create();
    $habit = shiftable($user);

    $this->actingAs($user)
        ->put(route('habits.shifts.move', $habit), [
            'date' => Carbon::today()->toDateString(),
            'start_minute' => 14 * 60,
            'scope' => 'always',
        ])
        ->assertRedirect();

    expect($habit->fresh())
        ->schedule_type->toBe(ScheduleType::Fixed)
        ->scheduled_time->format('H:i')->toBe('14:00')
        ->trigger_situation->toBeNull()
        // Eine Gewohnheit ohne eigene Tage lief täglich und tut es weiter.
        ->scheduled_days->toBe([1, 2, 3, 4, 5, 6, 7]);
});

test('a permanent shift releases the habit from its chain', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('08:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(20)->create(['title' => 'Spazieren gehen']);
    $read = Habit::factory()->for($user)->withMeasure(15)->create([
        'title' => 'Lesen',
        'schedule_type' => ScheduleType::Chained,
        'chained_to_habit_id' => $walk->id,
        'trigger_situation' => null,
    ]);

    $this->actingAs($user)
        ->put(route('habits.shifts.move', $read), [
            'date' => Carbon::today()->toDateString(),
            'start_minute' => 15 * 60,
            'scope' => 'always',
        ])
        ->assertRedirect();

    expect($read->fresh())
        ->schedule_type->toBe(ScheduleType::Fixed)
        ->scheduled_time->format('H:i')->toBe('15:00')
        ->chained_to_habit_id->toBeNull();
});

/**
 * Eine Kette heißt „danach". Bliebe der Nachfolger stehen, wäre sein eigener
 * Anker gelogen — auch wenn der Vorgänger nur für heute woanders liegt.
 */
test('a follower moves along, even for a single day', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('08:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(20)->create(['title' => 'Spazieren gehen', 'position' => 0]);
    Habit::factory()->for($user)->withMeasure(15)->create([
        'title' => 'Lesen',
        'schedule_type' => ScheduleType::Chained,
        'chained_to_habit_id' => $walk->id,
        'trigger_situation' => null,
        'position' => 1,
    ]);

    $this->actingAs($user)->put(route('habits.shifts.move', $walk), [
        'date' => Carbon::today()->toDateString(),
        'start_minute' => 15 * 60,
        'scope' => 'today',
    ])->assertRedirect();

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.startMinute', 15 * 60)
            ->where('blocks.1.title', 'Lesen')
            ->where('blocks.1.startMinute', 15 * 60 + 20 + 15)
        );
});

test('a habit that already sits there blocks the move and is named', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('14:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(30)->create(['title' => 'Essen vorkochen']);
    $habit = shiftable($user);

    $this->actingAs($user)
        ->put(route('habits.shifts.move', $habit), [
            'date' => Carbon::today()->toDateString(),
            'start_minute' => 14 * 60,
            'scope' => 'always',
        ])
        ->assertSessionHasErrors('start_minute');

    expect($habit->fresh()->schedule_type)->toBe(ScheduleType::Dynamic);
});

/**
 * Der Fall, um den es geht: Heute ist frei, aber montags liegt dort etwas.
 */
test('a collision on another weekday stops the permanent move', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)
        ->create(['title' => 'Essen vorkochen']);
    // Läuft an allen Tagen — der Montag ist damit unvermeidlich.
    $habit = Habit::factory()->for($user)->fixedSchedule('09:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(30)->create(['title' => 'Joggen gehen']);

    $this->actingAs($user)
        ->put(route('habits.shifts.move', $habit), [
            'date' => Carbon::today()->toDateString(),
            'start_minute' => 14 * 60,
            'scope' => 'always',
        ])
        ->assertSessionHasErrors('start_minute');

    expect(session('errors')->first('start_minute'))
        ->toStartWith('„Essen vorkochen" liegt montags schon um 14:00')
        ->toContain('eine Viertelstunde Luft')
        ->toContain('Verschiebe die zuerst, dann lässt sich die Zeit hier umstellen.');

    expect($habit->fresh()->scheduled_time->format('H:i'))->toBe('09:00');
});

test('a place outside the waking day is refused', function () {
    $user = User::factory()->create();
    $habit = shiftable($user);

    $this->actingAs($user)
        ->put(route('habits.shifts.move', $habit), [
            'date' => Carbon::today()->toDateString(),
            // Der Rahmen endet um 23:00; 30 Minuten ab 22:45 ragen darüber hinaus.
            'start_minute' => 22 * 60 + 45,
            'scope' => 'today',
        ])
        ->assertSessionHasErrors('start_minute');

    expect($habit->dayShifts()->count())->toBe(0);
});

test('a follower that would run past bedtime stops the move', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('08:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(30)->create(['title' => 'Spazieren gehen']);
    Habit::factory()->for($user)->withMeasure(60)->create([
        'title' => 'Lesen',
        'schedule_type' => ScheduleType::Chained,
        'chained_to_habit_id' => $walk->id,
        'trigger_situation' => null,
    ]);

    $this->actingAs($user)
        ->put(route('habits.shifts.move', $walk), [
            'date' => Carbon::today()->toDateString(),
            // Selbst passt der Spaziergang noch; „Lesen" liefe bis 23:30.
            'start_minute' => 22 * 60,
            'scope' => 'today',
        ])
        ->assertSessionHasErrors('start_minute');

    expect($walk->dayShifts()->count())->toBe(0);
});

test('a shift can be taken back', function () {
    $user = User::factory()->create();
    $habit = shiftable($user);

    $this->actingAs($user)->put(route('habits.shifts.move', $habit), [
        'date' => Carbon::today()->toDateString(),
        'start_minute' => 14 * 60,
        'scope' => 'today',
    ]);

    $this->actingAs($user)
        ->delete(route('habits.shifts.destroy', $habit), [
            'date' => Carbon::today()->toDateString(),
        ])
        ->assertRedirect();

    expect($habit->dayShifts()->count())->toBe(0);
});

/**
 * Zwei Antworten für denselben Tag wären eine zu viel.
 */
test('a permanent time clears the exceptions it supersedes', function () {
    $user = User::factory()->create();
    $habit = shiftable($user);

    $this->actingAs($user)->put(route('habits.shifts.move', $habit), [
        'date' => Carbon::today()->toDateString(),
        'start_minute' => 14 * 60,
        'scope' => 'today',
    ]);

    $this->actingAs($user)->put(route('habits.shifts.move', $habit), [
        'date' => Carbon::today()->toDateString(),
        'start_minute' => 16 * 60,
        'scope' => 'always',
    ])->assertRedirect();

    expect($habit->dayShifts()->count())->toBe(0)
        ->and($habit->fresh()->scheduled_time->format('H:i'))->toBe('16:00');
});

test('the minute snaps to a quarter of an hour', function () {
    $user = User::factory()->create();
    $habit = shiftable($user);

    $this->actingAs($user)->put(route('habits.shifts.move', $habit), [
        'date' => Carbon::today()->toDateString(),
        'start_minute' => 14 * 60 + 7,
        'scope' => 'today',
    ])->assertRedirect();

    expect($habit->dayShifts()->sole()->scheduled_time->format('H:i'))->toBe('14:00');
});

test('a past day cannot be rearranged', function () {
    $user = User::factory()->create();
    $habit = shiftable($user);

    $this->actingAs($user)
        ->put(route('habits.shifts.move', $habit), [
            'date' => Carbon::yesterday()->toDateString(),
            'start_minute' => 14 * 60,
            'scope' => 'today',
        ])
        ->assertSessionHasErrors('date');

    expect($habit->dayShifts()->count())->toBe(0);
});

test('the calendar says whether a day can be rearranged at all', function () {
    $user = User::factory()->create();
    shiftable($user, ['created_at' => Carbon::today()->subDays(10)]);

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canShift', true));

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->subDay()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canShift', false));
});

test('a foreign habit stays where it is', function () {
    $user = User::factory()->create();
    $foreign = Habit::factory()->create();

    $this->actingAs($user)
        ->put(route('habits.shifts.move', $foreign), [
            'date' => Carbon::today()->toDateString(),
            'start_minute' => 14 * 60,
            'scope' => 'today',
        ])
        ->assertForbidden();

    expect($foreign->dayShifts()->count())->toBe(0);
});

/**
 * Der Grund, warum die Ausnahme überall gelten muss.
 *
 * Die Erinnerung ist ein Timer auf der Uhrzeit der Gewohnheit. Läse sie die
 * Ausnahme nicht, meldete sie sich um 17:00, während der Kalender 14:00 zeigt
 * — genau der Widerspruch, gegen den die App gebaut ist.
 */
test('the reminder follows the shift', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)
        ->fixedSchedule('17:00', [1, 2, 3, 4, 5, 6, 7])
        ->withReminder()
        ->withMeasure(30)
        ->create(['title' => 'Joggen gehen']);

    $this->actingAs($user)->put(route('habits.shifts.move', $habit), [
        'date' => Carbon::today()->toDateString(),
        'start_minute' => 14 * 60,
        'scope' => 'today',
    ])->assertRedirect();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habitReminders.0.scheduledTime', '14:00')
        );
});

test('the overview shows the shifted time, not the regular one', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)
        ->fixedSchedule('17:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(30)
        ->create(['title' => 'Joggen gehen']);

    $this->actingAs($user)->put(route('habits.shifts.move', $habit), [
        'date' => Carbon::today()->toDateString(),
        'start_minute' => 14 * 60,
        'scope' => 'today',
    ])->assertRedirect();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Ihre Fassung sagt beides: die Uhrzeit und dass sie nur
            // heute gilt — sonst läse sich die Zeile wie eine dauerhafte
            // Änderung.
            ->where('habits.0.scheduleLabel', '14:00 · nur an diesem Tag')
        );
});

/**
 * Die freien Fenster der KI müssen die Ausnahme kennen — sonst böte sie einen
 * Platz an, der an diesem Tag längst belegt ist.
 */
test('a shift occupies its new place for the AI as well', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)
        ->fixedSchedule('09:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(60)
        ->create(['title' => 'Joggen gehen']);

    $day = Carbon::today();
    $habit->dayShifts()->create(['shifted_on' => $day, 'scheduled_time' => '14:00']);

    $habits = $user->habits()->active()->get();
    $habits->each(fn (Habit $each) => $each->setRelation('user', $user));
    $habits->each(fn (Habit $each) => $each->load(['dayShifts' => fn ($query) => $query
        ->whereDate('shifted_on', $day)]));

    $plan = DayPlan::for($habits, $day->dayOfWeekIso, $user->sleepWindows(), $day);

    expect($plan->occupied()[0]['from'])->toBe(14 * 60)
        ->and($plan->collisionWith(14 * 60, 14 * 60 + 30))->not->toBeNull()
        ->and($plan->collisionWith(9 * 60, 10 * 60))->toBeNull();
});

/**
 * Der Stundenplan ist die zweite Grenze neben dem Schlafrahmen.
 *
 * Eine Vorlesung lässt sich nicht wegschieben, und der Satz darf deshalb auch
 * nichts anderes anbieten als eine andere Uhrzeit.
 */
test('a lecture blocks the move and says so without offering to move it', function () {
    $user = User::factory()->create();
    $monday = Carbon::today()->next(Carbon::MONDAY);

    $semester = Semester::factory()->for($user)->create();
    Course::factory()->for($semester)->onWeekday(1)->at('10:00', '11:30')
        ->create(['title' => 'Analysis I']);

    $habit = shiftable($user);

    $response = $this->actingAs($user)
        ->put(route('habits.shifts.move', $habit), [
            'date' => $monday->toDateString(),
            'start_minute' => 10 * 60,
            'scope' => 'today',
        ])
        ->assertSessionHasErrors('start_minute');

    $message = session('errors')->first('start_minute');

    expect($message)->toContain('Analysis I')
        ->and($message)->toContain('der Kurs rückt nicht')
        ->and($message)->not->toContain('Verschiebe die zuerst')
        ->and($habit->dayShifts()->count())->toBe(0);

    $response->assertRedirect();
});

test('the gap between two lectures takes the habit', function () {
    $user = User::factory()->create();
    $monday = Carbon::today()->next(Carbon::MONDAY);

    $semester = Semester::factory()->for($user)->create();
    Course::factory()->for($semester)->onWeekday(1)->at('08:00', '09:30')->create();
    Course::factory()->for($semester)->onWeekday(1)->at('12:00', '13:30')
        ->create(['title' => 'Statistik']);

    $habit = shiftable($user);

    $this->actingAs($user)
        ->put(route('habits.shifts.move', $habit), [
            'date' => $monday->toDateString(),
            'start_minute' => 10 * 60,
            'scope' => 'today',
        ])
        ->assertSessionHasNoErrors();

    expect($habit->dayShifts()->sole()->scheduled_time->format('H:i'))->toBe('10:00');
});

/**
 * Ein ausgefallener Kurs belegt nichts mehr — sonst wäre der freie Vormittag,
 * den eine Absage schenkt, im Kalender weiter blockiert.
 */
test('a cancelled lecture frees its place again', function () {
    $user = User::factory()->create();
    $monday = Carbon::today()->next(Carbon::MONDAY);

    $semester = Semester::factory()->for($user)->create();
    $course = Course::factory()->for($semester)->onWeekday(1)->at('10:00', '11:30')->create();
    CourseException::factory()->for($course)->cancelledOn($monday)->create();

    $habit = shiftable($user);

    $this->actingAs($user)
        ->put(route('habits.shifts.move', $habit), [
            'date' => $monday->toDateString(),
            'start_minute' => 10 * 60,
            'scope' => 'today',
        ])
        ->assertSessionHasNoErrors();

    expect($habit->dayShifts()->count())->toBe(1);
});
