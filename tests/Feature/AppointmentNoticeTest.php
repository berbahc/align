<?php

use App\Models\Appointment;
use App\Models\AppointmentNotice;
use App\Models\Friendship;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Zwei befreundete Personen, eine Gewohnheit und eine Verabredung dazu.
 *
 * `pair()` aus AppointmentTest.php steht hier bewusst nicht zur Verfügung:
 * Pest lädt Testdateien einzeln, eine Funktion aus einer anderen Datei wäre
 * von der Reihenfolge abhängig.
 *
 * @return array{0: User, 1: User, 2: Appointment}
 */
function arranged(bool $accepted = false): array
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
    ]);

    $appointment = Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::tomorrow(),
        'accepted_at' => $accepted ? now() : null,
    ]);

    return [$me, $friend, $appointment];
}

test('declining an open request tells the person who asked', function () {
    [$me, $friend, $appointment] = arranged();

    $this->actingAs($friend)
        ->delete(route('appointments.destroy', $appointment))
        ->assertRedirect();

    // §5 verlangt genau diesen Satz — vorher verschwand die Anfrage spurlos,
    // und die fragende Seite wusste nicht, ob überhaupt jemand hingesehen hat.
    $notice = AppointmentNotice::query()->sole();

    expect($notice->user_id)->toBe($me->id)
        ->and($notice->message())->toBe('Passt Silas diesmal nicht.')
        ->and($notice->detail())->toBe('Laufen gehen · morgen');

    // Die absagende Seite bekommt selbst nichts zu sehen.
    expect(AppointmentNotice::forUser($friend))->toBeEmpty();
});

test('dissolving an accepted appointment tells the other side which day is free', function () {
    [$me, $friend, $appointment] = arranged(accepted: true);

    $this->actingAs($me)
        ->delete(route('appointments.destroy', $appointment))
        ->assertRedirect();

    // Der schwerere Fall: Silas hat den Tag um diese Zusage herum geplant und
    // stünde ohne Notiz allein da.
    $notice = AppointmentNotice::query()->sole();

    expect($notice->user_id)->toBe($friend->id)
        ->and($notice->message())->toBe('Berkay kann morgen doch nicht.')
        ->and($notice->detail())->toBe('Laufen gehen');
});

test('dissolving works the same way from the invited side', function () {
    [$me, $friend, $appointment] = arranged(accepted: true);

    $this->actingAs($friend)->delete(route('appointments.destroy', $appointment));

    expect(AppointmentNotice::query()->sole())
        ->user_id->toBe($me->id)
        ->and(AppointmentNotice::query()->sole()->message())->toBe('Silas kann morgen doch nicht.');
});

test('withdrawing your own unanswered request stays silent', function () {
    [, $friend, $appointment] = arranged();

    $this->actingAs($appointment->requester)
        ->delete(route('appointments.destroy', $appointment));

    // Silas hatte nichts zugesagt und nichts verplant — ihm entgeht nichts.
    // Eine Meldung wäre bloß Lärm.
    expect(AppointmentNotice::query()->count())->toBe(0)
        ->and(AppointmentNotice::forUser($friend))->toBeEmpty();
});

test('the notice appears on the overview and in the community area', function () {
    [$me, $friend, $appointment] = arranged();

    $this->actingAs($friend)->delete(route('appointments.destroy', $appointment));

    foreach (['dashboard', 'community'] as $page) {
        $this->actingAs($me)
            ->get(route($page))
            ->assertInertia(fn (AssertableInertia $props) => $props
                ->has('appointmentNotices', 1, fn (AssertableInertia $notice) => $notice
                    ->where('message', 'Passt Silas diesmal nicht.')
                    ->where('detail', 'Laufen gehen · morgen')
                    ->etc())
                ->etc());
    }
});

test('clicking it away leaves nothing behind', function () {
    [$me, $friend, $appointment] = arranged();

    $this->actingAs($friend)->delete(route('appointments.destroy', $appointment));

    $notice = AppointmentNotice::query()->sole();

    $this->actingAs($me)
        ->delete(route('appointment-notices.destroy', $notice))
        ->assertRedirect();

    // Gelesen heißt gelöscht, nicht markiert: Eine Zeile, die bleibt, wäre der
    // Anfang der Absage-Historie, die §9 ausschließt.
    expect(AppointmentNotice::query()->count())->toBe(0)
        ->and(Appointment::query()->count())->toBe(0);
});

test('a notice can only be dismissed by the person it is addressed to', function () {
    [, $friend, $appointment] = arranged();

    $this->actingAs($friend)->delete(route('appointments.destroy', $appointment));

    $notice = AppointmentNotice::query()->sole();

    // Die absagende Seite darf die Notiz nicht wegräumen, die ihretwegen steht.
    $this->actingAs($friend)
        ->delete(route('appointment-notices.destroy', $notice))
        ->assertForbidden();

    expect(AppointmentNotice::query()->count())->toBe(1);
});

test('the notice keeps no trace of who cancelled', function () {
    [, $friend, $appointment] = arranged(accepted: true);

    $this->actingAs($friend)->delete(route('appointments.destroy', $appointment));

    // Nur der Name als Text, keine `id` der absagenden Person: Ohne sie lässt
    // sich aus diesen Zeilen keine Quote bilden, auch nicht nachträglich (§9).
    $columns = array_keys(AppointmentNotice::query()->sole()->getAttributes());

    expect($columns)->not->toContain('companion_id')
        ->and($columns)->not->toContain('appointment_id')
        ->and(AppointmentNotice::query()->where('user_id', $friend->id)->count())->toBe(0);
});
