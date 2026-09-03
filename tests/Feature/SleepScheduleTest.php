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
        'trigger_situation' => 'wenn ich nach Hause komme',
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
