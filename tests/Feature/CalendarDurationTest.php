<?php

use App\Enums\MeasureUnit;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Die Dauer im Kalender.
 *
 * Nur Minuten sind eine Dauer. „10 Seiten" und „2 Liter" sagen, wie viel — nicht,
 * wie lange. Nur wo eine feste Uhrzeit auf Minuten trifft, entsteht eine belegte
 * Spanne, und nur die darf der Tag als solche zeigen.
 */
test('a fixed habit measured in minutes occupies a span', function () {
    $habit = Habit::factory()
        ->fixedSchedule('17:00')
        ->withMeasure(20)
        ->make();

    expect($habit->durationMinutes())->toBe(20)
        ->and($habit->startsAt()?->format('H:i'))->toBe('17:00')
        ->and($habit->endsAt()?->format('H:i'))->toBe('17:20')
        ->and($habit->timeRangeLabel())->toBe('17:00 – 17:20');
});

test('a measure that is not time is no duration', function (string $unit) {
    $habit = Habit::factory()
        ->fixedSchedule('17:00')
        ->withMeasure(10, MeasureUnit::from($unit))
        ->make();

    expect($habit->durationMinutes())->toBeNull()
        ->and($habit->endsAt())->toBeNull()
        ->and($habit->timeRangeLabel())->toBeNull();
})->with([
    MeasureUnit::Pages->value,
    MeasureUnit::Liters->value,
    MeasureUnit::Times->value,
]);

test('without a clock time there is no span, however long it takes', function () {
    $habit = Habit::factory()->withMeasure(20)->make([
        'trigger_situation' => 'nach dem Aufstehen',
    ]);

    expect($habit->durationMinutes())->toBe(20)
        // Eine Situation ist keine Uhrzeit — sie belegt keinen Platz im Tag.
        ->and($habit->startsAt())->toBeNull()
        ->and($habit->timeRangeLabel())->toBeNull();
});

test('a habit without a measure has neither duration nor span', function () {
    $habit = Habit::factory()->fixedSchedule('17:00')->withoutMeasure()->make();

    expect($habit->durationMinutes())->toBeNull()
        ->and($habit->timeRangeLabel())->toBeNull();
});

test('the span crosses the hour correctly', function () {
    $habit = Habit::factory()->fixedSchedule('17:50')->withMeasure(25)->make();

    expect($habit->timeRangeLabel())->toBe('17:50 – 18:15');
});

test('the calendar ships the span and the measure', function () {
    $user = User::factory()->create();

    // Alle sieben Tage, damit der Test nicht am Wochenende anders ausgeht.
    Habit::factory()->for($user)->fixedSchedule('17:00', [1, 2, 3, 4, 5, 6, 7])->withMeasure(20)->create([
        'title' => 'Spazieren gehen',
        'position' => 0,
    ]);

    Habit::factory()->for($user)->withMeasure(10, MeasureUnit::Pages)->create([
        'title' => 'Lesen',
        'trigger_situation' => 'vor dem Schlafengehen',
        'position' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar-day')
            ->where('blocks.0.title', 'Spazieren gehen')
            ->where('blocks.0.timeRange', '17:00 – 17:20')
            ->where('blocks.0.measureLabel', '20 Min')
            // Für das Stundenraster: die Minute, an der der Block anfängt,
            // seine Höhe — und dass die Stelle eine echte Uhrzeit ist.
            ->where('blocks.0.startMinute', 17 * 60)
            ->where('blocks.0.durationMinutes', 20)
            ->where('blocks.0.exact', true)
            // Seiten belegen keine Spanne, bleiben aber als Umfang sichtbar.
            ->where('blocks.1.title', 'Lesen')
            ->where('blocks.1.timeRange', null)
            ->where('blocks.1.measureLabel', '10 Seiten')
            // „Vor dem Schlafengehen" hat keine Uhrzeit, sondern eine Gegend:
            // eine Stunde vor der Schlafenszeit, und das Raster zeichnet sie
            // gestrichelt statt als Zusage.
            ->where('blocks.1.durationMinutes', null)
            ->where('blocks.1.exact', false)
        );
});
