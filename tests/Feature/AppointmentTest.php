<?php

use App\Models\Appointment;
use App\Models\Friendship;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Zwei befreundete Personen und eine Gewohnheit der ersten.
 *
 * @return array{0: User, 1: User, 2: Habit}
 */
function pair(array $habitAttributes = []): array
{
    $me = User::factory()->create(['name' => 'Berkay']);
    $friend = User::factory()->create(['name' => 'Silas']);

    Friendship::factory()->accepted()->create([
        'requester_id' => $me->id,
        'addressee_id' => $friend->id,
    ]);

    $habit = Habit::factory()->for($me)->create([
        'title' => 'Laufen gehen',
        'trigger_situation' => 'nach der Vorlesung',
        ...$habitAttributes,
    ]);

    return [$me, $friend, $habit];
}

test('a friend can be asked for a single day', function () {
    [$me, $friend, $habit] = pair();

    $this->actingAs($me)
        ->post(route('appointments.store', $habit), [
            'friend_id' => $friend->id,
            'scheduled_for' => Carbon::tomorrow()->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    $appointment = Appointment::query()->sole();

    expect($appointment->requester_id)->toBe($me->id)
        ->and($appointment->invitee_id)->toBe($friend->id)
        ->and($appointment->habit_id)->toBe($habit->id)
        ->and($appointment->accepted_at)->toBeNull()
        ->and($appointment->scheduled_for->toDateString())
        ->toBe(Carbon::tomorrow()->toDateString());
});

test('only people in your circle can be asked', function () {
    [$me, , $habit] = pair();
    $stranger = User::factory()->create();

    $this->actingAs($me)
        ->post(route('appointments.store', $habit), [
            'friend_id' => $stranger->id,
            'scheduled_for' => Carbon::today()->toDateString(),
        ])
        ->assertSessionHasErrors('friend_id');

    expect(Appointment::query()->count())->toBe(0);
});

test('an open friend request is not yet a circle', function () {
    $me = User::factory()->create();
    $almost = User::factory()->create();

    // Angefragt, aber nicht bestätigt.
    Friendship::factory()->create([
        'requester_id' => $me->id,
        'addressee_id' => $almost->id,
    ]);

    $habit = Habit::factory()->for($me)->create();

    $this->actingAs($me)
        ->post(route('appointments.store', $habit), [
            'friend_id' => $almost->id,
            'scheduled_for' => Carbon::today()->toDateString(),
        ])
        ->assertSessionHasErrors('friend_id');
});

test('someone who switched appointments off cannot be asked', function () {
    [$me, $friend, $habit] = pair();

    $friend->appointments_enabled = false;
    $friend->save();

    $this->actingAs($me)
        ->post(route('appointments.store', $habit), [
            'friend_id' => $friend->id,
            'scheduled_for' => Carbon::today()->toDateString(),
        ])
        ->assertSessionHasErrors('friend_id');
});

test('only the three offered days are accepted', function (int $offset, bool $allowed) {
    [$me, $friend, $habit] = pair();

    $response = $this->actingAs($me)
        ->post(route('appointments.store', $habit), [
            'friend_id' => $friend->id,
            'scheduled_for' => Carbon::today()->addDays($offset)->toDateString(),
        ]);

    // Weiter vorauszuplanen wäre der Anfang einer Terminfindung — die gehört
    // laut §9 nicht in die App, sondern in WhatsApp.
    $allowed
        ? $response->assertSessionHasNoErrors()
        : $response->assertSessionHasErrors('scheduled_for');
})->with([
    'yesterday' => [-1, false],
    'today' => [0, true],
    'tomorrow' => [1, true],
    'the day after' => [2, true],
    'in three days' => [3, false],
]);

test('a habit carries at most one appointment per day', function () {
    [$me, $friend, $habit] = pair();
    $other = User::factory()->create();

    Friendship::factory()->accepted()->create([
        'requester_id' => $me->id,
        'addressee_id' => $other->id,
    ]);

    Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::today(),
    ]);

    // §4: maximal eine Person pro Verabredung.
    $this->actingAs($me)
        ->post(route('appointments.store', $habit), [
            'friend_id' => $other->id,
            'scheduled_for' => Carbon::today()->toDateString(),
        ])
        ->assertSessionHasErrors('scheduled_for');

    expect(Appointment::query()->count())->toBe(1);
});

test('a habit that is not yours cannot be shared', function () {
    [, $friend] = pair();
    $stranger = User::factory()->create();
    $theirHabit = Habit::factory()->for($stranger)->create();

    $this->actingAs($friend)
        ->post(route('appointments.store', $theirHabit), [
            'friend_id' => $stranger->id,
            'scheduled_for' => Carbon::today()->toDateString(),
        ])
        ->assertForbidden();
});

test('the invitee sees the request on the dashboard', function () {
    [$me, $friend, $habit] = pair();

    $appointment = Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::tomorrow(),
    ]);

    $this->actingAs($friend)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('appointmentRequests', 1, fn (AssertableInertia $request) => $request
                ->where('id', $appointment->id)
                ->where('name', 'Berkay')
                ->where('title', 'Laufen gehen')
                ->where('anchor', 'nach der Vorlesung')
                ->where('day', 'morgen')
                ->etc())
            ->etc());
});

test('a request for a day gone by quietly disappears', function () {
    [$me, $friend, $habit] = pair();

    Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::yesterday(),
    ]);

    // Eine Anfrage für gestern ist keine Frage mehr, und ein Hinweis darauf
    // wäre ein Vorwurf.
    $this->actingAs($friend)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('appointmentRequests', 0)
            ->etc());
});

test('accepting puts both people into the habit row', function () {
    [$me, $friend, $habit] = pair();

    $appointment = Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::today(),
    ]);

    $this->actingAs($friend)
        ->patch(route('appointments.update', $appointment))
        ->assertRedirect();

    expect($appointment->refresh()->accepted_at)->not->toBeNull();

    // Screen A3: bei der fragenden Seite steht die Verabredung in der Zeile
    // der Gewohnheit — mit Namen, aber ohne Fortschritt der anderen Person.
    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 1, fn (AssertableInertia $row) => $row
                ->where('companion.name', 'Silas')
                ->where('companion.initial', 'S')
                ->etc())
            ->etc());

    // Die eingeladene Seite führt diese Gewohnheit nicht — sie sieht die
    // Verabredung als eigene Karte, sonst wüsste sie am Tag nichts davon.
    $this->actingAs($friend)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('upcomingAppointments', 1, fn (AssertableInertia $row) => $row
                ->where('name', 'Berkay')
                ->where('title', 'Laufen gehen')
                ->where('accepted', true)
                ->where('iAsked', false)
                ->etc())
            ->etc());

    // Bei der fragenden Seite steht sie nicht doppelt: Was heute an der
    // eigenen Gewohnheit hängt, trägt schon die Habit-Zeile.
    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('upcomingAppointments', 0)
            ->etc());
});

test('a request you sent stays visible until it is answered', function () {
    [$me, $friend, $habit] = pair();

    $this->actingAs($me)->post(route('appointments.store', $habit), [
        'friend_id' => $friend->id,
        'scheduled_for' => Carbon::tomorrow()->toDateString(),
    ]);

    // Vorher verschwand die eigene Anfrage spurlos, und ein zweiter Versuch
    // lief in „Für diesen Tag steht schon eine Verabredung" — ohne dass
    // irgendwo stand, warum.
    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('upcomingAppointments', 1, fn (AssertableInertia $row) => $row
                ->where('name', 'Silas')
                ->where('day', 'morgen')
                ->where('accepted', false)
                ->where('iAsked', true)
                ->etc())
            ->etc());
});

test('an accepted appointment for tomorrow is visible on both sides today', function () {
    [$me, $friend, $habit] = pair();

    $appointment = Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::tomorrow(),
    ]);

    $this->actingAs($friend)->patch(route('appointments.update', $appointment));

    // Genau hier klaffte die Lücke: Wer für morgen zusagte, sah danach nichts
    // mehr — die Verabredung erschien erst am Tag selbst.
    foreach ([$me, $friend] as $person) {
        $this->actingAs($person)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('upcomingAppointments', 1, fn (AssertableInertia $row) => $row
                    ->where('day', 'morgen')
                    ->where('accepted', true)
                    ->etc())
                ->etc());
    }
});

test('an open request to me is not listed twice', function () {
    [$me, $friend, $habit] = pair();

    Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::today(),
    ]);

    // Sie steht schon als Karte mit „Passt mir" und „Lieber nicht" oben.
    $this->actingAs($friend)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('appointmentRequests', 1)
            ->has('upcomingAppointments', 0)
            ->etc());
});

test('the community page shows what is arranged with whom', function () {
    [$me, $friend, $habit] = pair();

    $appointment = Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::tomorrow(),
    ]);

    $this->actingAs($friend)->patch(route('appointments.update', $appointment));

    // Der Bereich verspricht, mit wem man sich verabreden kann — zeigte aber
    // als einziger nicht, mit wem gerade etwas ausgemacht ist.
    foreach ([$me, $friend] as $person) {
        $this->actingAs($person)
            ->get(route('community'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('upcomingAppointments', 1, fn (AssertableInertia $row) => $row
                    ->where('title', 'Laufen gehen')
                    ->where('day', 'morgen')
                    ->where('anchor', 'nach der Vorlesung')
                    ->where('accepted', true)
                    ->etc())
                ->etc());
    }
});

test('the community page keeps what the overview leaves to the habit row', function () {
    [$me, $friend, $habit] = pair();

    $appointment = Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::today(),
    ]);

    $this->actingAs($friend)->patch(route('appointments.update', $appointment));

    // Auf der Übersicht trägt die Habit-Zeile diesen Fall, hier gibt es keine.
    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('upcomingAppointments', 0)
            ->etc());

    $this->actingAs($me)
        ->get(route('community'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('upcomingAppointments', 1, fn (AssertableInertia $row) => $row
                ->where('name', 'Silas')
                ->where('day', 'heute')
                ->where('iAsked', true)
                ->etc())
            ->etc());
});

test('an open request is not listed twice on the community page either', function () {
    [$me, $friend, $habit] = pair();

    Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::today(),
    ]);

    // Sie steht als Karte mit „Passt mir" und „Lieber nicht" darüber.
    $this->actingAs($friend)
        ->get(route('community'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('appointmentRequests', 1, fn (AssertableInertia $row) => $row
                ->where('name', 'Berkay')
                ->where('title', 'Laufen gehen')
                ->etc())
            ->has('upcomingAppointments', 0)
            ->etc());

    // Die fragende Seite sieht dieselbe Verabredung als offenen Eintrag.
    $this->actingAs($me)
        ->get(route('community'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('appointmentRequests', 0)
            ->has('upcomingAppointments', 1, fn (AssertableInertia $row) => $row
                ->where('accepted', false)
                ->where('iAsked', true)
                ->etc())
            ->etc());
});

test('only the person who was asked can accept', function () {
    [$me, $friend, $habit] = pair();

    $appointment = Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
    ]);

    $this->actingAs($me)
        ->patch(route('appointments.update', $appointment))
        ->assertForbidden();

    expect($appointment->refresh()->accepted_at)->toBeNull();
});

test('a refusal leaves no trace and blocks nothing', function () {
    [$me, $friend, $habit] = pair();

    $appointment = Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::today(),
    ]);

    $this->actingAs($friend)
        ->delete(route('appointments.destroy', $appointment))
        ->assertRedirect();

    // Kein Status, kein Zähler, keine Historie — eine Absage-Statistik wäre
    // bei Schuldgefühl ø 3,92 die schärfste denkbare Bestrafung (§9).
    expect(Appointment::query()->count())->toBe(0);

    // Und der Tag ist wieder frei.
    $this->actingAs($me)
        ->post(route('appointments.store', $habit), [
            'friend_id' => $friend->id,
            'scheduled_for' => Carbon::today()->toDateString(),
        ])
        ->assertSessionHasNoErrors();
});

test('both sides can dissolve an accepted appointment', function (bool $asRequester) {
    [$me, $friend, $habit] = pair();

    $appointment = Appointment::factory()->accepted()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
    ]);

    $this->actingAs($asRequester ? $me : $friend)
        ->delete(route('appointments.destroy', $appointment))
        ->assertRedirect();

    expect(Appointment::query()->count())->toBe(0);
})->with([
    'the person who asked' => [true],
    'the person who was asked' => [false],
]);

test('an outsider can neither accept nor dissolve an appointment', function () {
    [$me, $friend, $habit] = pair();
    $outsider = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
    ]);

    $this->actingAs($outsider)
        ->patch(route('appointments.update', $appointment))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->delete(route('appointments.destroy', $appointment))
        ->assertForbidden();

    expect(Appointment::query()->count())->toBe(1);
});

test('yesterdays appointment is gone from today', function () {
    [$me, $friend, $habit] = pair();

    Appointment::factory()->accepted()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::yesterday(),
    ]);

    // Eine Verabredung ist ein Ereignis, kein Zustand — danach hinterlässt
    // sie keine Spur in der Oberfläche (§2, §7).
    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 1, fn (AssertableInertia $row) => $row
                ->where('companion', null)
                ->etc())
            ->etc());
});

test('the dashboard offers three days and the own circle', function () {
    [$me] = pair();

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('appointmentDays', 3)
            ->where('appointmentDays.0.label', 'heute')
            ->where('appointmentDays.1.label', 'morgen')
            ->has('friends', 1)
            ->where('appointmentsEnabled', true)
            ->etc());
});

test('creating a habit leads to the companion step when there is someone to ask', function () {
    [$me] = pair();

    $this->actingAs($me)
        ->post(route('habits.store'), [
            'behavior_type' => 'movement',
            'title' => 'Schwimmen',
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        // Zurück in den Assistenten — dort steht jetzt der letzte Schritt.
        ->assertRedirect(route('habits.create'))
        // Die Kennung trägt den Schritt: die Verabredung hängt daran.
        ->assertInertiaFlash('habitCreated.title', 'Schwimmen')
        ->assertInertiaFlash('habitCreated.anchor', 'nach dem Aufstehen');

    $this->actingAs($me)
        ->get(route('habits.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('habits/create')
            ->has('friends', 1)
            ->has('appointmentDays', 3)
            ->etc());
});

test('without anyone to ask the way leads straight to the overview', function (string $case) {
    $me = User::factory()->create();

    if ($case === 'appointments switched off') {
        $friend = User::factory()->create();
        Friendship::factory()->accepted()->create([
            'requester_id' => $me->id,
            'addressee_id' => $friend->id,
        ]);
        $me->appointments_enabled = false;
        $me->save();
    }

    // Ein leerer Schritt wäre eine Seite, die nichts anbietet.
    $this->actingAs($me)
        ->post(route('habits.store'), [
            'behavior_type' => 'movement',
            'title' => 'Schwimmen',
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertRedirect(route('dashboard'));
})->with(['no circle', 'appointments switched off']);

test('the companion step can create the appointment right away', function () {
    [$me, $friend] = pair();

    $this->actingAs($me)->post(route('habits.store'), [
        'behavior_type' => 'movement',
        'title' => 'Schwimmen',
        'trigger_situation' => 'nach dem Aufstehen',
    ]);

    $habit = $me->habits()->where('title', 'Schwimmen')->sole();

    $this->actingAs($me)
        ->post(route('appointments.store', $habit), [
            'friend_id' => $friend->id,
            'scheduled_for' => Carbon::tomorrow()->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    expect(Appointment::query()->sole()->habit_id)->toBe($habit->id);
});

test('guests are kept out of every appointment route', function () {
    $appointment = Appointment::factory()->create();
    $habit = Habit::factory()->create();

    $this->post(route('appointments.store', $habit))->assertRedirect(route('login'));
    $this->patch(route('appointments.update', $appointment))->assertRedirect(route('login'));
    $this->delete(route('appointments.destroy', $appointment))->assertRedirect(route('login'));
});
