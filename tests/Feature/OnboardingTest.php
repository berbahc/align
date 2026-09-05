<?php

use App\Enums\HabitCategory;
use App\Enums\HabitTemplate;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('a freshly registered user is sent to onboarding instead of an empty dashboard', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.show'));
});

test('onboarding starts with the frame, then offers the catalog', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->get(route('onboarding.show'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('onboarding')
            // Ohne gespeicherten Rahmen zeigt die Seite zuerst die Frage nach
            // Aufsteh- und Schlafenszeit.
            ->where('hasSleepSchedule', false)
            ->has('categories', 4)
            ->has('categories.0.templates')
            ->has('triggerSuggestions', 2)
        );
});

test('the onboarding frame is stored for all seven weekdays', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->post(route('onboarding.sleep'), [
            'wake_time' => '06:30',
            'bedtime' => '22:30',
        ])
        ->assertRedirect(route('onboarding.show'));

    expect($user->sleepSchedules()->count())->toBe(7)
        ->and($user->sleepWindowFor(3)['wakeTime'])->toBe('06:30')
        ->and($user->sleepWindowFor(7)['bedtime'])->toBe('22:30');

    // Beim nächsten Aufruf steht die zweite Stufe an: die erste Gewohnheit.
    $this->get(route('onboarding.show'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('hasSleepSchedule', true)
        );
});

test('every category carries templates so the second step is never empty', function () {
    foreach (HabitCategory::cases() as $category) {
        expect($category->templates())->not->toBeEmpty()
            ->and($category->label())->not->toBeEmpty();
    }
});

test('completing onboarding creates a committed habit and releases the dashboard', function () {
    $user = User::factory()->notOnboarded()->create();

    $this->actingAs($user)
        ->post(route('onboarding.store'), [
            'template_key' => HabitTemplate::Lesen->value,
            'target_amount' => 20,
            'trigger_situation' => 'vor dem Schlafengehen',
            'motivation' => 'damit ich abends runterkomme',
        ])
        ->assertRedirect(route('dashboard'));

    $habit = $user->habits()->sole();

    expect($habit->title)->toBe('Lesen')
        ->and($habit->template())->toBe(HabitTemplate::Lesen)
        ->and($habit->trigger_situation)->toBe('vor dem Schlafengehen')
        ->and($habit->motivation)->toBe('damit ich abends runterkomme')
        ->and($habit->behavior_type)->toBe(HabitTemplate::Lesen->behaviorType())
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
            'template_key' => '',
            'trigger_situation' => '',
        ])
        ->assertSessionHasErrors(['template_key', 'trigger_situation', 'target_amount']);

    expect($user->refresh()->onboarded_at)->toBeNull();
});

test('a habit can be created from the dashboard later', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('habits.create'))->assertOk();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Krafttraining->value,
            'target_amount' => 45,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertRedirect(route('dashboard'));

    expect($user->habits()->sole()->title)->toBe('Krafttraining');
});

test('new habits are appended to the end of the list', function () {
    $user = User::factory()->create();
    // Vorlagen und Momente explizit setzen: die Factory würfelt aus dem
    // Katalog, in dem auch die hier gesuchte Vorlage vorkommt — und jeder
    // Moment trägt genau eine Gewohnheit, der dritte muss also frei sein.
    Habit::factory()->for($user)->fromTemplate(HabitTemplate::Joggen)
        ->create(['position' => 0, 'trigger_situation' => 'nach dem Aufstehen']);
    Habit::factory()->for($user)->fromTemplate(HabitTemplate::Lesen)
        ->create(['position' => 1, 'trigger_situation' => 'vor dem Schlafengehen']);

    $this->actingAs($user)->post(route('habits.store'), [
        'template_key' => HabitTemplate::Meditieren->value,
        'target_amount' => 10,
        'trigger_situation' => 'nach der Vorlesung',
    ]);

    expect($user->habits()->where('title', 'Meditieren')->sole()->position)->toBe(2);
});

test('a habit outside the catalog is refused', function () {
    $user = User::factory()->create();

    // Die freie Eingabe ist bewusst weg: Ein Titel im Request ist kein Feld
    // mehr, und ein erfundener Vorlagen-Schlüssel trifft nichts.
    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => 'sterne-deuten',
            'target_amount' => 10,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertSessionHasErrors('template_key');

    expect($user->habits()->count())->toBe(0);
});

test('the sixth active habit is refused by the server, not only by the interface', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->count(Habit::MaxActivePerUser)->create();

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Meditieren->value,
            'target_amount' => 10,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertSessionHasErrors('template_key');

    expect($user->habits()->count())->toBe(Habit::MaxActivePerUser);
});

test('a graduated habit frees a slot', function () {
    $user = User::factory()->create();

    // Die vier hängen an Uhrzeiten, nicht an Momenten: Es gibt nur noch drei
    // Situationen, und der Test braucht genau eine davon frei.
    foreach ([9, 11, 13, 15] as $index => $hour) {
        Habit::factory()->for($user)
            ->fixedSchedule(sprintf('%02d:00', $hour), [1, 2, 3, 4, 5, 6, 7])
            ->withMeasure(30)
            ->create(['position' => $index]);
    }

    // Die beendete Gewohnheit gibt ihren Moment mit frei — sie zählt weder
    // gegen die fünf Plätze noch gegen die Belegung.
    $graduated = Habit::factory()->for($user)->graduated()->create([
        'schedule_type' => ScheduleType::Dynamic,
        'trigger_situation' => 'vor dem Schlafengehen',
        'scheduled_time' => null,
        'scheduled_days' => null,
    ]);

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Meditieren->value,
            'target_amount' => 10,
            'trigger_situation' => $graduated->trigger_situation,
        ])
        ->assertSessionHasNoErrors();

    expect($user->habits()->active()->count())->toBe(Habit::MaxActivePerUser);
});
