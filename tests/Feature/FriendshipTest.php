<?php

use App\Models\Friendship;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('the community page shows friends, incoming and outgoing requests', function () {
    $me = User::factory()->create();
    $friend = User::factory()->create(['name' => 'Silas']);
    $asking = User::factory()->create(['name' => 'Ngoc Ha']);
    $asked = User::factory()->create(['name' => 'Prabjot']);

    $accepted = Friendship::factory()->accepted()->create([
        'requester_id' => $me->id,
        'addressee_id' => $friend->id,
    ]);
    Friendship::factory()->create([
        'requester_id' => $asking->id,
        'addressee_id' => $me->id,
    ]);
    Friendship::factory()->create([
        'requester_id' => $me->id,
        'addressee_id' => $asked->id,
    ]);

    $this->actingAs($me)
        ->get(route('community'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('community')
            ->where('appointmentsEnabled', true)
            // Die ausgelieferte `id` ist die Freundschaft, nicht die Person —
            // sonst würde „Entfernen" auf einen fremden Eintrag zeigen.
            ->has('friends', 1, fn (AssertableInertia $friend) => $friend
                ->where('id', $accepted->id)
                ->where('name', 'Silas')
                ->where('initial', 'S')
                ->etc())
            ->has('incoming', 1, fn (AssertableInertia $request) => $request
                ->where('name', 'Ngoc Ha')
                ->etc())
            ->has('outgoing', 1, fn (AssertableInertia $request) => $request
                ->where('name', 'Prabjot')
                ->etc()));
});

test('a request can be sent by email and starts out pending', function () {
    $me = User::factory()->create();
    $other = User::factory()->create(['email' => 'silas@example.com']);

    $this->actingAs($me)
        ->post(route('friendships.store'), ['handle' => 'silas@example.com'])
        ->assertRedirect();

    $friendship = Friendship::query()->sole();

    expect($friendship->requester_id)->toBe($me->id)
        ->and($friendship->addressee_id)->toBe($other->id)
        ->and($friendship->accepted_at)->toBeNull()
        ->and($me->friends())->toBeEmpty();
});

test('the email is matched regardless of case', function () {
    $me = User::factory()->create();
    User::factory()->create(['email' => 'silas@example.com']);

    $this->actingAs($me)
        ->post(route('friendships.store'), ['handle' => 'SILAS@Example.COM'])
        ->assertSessionHasNoErrors();

    expect(Friendship::query()->count())->toBe(1);
});

test('asking someone who already asked you accepts their request instead of opening a second', function () {
    $me = User::factory()->create();
    $other = User::factory()->create(['email' => 'silas@example.com']);

    Friendship::factory()->create([
        'requester_id' => $other->id,
        'addressee_id' => $me->id,
    ]);

    $this->actingAs($me)
        ->post(route('friendships.store'), ['handle' => 'silas@example.com'])
        ->assertSessionHasNoErrors();

    expect(Friendship::query()->count())->toBe(1)
        ->and(Friendship::query()->sole()->accepted_at)->not->toBeNull()
        ->and($me->friends()->pluck('id')->all())->toBe([$other->id]);
});

test('a request is refused when it would go nowhere', function (string $case) {
    $me = User::factory()->create(['email' => 'me@example.com', 'username' => 'ich']);

    $email = match ($case) {
        'own address' => 'me@example.com',
        'own username' => 'ich',
        'nobody at that address' => 'niemand@example.com',
        'nobody with that username' => 'niemand',
        'appointments switched off' => User::factory()
            ->create(['appointments_enabled' => false])->email,
        'already connected' => tap(User::factory()->create(), function (User $other) use ($me) {
            Friendship::factory()->accepted()->create([
                'requester_id' => $me->id,
                'addressee_id' => $other->id,
            ]);
        })->email,
        'already asked' => tap(User::factory()->create(), function (User $other) use ($me) {
            Friendship::factory()->create([
                'requester_id' => $me->id,
                'addressee_id' => $other->id,
            ]);
        })->email,
    };

    $before = Friendship::query()->count();

    $this->actingAs($me)
        ->post(route('friendships.store'), ['handle' => $email])
        ->assertSessionHasErrors('handle');

    expect(Friendship::query()->count())->toBe($before);
})->with([
    'own address',
    'own username',
    'nobody at that address',
    'nobody with that username',
    'appointments switched off',
    'already connected',
    'already asked',
]);

test('an open request also shows on the dashboard', function () {
    $me = User::factory()->create();
    $asking = User::factory()->create(['name' => 'Berkay']);

    $friendship = Friendship::factory()->create([
        'requester_id' => $asking->id,
        'addressee_id' => $me->id,
    ]);

    // Mockup A2: Die Übersicht ist der einzige Ort, an dem jemand von der
    // Anfrage erfährt — es gibt keine Mail und kein Nachfassen.
    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('friendRequests', 1, fn (AssertableInertia $request) => $request
                ->where('id', $friendship->id)
                ->where('name', 'Berkay')
                ->where('initial', 'B'))
            ->etc());
});

test('the dashboard stays quiet when nothing is open', function () {
    $me = User::factory()->create();
    $friend = User::factory()->create();

    // Eine bestätigte Freundschaft ist keine offene Anfrage.
    Friendship::factory()->accepted()->create([
        'requester_id' => $friend->id,
        'addressee_id' => $me->id,
    ]);
    // Eine eigene, noch unbeantwortete Anfrage gehört der anderen Seite.
    Friendship::factory()->create([
        'requester_id' => $me->id,
        'addressee_id' => $friend->id,
    ]);

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('friendRequests', 0)
            ->etc());
});

test('answering from the dashboard clears the notice', function () {
    $me = User::factory()->create();
    $asking = User::factory()->create();

    $friendship = Friendship::factory()->create([
        'requester_id' => $asking->id,
        'addressee_id' => $me->id,
    ]);

    $this->actingAs($me)->patch(route('friendships.update', $friendship));

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('friendRequests', 0)
            ->etc());
});

test('a request can be sent by username', function () {
    $me = User::factory()->create();
    $other = User::factory()->create(['username' => 'silas']);

    $this->actingAs($me)
        ->post(route('friendships.store'), ['handle' => 'silas'])
        ->assertSessionHasNoErrors();

    expect(Friendship::query()->sole()->addressee_id)->toBe($other->id);
});

test('a username is matched regardless of case', function () {
    $me = User::factory()->create();
    User::factory()->create(['username' => 'silas']);

    $this->actingAs($me)
        ->post(route('friendships.store'), ['handle' => 'SiLaS'])
        ->assertSessionHasNoErrors();

    expect(Friendship::query()->count())->toBe(1);
});

test('the search never matches part of a username', function (string $attempt) {
    $me = User::factory()->create();
    User::factory()->create(['username' => 'silas_bauer']);

    $this->actingAs($me)
        ->post(route('friendships.store'), ['handle' => $attempt])
        ->assertSessionHasErrors('handle');

    // Ein Teiltreffer machte aus dem Handle ein durchblätterbares
    // Personenverzeichnis — die Umfrage trägt den engen Kreis, nicht das
    // Entdecken Fremder.
    expect(Friendship::query()->count())->toBe(0);
})->with(['silas', 'bauer', 'silas_', '_bauer', 'sila']);

test('accepting a request connects both sides', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $friendship = Friendship::factory()->create([
        'requester_id' => $other->id,
        'addressee_id' => $me->id,
    ]);

    $this->actingAs($me)
        ->patch(route('friendships.update', $friendship))
        ->assertRedirect();

    expect($friendship->refresh()->accepted_at)->not->toBeNull()
        ->and($me->friends()->pluck('id')->all())->toBe([$other->id])
        ->and($other->friends()->pluck('id')->all())->toBe([$me->id]);
});

test('only the person who was asked can accept', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $friendship = Friendship::factory()->create([
        'requester_id' => $me->id,
        'addressee_id' => $other->id,
    ]);

    $this->actingAs($me)
        ->patch(route('friendships.update', $friendship))
        ->assertForbidden();

    expect($friendship->refresh()->accepted_at)->toBeNull();
});

test('an outsider can neither accept nor delete a friendship', function () {
    $outsider = User::factory()->create();
    $friendship = Friendship::factory()->create();

    $this->actingAs($outsider)
        ->patch(route('friendships.update', $friendship))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->delete(route('friendships.destroy', $friendship))
        ->assertForbidden();

    expect(Friendship::query()->count())->toBe(1);
});

test('a refusal leaves no trace at all', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();

    $friendship = Friendship::factory()->create([
        'requester_id' => $other->id,
        'addressee_id' => $me->id,
    ]);

    $this->actingAs($me)
        ->delete(route('friendships.destroy', $friendship))
        ->assertRedirect();

    // Kein Eintrag, kein Status, kein Zähler — community_feature3.md §9:
    // eine Absage-Statistik wäre die schärfste denkbare Bestrafung.
    expect(Friendship::query()->count())->toBe(0);
});

test('a refusal does not block a later request', function () {
    $me = User::factory()->create();
    $other = User::factory()->create(['email' => 'silas@example.com']);

    $friendship = Friendship::factory()->create([
        'requester_id' => $other->id,
        'addressee_id' => $me->id,
    ]);

    $this->actingAs($me)->delete(route('friendships.destroy', $friendship));

    $this->actingAs($other)
        ->post(route('friendships.store'), ['handle' => $me->email])
        ->assertSessionHasNoErrors();

    expect(Friendship::query()->count())->toBe(1);
});

test('both sides can end a friendship they are part of', function (bool $asRequester) {
    $requester = User::factory()->create();
    $addressee = User::factory()->create();

    $friendship = Friendship::factory()->accepted()->create([
        'requester_id' => $requester->id,
        'addressee_id' => $addressee->id,
    ]);

    $this->actingAs($asRequester ? $requester : $addressee)
        ->delete(route('friendships.destroy', $friendship))
        ->assertRedirect();

    expect(Friendship::query()->count())->toBe(0)
        ->and($requester->friends())->toBeEmpty()
        ->and($addressee->friends())->toBeEmpty();
})->with([
    'the person who asked' => [true],
    'the person who was asked' => [false],
]);

test('friends are found in both directions and sorted by name', function () {
    $me = User::factory()->create();
    $zora = User::factory()->create(['name' => 'Zora']);
    $anton = User::factory()->create(['name' => 'Anton']);

    Friendship::factory()->accepted()->create([
        'requester_id' => $me->id,
        'addressee_id' => $zora->id,
    ]);
    Friendship::factory()->accepted()->create([
        'requester_id' => $anton->id,
        'addressee_id' => $me->id,
    ]);

    expect($me->friends()->pluck('name')->all())->toBe(['Anton', 'Zora']);
});

test('appointments can be switched off and on again without losing friends', function () {
    $me = User::factory()->create();
    $friend = User::factory()->create();

    Friendship::factory()->accepted()->create([
        'requester_id' => $me->id,
        'addressee_id' => $friend->id,
    ]);

    $this->actingAs($me)
        ->put(route('appointments.availability'), ['enabled' => false])
        ->assertRedirect();

    expect($me->refresh()->appointments_enabled)->toBeFalse();

    $this->actingAs($me)->put(route('appointments.availability'), ['enabled' => true]);

    expect($me->refresh()->appointments_enabled)->toBeTrue()
        ->and($me->friends())->toHaveCount(1);
});

test('guests are kept out of every friendship route', function () {
    $friendship = Friendship::factory()->create();

    $this->post(route('friendships.store'), ['handle' => 'a@example.com'])->assertRedirect(route('login'));
    $this->patch(route('friendships.update', $friendship))->assertRedirect(route('login'));
    $this->delete(route('friendships.destroy', $friendship))->assertRedirect(route('login'));
    $this->put(route('appointments.availability'), ['enabled' => false])->assertRedirect(route('login'));
});
