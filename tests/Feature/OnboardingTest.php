<?php

use App\Enums\BehaviorType;
use App\Models\Habit;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('a freshly registered user is sent to onboarding instead of an empty dashboard', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.show'));
});

test('onboarding offers directions, each with its own student habit suggestions', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->get(route('onboarding.show'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('onboarding')
            ->has('directions', 4)
            ->has('directions.0.suggestions')
            ->has('triggerSuggestions', count(Habit::TriggerSuggestions))
        );
});

test('every direction carries suggestions so the second step is never empty', function () {
    foreach (BehaviorType::cases() as $direction) {
        expect($direction->suggestions())->not->toBeEmpty()
            ->and($direction->label())->not->toBeEmpty()
            ->and($direction->description())->not->toBeEmpty();
    }
});

test('completing onboarding creates a committed habit and releases the dashboard', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->post(route('onboarding.store'), [
            'title' => '10 Seiten lesen',
            'behavior_type' => BehaviorType::Learning->value,
            'trigger_situation' => 'vor dem Schlafengehen',
            'motivation' => 'damit ich abends runterkomme',
        ])
        ->assertRedirect(route('dashboard'));

    $habit = $user->habits()->sole();

    expect($habit->title)->toBe('10 Seiten lesen')
        ->and($habit->trigger_situation)->toBe('vor dem Schlafengehen')
        ->and($habit->motivation)->toBe('damit ich abends runterkomme')
        ->and($habit->behavior_type)->toBe(BehaviorType::Learning)
        // Der letzte Schritt ist das ausdrückliche „Ich nehme mir das vor".
        ->and($habit->committed_at)->not->toBeNull()
        ->and($user->refresh()->onboarded_at)->not->toBeNull();

    $this->get(route('dashboard'))->assertOk();
});

test('skipping onboarding is equivalent to finishing it', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->post(route('onboarding.skip'))
        ->assertRedirect(route('dashboard'));

    expect($user->refresh()->onboarded_at)->not->toBeNull()
        ->and($user->habits()->count())->toBe(0);

    // Wer abbricht, wird nicht erneut in den Ablauf gedrängt.
    $this->get(route('dashboard'))->assertOk();
});

test('onboarding rejects an empty habit', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->post(route('onboarding.store'), [
            'behavior_type' => '',
            'title' => '',
            'trigger_situation' => '',
        ])
        ->assertSessionHasErrors(['behavior_type', 'title', 'trigger_situation']);

    expect($user->refresh()->onboarded_at)->toBeNull();
});

test('a habit can be created from the dashboard later', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('habits.create'))->assertOk();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'title' => 'Morgentraining',
            'behavior_type' => BehaviorType::Movement->value,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertRedirect(route('dashboard'));

    expect($user->habits()->sole()->title)->toBe('Morgentraining');
});

test('new habits are appended to the end of the list', function () {
    $user = User::factory()->create();
    // Titel explizit setzen: die Factory würfelt aus einer festen Liste, in
    // der auch der hier gesuchte Titel vorkommt — sonst ist der Test flaky.
    Habit::factory()->for($user)->create(['title' => 'Erste', 'position' => 0]);
    Habit::factory()->for($user)->create(['title' => 'Zweite', 'position' => 1]);

    $this->actingAs($user)->post(route('habits.store'), [
        'title' => 'Trinken',
        'behavior_type' => BehaviorType::Nutrition->value,
        'trigger_situation' => 'nach dem Mittagessen',
    ]);

    expect($user->habits()->where('title', 'Trinken')->sole()->position)->toBe(2);
});

test('an unknown direction is refused', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'behavior_type' => 'astrologie',
            'title' => 'Sterne deuten',
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertSessionHasErrors('behavior_type');

    expect($user->habits()->count())->toBe(0);
});

test('the sixth active habit is refused by the server, not only by the interface', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->count(Habit::MaxActivePerUser)->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'title' => 'Eine zu viel',
            'behavior_type' => BehaviorType::Other->value,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertSessionHasErrors('title');

    expect($user->habits()->count())->toBe(Habit::MaxActivePerUser);
});

test('a graduated habit frees a slot', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->count(Habit::MaxActivePerUser - 1)->create();
    Habit::factory()->for($user)->graduated()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'title' => 'Passt noch rein',
            'behavior_type' => BehaviorType::Other->value,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertSessionHasNoErrors();

    expect($user->habits()->active()->count())->toBe(Habit::MaxActivePerUser);
});
