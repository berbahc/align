<?php

use App\Enums\HabitTemplate;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\SleepSchedule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Der Schlafplan — der Rahmen, in dem Gewohnheiten geplant werden.
 *
 * Kein Tracking und keine Gewohnheit: Er wird nicht abgehakt und hat keine
 * Serie. Er begrenzt, wann sich feste Uhrzeiten legen lassen, kündigt die
 * Schlafenszeit an und weckt am Morgen.
 */

/** Ein gültiger Sieben-Tage-Plan, einzelne Tage überschreibbar. */
function sleepPlan(array $overrides = []): array
{
    $days = [];

    foreach (range(1, 7) as $weekday) {
        $days[] = [
            'weekday' => $weekday,
            'wake_time' => '07:00',
            'bedtime' => '23:00',
            'alarm_enabled' => false,
            ...($overrides[$weekday] ?? []),
        ];
    }

    return [
        'days' => $days,
        'bedtime_reminder_enabled' => true,
    ];
}

test('the frame exists before anyone has set it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('sleep.show'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('sleep')
            ->has('windows', 7)
            // Gespeichert ist nichts — der Rahmen gilt trotzdem, als
            // Voreinstellung statt als Lücke.
            ->where('windows.0.wakeTime', SleepSchedule::DefaultWakeTime)
            ->where('windows.0.bedtime', SleepSchedule::DefaultBedtime)
            ->where('windows.0.alarmEnabled', false)
            ->where('bedtimeReminderEnabled', true)
            ->where('reminderLeadMinutes', SleepSchedule::BedtimeReminderLeadMinutes)
        );
});

test('the plan stores every weekday on its own', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('sleep.update'), sleepPlan([
            1 => ['wake_time' => '06:30', 'alarm_enabled' => true],
            6 => ['wake_time' => '09:30', 'bedtime' => '23:45'],
        ]))
        ->assertSessionHasNoErrors();

    expect($user->sleepSchedules()->count())->toBe(7)
        ->and($user->sleepWindowFor(1))->toMatchArray(['wakeTime' => '06:30', 'alarmEnabled' => true])
        ->and($user->sleepWindowFor(6))->toMatchArray(['wakeTime' => '09:30', 'bedtime' => '23:45'])
        ->and($user->sleepWindowFor(3)['wakeTime'])->toBe('07:00');
});

test('saving twice updates instead of duplicating', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('sleep.update'), sleepPlan());
    $this->actingAs($user)->put(route('sleep.update'), sleepPlan([
        2 => ['bedtime' => '22:00'],
    ]))->assertSessionHasNoErrors();

    expect($user->sleepSchedules()->count())->toBe(7)
        ->and($user->refresh()->sleepWindowFor(2)['bedtime'])->toBe('22:00');
});

test('a plan needs all seven days and distinct times', function () {
    $user = User::factory()->create();

    // Sechs Tage sind keine Woche.
    $plan = sleepPlan();
    array_pop($plan['days']);

    $this->actingAs($user)
        ->put(route('sleep.update'), $plan)
        ->assertSessionHasErrors('days');

    // Aufstehen und Schlafen zur selben Minute beschreibt keinen Tag.
    $this->actingAs($user)
        ->put(route('sleep.update'), sleepPlan([
            4 => ['wake_time' => '07:00', 'bedtime' => '07:00'],
        ]))
        ->assertSessionHasErrors('days.3.bedtime');
});

test('the bedtime reminder can be switched off', function () {
    $user = User::factory()->create();

    $plan = sleepPlan();
    $plan['bedtime_reminder_enabled'] = false;

    $this->actingAs($user)->put(route('sleep.update'), $plan);

    expect($user->refresh()->bedtime_reminder_enabled)->toBeFalse();
});

test('a fixed habit outside the frame is refused', function () {
    $user = User::factory()->create();

    // 06:00 liegt vor der voreingestellten Aufstehzeit von 07:00.
    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Joggen->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '06:00',
            'scheduled_days' => [1, 3, 5],
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect($user->habits()->count())->toBe(0);
});

test('widening the frame makes the same time plannable', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('sleep.update'), sleepPlan([
        1 => ['wake_time' => '05:30'],
        3 => ['wake_time' => '05:30'],
        5 => ['wake_time' => '05:30'],
    ]));

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Joggen->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '06:00',
            'scheduled_days' => [1, 3, 5],
        ])
        ->assertSessionHasNoErrors();

    expect($user->habits()->count())->toBe(1);
});

test('the frame is checked for every chosen weekday on its own', function () {
    $user = User::factory()->create();

    // Nur montags beginnt der Tag früh genug — der Mittwoch weist ab.
    $this->actingAs($user)->put(route('sleep.update'), sleepPlan([
        1 => ['wake_time' => '05:30'],
    ]));

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Joggen->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '06:00',
            'scheduled_days' => [1, 3],
        ])
        ->assertSessionHasErrors('scheduled_time');
});

test('a bedtime after midnight keeps the late evening awake', function () {
    // Schlafenszeit 00:30 heißt: bis nach Mitternacht wach.
    expect(SleepSchedule::containsTime('07:00', '00:30', '23:30'))->toBeTrue()
        ->and(SleepSchedule::containsTime('07:00', '00:30', '00:15'))->toBeTrue()
        ->and(SleepSchedule::containsTime('07:00', '00:30', '03:00'))->toBeFalse()
        ->and(SleepSchedule::containsTime('07:00', '23:00', '23:30'))->toBeFalse();
});

test('every page shares the frame of the surrounding days', function () {
    // Ein Mittwoch.
    Carbon::setTestNow(Carbon::parse('2026-08-26 12:00'));

    $user = User::factory()->create();

    $this->actingAs($user)->put(route('sleep.update'), sleepPlan([
        2 => ['bedtime' => '22:30'],
        3 => ['wake_time' => '06:00'],
        4 => ['alarm_enabled' => true],
    ]));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sleep.today.wakeTime', '06:00')
            ->where('sleep.yesterday.bedtime', '22:30')
            ->where('sleep.tomorrow.alarmEnabled', true)
            ->where('sleep.reminderEnabled', true)
            ->where('sleep.leadMinutes', SleepSchedule::BedtimeReminderLeadMinutes)
        );
});

test('the dashboard frames the day with tonight and tomorrow morning', function () {
    // Ein Freitag: Schlafenszeit von Freitag, Aufstehzeit von Samstag.
    Carbon::setTestNow(Carbon::parse('2026-08-28 12:00'));

    $user = User::factory()->create();

    $this->actingAs($user)->put(route('sleep.update'), sleepPlan([
        5 => ['bedtime' => '23:45'],
        6 => ['wake_time' => '09:30', 'alarm_enabled' => true],
    ]));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sleepCard.bedtime', '23:45')
            ->where('sleepCard.wakeTime', '09:30')
            ->where('sleepCard.alarmEnabled', true)
        );
});

test('the calendar carries the frame of the shown day', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-26 12:00'));

    $user = User::factory()->create();

    $this->actingAs($user)->put(route('sleep.update'), sleepPlan([
        6 => ['wake_time' => '09:30', 'bedtime' => '23:45'],
    ]));

    // Der nächste Samstag.
    $this->actingAs($user)
        ->get(route('calendar.day', '2026-08-29'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('wakeTime', '09:30')
            ->where('bedtime', '23:45')
        );
});

test('the plan belongs to its person alone', function () {
    $owner = User::factory()->create();
    $this->actingAs($owner)->put(route('sleep.update'), sleepPlan([
        1 => ['wake_time' => '05:00'],
    ]));

    $other = User::factory()->create();

    // Die andere Person sieht ihre eigene Voreinstellung, nicht den fremden Plan.
    $this->actingAs($other)
        ->get(route('sleep.show'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('windows.0.wakeTime', SleepSchedule::DefaultWakeTime)
        );

    expect($other->sleepSchedules()->count())->toBe(0);
});

/**
 * Der Schlafplan ist je Wochentag einstellbar — und das muss sich an den
 * beiden Situationen am Tagesrand auszahlen, sonst ist er Dekoration.
 */
test('a habit after waking follows the wake time of the day it is shown on', function () {
    $user = User::factory()->create();

    // Ein fester Montag und ein fester Samstag, damit der Test nicht vom
    // Wochentag des Laufs abhängt.
    $monday = Carbon::today()->startOfWeek();
    $saturday = $monday->copy()->addDays(5);

    $user->sleepSchedules()->create([
        'weekday' => $monday->dayOfWeekIso, 'wake_time' => '06:30', 'bedtime' => '22:00',
    ]);
    $user->sleepSchedules()->create([
        'weekday' => $saturday->dayOfWeekIso, 'wake_time' => '10:15', 'bedtime' => '23:30',
    ]);

    Habit::factory()->for($user)->withMeasure(20)->create([
        'title' => 'Meditieren',
        'trigger_situation' => 'nach dem Aufstehen',
        'created_at' => $monday->copy()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('calendar.day', $monday->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Auf die Minute, nicht auf 06:00 gerundet — das läge vor dem
            // Aufstehen und damit außerhalb des Tages.
            ->where('blocks.0.startMinute', 6 * 60 + 30)
        );

    $this->actingAs($user)
        ->get(route('calendar.day', $saturday->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.startMinute', 10 * 60 + 15)
        );
});

/**
 * „Vor dem Schlafengehen" endet an der Schlafenszeit, statt eine feste Stunde
 * davor zu beginnen: Zehn Minuten Meditation lägen sonst fünfzig Minuten zu
 * früh, eine Stunde Lesen ragte darüber hinaus.
 */
test('a habit before sleeping ends at bedtime, whatever it takes', function () {
    $user = User::factory()->create();
    $monday = Carbon::today()->startOfWeek();

    $user->sleepSchedules()->create([
        'weekday' => $monday->dayOfWeekIso, 'wake_time' => '07:00', 'bedtime' => '22:45',
    ]);

    $short = Habit::factory()->for($user)->withMeasure(10)->create([
        'title' => 'Meditieren',
        'trigger_situation' => 'vor dem Schlafengehen',
        'created_at' => $monday->copy()->subDay(),
    ]);
    $short->setRelation('user', $user);

    $long = Habit::factory()->for($user)->withMeasure(60)->create([
        'title' => 'Lesen',
        'trigger_situation' => 'vor dem Schlafengehen',
        'created_at' => $monday->copy()->subDay(),
    ]);
    $long->setRelation('user', $user);
    $long->forceFill(['trigger_situation' => 'vor dem Schlafengehen'])->save();
    $long->setRelation('user', $user);

    expect($short->sleepBoundStartMinute($monday))->toBe(22 * 60 + 35)
        ->and($long->sleepBoundStartMinute($monday))->toBe(21 * 60 + 45);
});

/**
 * Eine Schlafenszeit nach Mitternacht liegt jenseits des Tagesrands — der
 * Abendblock rutscht mit, statt an den Morgen zu springen.
 */
test('a bedtime after midnight carries the evening habit with it', function () {
    $user = User::factory()->create();
    $monday = Carbon::today()->startOfWeek();

    $user->sleepSchedules()->create([
        'weekday' => $monday->dayOfWeekIso, 'wake_time' => '07:00', 'bedtime' => '00:30',
    ]);

    $habit = Habit::factory()->for($user)->withMeasure(30)->create([
        'trigger_situation' => 'vor dem Schlafengehen',
        'created_at' => $monday->copy()->subDay(),
    ]);
    $habit->setRelation('user', $user);

    expect($habit->sleepBoundStartMinute($monday))->toBe(24 * 60);
});

/**
 * Wandert der Rahmen, wandern die festen Uhrzeiten mit.
 *
 * Situative Gewohnheiten hielten sich immer schon an den Schlafplan; feste
 * blieben liegen, wo sie lagen — und landeten im Nachtband, sobald jemand
 * später aufstand. Diese Gruppe ist die Gegenprobe dazu.
 */
test('a fixed habit moves by the same amount the wake time did', function () {
    $user = User::factory()->create();

    $breakfast = Habit::factory()->for($user)
        ->withMeasure(20)
        ->fixedSchedule('08:00', [1])
        ->create(['title' => 'Frühstück']);

    $reading = Habit::factory()->for($user)
        ->withMeasure(30)
        ->fixedSchedule('20:00', [1])
        ->create(['title' => 'Lesen']);

    $this->actingAs($user)
        ->put(route('sleep.update'), [
            ...sleepPlan([1 => ['wake_time' => '10:00']]),
            'carry_habits' => true,
        ])
        ->assertSessionHasNoErrors();

    // Drei Stunden später aufgestanden, drei Stunden später gefrühstückt.
    expect($breakfast->refresh()->scheduled_time->format('H:i'))->toBe('11:00')
        // Der Abend lag ohnehin im Rahmen und bleibt deshalb, wo er war.
        ->and($reading->refresh()->scheduled_time->format('H:i'))->toBe('20:00');
});

test('an earlier bedtime pulls the evening forward', function () {
    $user = User::factory()->create();

    $habit = Habit::factory()->for($user)
        ->withMeasure(30)
        ->fixedSchedule('22:00', [1])
        ->create(['title' => 'Lesen']);

    $this->actingAs($user)
        ->put(route('sleep.update'), [
            ...sleepPlan([1 => ['bedtime' => '21:00']]),
            'carry_habits' => true,
        ])
        ->assertSessionHasNoErrors();

    expect($habit->refresh()->scheduled_time->format('H:i'))->toBe('20:00');
});

test('without the go-ahead the plan saves and the habits stay put', function () {
    $user = User::factory()->create();

    $habit = Habit::factory()->for($user)
        ->withMeasure(20)
        ->fixedSchedule('08:00', [1])
        ->create(['title' => 'Frühstück']);

    $this->actingAs($user)
        ->put(route('sleep.update'), sleepPlan([1 => ['wake_time' => '10:00']]))
        ->assertSessionHasNoErrors();

    // Der Schlafplan gehört dem Nutzer — er wird gespeichert, ob etwas
    // mitzieht oder nicht.
    expect($user->refresh()->sleepWindowFor(1)['wakeTime'])->toBe('10:00')
        ->and($habit->refresh()->scheduled_time->format('H:i'))->toBe('08:00');
});

/**
 * Eine Uhrzeit gilt an allen Tagen der Gewohnheit. Verschiebt sich nur der
 * Montag, darf die neue Zeit den Dienstag nicht sprengen.
 */
test('the new time has to work on every day the habit runs', function () {
    $user = User::factory()->create();

    $habit = Habit::factory()->for($user)
        ->withMeasure(30)
        ->fixedSchedule('08:00', [1, 2])
        ->create(['title' => 'Frühstück']);

    // Montags erst um zehn auf, dienstags um sieben ins Bett — der Schnitt
    // beider Tage lässt nur noch 10:00 bis 18:30 zu.
    $this->actingAs($user)
        ->put(route('sleep.update'), [
            ...sleepPlan([
                1 => ['wake_time' => '10:00'],
                2 => ['bedtime' => '19:00'],
            ]),
            'carry_habits' => true,
        ])
        ->assertSessionHasNoErrors();

    $moved = $habit->refresh()->scheduled_time->format('H:i');

    expect($moved)->toBe('11:00')
        ->and(SleepSchedule::containsTime('10:00', '23:00', $moved))->toBeTrue()
        ->and(SleepSchedule::containsTime('07:00', '19:00', $moved))->toBeTrue();
});

test('what fits nowhere stays where it is and says why', function () {
    $user = User::factory()->create();

    // Sechs Stunden am Stück, in einem Tag von 10:00 bis 14:00.
    $habit = Habit::factory()->for($user)
        ->withMeasure(360)
        ->fixedSchedule('08:00', [1])
        ->create(['title' => 'Lernblock']);

    $response = $this->actingAs($user)
        ->postJson(route('sleep.preview'), [
            ...sleepPlan([1 => ['wake_time' => '10:00', 'bedtime' => '14:00']]),
        ]);

    $response->assertOk()
        ->assertJsonCount(0, 'moves')
        ->assertJsonCount(1, 'blocked')
        ->assertJsonPath('blocked.0.title', 'Lernblock');

    $this->actingAs($user)
        ->put(route('sleep.update'), [
            ...sleepPlan([1 => ['wake_time' => '10:00', 'bedtime' => '14:00']]),
            'carry_habits' => true,
        ])
        ->assertSessionHasNoErrors();

    expect($habit->refresh()->scheduled_time->format('H:i'))->toBe('08:00');
});

test('a habit carried onto an occupied slot moves to the next free one', function () {
    $user = User::factory()->create();

    $breakfast = Habit::factory()->for($user)
        ->withMeasure(30)
        ->fixedSchedule('08:00', [1])
        ->create(['title' => 'Frühstück']);

    // Genau dort, wo das Frühstück landen würde.
    Habit::factory()->for($user)
        ->withMeasure(30)
        ->fixedSchedule('11:00', [1])
        ->create(['title' => 'Telefonat']);

    $this->actingAs($user)
        ->put(route('sleep.update'), [
            ...sleepPlan([1 => ['wake_time' => '10:00']]),
            'carry_habits' => true,
        ])
        ->assertSessionHasNoErrors();

    // 11:30 plus die Viertelstunde Luft — der nächste Rasterpunkt ist 11:45.
    expect($breakfast->refresh()->scheduled_time->format('H:i'))->toBe('11:45');
});

test('the preview shows exactly what saving would do', function () {
    $user = User::factory()->create();

    Habit::factory()->for($user)
        ->withMeasure(20)
        ->fixedSchedule('08:00', [1])
        ->create(['title' => 'Frühstück']);

    $plan = sleepPlan([1 => ['wake_time' => '10:00']]);

    $this->actingAs($user)
        ->postJson(route('sleep.preview'), $plan)
        ->assertOk()
        ->assertJsonPath('moves.0.title', 'Frühstück')
        ->assertJsonPath('moves.0.from', '08:00')
        ->assertJsonPath('moves.0.to', '11:00');

    // Die Vorschau hat nichts gespeichert.
    expect($user->refresh()->sleepWindowFor(1)['wakeTime'])->toBe('07:00');
});

/**
 * „Unmittelbar davor bzw. danach": Die beiden Situationen am Tagesrand kleben
 * an ihrer Kante, nicht am Anfang ihrer Spanne.
 *
 * Der Morgen weicht nach hinten aus, der Abend nach vorn. Beides von vorn zu
 * suchen legte den Abendblock an den Anfang seiner Stunde — also eine Stunde
 * vor die Schlafenszeit, sobald dort gerade Platz ist. Das ist nicht, was
 * „vor dem Schlafengehen" heißt.
 */
test('the evening habit ends at bedtime, not an hour before it', function () {
    $monday = Carbon::today()->startOfWeek()->addWeek();
    $user = User::factory()->create();

    $user->sleepSchedules()->create([
        'weekday' => 1, 'wake_time' => '07:00', 'bedtime' => '23:00',
    ]);

    Habit::factory()->for($user)->withMeasure(30)->create([
        'title' => 'Lesen',
        'schedule_type' => ScheduleType::Dynamic,
        'trigger_situation' => 'vor dem Schlafengehen',
        'scheduled_time' => null,
        'scheduled_days' => [1],
        'created_at' => Carbon::today()->subWeek(),
    ]);

    $this->actingAs($user)
        ->get(route('calendar.day', $monday->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // 22:30 bis 23:00 — der Block endet an der Schlafenszeit.
            ->where('blocks.0.startMinute', 22 * 60 + 30)
        );
});

test('the evening habit slides forward when something blocks the last hour', function () {
    $monday = Carbon::today()->startOfWeek()->addWeek();
    $user = User::factory()->create();

    $user->sleepSchedules()->create([
        'weekday' => 1, 'wake_time' => '07:00', 'bedtime' => '23:00',
    ]);

    Habit::factory()->for($user)->withMeasure(60)->fixedSchedule('22:00', [1])->create([
        'title' => 'Telefonat',
        'created_at' => Carbon::today()->subWeek(),
    ]);

    Habit::factory()->for($user)->withMeasure(30)->create([
        'title' => 'Lesen',
        'schedule_type' => ScheduleType::Dynamic,
        'trigger_situation' => 'vor dem Schlafengehen',
        'scheduled_time' => null,
        'scheduled_days' => [1],
        'created_at' => Carbon::today()->subWeek(),
    ]);

    $this->actingAs($user)
        ->get(route('calendar.day', $monday->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // 21:15 bis 21:45, dann die Viertelstunde Luft, dann das Telefonat
            // — so spät wie möglich, aber nicht darüber.
            ->where('blocks.0.startMinute', 21 * 60 + 15)
            ->where('blocks.1.startMinute', 22 * 60)
        );
});

test('the morning habit starts at the wake time and slides back if blocked', function () {
    $monday = Carbon::today()->startOfWeek()->addWeek();
    $user = User::factory()->create();

    $user->sleepSchedules()->create([
        'weekday' => 1, 'wake_time' => '07:00', 'bedtime' => '23:00',
    ]);

    Habit::factory()->for($user)->withMeasure(30)->fixedSchedule('07:00', [1])->create([
        'title' => 'Duschen',
        'created_at' => Carbon::today()->subWeek(),
    ]);

    Habit::factory()->for($user)->withMeasure(20)->create([
        'title' => 'Meditieren',
        'schedule_type' => ScheduleType::Dynamic,
        'trigger_situation' => 'nach dem Aufstehen',
        'scheduled_time' => null,
        'scheduled_days' => [1],
        'created_at' => Carbon::today()->subWeek(),
    ]);

    $this->actingAs($user)
        ->get(route('calendar.day', $monday->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.startMinute', 7 * 60)
            // 07:30 plus die Viertelstunde Luft — direkt hinter dem, was im
            // Weg lag, und nicht irgendwo im Vormittag.
            ->where('blocks.1.startMinute', 7 * 60 + 45)
        );
});

/**
 * Zwei Gewohnheiten, die zusammen ausweichen müssen, dürfen nicht auf
 * demselben Platz landen.
 *
 * Der Fehler war, alle Züge zu rechnen und danach zu schreiben: Der zweite
 * wurde gegen einen Tag geprüft, in dem der erste noch an seiner alten Stelle
 * stand. Im Browser wanderten so „Tagebuch schreiben" und „Frühstücken" beide
 * auf 13:15.
 */
test('two carried habits do not land on the same slot', function () {
    $user = User::factory()->create();

    $first = Habit::factory()->for($user)
        ->withMeasure(15)
        ->fixedSchedule('08:30', [1])
        ->create(['title' => 'Tagebuch schreiben']);

    $second = Habit::factory()->for($user)
        ->withMeasure(30)
        ->fixedSchedule('09:00', [1])
        ->create(['title' => 'Frühstücken']);

    $this->actingAs($user)
        ->put(route('sleep.update'), [
            ...sleepPlan([1 => ['wake_time' => '10:30']]),
            'carry_habits' => true,
        ])
        ->assertSessionHasNoErrors();

    $one = $first->refresh()->scheduled_time->format('H:i');
    $two = $second->refresh()->scheduled_time->format('H:i');

    // Beide um dieselben dreieinhalb Stunden versetzt (07:00 → 10:30): die
    // frühere auf 12:00, die spätere dahinter, mit der Viertelstunde Luft
    // hinter deren fünfzehn Minuten.
    expect($one)->toBe('12:00')
        ->and($two)->toBe('12:30')
        ->and($one)->not->toBe($two);
});

test('the same holds for a single day', function () {
    $monday = Carbon::today()->startOfWeek()->addWeek();
    $user = User::factory()->create();

    $first = Habit::factory()->for($user)
        ->withMeasure(15)
        ->fixedSchedule('08:30', [1])
        ->create(['title' => 'Tagebuch schreiben']);

    $second = Habit::factory()->for($user)
        ->withMeasure(30)
        ->fixedSchedule('09:00', [1])
        ->create(['title' => 'Frühstücken']);

    $this->actingAs($user)->post(route('sleep.days.store'), [
        'date' => $monday->toDateString(),
        'wake_time' => '10:30',
        'carry_habits' => true,
    ])->assertSessionHasNoErrors();

    $one = $first->dayShifts()->firstOrFail()->scheduled_time->format('H:i');
    $two = $second->dayShifts()->firstOrFail()->scheduled_time->format('H:i');

    expect($one)->toBe('12:00')
        ->and($two)->toBe('12:30');
});
