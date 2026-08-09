<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

dataset('feature pages', [
    'Gewohnheiten' => ['habits.index', 'habits/index'],
    'Kalender' => ['calendar', 'calendar'],
    'Community' => ['community', 'community'],
]);

test('guests cannot reach the feature pages', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with('feature pages');

test('the feature pages render for a signed in user', function (string $route, string $component) {
    $this->actingAs(User::factory()->create())
        ->get(route($route))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
})->with('feature pages');

test('the feature pages are behind onboarding like the dashboard', function (string $route) {
    $this->actingAs(User::factory()->notOnboarded()->create())
        ->get(route($route))
        ->assertRedirect(route('onboarding.show'));
})->with('feature pages');
