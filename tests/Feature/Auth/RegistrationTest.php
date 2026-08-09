<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'test_user',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('the handle is stored in lower case whatever was typed', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'TestUser',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // Sonst wären „TestUser" und „testuser" zwei Konten, und die
    // Eindeutigkeitsprüfung liefe an SQLites zeichengenauem Vergleich vorbei.
    expect(User::query()->sole()->username)->toBe('testuser');
});

test('a handle that is already taken is refused', function () {
    User::factory()->create(['username' => 'silas']);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'Silas',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('a handle with characters outside the allowed set is refused', function (string $username) {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => $username,
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('username');
})->with([
    'too short' => ['ab'],
    'with a dot' => ['test.user'],
    'with a hyphen' => ['test-user'],
    'with a space' => ['test user'],
    'looks like an email' => ['test@example.com'],
]);
