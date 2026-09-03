<?php

use App\Enums\BehaviorType;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Der Kalender hat zwei Ebenen. Man kommt im Monat an — ein einzelner Tag
 * beantwortet die Frage nicht, wie es gerade läuft.
 */
test('the calendar opens on the month', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('calendar')
            ->where('month', Carbon::today()->format('Y-m'))
            ->where('isCurrentMonth', true)
        );
});

test('the month is drawn in whole weeks, Monday first', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('calendar', ['month' => '2026-09']))
        ->assertInertia(function (AssertableInertia $page) {
            $days = $page->toArray()['props']['days'];

            // September 2026 beginnt an einem Dienstag: Der Montag davor füllt
            // die erste Woche auf und gehört noch zum August.
            expect($days[0]['date'])->toBe('2026-08-31')
                ->and($days[0]['inMonth'])->toBeFalse()
                ->and($days[1]['date'])->toBe('2026-09-01')
                ->and($days[1]['inMonth'])->toBeTrue()
                ->and(count($days) % 7)->toBe(0);
        });
});

/**
 * Punkte statt Quote: Wie viele Gewohnheiten anstanden und wie viele davon
 * liefen — eine Zahl über einem einzelnen Tag wäre eine Note.
 */
test('a day in the month counts what stood and what ran', function () {
    $user = User::factory()->create();
    $done = Habit::factory()->for($user)->create([
        'created_at' => Carbon::today()->subDays(10),
    ]);
    Habit::factory()->for($user)->create([
        'created_at' => Carbon::today()->subDays(10),
    ]);

    $done->completions()->create([
        'completed_on' => Carbon::today(),
        'completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(function (AssertableInertia $page) {
            $today = collect($page->toArray()['props']['days'])
                ->firstWhere('date', Carbon::today()->toDateString());

            expect($today['planned'])->toBe(2)
                ->and($today['done'])->toBe(1)
                ->and($today['isToday'])->toBeTrue()
                ->and($today['isFuture'])->toBeFalse();
        });
});

test('a nonsense month is rejected instead of guessed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('calendar', ['month' => 'irgendwann']))
        ->assertSessionHasErrors('month');
});

test('a day has its own address', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('calendar-day')
            ->where('date', Carbon::today()->toDateString())
            ->where('isToday', true)
        );
});

test('a date shows that day instead', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create();

    $yesterday = Carbon::yesterday()->toDateString();

    $this->actingAs($user)
        ->get(route('calendar.day', $yesterday))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('date', $yesterday)
            ->where('isToday', false)
        );
});

/**
 * Ein Wort, das kein Datum ist, kommt schon am Routenmuster nicht vorbei —
 * ein ziffernrichtiges Nicht-Datum am `hasFormat`-Riegel dahinter.
 */
test('a nonsense date gets no page at all', function (string $written) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/calendar/'.$written)
        ->assertNotFound();
})->with(['irgendwann', '2026-13-45', '2026-02-30']);

test('a Monday to Friday habit is absent on Saturday', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5])->create();

    // Ein fester Samstag, damit der Test nicht vom Wochentag des Laufs abhängt.
    $saturday = Carbon::today()->startOfWeek()->addDays(5);

    $this->actingAs($user)
        ->get(route('calendar.day', $saturday->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('blocks', 0));
});

test('a habit does not appear on days before it existed', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create(['created_at' => Carbon::today()]);

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->subDays(3)->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('blocks', 0));
});

/**
 * Der Tag, an dem sie lief, hat stattgefunden — ihn nachträglich zu leeren wäre
 * eine Geschichtsfälschung.
 */
test('a graduated habit stays visible on the days it was still running', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create([
        'title' => 'Morgentraining',
        'created_at' => Carbon::today()->subDays(10),
        'graduated_at' => Carbon::today()->subDays(2),
    ]);

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->subDays(5)->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('blocks', 1)
            ->where('blocks.0.title', 'Morgentraining')
            ->where('blocks.0.graduated', true)
        );

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('blocks', 0));
});

test('the blocks are ordered along the day, not by creation', function () {
    $user = User::factory()->create();

    // Absichtlich in umgekehrter Tagesreihenfolge angelegt.
    Habit::factory()->for($user)->create([
        'title' => 'Lesen',
        'trigger_situation' => 'vor dem Schlafengehen',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->fixedSchedule('17:00', [1, 2, 3, 4, 5, 6, 7])->create([
        'title' => 'Laufen',
        'position' => 1,
    ]);
    Habit::factory()->for($user)->create([
        'title' => 'Wasser trinken',
        'trigger_situation' => 'nach dem Aufstehen',
        'position' => 2,
    ]);

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.title', 'Wasser trinken')
            ->where('blocks.1.title', 'Laufen')
            ->where('blocks.2.title', 'Lesen')
            ->etc()
        );
});

test('a habit with a made up situation lands in the middle of the day', function () {
    $habit = Habit::factory()->make(['trigger_situation' => 'wenn ich aus der Bib komme']);

    expect($habit->dayAnchorHour())->toBe(Habit::UnknownAnchorHour);
});

test('a completed day carries its tick', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create([
        'created_at' => Carbon::today()->subDays(3),
    ]);
    $habit->completions()->create([
        'completed_on' => Carbon::yesterday(),
        'completed_at' => Carbon::yesterday()->setTime(20, 0),
    ]);

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::yesterday()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('blocks.0.completed', true));

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('blocks.0.completed', false));
});

test('backdating is offered inside the window and withdrawn outside it', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create(['created_at' => Carbon::today()->subDays(30)]);

    $this->actingAs($user);

    $inside = Carbon::today()->subDays(Habit::WeekOverviewDays - 1);
    $outside = $inside->copy()->subDay();

    $this->get(route('calendar.day', $inside->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canComplete', true));

    $this->get(route('calendar.day', $outside->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canComplete', false));
});

test('a future day cannot be ticked off', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::tomorrow()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canComplete', false));
});

/**
 * Ein Pfeil, der in leere Tage führt, verspricht etwas, das er nicht hält.
 */
test('the way back ends at the first habit', function () {
    $user = User::factory()->create();
    $start = Carbon::today()->subDays(3);
    Habit::factory()->for($user)->create(['created_at' => $start]);

    $this->actingAs($user);

    $this->get(route('calendar.day', $start->copy()->addDay()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('previousDate', $start->toDateString())
        );

    $this->get(route('calendar.day', $start->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('previousDate', null));
});

test('a user without habits gets no way back at all', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('previousDate', null)
            ->has('blocks', 0)
        );
});

test('the calendar shows only the habits of the signed in user', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create(['title' => 'Eigene Gewohnheit']);
    Habit::factory()->create(['title' => 'Fremde Gewohnheit']);

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('blocks', 1)
            ->where('blocks.0.title', 'Eigene Gewohnheit')
            ->etc()
        );
});

test('the smallest step travels to the calendar', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create([
        'behavior_type' => BehaviorType::Movement,
        'smallest_step' => 'Zieh die Laufschuhe an.',
    ]);

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.smallestStep', 'Zieh die Laufschuhe an.')
            ->where('blocks.0.behaviorType', 'movement')
            ->etc()
        );
});

test('ticking off from the calendar lands on the shown day', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create(['created_at' => Carbon::today()->subDays(10)]);

    $twoDaysAgo = Carbon::today()->subDays(2);

    $this->actingAs($user)
        ->post(route('habits.completions.store', $habit), [
            'completed_on' => $twoDaysAgo->toDateString(),
        ])
        ->assertRedirect();

    expect($habit->completions()->sole()->completed_on->isSameDay($twoDaysAgo))->toBeTrue();
});

/**
 * Das Stundenraster braucht zu jedem Block eine Minute — und die Auskunft, ob
 * sie eine Zusage ist oder eine Gegend.
 *
 * Beides kommt aus derselben Rechnung, die auch das Time-Blocking benutzt
 * ({@see Habit::dayStartMinute()}). Liefen sie auseinander, stünde ein Block
 * woanders, als der Server ihn belegt glaubt.
 */
test('the grid gets a minute for every block', function () {
    $user = User::factory()->create();

    Habit::factory()->for($user)->fixedSchedule('17:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(20)->create(['title' => 'Spazieren gehen', 'position' => 0]);

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.startMinute', 17 * 60)
            ->where('blocks.0.exact', true)
        );
});

test('a situation lands at its anchor hour and says it is no clock time', function () {
    $user = User::factory()->create();

    Habit::factory()->for($user)->create([
        'title' => 'Lesen',
        'trigger_situation' => 'nach dem Mittagessen',
    ]);

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // 13 Uhr laut Vorschlagsliste — eine Näherung, keine Zusage.
            ->where('blocks.0.startMinute', 13 * 60)
            ->where('blocks.0.exact', false)
        );
});

/**
 * Eine gekettete Gewohnheit erbt ihre Minute vom Vorgänger — sie ist deshalb
 * genauso genau wie er.
 */
test('a chained habit starts where the previous one ends', function () {
    $user = User::factory()->create();

    $walk = Habit::factory()->for($user)->fixedSchedule('17:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(20)->create(['title' => 'Spazieren gehen', 'position' => 0]);

    Habit::factory()->for($user)->withMeasure(15)->create([
        'title' => 'Lesen',
        'schedule_type' => ScheduleType::Chained,
        'chained_to_habit_id' => $walk->id,
        'trigger_situation' => null,
        'position' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.1.title', 'Lesen')
            ->where('blocks.1.startMinute', 17 * 60 + 20)
            ->where('blocks.1.durationMinutes', 15)
            ->where('blocks.1.exact', true)
        );
});

/**
 * Der Rahmen reist als Minute mit, damit das Raster weiß, wo es anfängt und
 * aufhört — eine Schlafenszeit nach Mitternacht zählt dabei über 1440 weiter.
 */
test('the frame travels as minutes so the grid knows its ends', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create();

    $user->sleepSchedules()->create([
        'weekday' => Carbon::today()->dayOfWeekIso,
        'wake_time' => '06:30',
        'bedtime' => '00:30',
    ]);

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('wakeTime', '06:30')
            ->where('bedtime', '00:30')
            ->where('frameFrom', 6 * 60 + 30)
            ->where('frameTo', 24 * 60 + 30)
        );
});
