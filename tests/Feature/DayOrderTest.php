<?php

use App\Ai\Agents\SuggestDayOrder;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\User;
use App\Support\DayPlan;
use Illuminate\Support\Carbon;
use Laravel\Ai\Prompts\AgentPrompt;

/**
 * Den ganzen Tag neu ordnen.
 *
 * Die Einzelanpassung verschiebt eine Gewohnheit; hier geht es um die Frage
 * danach — wie liegt der Tag insgesamt. Was die KI zurückgibt, wird geprüft,
 * nicht geglaubt: gegen den Rahmen, gegen die Dauern und gegeneinander.
 */

/** Ein Montag, damit die Wochentage der Testdaten verlässlich greifen. */
function orderingMonday(): Carbon
{
    Carbon::setTestNow(Carbon::parse('2026-09-07 09:00'));

    return Carbon::parse('2026-09-07');
}

test('the AI is given the frame and every habit of that day', function () {
    $monday = orderingMonday();

    SuggestDayOrder::fake([[
        'order' => [
            ['id' => 1, 'time' => '08:00'],
            ['id' => 2, 'time' => '10:00'],
        ],
        'reason' => 'Erst Bewegung, dann Ruhe.',
    ]]);

    $user = User::factory()->create();
    $first = Habit::factory()->for($user)->fixedSchedule('17:00', [1])->withMeasure(30)->create();
    $second = Habit::factory()->for($user)->fixedSchedule('18:00', [1])->withMeasure(20)->create();

    SuggestDayOrder::fake([[
        'order' => [
            ['id' => $first->id, 'time' => '08:00'],
            ['id' => $second->id, 'time' => '10:00'],
        ],
        'reason' => 'Erst Bewegung, dann Ruhe.',
    ]]);

    $this->actingAs($user)
        ->postJson(route('calendar.order.suggestions'), ['date' => $monday->toDateString()])
        ->assertOk()
        ->assertJsonPath('reason', 'Erst Bewegung, dann Ruhe.')
        ->assertJsonPath('order.0.time', '08:00')
        // Die Spanne rechnet der Server, nicht die KI.
        ->assertJsonPath('order.0.timeRange', '08:00 – 08:30');

    SuggestDayOrder::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('Wacher Tag: 07:00 bis 23:00')
            && $prompt->contains($first->title),
    );
});

test('a day that does not fit is refused with a reason, not a shrug', function () {
    $monday = orderingMonday();

    $user = User::factory()->create();
    // Vier Stunden Gewohnheiten passen, sechzehn nicht.
    foreach (range(1, 5) as $ignored) {
        Habit::factory()->for($user)->fixedSchedule('09:00', [1])->withMeasure(240)->create();
    }

    $this->actingAs($user)
        ->postJson(route('calendar.order.suggestions'), ['date' => $monday->toDateString()])
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'brauchen zusammen'));

    // Die KI wird gar nicht erst gefragt — das ist eine Rechnung, keine
    // Einschätzung.
    SuggestDayOrder::assertNotPrompted(fn (AgentPrompt $prompt): bool => true);
});

test('a single habit has no order to speak of', function () {
    $monday = orderingMonday();

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('09:00', [1])->withMeasure(30)->create();

    $this->actingAs($user)
        ->postJson(route('calendar.order.suggestions'), ['date' => $monday->toDateString()])
        ->assertStatus(422);

    SuggestDayOrder::assertNotPrompted(fn (AgentPrompt $prompt): bool => true);
});

test('an order that drops a habit is no order at all', function () {
    $monday = orderingMonday();

    $user = User::factory()->create();
    $first = Habit::factory()->for($user)->fixedSchedule('17:00', [1])->withMeasure(30)->create();
    Habit::factory()->for($user)->fixedSchedule('18:00', [1])->withMeasure(20)->create();

    // Nur eine von zwei — der Tag hätte danach eine Gewohnheit weniger.
    SuggestDayOrder::fake([[
        'order' => [['id' => $first->id, 'time' => '08:00']],
        'reason' => 'Unvollständig.',
    ]]);

    $this->actingAs($user)
        ->postJson(route('calendar.order.suggestions'), ['date' => $monday->toDateString()])
        ->assertStatus(503);
});

test('an order outside the waking day is refused', function () {
    $monday = orderingMonday();

    $user = User::factory()->create();
    $first = Habit::factory()->for($user)->fixedSchedule('17:00', [1])->withMeasure(30)->create();
    $second = Habit::factory()->for($user)->fixedSchedule('18:00', [1])->withMeasure(20)->create();

    // 05:00 liegt vor der Aufstehzeit.
    SuggestDayOrder::fake([[
        'order' => [
            ['id' => $first->id, 'time' => '05:00'],
            ['id' => $second->id, 'time' => '10:00'],
        ],
        'reason' => 'Zu früh.',
    ]]);

    $this->actingAs($user)
        ->postJson(route('calendar.order.suggestions'), ['date' => $monday->toDateString()])
        ->assertStatus(503);
});

test('an order that overlaps itself is refused', function () {
    $monday = orderingMonday();

    $user = User::factory()->create();
    $first = Habit::factory()->for($user)->fixedSchedule('17:00', [1])->withMeasure(60)->create();
    $second = Habit::factory()->for($user)->fixedSchedule('18:00', [1])->withMeasure(20)->create();

    // Die zweite beginnt, während die erste noch läuft.
    SuggestDayOrder::fake([[
        'order' => [
            ['id' => $first->id, 'time' => '09:00'],
            ['id' => $second->id, 'time' => '09:30'],
        ],
        'reason' => 'Überschneidet sich.',
    ]]);

    $this->actingAs($user)
        ->postJson(route('calendar.order.suggestions'), ['date' => $monday->toDateString()])
        ->assertStatus(503);
});

test('taking over the order puts every habit on a fixed time', function () {
    $monday = orderingMonday();

    $user = User::factory()->create();
    $situational = Habit::factory()->for($user)->create([
        'trigger_situation' => 'nach dem Aufstehen',
    ]);
    $fixed = Habit::factory()->for($user)->fixedSchedule('18:00', [1])->withMeasure(20)->create();

    $this->actingAs($user)
        ->post(route('calendar.order.store'), [
            'date' => $monday->toDateString(),
            'order' => [
                ['id' => $situational->id, 'time' => '08:00'],
                ['id' => $fixed->id, 'time' => '10:00'],
            ],
        ])
        ->assertRedirect();

    expect($situational->refresh())
        ->schedule_type->toBe(ScheduleType::Fixed)
        ->scheduled_time->format('H:i')->toBe('08:00')
        // Die Situation geht dabei verloren — das ist der Preis einer
        // ausgerechneten Reihenfolge, und er steht so im Vorschlag.
        ->trigger_situation->toBeNull();

    expect($fixed->refresh())
        ->scheduled_time->format('H:i')->toBe('10:00')
        // Die Wochentage bleiben: Umgeordnet wird der Tag, nicht die Woche.
        ->scheduled_days->toBe([1]);
});

test('a foreign habit cannot be reordered into my day', function () {
    $monday = orderingMonday();

    $stranger = Habit::factory()->create(['trigger_situation' => 'nach dem Aufstehen']);
    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('09:00', [1])->create();

    $this->actingAs($user)->post(route('calendar.order.store'), [
        'date' => $monday->toDateString(),
        'order' => [['id' => $stranger->id, 'time' => '08:00']],
    ]);

    expect($stranger->refresh()->trigger_situation)->toBe('nach dem Aufstehen');
});

test('the day plan reads the frame and the gaps between habits', function () {
    $monday = orderingMonday();

    $user = User::factory()->create();
    $habits = collect([
        Habit::factory()->for($user)->fixedSchedule('09:00', [1])->withMeasure(60)->create(),
        Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create(),
    ]);
    $habits->each(fn (Habit $habit) => $habit->setRelation('user', $user));

    $plan = DayPlan::for($habits, $monday->dayOfWeekIso, $user->sleepWindows());

    expect($plan->frame())->toBe(['from' => 420, 'to' => 1380])
        ->and($plan->freeWindowLabels(30))->toBe([
            '07:00 bis 08:45',   // vor der ersten, mit Atempause
            '10:15 bis 13:45',   // dazwischen
            '14:45 bis 23:00',   // danach bis zur Schlafenszeit
        ])
        // Zwölf Stunden am Stück gibt dieser Tag nicht mehr her.
        ->and($plan->hasRoomFor(720))->toBeFalse();
});
