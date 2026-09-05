<?php

use App\Ai\Agents\SuggestSmallestStep;
use App\Enums\HabitTemplate;
use App\Models\Habit;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Laravel\Ai\Prompts\AgentPrompt;

test('the wizard receives the steps Claude proposed', function () {
    SuggestSmallestStep::fake([
        ['steps' => ['Zieh die Laufschuhe an.', 'Stell sie an die Tür.']],
    ]);

    $user = User::factory()->create(['onboarded_at' => now()]);

    $this->actingAs($user)
        ->postJson(route('habits.smallest-step.suggestions'), [
            'template_key' => HabitTemplate::Joggen->value,
            'trigger_situation' => 'vor dem Schlafengehen',
        ])
        ->assertOk()
        ->assertExactJson([
            'steps' => ['Zieh die Laufschuhe an.', 'Stell sie an die Tür.'],
        ]);
});

test('the situation travels with the prompt so the step fits the moment', function () {
    SuggestSmallestStep::fake([['steps' => ['Stell das Glas ans Bett.']]]);

    $user = User::factory()->create(['onboarded_at' => now()]);

    $this->actingAs($user)->postJson(route('habits.smallest-step.suggestions'), [
        'template_key' => HabitTemplate::Meditieren->value,
        'trigger_situation' => 'nach dem Aufstehen',
    ])->assertOk();

    SuggestSmallestStep::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('nach dem Aufstehen')
            && $prompt->contains('Meditieren'),
    );
});

/**
 * Der wichtigste Test des Features.
 *
 * Die App hat bewusst keinen Ersatz für einen ausgefallenen KI-Aufruf: was wie
 * ein Vorschlag aussieht, muss von der KI stammen. Fällt sie aus, sagt der
 * Endpunkt das — mit 503, weil der Zustand vorübergehend ist, und nicht mit
 * einem 500, das nach einem Programmfehler klingt.
 */
test('a failing call answers with a plain refusal instead of invented steps', function () {
    SuggestSmallestStep::fake(function (): never {
        throw new RuntimeException('Anbieter nicht erreichbar');
    });

    $user = User::factory()->create(['onboarded_at' => now()]);

    $this->actingAs($user)
        ->postJson(route('habits.smallest-step.suggestions'), [
            'template_key' => HabitTemplate::Joggen->value,
        ])
        ->assertStatus(503)
        ->assertJsonMissingPath('steps')
        ->assertJsonStructure(['message']);
});

test('an answer without a usable step counts as a failure', function () {
    SuggestSmallestStep::fake([['steps' => ['', '   ']]]);

    $user = User::factory()->create(['onboarded_at' => now()]);

    $this->actingAs($user)
        ->postJson(route('habits.smallest-step.suggestions'), [
            'template_key' => HabitTemplate::Joggen->value,
        ])
        ->assertStatus(503);
});

test('a step longer than the column allows is dropped', function () {
    SuggestSmallestStep::fake([[
        'steps' => [str_repeat('a', 161), 'Zieh die Schuhe an.'],
    ]]);

    $user = User::factory()->create(['onboarded_at' => now()]);

    $this->actingAs($user)
        ->postJson(route('habits.smallest-step.suggestions'), [
            'template_key' => HabitTemplate::Joggen->value,
        ])
        ->assertOk()
        ->assertExactJson(['steps' => ['Zieh die Schuhe an.']]);
});

test('suggestions need a template to work with', function () {
    SuggestSmallestStep::fake();

    $user = User::factory()->create(['onboarded_at' => now()]);

    $this->actingAs($user)
        ->postJson(route('habits.smallest-step.suggestions'), [])
        ->assertJsonValidationErrors(['template_key']);
});

test('guests get no suggestions', function () {
    $this->postJson(route('habits.smallest-step.suggestions'), [
        'template_key' => HabitTemplate::Joggen->value,
    ])->assertUnauthorized();
});

test('a habit can have its step broken down further', function () {
    SuggestSmallestStep::fake([['steps' => ['Geh nur bis zur Tür.']]]);

    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)->create([
        'title' => 'Laufen gehen',
        'smallest_step' => 'Zieh die Laufschuhe an.',
    ]);

    $this->actingAs($user)
        ->postJson(route('habits.smallest-step.smaller', $habit), [
            'current' => 'Zieh die Laufschuhe an.',
        ])
        ->assertOk()
        ->assertExactJson(['steps' => ['Geh nur bis zur Tür.']]);

    SuggestSmallestStep::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('Zieh die Laufschuhe an.'),
    );
});

test('breaking down leaves the prepared step untouched', function () {
    SuggestSmallestStep::fake([['steps' => ['Geh nur bis zur Tür.']]]);

    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)->create([
        'smallest_step' => 'Zieh die Laufschuhe an.',
    ]);

    $this->actingAs($user)->postJson(route('habits.smallest-step.smaller', $habit), [
        'current' => 'Zieh die Laufschuhe an.',
    ])->assertOk();

    // Ein schwacher Tag darf die Planung nicht dauerhaft nach unten ziehen.
    expect($habit->fresh()->smallest_step)->toBe('Zieh die Laufschuhe an.');
});

test('a habit without a prepared step still gets starting help', function () {
    SuggestSmallestStep::fake([['steps' => ['Leg das Buch aufs Kopfkissen.']]]);

    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)->create(['smallest_step' => null]);

    $this->actingAs($user)
        ->postJson(route('habits.smallest-step.smaller', $habit), [])
        ->assertOk()
        ->assertExactJson(['steps' => ['Leg das Buch aufs Kopfkissen.']]);
});

test('starting help stops at the border to another account', function () {
    SuggestSmallestStep::fake([['steps' => ['Geh nur bis zur Tür.']]]);

    $user = User::factory()->create(['onboarded_at' => now()]);
    $foreign = Habit::factory()->create();

    $this->actingAs($user)
        ->postJson(route('habits.smallest-step.smaller', $foreign), [])
        ->assertForbidden();

    SuggestSmallestStep::assertNeverPrompted();
});

test('the step is stored with the habit', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);

    $this->actingAs($user)->post(route('habits.store'), [
        'template_key' => HabitTemplate::Joggen->value,
        'target_amount' => 30,
        'trigger_situation' => 'vor dem Schlafengehen',
        'smallest_step' => 'Zieh die Laufschuhe an.',
    ])->assertRedirect(route('dashboard'));

    expect($user->habits()->sole()->smallest_step)->toBe('Zieh die Laufschuhe an.');
});

test('the step stays optional', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);

    $this->actingAs($user)->post(route('habits.store'), [
        'template_key' => HabitTemplate::Joggen->value,
        'target_amount' => 30,
        'trigger_situation' => 'vor dem Schlafengehen',
    ])->assertRedirect(route('dashboard'));

    expect($user->habits()->sole()->smallest_step)->toBeNull();
});

test('an overlong step is rejected on creation', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Joggen->value,
            'target_amount' => 30,
            'trigger_situation' => 'vor dem Schlafengehen',
            'smallest_step' => str_repeat('a', 161),
        ])
        ->assertSessionHasErrors('smallest_step');

    expect($user->habits()->count())->toBe(0);
});

test('the dashboard carries the step and the reason into the sheet', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    Habit::factory()->for($user)->create([
        'smallest_step' => 'Zieh die Laufschuhe an.',
        'motivation' => 'damit ich den Kopf freikriege',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.smallestStep', 'Zieh die Laufschuhe an.')
            ->where('habits.0.motivation', 'damit ich den Kopf freikriege')
        );
});

test('the suggestion endpoint is rate limited', function () {
    SuggestSmallestStep::fake([['steps' => ['Zieh die Schuhe an.']]]);

    $user = User::factory()->create(['onboarded_at' => now()]);
    $this->actingAs($user);

    $payload = [
        'template_key' => HabitTemplate::Joggen->value,
    ];

    foreach (range(1, 20) as $ignored) {
        $this->postJson(route('habits.smallest-step.suggestions'), $payload);
    }

    $this->postJson(route('habits.smallest-step.suggestions'), $payload)
        ->assertStatus(429);
});

/**
 * Der gewählte Schritt bleibt an der Gewohnheit stehen — er hakt sie nicht ab.
 *
 * Der Knopf tat vorher beides in einem: Er speicherte den Schritt nicht und
 * trug stattdessen einen erledigten Tag ein. Wer den Plan kleiner macht, hat
 * ihn damit aber noch nicht ausgeführt.
 */
test('the chosen step is kept at the habit', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)->create([
        'smallest_step' => 'Lauf zehn Minuten.',
    ]);

    $this->actingAs($user)
        ->patch(route('habits.smallest-step.update', $habit), [
            'smallest_step' => 'Zieh die Laufschuhe an.',
        ])
        ->assertRedirect();

    expect($habit->fresh()->smallest_step)->toBe('Zieh die Laufschuhe an.');
});

test('keeping a step leaves the day open', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('habits.smallest-step.update', $habit), [
            'smallest_step' => 'Zieh die Laufschuhe an.',
        ])
        ->assertRedirect();

    expect($habit->completions()->count())->toBe(0);
});

/**
 * Ohne diesen Eintrag läse die KI ihren eigenen Vorschlag beim nächsten Mal
 * als „angeboten und liegengelassen".
 */
test('keeping a step marks the suggestion as taken', function () {
    SuggestSmallestStep::fake([['steps' => ['Zieh die Laufschuhe an.']]]);

    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)->postJson(route('habits.smallest-step.smaller', $habit))->assertOk();

    $this->actingAs($user)
        ->patch(route('habits.smallest-step.update', $habit), [
            'smallest_step' => 'Zieh die Laufschuhe an.',
        ])
        ->assertRedirect();

    expect($user->aiSuggestions()->sole())
        ->accepted_at->not->toBeNull()
        ->habit_id->toBe($habit->id);
});

/**
 * Die Strecke schreibt nur dieses eine Feld. Sonst löschte ein Speichern den
 * Zeitpunkt: `PUT habits/{habit}` setzt jeden nicht gesendeten Zweig auf null,
 * und genau deshalb ist das hier eine eigene Route.
 */
test('keeping a step leaves the anchor alone', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)->fixedSchedule('17:00', [1, 3])->create();

    $this->actingAs($user)
        ->patch(route('habits.smallest-step.update', $habit), [
            'smallest_step' => 'Zieh die Laufschuhe an.',
        ])
        ->assertRedirect();

    expect($habit->fresh())
        ->scheduled_time->format('H:i')->toBe('17:00')
        ->scheduled_days->toBe([1, 3]);
});

test('an empty step is refused', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)->create(['smallest_step' => 'Lauf zehn Minuten.']);

    $this->actingAs($user)
        ->patch(route('habits.smallest-step.update', $habit), ['smallest_step' => ''])
        ->assertSessionHasErrors('smallest_step');

    expect($habit->fresh()->smallest_step)->toBe('Lauf zehn Minuten.');
});

test('a foreign habit keeps its step', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    $foreign = Habit::factory()->create(['smallest_step' => 'Lauf zehn Minuten.']);

    $this->actingAs($user)
        ->patch(route('habits.smallest-step.update', $foreign), [
            'smallest_step' => 'Zieh die Laufschuhe an.',
        ])
        ->assertForbidden();

    expect($foreign->fresh()->smallest_step)->toBe('Lauf zehn Minuten.');
});
