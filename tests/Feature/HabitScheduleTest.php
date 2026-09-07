<?php

use App\Enums\HabitTemplate;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

test('a habit can be anchored to a fixed time on chosen weekdays', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Lesen->value,
            'target_amount' => 20,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '17:00',
            'scheduled_days' => [1, 2, 3, 4, 5],
        ])
        ->assertRedirect(route('dashboard'));

    $habit = $user->habits()->sole();

    expect($habit->schedule_type)->toBe(ScheduleType::Fixed)
        ->and($habit->scheduled_time->format('H:i'))->toBe('17:00')
        ->and($habit->scheduled_days)->toBe([1, 2, 3, 4, 5])
        // Die Situation gehört zum anderen Zweig und darf nicht stehenbleiben.
        ->and($habit->trigger_situation)->toBeNull();
});

test('the situation stays the default when no schedule type is sent', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('habits.store'), [
        'template_key' => HabitTemplate::Lesen->value,
        'target_amount' => 20,
        'trigger_situation' => 'vor dem Schlafengehen',
    ])->assertRedirect(route('dashboard'));

    expect($user->habits()->sole())
        ->schedule_type->toBe(ScheduleType::Dynamic)
        ->trigger_situation->toBe('vor dem Schlafengehen')
        ->scheduled_time->toBeNull();
});

test('a fixed habit without weekdays is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Lesen->value,
            'target_amount' => 20,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '17:00',
        ])
        ->assertSessionHasErrors('scheduled_days');

    expect($user->habits()->count())->toBe(0);
});

test('a dynamic habit without a situation is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Lesen->value,
            'target_amount' => 20,
            'schedule_type' => ScheduleType::Dynamic->value,
        ])
        ->assertSessionHasErrors('trigger_situation');
});

test('a fixed habit is only scheduled on its own weekdays', function () {
    // 2026-08-03 ist ein Montag, 2026-08-08 ein Samstag.
    $habit = Habit::factory()->fixedSchedule(days: [1, 2, 3, 4, 5])->make();

    expect($habit->isScheduledOn(Carbon::parse('2026-08-03')))->toBeTrue()
        ->and($habit->isScheduledOn(Carbon::parse('2026-08-08')))->toBeFalse();
});

test('a dynamic habit is scheduled on every day', function () {
    $habit = Habit::factory()->make();

    expect($habit->isScheduledOn(Carbon::parse('2026-08-03')))->toBeTrue()
        ->and($habit->isScheduledOn(Carbon::parse('2026-08-08')))->toBeTrue();
});

test('only fixed habits can carry a reminder', function () {
    expect(Habit::factory()->fixedSchedule()->make()->canRemind())->toBeTrue()
        ->and(Habit::factory()->make()->canRemind())->toBeFalse();
});

test('the schedule label folds consecutive weekdays into a span', function (array $days, string $expected) {
    $habit = Habit::factory()->fixedSchedule('17:00', $days)->make();

    expect($habit->scheduleLabel())->toBe("17:00 · {$expected}");
})->with([
    'weekdays fold' => [[1, 2, 3, 4, 5], 'Mo–Fr'],
    'all seven read as daily' => [[1, 2, 3, 4, 5, 6, 7], 'täglich'],
    // Zwei aufeinanderfolgende Tage bleiben eine Aufzählung — „Mo–Di" wäre
    // länger als „Mo, Di" und läse sich als Spanne über nichts.
    'two in a row stay listed' => [[1, 2], 'Mo, Di'],
    'gaps stay listed' => [[1, 3, 5], 'Mo, Mi, Fr'],
    'a span next to a single day' => [[1, 2, 3, 6], 'Mo–Mi, Sa'],
    'a single day' => [[7], 'So'],
]);

test('a dynamic habit shows its situation as the schedule label', function () {
    $habit = Habit::factory()->make(['trigger_situation' => 'nach dem Aufstehen']);

    expect($habit->scheduleLabel())->toBe('nach dem Aufstehen');
});

test('the wizard receives every way of anchoring a habit', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('habits.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('habits/create')
            // Situation, feste Uhrzeit, Anschluss an eine andere. Die vierte
            // Form — „wenn es sich ergibt" — ist bewusst weg: Der Katalog
            // kennt nur noch planbare Aktivitäten.
            ->has('scheduleTypes', 3)
            // Die Situation steht vorn: sie ist die Empfehlung, nicht nur eine
            // von drei gleichrangigen Optionen (time-blocking.md).
            ->where('scheduleTypes.0.value', ScheduleType::Dynamic->value)
            ->has('scheduleTypes.0.description')
        );
});

test('creating a habit that is not due today says when it will be', function () {
    // Ein Samstag: die Mo–Fr-Gewohnheit steht heute nicht in der Tagesliste.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $this->actingAs(User::factory()->create())
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::VorlesungNachbereiten->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '17:00',
            'scheduled_days' => [1, 2, 3, 4, 5],
        ])
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('habitCreated.title', 'Vorlesung nachbereiten')
        ->assertInertiaFlash('habitCreated.when', 'am Montag um 17:00')
        ->assertInertiaFlash('habitCreated.scheduledToday', false);
});

test('a habit due today is confirmed as due today', function () {
    // Ein Montag.
    Carbon::setTestNow(Carbon::parse('2026-08-03'));

    $this->actingAs(User::factory()->create())
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::VorlesungNachbereiten->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '17:00',
            'scheduled_days' => [1, 2, 3, 4, 5],
        ])
        ->assertInertiaFlash('habitCreated.when', 'heute um 17:00')
        ->assertInertiaFlash('habitCreated.scheduledToday', true);
});

test('the day after today is named as tomorrow', function () {
    // Ein Sonntag — der nächste Mo–Fr-Termin ist der Folgetag.
    Carbon::setTestNow(Carbon::parse('2026-08-09'));

    $this->actingAs(User::factory()->create())
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::VorlesungNachbereiten->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '17:00',
            'scheduled_days' => [1, 2, 3, 4, 5],
        ])
        ->assertInertiaFlash('habitCreated.when', 'morgen um 17:00');
});

test('a situational habit counts from today', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Meditieren->value,
            'target_amount' => 10,
            'schedule_type' => ScheduleType::Dynamic->value,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertInertiaFlash('habitCreated.when', 'ab heute')
        ->assertInertiaFlash('habitCreated.scheduledToday', true);
});

test('the next occurrence of a fixed habit skips the days it is not planned for', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $habit = Habit::factory()->fixedSchedule(days: [1, 2, 3, 4, 5])->create();

    expect($habit->nextOccurrence()->toDateString())->toBe('2026-08-10');
});

test('a situational habit is due every day', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    expect(Habit::factory()->create()->nextOccurrence()->isToday())->toBeTrue();
});

test('the habits page lists what is due first, then the following days', function () {
    // Ein Samstag: heute steht nur die situative Gewohnheit an, die anderen
    // liegen am Montag und am Mittwoch.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('09:00', [3])->create([
        'title' => 'Am Mittwoch',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->fixedSchedule('09:00', [1])->create([
        'title' => 'Am Montag',
        'position' => 1,
    ]);
    Habit::factory()->for($user)->create([
        'title' => 'Heute',
        'trigger_situation' => 'nach dem Aufstehen',
        'position' => 2,
    ]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.title', 'Heute')
            ->where('habits.1.title', 'Am Montag')
            ->where('habits.2.title', 'Am Mittwoch')
            ->where('habits.0.nextOccurrence', 'heute')
            ->where('habits.1.nextOccurrence', 'am Montag')
            ->where('habits.2.nextOccurrence', 'am Mittwoch')
        );
});

test('within the same day the habits page reads from morning to evening', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-03'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('19:00', [1, 2, 3, 4, 5])->create([
        'title' => 'Abends',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->fixedSchedule('06:30', [1, 2, 3, 4, 5])->create([
        'title' => 'Früh',
        'position' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.title', 'Früh')
            ->where('habits.1.title', 'Abends')
        );
});

test('the habits page marks which habits belong in the today block', function () {
    // Ein Samstag: die Mo–Fr-Gewohnheit steht heute nicht an.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->create([
        'title' => 'Heute',
        'trigger_situation' => 'nach dem Aufstehen',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->fixedSchedule('09:00', [1, 2, 3, 4, 5])->create([
        'title' => 'Später',
        'position' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.group', 'today')
            ->where('habits.1.group', 'later')
        );
});

test('a chained habit shares the block of the habit it hangs on', function () {
    // Samstag: Der Anker läuft Mo–Fr, also stehen beide erst später an.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    $anchor = Habit::factory()->for($user)->fixedSchedule('17:00', [1, 2, 3, 4, 5])->create([
        'title' => 'Spaziergang',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->create([
        'title' => 'Dehnen',
        'schedule_type' => ScheduleType::Chained,
        'chained_to_habit_id' => $anchor->id,
        'trigger_situation' => null,
        'scheduled_time' => null,
        'scheduled_days' => null,
        'position' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.group', 'later')
            ->where('habits.1.group', 'later')
        );
});

test('tomorrow is named as such instead of by its weekday', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('09:00', [7])->create();

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.nextOccurrence', 'morgen'));
});

test('a habit without a chosen weekday has no next occurrence and sorts last', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    // Über die Validierung ginge das nicht — im Bestand kann es die Zeile
    // trotzdem geben, und sie darf die Liste nicht anführen.
    Habit::factory()->for($user)->fixedSchedule('06:00', [])->create([
        'title' => 'Ohne Tag',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->create([
        'title' => 'Heute',
        'trigger_situation' => 'vor dem Schlafengehen',
        'position' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.title', 'Heute')
            ->where('habits.1.title', 'Ohne Tag')
            ->where('habits.1.nextOccurrence', null)
        );
});

test('the overview carries the limit so the interface never hardcodes it', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('maxActive', Habit::MaxActivePerUser));

    $this->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('maxActive', Habit::MaxActivePerUser));
});

/**
 * Eine Uhrzeit je Wochentag.
 *
 * `scheduled_time` galt für alle Tage. Das reicht für die meisten, aber nicht
 * für den Alltag, den es abbilden soll: Wer dienstags um acht Vorlesung hat
 * und donnerstags um zehn, lernt nicht an beiden Tagen zur selben Zeit nach.
 */
test('a habit can start at a different time on each weekday', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Karteikarten->value,
            'target_amount' => 20,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '13:00',
            'scheduled_days' => [1, 2],
            'scheduled_times' => [1 => '13:00', 2 => '16:00'],
        ])
        ->assertSessionHasNoErrors();

    $habit = $user->habits()->sole();
    $montag = Carbon::today()->next(Carbon::MONDAY);
    $dienstag = Carbon::today()->next(Carbon::TUESDAY);

    expect($habit->scheduled_times)->toBe([1 => '13:00', 2 => '16:00'])
        ->and($habit->startsAt($montag)?->format('H:i'))->toBe('13:00')
        ->and($habit->startsAt($dienstag)?->format('H:i'))->toBe('16:00')
        // Ohne Datum sagt die Zeile, dass es mehrere sind, statt eine zu
        // behaupten: „13:00 · Mo, Di" wäre für den Dienstag gelogen.
        ->and($habit->scheduleLabel())->toBe('wechselnd · Mo, Di')
        ->and($habit->scheduleLabel($dienstag))->toBe('16:00 · Mo, Di');
});

/**
 * Dieselbe Zeit an allen Tagen ist keine Abbildung, sondern eine Zahl.
 *
 * Sieben gleiche Einträge liefen beim nächsten Ändern der gemeinsamen Zeit
 * auseinander — und die Beschriftung müsste „wechselnd" sagen, wo nichts
 * wechselt.
 */
test('identical times collapse back into one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Karteikarten->value,
            'target_amount' => 20,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '13:00',
            'scheduled_days' => [1, 2],
            'scheduled_times' => [1 => '13:00', 2 => '13:00'],
        ])
        ->assertSessionHasNoErrors();

    $habit = $user->habits()->sole();

    expect($habit->scheduled_times)->toBeNull()
        ->and($habit->hasVaryingTimes())->toBeFalse()
        ->and($habit->scheduleLabel())->toBe('13:00 · Mo, Di');
});

/**
 * Jeder Tag wird mit seiner eigenen Zeit geprüft.
 *
 * Vorher stellte die Prüfung eine Frage für alle Tage; seit jeder Wochentag
 * eine eigene Uhrzeit haben kann, sind es sieben. Eine Kollision, die nur den
 * Dienstag trifft, muss den Dienstag treffen — und nur ihn.
 */
test('a per day time is checked against that day alone', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->fromTemplate(HabitTemplate::Joggen)
        ->fixedSchedule('16:00', [2])->withMeasure(30)->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Karteikarten->value,
            'target_amount' => 20,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '13:00',
            'scheduled_days' => [1, 2],
            // Montag ist frei, Dienstag läuft in „Joggen gehen".
            'scheduled_times' => [1 => '13:00', 2 => '16:00'],
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect($user->habits()->count())->toBe(1);

    // Derselbe Dienstag zwei Stunden später geht durch.
    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Karteikarten->value,
            'target_amount' => 20,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '13:00',
            'scheduled_days' => [1, 2],
            'scheduled_times' => [1 => '13:00', 2 => '18:00'],
        ])
        ->assertSessionHasNoErrors();

    expect($user->habits()->count())->toBe(2);
});
