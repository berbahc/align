<?php

use App\Ai\Agents\SuggestBetterAnchor;
use App\Ai\Agents\SuggestSmallestStep;
use App\Ai\UserContext;
use App\Enums\HabitTemplate;
use App\Enums\SuggestionKind;
use App\Models\AiSuggestion;
use App\Models\Habit;
use App\Models\HabitCompletion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Ai\Prompts\AgentPrompt;

/**
 * Das Gedächtnis der KI.
 *
 * Der Punkt des Ganzen: Ohne diese Tabelle beginnt jeder Aufruf bei null, und
 * eine KI, die sich wiederholt, hat nicht mitgedacht, sondern nur geantwortet
 * (align.md Z. 31: „… und daraus lernt").
 */

/** Eine Gewohnheit, die es seit drei Wochen gibt — alt genug für einen Rückblick. */
function rememberedHabit(User $user, array $attributes = []): Habit
{
    return Habit::factory()->for($user)->create([
        'created_at' => Carbon::today()->subDays(20),
        ...$attributes,
    ]);
}

/** Drei Zeitpunkte, wie sie der Agent liefert. */
function threeAnchors(): array
{
    return [[
        'alternatives' => [
            ['situation' => 'nach dem Aufstehen', 'reason' => 'Morgens ist der Tag noch ruhig.'],
            ['situation' => 'nach dem Mittagessen', 'reason' => 'Danach ist ohnehin eine Pause.'],
            ['situation' => 'vor dem Schlafengehen', 'reason' => 'Der Abend ist verlässlich.'],
        ],
    ]];
}

test('every offered anchor is remembered, none of them as taken', function () {
    SuggestBetterAnchor::fake(threeAnchors());

    $user = User::factory()->create();
    $habit = rememberedHabit($user, ['trigger_situation' => 'wenn ich nach Hause komme']);

    $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk();

    $remembered = $user->aiSuggestions()->ofKind(SuggestionKind::Anchor)->get();

    expect($remembered)->toHaveCount(3)
        ->and($remembered->pluck('label')->all())->toBe([
            'nach dem Aufstehen',
            'nach dem Mittagessen',
            'vor dem Schlafengehen',
        ])
        ->and($remembered->whereNotNull('accepted_at'))->toBeEmpty()
        ->and($remembered->every(fn (AiSuggestion $s): bool => $s->habit_id === $habit->id))->toBeTrue();
});

test('a fixed anchor is remembered in the same wording as an existing one', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['time' => '07:30', 'days' => [1, 2, 3, 4, 5], 'reason' => 'Vor der Uni ist der Kopf frei.']],
    ]]);

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('17:00')->create([
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $this->actingAs($user)->postJson(route('habits.adjustment.suggestions', $habit))->assertOk();

    // Derselbe Zuschnitt wie `scheduleLabel()` — der Vorschlag landet später
    // wieder im Prompt und muss dort dieselbe Sprache sprechen wie ein
    // bestehender Anker.
    expect($user->aiSuggestions()->sole()->label)->toBe('07:30 · Mo–Fr');
});

test('taking one anchor marks exactly that one and leaves the others open', function () {
    SuggestBetterAnchor::fake(threeAnchors());

    $user = User::factory()->create();
    $habit = rememberedHabit($user, ['trigger_situation' => 'wenn ich nach Hause komme']);

    $chosen = $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->json('alternatives.1');

    $this->actingAs($user)->post(route('habits.adjustment.store', $habit), [
        'trigger_situation' => $chosen['situation'],
        'suggestion_id' => $chosen['id'],
    ]);

    expect(AiSuggestion::find($chosen['id'])->accepted_at)->not->toBeNull()
        ->and($user->aiSuggestions()->notTaken()->count())->toBe(2);
});

test('the anchor an adjustment offered before travels into the next prompt', function () {
    SuggestBetterAnchor::fake(threeAnchors());

    $user = User::factory()->create();
    $habit = rememberedHabit($user, ['trigger_situation' => 'wenn ich nach Hause komme']);

    $this->actingAs($user)->postJson(route('habits.adjustment.suggestions', $habit))->assertOk();
    $this->actingAs($user)->postJson(route('habits.adjustment.suggestions', $habit))->assertOk();

    // Der zweite Aufruf weiß, was der erste angeboten hat. Ohne diese Zeile
    // könnte die KI dieselben drei Zeitpunkte noch einmal vorschlagen.
    SuggestBetterAnchor::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('nach dem Aufstehen')
            && $prompt->contains('vor dem Schlafengehen'),
    );
});

test('an anchor taken on advice is named as taken, not as refused', function () {
    SuggestBetterAnchor::fake(threeAnchors());

    $user = User::factory()->create();
    $habit = rememberedHabit($user, ['trigger_situation' => 'wenn ich nach Hause komme']);

    $chosen = $this->actingAs($user)
        ->postJson(route('habits.adjustment.suggestions', $habit))
        ->assertOk()
        ->json('alternatives.0');

    $this->actingAs($user)->post(route('habits.adjustment.store', $habit), [
        'trigger_situation' => $chosen['situation'],
        'suggestion_id' => $chosen['id'],
    ]);

    $this->actingAs($user)->postJson(route('habits.adjustment.suggestions', $habit))->assertOk();

    SuggestBetterAnchor::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('Übernommen wurde dagegen: „nach dem Aufstehen"'),
    );
});

/**
 * Ein Vorschlag darf nicht daran scheitern, dass die App ihn sich nicht merken
 * kann. Der Nutzer hat einen Zeitpunkt gewählt, und der gilt.
 */
test('an unknown suggestion id does not block the adjustment', function () {
    $user = User::factory()->create();
    $habit = rememberedHabit($user, ['trigger_situation' => 'wenn ich nach Hause komme']);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), [
            'trigger_situation' => 'nach dem Frühstück',
            'suggestion_id' => 99999,
        ])
        ->assertSessionHasNoErrors();

    expect($habit->fresh()->trigger_situation)->toBe('nach dem Frühstück');
});

test('a suggestion belonging to another habit is not marked as taken', function () {
    $user = User::factory()->create();
    $habit = rememberedHabit($user, ['trigger_situation' => 'wenn ich nach Hause komme']);
    $foreign = AiSuggestion::factory()->for($user)->anchor()->create([
        'habit_id' => rememberedHabit($user)->id,
    ]);

    $this->actingAs($user)->post(route('habits.adjustment.store', $habit), [
        'trigger_situation' => 'nach dem Frühstück',
        'suggestion_id' => $foreign->id,
    ]);

    expect($foreign->fresh()->accepted_at)->toBeNull();
});

test('the reason a habit exists reaches the prompt', function () {
    SuggestSmallestStep::fake([['steps' => ['Geh nur bis zur Tür.']]]);

    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)->create([
        'title' => 'Laufen gehen',
        'motivation' => 'damit ich den Kopf freikriege',
        'smallest_step' => 'Zieh die Laufschuhe an.',
    ]);

    $this->actingAs($user)->postJson(route('habits.smallest-step.smaller', $habit))->assertOk();

    // Der persönlichste Satz der App stand bis hierher in keinem Prompt.
    SuggestSmallestStep::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('damit ich den Kopf freikriege'),
    );
});

test('offered steps are remembered and reach the next prompt', function () {
    SuggestSmallestStep::fake([
        ['steps' => ['Zieh die Laufschuhe an.']],
        ['steps' => ['Geh nur bis zur Tür.']],
    ]);

    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)->create(['title' => 'Laufen gehen']);

    $this->actingAs($user)->postJson(route('habits.smallest-step.smaller', $habit))->assertOk();
    $this->actingAs($user)->postJson(route('habits.smallest-step.smaller', $habit))->assertOk();

    expect($user->aiSuggestions()->ofKind(SuggestionKind::SmallestStep)->count())->toBe(2);

    SuggestSmallestStep::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->contains('Zieh die Laufschuhe an.')
            && $prompt->contains('schon 1× ein Schritt gesucht'),
    );
});

/**
 * K7 gilt unverändert: Ein schwacher Tag darf die Planung nicht dauerhaft nach
 * unten ziehen. Gemerkt wird der Vorschlag trotzdem — als Beobachtung, nicht
 * als Plan.
 */
test('remembering a smaller step still leaves the prepared step untouched', function () {
    SuggestSmallestStep::fake([['steps' => ['Geh nur bis zur Tür.']]]);

    $user = User::factory()->create(['onboarded_at' => now()]);
    $habit = Habit::factory()->for($user)->create(['smallest_step' => 'Zieh die Laufschuhe an.']);

    $this->actingAs($user)->postJson(route('habits.smallest-step.smaller', $habit))->assertOk();

    expect($habit->fresh()->smallest_step)->toBe('Zieh die Laufschuhe an.');
});

test('a step adopted word for word counts as taken', function () {
    SuggestSmallestStep::fake([['steps' => ['Zieh die Laufschuhe an.', 'Stell sie an die Tür.']]]);

    $user = User::factory()->create(['onboarded_at' => now()]);

    $this->actingAs($user)->postJson(route('habits.smallest-step.suggestions'), [
        'template_key' => HabitTemplate::Joggen->value,
    ])->assertOk();

    $this->actingAs($user)->post(route('habits.store'), [
        'template_key' => HabitTemplate::Joggen->value,
        'target_amount' => 30,
        'trigger_situation' => 'wenn ich nach Hause komme',
        'smallest_step' => 'Zieh die Laufschuhe an.',
    ])->assertRedirect();

    $taken = $user->aiSuggestions()->taken()->sole();

    expect($taken->label)->toBe('Zieh die Laufschuhe an.')
        ->and($taken->habit_id)->toBe($user->habits()->sole()->id);
});

/**
 * Das Feld im Wizard ist editierbar. Wer den Vorschlag umformuliert hat, hat
 * etwas Eigenes geschrieben — und die KI darf sich das nicht anrechnen.
 */
test('a step the user rewrote does not count as taken', function () {
    SuggestSmallestStep::fake([['steps' => ['Zieh die Laufschuhe an.']]]);

    $user = User::factory()->create(['onboarded_at' => now()]);

    $this->actingAs($user)->postJson(route('habits.smallest-step.suggestions'), [
        'template_key' => HabitTemplate::Joggen->value,
    ])->assertOk();

    $this->actingAs($user)->post(route('habits.store'), [
        'template_key' => HabitTemplate::Joggen->value,
        'target_amount' => 30,
        'trigger_situation' => 'wenn ich nach Hause komme',
        'smallest_step' => 'Laufschuhe raussuchen und anziehen.',
    ])->assertRedirect();

    expect($user->aiSuggestions()->taken()->count())->toBe(0);
});

test('a rhythm is only named once there is enough to see', function () {
    $user = User::factory()->create();
    $habit = rememberedHabit($user);

    // Einer zu wenig für eine Aussage — alle morgens, und trotzdem still.
    foreach (range(1, UserContext::MinimumCompletionsForRhythm - 1) as $offset) {
        $day = Carbon::today()->subDays($offset);

        HabitCompletion::factory()->for($habit)->create([
            'completed_on' => $day,
            'completed_at' => $day->copy()->setTime(8, 0),
            'created_at' => $day->copy()->setTime(8, 0),
        ]);
    }

    expect(UserContext::for($user, SuggestionKind::Anchor, $habit)->lines())
        ->not->toContain('Sie hakt ihre Gewohnheiten meistens vormittags ab.');

    $day = Carbon::today()->subDays(UserContext::MinimumCompletionsForRhythm);
    HabitCompletion::factory()->for($habit)->create([
        'completed_on' => $day,
        'completed_at' => $day->copy()->setTime(8, 0),
        'created_at' => $day->copy()->setTime(8, 0),
    ]);

    expect(UserContext::for($user, SuggestionKind::Anchor, $habit)->lines())
        ->toContain('Sie hakt ihre Gewohnheiten meistens vormittags ab.');
});

/**
 * Nachgetragene Haken bekommen `endOfDay()` als Uhrzeit. Zählte man sie mit,
 * wäre jede Person, die einmal nachträgt, ein Abendmensch.
 */
test('backdated completions do not turn everyone into an evening person', function () {
    $user = User::factory()->create();
    $habit = rememberedHabit($user);

    foreach (range(1, 14) as $offset) {
        $day = Carbon::today()->subDays($offset);

        HabitCompletion::factory()->for($habit)->create([
            'completed_on' => $day,
            // Wie HabitCompletionController es beim Nachtragen schreibt …
            'completed_at' => $day->copy()->endOfDay(),
            // … und zwar heute, nicht an dem Tag, den es beschreibt.
            'created_at' => Carbon::today()->setTime(9, 0),
        ]);
    }

    expect(UserContext::for($user, SuggestionKind::Anchor, $habit)->lines())
        ->not->toContain('Sie hakt ihre Gewohnheiten meistens abends ab.');
});

test('the memory of one person stays out of another persons prompt', function () {
    $user = User::factory()->create();
    $habit = rememberedHabit($user, ['trigger_situation' => 'wenn ich nach Hause komme']);

    $stranger = User::factory()->create();
    AiSuggestion::factory()->for($stranger)->anchor('nach dem Frühstück')->create([
        'habit_id' => rememberedHabit($stranger)->id,
    ]);

    expect(UserContext::for($user, SuggestionKind::Anchor, $habit)->lines())
        ->not->toContain('Diese Zeitpunkte wurden schon vorgeschlagen und nicht übernommen: „nach dem Frühstück".');
});

test('deleting the account takes the memory with it', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    AiSuggestion::factory()->for($user)->count(3)->create();

    $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password']);

    expect(AiSuggestion::count())->toBe(0);
});
