<?php

use App\Enums\ScheduleType;
use App\Enums\ShiftOrigin;
use App\Http\Controllers\HabitDayShiftController;
use App\Models\Course;
use App\Models\Habit;
use App\Models\HabitDayShift;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * „Heute bin ich später aufgestanden."
 *
 * Der Schlafplan kennt Wochentage; dieser Weg kennt Daten. Er verschiebt den
 * Rahmen für einen einzigen Tag — und mit ihm alles, was an ihm hängt.
 */

/** Ein fester Montag in der Zukunft, damit nichts vom Wochentag des Laufs abhängt. */
function overriddenMonday(): Carbon
{
    return Carbon::today()->startOfWeek()->addWeek();
}

test('the frame of a single day differs without touching the plan', function () {
    $monday = overriddenMonday();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('sleep.days.store'), [
            'date' => $monday->toDateString(),
            'wake_time' => '10:00',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->get(route('calendar.day', $monday->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('wakeTime', '10:00')
            ->where('frameFrom', 10 * 60)
        );

    // Der nächste Montag läuft wieder nach Plan — die Ausnahme gilt für ein
    // Datum, nicht für einen Wochentag.
    $this->actingAs($user)
        ->get(route('calendar.day', $monday->copy()->addWeek()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('wakeTime', '07:00'));

    expect($user->refresh()->sleepWindowFor(1)['wakeTime'])->toBe('07:00');
});

/**
 * Der Kern des Ganzen: Eine situative Gewohnheit folgt dem Rahmen von selbst.
 * Sie braucht keine gespeicherte Zeile — sie liest die Aufstehzeit des Tages.
 */
test('a habit after waking follows the overridden wake time by itself', function () {
    $monday = overriddenMonday();
    $user = User::factory()->create();

    $habit = Habit::factory()->for($user)->withMeasure(20)->create([
        'title' => 'Meditieren',
        'trigger_situation' => 'nach dem Aufstehen',
        'schedule_type' => ScheduleType::Dynamic,
        'scheduled_time' => null,
        'created_at' => Carbon::today()->subWeek(),
    ]);

    $this->actingAs($user)->post(route('sleep.days.store'), [
        'date' => $monday->toDateString(),
        'wake_time' => '10:00',
        'carry_habits' => true,
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->get(route('calendar.day', $monday->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.startMinute', 10 * 60)
        );

    // Ohne eine einzige Zeile in der Tabelle der Umzüge.
    expect($habit->dayShifts()->count())->toBe(0);
});

test('a fixed habit gets a shift for that day, marked as coming from the frame', function () {
    $monday = overriddenMonday();
    $user = User::factory()->create();

    $habit = Habit::factory()->for($user)
        ->withMeasure(20)
        ->fixedSchedule('08:00', [1])
        ->create(['title' => 'Frühstück']);

    $this->actingAs($user)->post(route('sleep.days.store'), [
        'date' => $monday->toDateString(),
        'wake_time' => '10:00',
        'carry_habits' => true,
    ])->assertSessionHasNoErrors();

    $shift = $habit->dayShifts()->firstOrFail();

    expect($shift->scheduled_time->format('H:i'))->toBe('11:00')
        ->and($shift->origin)->toBe(ShiftOrigin::Frame)
        // Die dauerhafte Uhrzeit bleibt, was sie war — nur heute ist anders.
        ->and($habit->refresh()->scheduled_time->format('H:i'))->toBe('08:00');
});

test('taking the day back drops only what the frame moved', function () {
    $monday = overriddenMonday();
    $user = User::factory()->create();

    $carried = Habit::factory()->for($user)
        ->withMeasure(20)
        ->fixedSchedule('08:00', [1])
        ->create(['title' => 'Frühstück']);

    $byHand = Habit::factory()->for($user)
        ->withMeasure(20)
        ->fixedSchedule('19:00', [1])
        ->create(['title' => 'Lesen']);

    // Von Hand für diesen Tag verlegt — das ist eine eigene Entscheidung.
    HabitDayShift::query()->create([
        'habit_id' => $byHand->id,
        'shifted_on' => $monday,
        'scheduled_time' => '20:30',
        'origin' => ShiftOrigin::Manual,
    ]);

    $this->actingAs($user)->post(route('sleep.days.store'), [
        'date' => $monday->toDateString(),
        'wake_time' => '10:00',
        'carry_habits' => true,
    ])->assertSessionHasNoErrors();

    expect($carried->dayShifts()->count())->toBe(1);

    $this->actingAs($user)
        ->delete(route('sleep.days.destroy'), ['date' => $monday->toDateString()])
        ->assertSessionHasNoErrors();

    expect($carried->dayShifts()->count())->toBe(0)
        ->and($byHand->dayShifts()->count())->toBe(1)
        ->and($user->refresh()->sleepDayOverrides()->count())->toBe(0);
});

test('without the go-ahead the frame moves and the fixed times stay', function () {
    $monday = overriddenMonday();
    $user = User::factory()->create();

    $habit = Habit::factory()->for($user)
        ->withMeasure(20)
        ->fixedSchedule('08:00', [1])
        ->create(['title' => 'Frühstück']);

    $this->actingAs($user)->post(route('sleep.days.store'), [
        'date' => $monday->toDateString(),
        'wake_time' => '10:00',
    ])->assertSessionHasNoErrors();

    expect($habit->dayShifts()->count())->toBe(0)
        ->and($user->refresh()->sleepWindowOn($monday)['wakeTime'])->toBe('10:00');
});

test('the preview shows what saving would do and stores nothing', function () {
    $monday = overriddenMonday();
    $user = User::factory()->create();

    Habit::factory()->for($user)
        ->withMeasure(20)
        ->fixedSchedule('08:00', [1])
        ->create(['title' => 'Frühstück']);

    $this->actingAs($user)
        ->postJson(route('sleep.days.preview'), [
            'date' => $monday->toDateString(),
            'wake_time' => '10:00',
        ])
        ->assertOk()
        ->assertJsonPath('moves.0.title', 'Frühstück')
        ->assertJsonPath('moves.0.from', '08:00')
        ->assertJsonPath('moves.0.to', '11:00');

    expect($user->refresh()->sleepDayOverrides()->count())->toBe(0);
});

test('only the deviation is stored, the rest keeps following the plan', function () {
    $monday = overriddenMonday();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('sleep.days.store'), [
        'date' => $monday->toDateString(),
        'wake_time' => '10:00',
    ]);

    // Die Schlafenszeit steht nicht in der Zeile — sie kommt weiter aus dem
    // Wochenplan und zieht deshalb mit, wenn der sich ändert.
    $this->actingAs($user)->put(route('sleep.update'), [
        'days' => collect(range(1, 7))->map(fn (int $weekday): array => [
            'weekday' => $weekday,
            'wake_time' => '07:00',
            'bedtime' => '22:00',
            'alarm_enabled' => false,
        ])->all(),
        'bedtime_reminder_enabled' => true,
    ]);

    $window = $user->refresh()->sleepWindowOn($monday);

    expect($window['wakeTime'])->toBe('10:00')
        ->and($window['bedtime'])->toBe('22:00');
});

test('past days cannot be rearranged', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('sleep.days.store'), [
            'date' => Carbon::yesterday()->toDateString(),
            'wake_time' => '10:00',
        ])
        ->assertSessionHasErrors('date');

    expect($user->sleepDayOverrides()->count())->toBe(0);
});

test('the day belongs to its person alone', function () {
    $monday = overriddenMonday();
    $owner = User::factory()->create();

    $this->actingAs($owner)->post(route('sleep.days.store'), [
        'date' => $monday->toDateString(),
        'wake_time' => '10:00',
    ]);

    $other = User::factory()->create();

    expect($other->sleepWindowOn($monday)['wakeTime'])->toBe('07:00');
});

/**
 * Der eigene Rahmen eines Tages gilt auch beim Ziehen im Raster.
 *
 * Die Prüfung sitzt in {@see HabitDayShiftController}
 * und liest den Rahmen jetzt über das Datum statt über den Wochentag. Ohne das
 * wiese sie an einem verschlafenen Tag Stellen ab, die längst wach sind — und
 * ließe umgekehrt welche zu, an denen noch geschlafen wird.
 */
test('dragging on a day with its own frame goes by that frame', function () {
    $monday = overriddenMonday();
    $user = User::factory()->create();

    $user->sleepSchedules()->create([
        'weekday' => 1, 'wake_time' => '07:00', 'bedtime' => '22:00',
    ]);

    $habit = Habit::factory()->for($user)
        ->withMeasure(30)
        ->fixedSchedule('15:00', [1])
        ->create(['title' => 'Lesen']);

    // Ohne Ausnahme ist 08:00 erlaubt — der Tag beginnt um 07:00.
    $this->actingAs($user)
        ->put(route('habits.shifts.move', $habit), [
            'date' => $monday->toDateString(),
            'start_minute' => 8 * 60,
            'scope' => 'today',
        ])
        ->assertSessionHasNoErrors();

    $user->sleepDayOverrides()->create([
        'on_date' => $monday,
        'wake_time' => '11:00',
    ]);

    // `actingAs()` reicht dieselbe Instanz in jede Anfrage weiter, und die
    // trägt die Beziehung aus der ersten noch im Speicher. Im Betrieb lädt
    // jede Anfrage frisch; hier muss der Test es nachholen.
    $user->unsetRelation('sleepDayOverrides');

    // Mit Ausnahme liegt 08:00 in der Nacht — und der Satz nennt die Zeit
    // dieses Tages, nicht die des Wochentags.
    $this->actingAs($user)
        ->put(route('habits.shifts.move', $habit), [
            'date' => $monday->toDateString(),
            'start_minute' => 8 * 60,
            'scope' => 'today',
        ])
        ->assertSessionHasErrors('start_minute');

    expect(session('errors')->first('start_minute'))->toContain('11:00');

    // Was im neuen Rahmen liegt, geht weiter.
    $this->actingAs($user)
        ->put(route('habits.shifts.move', $habit), [
            'date' => $monday->toDateString(),
            'start_minute' => 12 * 60,
            'scope' => 'today',
        ])
        ->assertSessionHasNoErrors();

    expect($habit->dayShifts()->firstOrFail()->scheduled_time->format('H:i'))->toBe('12:00');
});

/**
 * Der Rahmen eines Tages ändert nichts am Stundenplan: Eine Vorlesung rückt
 * nicht, und was mitzieht, muss an ihr vorbei.
 */
test('a carried habit goes around a lecture and takes the freed slot when it is cancelled', function () {
    $monday = overriddenMonday();
    $user = User::factory()->create();

    $user->sleepSchedules()->create([
        'weekday' => 1, 'wake_time' => '06:00', 'bedtime' => '22:00',
    ]);

    $semester = Semester::factory()->for($user)->create([
        'starts_on' => $monday->copy()->subMonth(),
        'ends_on' => $monday->copy()->addMonths(4),
    ]);

    $course = Course::factory()->for($semester)->onWeekday(1)->at('09:00', '10:30')->create([
        'title' => 'EC-Hauptseminar',
    ]);

    $habit = Habit::factory()->for($user)
        ->withMeasure(30)
        ->fixedSchedule('07:30', [1])
        ->create(['title' => 'Frühstücken']);

    // Aufstehen zwei Stunden später: 07:30 + 2:00 = 09:30 läge mitten im
    // Seminar — die Gewohnheit muss dahinter, mit der Viertelstunde Luft.
    $this->actingAs($user)->post(route('sleep.days.store'), [
        'date' => $monday->toDateString(),
        'wake_time' => '08:00',
        'carry_habits' => true,
    ])->assertSessionHasNoErrors();

    expect($habit->dayShifts()->firstOrFail()->scheduled_time->format('H:i'))->toBe('10:45');

    // Zurücknehmen, Seminar streichen, dasselbe noch einmal: Jetzt ist der
    // Platz frei, und die Gewohnheit landet dort, wo sie hingehört.
    $this->actingAs($user)->delete(route('sleep.days.destroy'), [
        'date' => $monday->toDateString(),
    ])->assertSessionHasNoErrors();

    $course->exceptions()->create(['on_date' => $monday, 'starts_at' => null, 'ends_at' => null]);

    $this->actingAs($user)->post(route('sleep.days.store'), [
        'date' => $monday->toDateString(),
        'wake_time' => '08:00',
        'carry_habits' => true,
    ])->assertSessionHasNoErrors();

    expect($habit->dayShifts()->firstOrFail()->scheduled_time->format('H:i'))->toBe('09:30');
});
