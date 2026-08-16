<?php

use App\Enums\BehaviorType;
use App\Enums\MeasureUnit;
use App\Models\Habit;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Der Umfang einer Gewohnheit — Menge und Einheit statt einer Zahl im Titel.
 *
 * Bis hierher trugen die Vorschläge ihre Menge im Namen („20 Minuten
 * spazieren") und niemand konnte sie verstellen. Diese Tests halten fest, dass
 * die Zahl jetzt ein eigenes, veränderliches Feld ist — und ein freiwilliges.
 */
test('a habit can be created with a measure', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('habits.store'), [
        'behavior_type' => BehaviorType::Nutrition->value,
        'title' => 'Wasser trinken',
        'trigger_situation' => 'nach dem Aufstehen',
        'target_amount' => 1.5,
        'target_unit' => MeasureUnit::Liters->value,
    ])->assertSessionHasNoErrors();

    $habit = $user->habits()->sole();

    expect($habit->title)->toBe('Wasser trinken')
        ->and($habit->target_amount)->toBe(1.5)
        ->and($habit->target_unit)->toBe(MeasureUnit::Liters)
        ->and($habit->measureLabel())->toBe('1,5 L');
});

test('a habit without a measure keeps both fields empty', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('habits.store'), [
        'behavior_type' => BehaviorType::Movement->value,
        'title' => 'Treppe statt Aufzug',
        'trigger_situation' => 'nach dem Mittagessen',
    ])->assertSessionHasNoErrors();

    $habit = $user->habits()->sole();

    expect($habit->target_amount)->toBeNull()
        ->and($habit->target_unit)->toBeNull()
        ->and($habit->measureLabel())->toBeNull()
        ->and($habit->titleWithMeasure())->toBe('Treppe statt Aufzug');
});

test('an amount without a unit is refused, and the other way round', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('habits.store'), [
        'behavior_type' => BehaviorType::Movement->value,
        'title' => 'Spazieren gehen',
        'trigger_situation' => 'nach dem Mittagessen',
        'target_amount' => 20,
    ])->assertSessionHasErrors('target_unit');

    $this->actingAs($user)->post(route('habits.store'), [
        'behavior_type' => BehaviorType::Movement->value,
        'title' => 'Spazieren gehen',
        'trigger_situation' => 'nach dem Mittagessen',
        'target_unit' => MeasureUnit::Minutes->value,
    ])->assertSessionHasErrors('target_amount');
});

/**
 * Die Grenzen hängen an der Einheit, nicht am Feld: 240 Minuten sind ein langer
 * Lerntag, 240 Liter ein Vertipper.
 */
test('the bounds of a measure follow its unit', function (string $unit, float $amount, bool $valid) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('habits.store'), [
        'behavior_type' => BehaviorType::Movement->value,
        'title' => 'Etwas tun',
        'trigger_situation' => 'nach dem Mittagessen',
        'target_amount' => $amount,
        'target_unit' => $unit,
    ]);

    $valid
        ? $response->assertSessionHasNoErrors()
        : $response->assertSessionHasErrors('target_amount');
})->with([
    'zwanzig Minuten' => [MeasureUnit::Minutes->value, 20.0, true],
    'vier Stunden' => [MeasureUnit::Minutes->value, 240.0, true],
    'fünf Stunden' => [MeasureUnit::Minutes->value, 300.0, false],
    'anderthalb Liter' => [MeasureUnit::Liters->value, 1.5, true],
    'zweihundertvierzig Liter' => [MeasureUnit::Liters->value, 240.0, false],
    'zehn Seiten' => [MeasureUnit::Pages->value, 10.0, true],
]);

test('a measure reads as a line, whole numbers without a decimal', function () {
    expect(MeasureUnit::Minutes->format(20))->toBe('20 Min')
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

/**
 * Die Vorschläge dürfen ihre Menge nicht mehr im Titel tragen — genau daran
 * scheiterte bisher jede Anpassung.
 */
test('the suggestions carry their measure beside the title, not inside it', function () {
    foreach (BehaviorType::cases() as $type) {
        foreach ($type->suggestions() as $suggestion) {
            expect($suggestion['title'])->not->toMatch('/\d/');

            // Menge und Einheit treten immer gemeinsam auf oder gar nicht.
            expect($suggestion['amount'] === null)
                ->toBe($suggestion['unit'] === null);

            if ($suggestion['unit'] !== null) {
                expect(MeasureUnit::tryFrom($suggestion['unit']))->not->toBeNull();
            }
        }
    }
});

/**
 * Was der Wizard tatsächlich bekommt — die Kachel baut sich aus diesen Feldern.
 */
test('the create page ships the suggestions and the units the stepper needs', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);

    $this->actingAs($user)
        ->get(route('habits.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('habits/create')
            ->has('directions.0.suggestions.0', fn (Assert $suggestion) => $suggestion
                ->has('title')
                ->has('amount')
                ->has('unit')
            )
            // Schrittweite und Grenzen kommen vom Server, damit der Stepper mit
            // der Validierung deckungsgleich bleibt.
            ->has('measureUnits.0', fn (Assert $unit) => $unit
                ->has('value')
                ->has('label')
                ->has('short')
                ->has('step')
                ->has('min')
                ->has('max')
            )
        );
});

test('a measure can be changed and removed later', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create([
        'title' => 'Spazieren gehen',
        'target_amount' => 20,
        'target_unit' => MeasureUnit::Minutes,
    ]);

    $form = [
        'behavior_type' => BehaviorType::Movement->value,
        'title' => 'Spazieren gehen',
        'trigger_situation' => 'nach dem Mittagessen',
    ];

    $this->actingAs($user)
        ->put(route('habits.update', $habit), [...$form, 'target_amount' => 35, 'target_unit' => MeasureUnit::Minutes->value])
        ->assertSessionHasNoErrors();

    expect($habit->refresh()->measureLabel())->toBe('35 Min');

    $this->actingAs($user)
        ->put(route('habits.update', $habit), $form)
        ->assertSessionHasNoErrors();

    $habit->refresh();

    expect($habit->target_amount)->toBeNull()
        ->and($habit->target_unit)->toBeNull();
});
