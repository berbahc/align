<?php

use App\Ai\Agents\SuggestBetterAnchor;
use App\Enums\ScheduleType;
use App\Models\Course;
use App\Models\Habit;
use App\Models\Semester;
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
            // Ohne Stundenplan gibt es diese Situation nicht zu wählen — sie
            // wird verworfen, statt an einer erfundenen Stunde zu landen.
            ['situation' => 'nach der Vorlesung', 'reason' => 'Danach ist ohnehin eine Pause.'],
        ],
    ]]);

    $user = User::factory()->create();
    $habit = neglectedHabit($user, [
        'title' => 'Laufen gehen',
        'trigger_situation' => 'vor dem Schlafengehen',
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonCount(1, 'alternatives')
        ->assertJsonPath('alternatives.0.situation', 'nach dem Aufstehen')
        // Die Stunde bestimmt der Server, damit der Ghost auf der Achse landen kann.
        ->assertJsonPath('alternatives.0.anchorHour', 7);
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

/**
 * Die KI muss den Tag kennen, um darin etwas verschieben zu können — was
 * schon darin steht, ist der Grund, warum ein Moment frei ist oder nicht.
 */
test('the other habits travel along so the AI knows the day', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['situation' => 'nach dem Aufstehen', 'reason' => 'Morgens ist der Tag noch ruhig.']],
    ]]);

    $user = User::factory()->create();
    $habit = neglectedHabit($user, [
        'title' => 'Lesen',
        'trigger_situation' => 'nach der Vorlesung',
    ]);
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
 * Bis hierher durfte die KI Momente erfinden — „nachdem ich die Laufschuhe
 * ausgezogen habe" klingt plausibel, steht aber in keinem Tag und in keiner
 * Auswahl. Der Tag besteht aus den Gewohnheiten und dem Schlafrhythmus; was
 * es dort nicht gibt, lässt sich nicht einplanen.
 */
test('an invented moment never reaches the interface', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['situation' => 'nachdem ich die Laufschuhe ausgezogen habe', 'reason' => 'Erfunden.'],
            ['situation' => 'bevor ich das Essen vorkoche', 'reason' => 'Auch erfunden.'],
            ['situation' => 'nach dem Aufstehen', 'reason' => 'Steht in der Liste.'],
        ],
    ]]);

    $user = User::factory()->create();
    $habit = neglectedHabit($user, ['trigger_situation' => 'nach der Vorlesung']);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonCount(1, 'alternatives')
        ->assertJsonPath('alternatives.0.situation', 'nach dem Aufstehen');
});

test('a moment that another habit already holds is not offered', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['situation' => 'vor dem Schlafengehen', 'reason' => 'Schon vergeben.'],
            ['situation' => 'nach dem Aufstehen', 'reason' => 'Frei.'],
        ],
    ]]);

    $user = User::factory()->create();
    $habit = neglectedHabit($user, ['trigger_situation' => 'nach der Vorlesung']);
    Habit::factory()->for($user)->create(['trigger_situation' => 'vor dem Schlafengehen']);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonCount(1, 'alternatives')
        ->assertJsonPath('alternatives.0.situation', 'nach dem Aufstehen');
});

/**
 * Eine Uhrzeit, die sich mit etwas überschneidet, das dort schon steht, ist
 * keine Alternative, sondern eine Doppelbuchung. Geprüft wird die ganze
 * Dauer: Eine Stunde ab 16:50 passt nicht in ein Fenster, das um 17:00 endet.
 */
test('a time that collides with another habit is dropped', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['time' => '09:00', 'days' => [1], 'reason' => 'Mitten in der anderen Gewohnheit.'],
            ['time' => '14:00', 'days' => [1], 'reason' => 'Da ist Platz.'],
        ],
    ]]);

    $user = User::factory()->create();
    // Montags 09:00–10:00 ist belegt.
    Habit::factory()->for($user)->fixedSchedule('09:00', [1])->withMeasure(60)->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('17:00', [1])->withMeasure(30)->create([
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonCount(1, 'alternatives')
        ->assertJsonPath('alternatives.0.time', '14:00');
});

test('the free windows travel into the prompt', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['time' => '14:00', 'days' => [1], 'reason' => 'Passt.']],
    ]]);

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('17:00', [1])->withMeasure(30)->create([
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk();

    SuggestBetterAnchor::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('Freie Fenster im Tag'),
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
            ['situation' => 'vor dem Schlafengehen', 'reason' => 'Bleibt, wie es ist.'],
            ['situation' => 'nach dem Aufstehen', 'reason' => 'Ruhiger Start.'],
        ],
    ]]);

    $user = User::factory()->create();
    $habit = neglectedHabit($user, ['trigger_situation' => 'vor dem Schlafengehen']);

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
    $habit = neglectedHabit($user, ['trigger_situation' => 'vor dem Schlafengehen']);

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
    $habit = neglectedHabit($user, ['trigger_situation' => 'vor dem Schlafengehen']);

    // Der einzige Weg zurück — Gewohnheiten lassen sich sonst nirgends
    // bearbeiten.
    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), [
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertInertiaFlash('habitAdjusted.previousLabel', 'vor dem Schlafengehen')
        ->assertInertiaFlash('habitAdjusted.anchor', 'nach dem Aufstehen')
        ->assertInertiaFlash('habitAdjusted.previous.trigger_situation', 'vor dem Schlafengehen');
});

test('an empty situation is refused', function () {
    $user = User::factory()->create();
    $habit = neglectedHabit($user, ['trigger_situation' => 'vor dem Schlafengehen']);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), ['trigger_situation' => ''])
        ->assertSessionHasErrors('trigger_situation');

    expect($habit->fresh()->trigger_situation)->toBe('vor dem Schlafengehen');
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
    $foreign = Habit::factory()->create(['trigger_situation' => 'vor dem Schlafengehen']);

    $this->actingAs($user);

    $this->postJson(route('habits.adjustment.suggestions', $foreign))->assertForbidden();
    $this->post(route('habits.adjustment.store', $foreign), [
        'trigger_situation' => 'nach dem Aufstehen',
    ])->assertForbidden();

    expect($foreign->fresh()->trigger_situation)->toBe('vor dem Schlafengehen');
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

/**
 * Die Anpassung kannte zwei Planungsarten, es gibt aber drei.
 *
 * Eine gekettete Gewohnheit fiel in den situativen Zweig: Sie bekam eine
 * `trigger_situation`, behielt sichtbar ihren alten Anker — denn die Spalte
 * liest bei ihr niemand — und sperrte nebenbei einen Moment, den sie gar nicht
 * belegte. Verschoben wurde nichts.
 */
test('a chained habit moves to a moment and leaves its chain', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('17:00')->withMeasure(20)->create([
        'title' => 'Spazieren gehen',
    ]);
    $read = Habit::factory()->for($user)->withoutMeasure()->create([
        'title' => 'Lesen',
        'schedule_type' => ScheduleType::Chained,
        'chained_to_habit_id' => $walk->id,
        'trigger_situation' => null,
    ]);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $read), [
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertRedirect();

    expect($read->fresh())
        ->schedule_type->toBe(ScheduleType::Dynamic)
        ->trigger_situation->toBe('nach dem Aufstehen')
        ->chained_to_habit_id->toBeNull()
        // Und der Kalender zeigt den neuen Anker, nicht mehr den Vorgänger.
        ->scheduleLabel()->toBe('nach dem Aufstehen');
});

test('a chained habit moves into a free window and leaves its chain', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('17:00')->withMeasure(20)->create();
    $read = Habit::factory()->for($user)->withoutMeasure()->create([
        'schedule_type' => ScheduleType::Chained,
        'chained_to_habit_id' => $walk->id,
        'trigger_situation' => null,
    ]);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $read), [
            'scheduled_time' => '09:00',
            'scheduled_days' => [2, 4],
        ])
        ->assertRedirect();

    expect($read->fresh())
        ->schedule_type->toBe(ScheduleType::Fixed)
        ->scheduled_time->format('H:i')->toBe('09:00')
        ->scheduled_days->toBe([2, 4])
        ->chained_to_habit_id->toBeNull();
});

/**
 * Wer ein Zeitfenster wählt, wechselt die Form seiner Planung — und wer den
 * Weg zurückgeht, muss ihn auch zurückwechseln können.
 */
test('a situational habit can move to a fixed time and back', function () {
    $user = User::factory()->create();
    $habit = neglectedHabit($user, ['trigger_situation' => 'vor dem Schlafengehen']);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), [
            'scheduled_time' => '09:00',
            'scheduled_days' => [1, 2],
        ])
        // Der Rückweg trägt genau die Felder, mit denen sich das rückgängig
        // machen lässt — und keine der neuen.
        ->assertInertiaFlash('habitAdjusted.previous.trigger_situation', 'vor dem Schlafengehen');

    expect($habit->fresh())
        ->schedule_type->toBe(ScheduleType::Fixed)
        // Der alte Moment bleibt nicht als Altwert stehen: Er wäre unsichtbar
        // und sperrte trotzdem.
        ->trigger_situation->toBeNull();

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), [
            'trigger_situation' => 'vor dem Schlafengehen',
        ])
        ->assertRedirect();

    expect($habit->fresh())
        ->schedule_type->toBe(ScheduleType::Dynamic)
        ->trigger_situation->toBe('vor dem Schlafengehen')
        ->scheduled_time->toBeNull();
});

test('the way back re-attaches a chained habit', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('17:00')->withMeasure(20)->create([
        'title' => 'Spazieren gehen',
    ]);
    $read = Habit::factory()->for($user)->withoutMeasure()->create([
        'schedule_type' => ScheduleType::Chained,
        'chained_to_habit_id' => $walk->id,
        'trigger_situation' => null,
    ]);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $read), [
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertInertiaFlash('habitAdjusted.previousLabel', 'nach „Spazieren gehen"')
        ->assertInertiaFlash('habitAdjusted.previous.chained_to_habit_id', $walk->id);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $read), [
            'chained_to_habit_id' => $walk->id,
        ])
        ->assertRedirect();

    expect($read->fresh())
        ->schedule_type->toBe(ScheduleType::Chained)
        ->chained_to_habit_id->toBe($walk->id)
        ->trigger_situation->toBeNull();
});

test('a chain that would close on itself is refused', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('17:00')->withMeasure(20)->create();
    $read = Habit::factory()->for($user)->withoutMeasure()->create([
        'schedule_type' => ScheduleType::Chained,
        'chained_to_habit_id' => $walk->id,
        'trigger_situation' => null,
    ]);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $read), ['chained_to_habit_id' => $read->id])
        ->assertSessionHasErrors('chained_to_habit_id');

    expect($read->fresh()->chained_to_habit_id)->toBe($walk->id);
});

/**
 * Beide Formen nebeneinander: Wenn ein Moment nicht trägt, ist eine Uhrzeit
 * manchmal genau die Antwort — und umgekehrt.
 */
test('a situational habit is offered free windows too', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['situation' => 'nach dem Aufstehen', 'time' => '', 'days' => [], 'reason' => 'Ein freier Moment.'],
            ['situation' => '', 'time' => '14:00', 'days' => [1], 'reason' => 'Ein freies Fenster.'],
        ],
    ]]);

    $user = User::factory()->create();
    $habit = neglectedHabit($user, [
        'trigger_situation' => 'nach der Vorlesung',
        'target_amount' => 30,
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonCount(2, 'alternatives')
        ->assertJsonPath('alternatives.0.situation', 'nach dem Aufstehen')
        ->assertJsonPath('alternatives.1.time', '14:00')
        ->assertJsonPath('alternatives.1.anchorHour', 14);
});

test('a fixed habit is offered free moments too', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['situation' => 'nach dem Aufstehen', 'time' => '', 'days' => [], 'reason' => 'Morgens ist es ruhig.'],
        ],
    ]]);

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('17:00')->create([
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonPath('alternatives.0.situation', 'nach dem Aufstehen');
});

test('both lists travel into the prompt, whatever the habit is', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['situation' => 'nach dem Aufstehen', 'time' => '', 'days' => [], 'reason' => 'Frei.']],
    ]]);

    $user = User::factory()->create();
    $habit = neglectedHabit($user, [
        'trigger_situation' => 'nach der Vorlesung',
        'target_amount' => 30,
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk();

    SuggestBetterAnchor::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('Freie Momente')
            && $prompt->contains('Freie Fenster im Tag'),
    );
});

test('an adjustment without any new time is refused', function () {
    $user = User::factory()->create();
    $habit = neglectedHabit($user, ['trigger_situation' => 'vor dem Schlafengehen']);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), ['suggestion_id' => 1])
        ->assertSessionHasErrors('trigger_situation');

    expect($habit->fresh()->trigger_situation)->toBe('vor dem Schlafengehen');
});

/**
 * Die Naht zum Semesterplan — bewiesen ohne eine Zeile im Agenten.
 *
 * Die freien Fenster kommen aus `DayPlan`, und `DayPlan` kennt seit dem
 * Stundenplan auch die Vorlesungen. Der Agent bekommt deshalb eine Liste, in
 * der die Vorlesungszeit gar nicht mehr vorkommt — er *kann* sie nicht mehr
 * vorschlagen.
 */
test('a lecture is missing from the free windows the AI is handed', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['time' => '14:00', 'days' => [1], 'reason' => 'Passt.']],
    ]]);

    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->create();
    Course::factory()->for($semester)->onWeekday(1)->at('08:00', '09:30')
        ->create(['title' => 'Analysis I']);

    $habit = Habit::factory()->for($user)->fixedSchedule('17:00', [1])->withMeasure(30)->create([
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk();

    SuggestBetterAnchor::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('Freie Fenster im Tag')
            // Das Fenster vor der Vorlesung endet eine Atempause davor, statt
            // bis zur Schlafenszeit durchzulaufen.
            && $prompt->contains('07:45')
            && ! $prompt->contains('08:00 bis'),
    );
});

/**
 * Und die Gegenprobe: Was das Modell trotzdem in eine Vorlesung legt, fällt
 * serverseitig durch — nicht, weil der Agent es besser wüsste, sondern weil
 * die Zeit in keinem der Fenster liegt, die er bekommen hat.
 */
test('a time inside a lecture is dropped even when the model returns it', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['situation' => '', 'time' => '08:30', 'days' => [1], 'reason' => 'Mitten in der Vorlesung.'],
            ['situation' => '', 'time' => '14:00', 'days' => [1], 'reason' => 'Der Nachmittag ist frei.'],
        ],
    ]]);

    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->create();
    Course::factory()->for($semester)->onWeekday(1)->at('08:00', '09:30')->create();

    $habit = Habit::factory()->for($user)->fixedSchedule('17:00', [1])->withMeasure(30)->create([
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonCount(1, 'alternatives')
        ->assertJsonPath('alternatives.0.time', '14:00');
});

/**
 * Und die Naht hält auch, wenn das Semester noch nicht angefangen hat: Die
 * Fenster gelten an beiden Daten — nächste Woche *und* am ersten
 * Vorlesungstag. Ein Vorschlag, der nur nächste Woche kennt, läge im Oktober
 * mitten in der Vorlesung.
 */
test('a lecture in a semester that has not started yet is already missing from the windows', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['time' => '14:00', 'days' => [1], 'reason' => 'Passt.']],
    ]]);

    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->between(
        Carbon::today()->addMonth()->toDateString(),
        Carbon::today()->addMonths(5)->toDateString(),
    )->create();
    Course::factory()->for($semester)->onWeekday(1)->at('08:00', '09:30')
        ->create(['title' => 'Analysis I']);

    $habit = Habit::factory()->for($user)->fixedSchedule('17:00', [1])->withMeasure(30)->create([
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk();

    SuggestBetterAnchor::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('07:45')
            && ! $prompt->contains('08:00 bis'),
    );
});

/**
 * Eine bestehende Gewohnheit ist der zuverlässigste Auslöser, den es gibt:
 * Sie hat eine feste Stelle im Tag und weiß ihre Uhrzeit selbst — anders als
 * eine Situation, die nur ungefähr weiß, wann sie stattfindet.
 */
test('every habit of the day is offered as something to hang on', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['situation' => '', 'time' => '', 'days' => [], 'afterHabit' => 'Abendessen', 'reason' => 'Danach sitzt man ohnehin.'],
        ],
    ]]);

    $user = User::factory()->create();
    $dinner = Habit::factory()->for($user)->fixedSchedule('18:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(30)->create(['title' => 'Abendessen']);
    $habit = neglectedHabit($user, ['title' => 'Lesen', 'trigger_situation' => 'vor dem Schlafengehen']);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonPath('alternatives.0.chainToId', $dinner->id)
        ->assertJsonPath('alternatives.0.chainToTitle', 'Abendessen')
        // Die Stunde erbt sie vom Vorgänger: 18:00 plus 30 Minuten.
        ->assertJsonPath('alternatives.0.anchorHour', 18);

    // Und jede Gewohnheit des Tages steht dem Agenten zur Wahl.
    SuggestBetterAnchor::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('Bestehende Gewohnheiten, an die du anknüpfen kannst')
            && $prompt->contains('Abendessen'),
    );
});

test('a habit the AI invented to hang on never reaches the interface', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['situation' => '', 'time' => '', 'days' => [], 'afterHabit' => 'Yoga im Park', 'reason' => 'Erfunden.'],
            ['situation' => 'nach dem Aufstehen', 'time' => '', 'days' => [], 'afterHabit' => '', 'reason' => 'Die gibt es.'],
        ],
    ]]);

    $user = User::factory()->create();
    $habit = neglectedHabit($user, ['trigger_situation' => 'vor dem Schlafengehen']);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->assertJsonCount(1, 'alternatives')
        ->assertJsonPath('alternatives.0.situation', 'nach dem Aufstehen');
});

/**
 * Sonst schlüge die KI einen Kreis vor: Was an dieser Gewohnheit hängt, kann
 * nicht gleichzeitig ihr Anker sein.
 */
test('what already hangs on the habit is no anchor for it', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [
            ['situation' => '', 'time' => '', 'days' => [], 'afterHabit' => 'Lesen', 'reason' => 'Wäre ein Kreis.'],
        ],
    ]]);

    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('18:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(30)->create(['title' => 'Spazieren gehen', 'created_at' => Carbon::today()->subDays(20)]);
    Habit::factory()->for($user)->withMeasure(20)->create([
        'title' => 'Lesen',
        'schedule_type' => ScheduleType::Chained,
        'chained_to_habit_id' => $walk->id,
        'trigger_situation' => null,
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $walk))
        ->assertStatus(503);
});

test('taking a chain hangs the habit on the other one', function () {
    $user = User::factory()->create();
    $dinner = Habit::factory()->for($user)->fixedSchedule('18:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(30)->create(['title' => 'Abendessen']);
    $habit = neglectedHabit($user, ['trigger_situation' => 'vor dem Schlafengehen']);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), [
            'chained_to_habit_id' => $dinner->id,
        ])
        ->assertRedirect();

    expect($habit->fresh())
        ->schedule_type->toBe(ScheduleType::Chained)
        ->chained_to_habit_id->toBe($dinner->id)
        ->trigger_situation->toBeNull();
});
