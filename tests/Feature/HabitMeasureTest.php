<?php

use App\Enums\HabitTemplate;
use App\Enums\MeasureUnit;
use App\Models\Habit;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Die Dauer einer Gewohnheit — Pflicht, in Minuten, verstellbar.
 *
 * Der Katalog enthält nur planbare Aktivitäten, und planbar heißt: Sie
 * belegen eine Spanne im Tag. Diese Tests halten fest, dass jede neue
 * Gewohnheit eine Dauer trägt — und dass alte Zeilen ohne Dauer weiterlaufen,
 * statt zu brechen.
 */
test('a habit is created with a duration in minutes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('habits.store'), [
        'template_key' => HabitTemplate::Spazieren->value,
        'trigger_situation' => 'nach der Vorlesung',
        'target_amount' => 20,
    ])->assertSessionHasNoErrors();

    $habit = $user->habits()->sole();

    expect($habit->target_amount)->toBe(20.0)
        ->and($habit->target_unit)->toBe(MeasureUnit::Minutes)
        ->and($habit->durationMinutes())->toBe(20)
        ->and($habit->measureLabel())->toBe('20 Min');
});

test('a habit without a duration is refused', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('habits.store'), [
        'template_key' => HabitTemplate::Spazieren->value,
        'trigger_situation' => 'nach der Vorlesung',
    ])->assertSessionHasErrors('target_amount');

    expect($user->habits()->count())->toBe(0);
});

/**
 * Die Grenzen stehen in {@see MeasureUnit::Minutes} und nicht in der Regel:
 * 240 Minuten sind ein langer Lerntag, 300 ein Vertipper.
 */
test('the duration has bounds', function (float $amount, bool $valid) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('habits.store'), [
        'template_key' => HabitTemplate::Spazieren->value,
        'trigger_situation' => 'nach der Vorlesung',
        'target_amount' => $amount,
    ]);

    $valid
        ? $response->assertSessionHasNoErrors()
        : $response->assertSessionHasErrors('target_amount');
})->with([
    'zwanzig Minuten' => [20.0, true],
    'vier Stunden' => [240.0, true],
    'fünf Stunden' => [300.0, false],
    'unter dem Minimum' => [2.0, false],
]);

test('a measure reads as a line, whole numbers without a decimal', function () {
    expect(MeasureUnit::Minutes->format(20))->toBe('20 Min')
        // Die übrigen Einheiten existieren nur noch in alten Zeilen — lesbar
        // bleiben müssen sie trotzdem.
        ->and(MeasureUnit::Liters->format(1.5))->toBe('1,5 L')
        ->and(MeasureUnit::Liters->format(2))->toBe('2 L')
        ->and(MeasureUnit::Pages->format(10))->toBe('10 Seiten')
        ->and(MeasureUnit::Times->format(3))->toBe('3 Mal');
});

test('a habit carries its measure into the line that names it', function () {
    $habit = Habit::factory()->make([
        'title' => 'Spazieren gehen',
        'target_amount' => 20,
        'target_unit' => MeasureUnit::Minutes,
    ]);

    expect($habit->measureLabel())->toBe('20 Min')
        ->and($habit->titleWithMeasure())->toBe('Spazieren gehen · 20 Min');
});

test('a legacy habit without a duration keeps working', function () {
    $habit = Habit::factory()->legacy()->withoutMeasure()->make();

    expect($habit->measureLabel())->toBeNull()
        ->and($habit->durationMinutes())->toBeNull()
        ->and($habit->titleWithMeasure())->toBe($habit->title);
});

/**
 * Was der Wizard tatsächlich bekommt — die Kacheln bauen sich aus diesen
 * Feldern.
 */
test('the create page ships the catalog and the duration limits', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);

    $this->actingAs($user)
        ->get(route('habits.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('habits/create')
            // Vier Bereiche, jeder mit Vorlagen — der zweite Schritt darf nie
            // leer sein.
            ->has('categories', 4)
            ->has('categories.0.templates.0', fn (Assert $template) => $template
                ->has('key')
                ->has('title')
                ->has('defaultMinutes')
            )
            // Schrittweite und Grenzen kommen vom Server, damit der Stepper
            // mit der Validierung deckungsgleich bleibt.
            ->has('durationLimits', fn (Assert $limits) => $limits
                ->has('step')
                ->has('min')
                ->has('max')
            )
            // Der Rahmen reist mit, damit der Uhrzeit-Stepper vorher sagen
            // kann, was der Server abweisen würde.
            ->has('sleepWindows', 7)
        );
});

test('no template in the catalog carries a number in its title', function () {
    foreach (HabitTemplate::cases() as $template) {
        // Die Menge steckte früher im Titel („20 Minuten spazieren") und war
        // damit unverstellbar — genau daran scheiterte jede Anpassung.
        expect($template->title())->not->toMatch('/\d/');
        expect($template->defaultMinutes())->toBeGreaterThanOrEqual((int) MeasureUnit::Minutes->min())
            ->toBeLessThanOrEqual((int) MeasureUnit::Minutes->max());
    }
});

test('the duration can be changed later', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fromTemplate(HabitTemplate::Spazieren)->create();

    $this->actingAs($user)
        ->put(route('habits.update', $habit), [
            'trigger_situation' => 'nach der Vorlesung',
            'target_amount' => 35,
        ])
        ->assertSessionHasNoErrors();

    expect($habit->refresh()->measureLabel())->toBe('35 Min');
});
