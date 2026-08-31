<?php

use App\Ai\Agents\SuggestBetterAnchor;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Ai\Prompts\AgentPrompt;

/**
 * Legt eine Gewohnheit an, die es seit zwei Wochen gibt und die nie erfüllt wurde.
 */
function neglectedHabit(User $user, array $attributes = []): Habit
{
    return Habit::factory()->for($user)->create([
        'created_at' => Carbon::today()->subDays(20),
        ...$attributes,
    ]);
}

test('a situational habit is offered other situations', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['situation' => 'nach dem Aufstehen', 'reason' => 'Morgens ist der Tag noch ruhig.'],
            ['situation' => 'nach dem Mittagessen', 'reason' => 'Danach ist ohnehin eine Pause.'],
        ],
    ]]);

    $user = User::factory()->create();
    $habit = neglectedHabit($user, [
        'title' => 'Laufen gehen',
        'trigger_situation' => 'wenn ich nach Hause komme',
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonPath('alternatives.0.situation', 'nach dem Aufstehen')
        ->assertJsonPath('alternatives.1.situation', 'nach dem Mittagessen')
        // Die Stunde bestimmt der Server, damit der Ghost auf der Achse landen kann.
        ->assertJsonPath('alternatives.0.anchorHour', 7)
        ->assertJsonPath('alternatives.1.anchorHour', 13);
});

test('a fixed habit is offered other times', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['time' => '07:30', 'days' => [1, 2, 3, 4, 5], 'reason' => 'Vor der Uni ist der Kopf frei.'],
        ],
    ]]);

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('17:00')->create([
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonPath('alternatives.0.time', '07:30')
        ->assertJsonPath('alternatives.0.days', [1, 2, 3, 4, 5])
        ->assertJsonPath('alternatives.0.anchorHour', 7);
});

test('the observation counts the days that actually passed unused', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['time' => '07:30', 'days' => [1, 2, 3, 4, 5], 'reason' => 'Ruhiger Start.']],
    ]]);

    $user = User::factory()->create();
    // Mo–Fr über zwei Wochen, drei Tage davon erfüllt.
    $habit = Habit::factory()->for($user)->fixedSchedule('17:00', [1, 2, 3, 4, 5])->create([
        'title' => 'Laufen gehen',
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $scheduled = collect(range(13, 0))
        ->map(fn (int $offset): Carbon => Carbon::today()->subDays($offset))
        ->filter(fn (Carbon $day): bool => $habit->isScheduledOn($day));

    $scheduled->take(3)->each(fn (Carbon $day) => $habit->completions()->create([
        'completed_on' => $day,
        'completed_at' => $day->copy()->setTime(17, 0),
    ]));

    $expected = $scheduled->count() - 3;

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonPath('observation', sprintf(
            '„Laufen gehen" stand in den letzten zwei Wochen %d× an, ohne dass etwas geschah.',
            $expected,
        ));
});

test('a habit without a single miss is told so instead of being nagged', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['situation' => 'nach dem Aufstehen', 'reason' => 'Ruhiger Start.']],
    ]]);

    $user = User::factory()->create();
    // Der Auslöser steht fest, damit der Vorschlag nicht zufällig derselbe ist:
    // Die Factory würfelt ihn aus fünf Situationen, und ein Vorschlag, der dem
    // aktuellen Anker entspricht, wird verworfen — bei „nach dem Aufstehen"
    // bliebe dann keine Alternative übrig und der Endpunkt antwortete mit 503.
    $habit = Habit::factory()->for($user)->create([
        'title' => 'Wasser trinken',
        'trigger_situation' => 'vor dem Schlafengehen',
        'created_at' => Carbon::today(),
    ]);
    $habit->completions()->create([
        'completed_on' => Carbon::today(),
        'completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonPath('observation', '„Wasser trinken" läuft bisher ohne Ausfall.');
});

test('days before the habit existed are not counted against it', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['situation' => 'nach dem Aufstehen', 'reason' => 'Ruhiger Start.']],
    ]]);

    $user = User::factory()->create();
    // Gestern angelegt, heute und gestern offen — mehr kann es nicht sein.
    // Der Auslöser steht fest, damit der Vorschlag nicht zufällig derselbe ist.
    $habit = Habit::factory()->for($user)->create([
        'title' => 'Lesen',
        'trigger_situation' => 'vor dem Schlafengehen',
        'created_at' => Carbon::today()->subDay(),
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonPath('observation', '„Lesen" stand in den letzten zwei Wochen 2× an, ohne dass etwas geschah.');
});

test('the other habits travel along so the AI can propose a chain', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['situation' => 'nach dem Zähneputzen', 'reason' => 'Hängt an etwas, das ohnehin passiert.']],
    ]]);

    $user = User::factory()->create();
    $habit = neglectedHabit($user, ['title' => 'Lesen']);
    Habit::factory()->for($user)->create([
        'title' => 'Zähneputzen',
        'trigger_situation' => 'vor dem Schlafengehen',
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk();

    SuggestBetterAnchor::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('Zähneputzen'),
    );
});

/**
 * Die App hat bewusst keinen Ersatz für einen ausgefallenen KI-Aufruf.
 */
test('a failing call answers plainly instead of inventing a time', function () {
    SuggestBetterAnchor::fake(function (): never {
        throw new RuntimeException('Anbieter nicht erreichbar');
    });

    $user = User::factory()->create();
    $habit = neglectedHabit($user);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertStatus(503)
        ->assertJsonMissingPath('alternatives')
        ->assertJsonStructure(['message']);
});

test('an alternative that repeats the current anchor is dropped', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['situation' => 'wenn ich nach Hause komme', 'reason' => 'Bleibt, wie es ist.'],
            ['situation' => 'nach dem Aufstehen', 'reason' => 'Ruhiger Start.'],
        ],
    ]]);

    $user = User::factory()->create();
    $habit = neglectedHabit($user, ['trigger_situation' => 'wenn ich nach Hause komme']);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonCount(1, 'alternatives')
        ->assertJsonPath('alternatives.0.situation', 'nach dem Aufstehen');
});

test('a malformed time from the model never reaches the interface', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['time' => 'morgens früh', 'days' => [1], 'reason' => 'Unbrauchbar.'],
            ['time' => '25:00', 'days' => [1], 'reason' => 'Gibt es nicht.'],
            ['time' => '08:00', 'days' => [9], 'reason' => 'Kein Wochentag.'],
            ['time' => '08:00', 'days' => [1, 2], 'reason' => 'Brauchbar.'],
        ],
    ]]);

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('17:00')->create([
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonCount(1, 'alternatives')
        ->assertJsonPath('alternatives.0.time', '08:00');
});

test('a suggestion outside the sleep frame never reaches the interface', function () {
    // Ein Vorschlag um sechs, wenn der Tag um sieben beginnt, würde beim
    // Übernehmen abgewiesen — er wird deshalb schon hier verworfen, genau wie
    // eine ungültige Uhrzeit.
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['time' => '06:00', 'days' => [1, 2], 'reason' => 'Vor der Aufstehzeit.'],
            ['time' => '23:45', 'days' => [1], 'reason' => 'Nach der Schlafenszeit.'],
            ['time' => '08:00', 'days' => [1, 2], 'reason' => 'Brauchbar.'],
        ],
    ]]);

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('17:00')->create([
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonCount(1, 'alternatives')
        ->assertJsonPath('alternatives.0.time', '08:00');
});

test('the frame travels into the prompt so the AI knows the day', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['time' => '08:00', 'days' => [1], 'reason' => 'Passt.']],
    ]]);

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('17:00')->create([
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk();

    SuggestBetterAnchor::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('07:00 bis 23:00'),
    );
});

test('taking over a time outside the frame is refused', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('17:00', [1, 2])->create();

    // Die Route lässt sich auch von Hand ansprechen — die Grenze gehört an
    // die Stelle, an der geschrieben wird.
    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), [
            'scheduled_time' => '05:00',
            'scheduled_days' => [1, 2],
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect($habit->fresh()->scheduled_time->format('H:i'))->toBe('17:00');
});

test('an answer without a usable alternative counts as a failure', function () {
    SuggestBetterAnchor::fake([['alternatives' => [['situation' => '  ', 'reason' => 'Leer.']]]]);

    $user = User::factory()->create();
    $habit = neglectedHabit($user);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertStatus(503);
});

test('taking over moves the habit', function () {
    $user = User::factory()->create();
    $habit = neglectedHabit($user, ['trigger_situation' => 'wenn ich nach Hause komme']);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), [
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertRedirect();

    expect($habit->fresh())
        ->trigger_situation->toBe('nach dem Aufstehen')
        ->schedule_type->toBe(ScheduleType::Dynamic);
});

test('taking over a time keeps the habit fixed', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('17:00', [1, 2, 3, 4, 5])->create();

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), [
            'scheduled_time' => '07:30',
            'scheduled_days' => [2, 4],
        ])
        ->assertRedirect();

    expect($habit->fresh())
        ->scheduled_time->format('H:i')->toBe('07:30')
        ->scheduled_days->toBe([2, 4])
        ->schedule_type->toBe(ScheduleType::Fixed);
});

test('the confirmation carries the way back', function () {
    $user = User::factory()->create();
    $habit = neglectedHabit($user, ['trigger_situation' => 'wenn ich nach Hause komme']);

    // Der einzige Weg zurück — Gewohnheiten lassen sich sonst nirgends
    // bearbeiten.
    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), [
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertInertiaFlash('habitAdjusted.previousLabel', 'wenn ich nach Hause komme')
        ->assertInertiaFlash('habitAdjusted.anchor', 'nach dem Aufstehen')
        ->assertInertiaFlash('habitAdjusted.previous.trigger_situation', 'wenn ich nach Hause komme');
});

test('an empty situation is refused', function () {
    $user = User::factory()->create();
    $habit = neglectedHabit($user, ['trigger_situation' => 'wenn ich nach Hause komme']);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), ['trigger_situation' => ''])
        ->assertSessionHasErrors('trigger_situation');

    expect($habit->fresh()->trigger_situation)->toBe('wenn ich nach Hause komme');
});

test('a time without a weekday is refused', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('17:00')->create();

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), [
            'scheduled_time' => '07:30',
            'scheduled_days' => [],
        ])
        ->assertSessionHasErrors('scheduled_days');

    expect($habit->fresh()->scheduled_time->format('H:i'))->toBe('17:00');
});

test('a foreign habit stays out of reach', function () {
    $user = User::factory()->create();
    $foreign = Habit::factory()->create(['trigger_situation' => 'wenn ich nach Hause komme']);

    $this->actingAs($user);

    $this->postJson(route('habits.adjustment.suggestions', $foreign))->assertForbidden();
    $this->post(route('habits.adjustment.store', $foreign), [
        'trigger_situation' => 'nach dem Aufstehen',
    ])->assertForbidden();

    expect($foreign->fresh()->trigger_situation)->toBe('wenn ich nach Hause komme');
    SuggestBetterAnchor::assertNeverPrompted();
});

test('guests get no suggestions', function () {
    $habit = Habit::factory()->create();

    $this->postJson(route('habits.adjustment.suggestions', $habit))->assertUnauthorized();
});

test('the suggestion endpoint is rate limited', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['situation' => 'nach dem Aufstehen', 'reason' => 'Ruhiger Start.']],
    ]]);

    $user = User::factory()->create();
    $habit = neglectedHabit($user);

    $this->actingAs($user);

    foreach (range(1, 20) as $ignored) {
        $this->postJson(route('habits.adjustment.suggestions', $habit));
    }

    $this->postJson(route('habits.adjustment.suggestions', $habit))->assertStatus(429);
});
