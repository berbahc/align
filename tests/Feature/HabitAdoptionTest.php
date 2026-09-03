<?php

use App\Enums\BehaviorType;
use App\Enums\HabitTemplate;
use App\Enums\ScheduleType;
use App\Models\Appointment;
use App\Models\AppointmentNotice;
use App\Models\Friendship;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Zwei befreundete Personen und eine Gewohnheit, zu der eingeladen wird.
 *
 * Eigene Fassung statt einer geteilten Hilfsfunktion: Pest lädt Testdateien
 * einzeln, eine Funktion aus einer anderen Datei wäre von der Reihenfolge
 * abhängig — dieselbe Begründung wie in AppointmentNoticeTest.php.
 *
 * @return array{0: User, 1: User, 2: Habit, 3: Appointment}
 */
function invitation(bool $accepted = false): array
{
    $owner = User::factory()->create(['name' => 'Berkay']);
    $guest = User::factory()->create(['name' => 'Silas']);

    Friendship::factory()->accepted()->create([
        'requester_id' => $owner->id,
        'addressee_id' => $guest->id,
    ]);

    $habit = Habit::factory()->for($owner)
        ->fromTemplate(HabitTemplate::Joggen)
        ->fixedSchedule('07:30', [1, 3, 5])
        ->create([
            'motivation' => 'damit ich den Kopf freikriege',
            'smallest_step' => 'Zieh die Laufschuhe an.',
        ]);

    $appointment = Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $owner->id,
        'invitee_id' => $guest->id,
        'scheduled_for' => Carbon::tomorrow(),
        'accepted_at' => $accepted ? now() : null,
    ]);

    return [$owner, $guest, $habit, $appointment];
}

test('the open request carries the habit as a template to adopt', function () {
    [, $guest] = invitation();

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.blueprint.title', 'Joggen gehen')
            ->where('appointmentRequests.0.blueprint.scheduleType', ScheduleType::Fixed->value)
            ->where('appointmentRequests.0.blueprint.templateKey', HabitTemplate::Joggen->value)
            ->where('appointmentRequests.0.blueprint.scheduledTime', '07:30')
            ->where('appointmentRequests.0.blueprint.scheduledDays', [1, 3, 5])
        );
});

test('adopting a habit creates an own one with the chosen days', function () {
    [, $guest, $habit] = invitation();

    $this->actingAs($guest)
        ->post(route('habits.adoptions.store'), [
            'template_key' => $habit->template()?->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            // Halb acht an drei Tagen ist der fremde Tagesablauf, nicht der
            // eigene — genau dafür ist der Zeitpunkt beim Übernehmen offen.
            'scheduled_time' => '18:30',
            'scheduled_days' => [2, 4],
        ])
        ->assertRedirect();

    $adopted = $guest->habits()->sole();

    expect($adopted->title)->toBe('Joggen gehen')
        ->and($adopted->behavior_type)->toBe(BehaviorType::Movement)
        ->and($adopted->scheduled_time->format('H:i'))->toBe('18:30')
        ->and($adopted->scheduled_days)->toBe([2, 4])
        // Es entsteht eine eigene Gewohnheit, keine geteilte: Die Vorlage
        // bleibt unberührt, und der Verlauf beginnt bei null.
        ->and($adopted->id)->not->toBe($habit->id)
        ->and($adopted->completions()->count())->toBe(0);
});

test('the reason and the first step stay with the person who wrote them', function () {
    [, $guest, $habit] = invitation();

    $this->actingAs($guest)->post(route('habits.adoptions.store'), [
        'template_key' => $habit->template()?->value,
        'target_amount' => 30,
        'schedule_type' => ScheduleType::Dynamic->value,
        'trigger_situation' => 'nach dem Aufstehen',
    ]);

    // „damit ich den Kopf freikriege" ist niemandes Grund außer dem eigenen.
    expect($guest->habits()->sole())
        ->motivation->toBeNull()
        ->smallest_step->toBeNull();
});

test('adopting cannot push someone past the five active habits', function () {
    [, $guest, $habit] = invitation();

    Habit::factory()->count(Habit::MaxActivePerUser)->for($guest)->create();

    $this->actingAs($guest)
        ->post(route('habits.adoptions.store'), [
            'template_key' => $habit->template()?->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Dynamic->value,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertSessionHasErrors('template_key');

    expect($guest->habits()->count())->toBe(Habit::MaxActivePerUser);
});

test('the person left behind keeps a pointer to their own habit', function () {
    [$owner, $guest, $habit, $appointment] = invitation(accepted: true);

    $this->actingAs($guest)->delete(route('appointments.destroy', $appointment));

    $notice = AppointmentNotice::query()->sole();

    // Ihm gehört die Gewohnheit — es gibt nichts zu übernehmen, sie steht
    // schon in seiner Liste und läuft ohne die andere Person weiter.
    expect($notice->user_id)->toBe($owner->id)
        ->and($notice->habit_id)->toBe($habit->id)
        ->and($notice->habit_blueprint)->toBeNull();
});

test('the guest left behind keeps a template instead of a pointer', function () {
    [, $guest, , $appointment] = invitation(accepted: true);

    $this->actingAs($appointment->requester)
        ->delete(route('appointments.destroy', $appointment));

    $notice = AppointmentNotice::query()->sole();

    // Ihm gehört sie nicht — ein Verweis nützte ihm nichts, weil er sie nicht
    // öffnen dürfte. Die Vorlage trägt genau das, was die Übernahme braucht,
    // und lässt sich mit niemandem verknüpfen (§9).
    expect($notice->user_id)->toBe($guest->id)
        ->and($notice->habit_id)->toBeNull()
        ->and($notice->habit_blueprint)->toMatchArray([
            'title' => 'Joggen gehen',
            'templateKey' => HabitTemplate::Joggen->value,
            'scheduleType' => ScheduleType::Fixed->value,
            'scheduledTime' => '07:30',
            'scheduledDays' => [1, 3, 5],
        ]);
});

test('the template survives the original habit being deleted', function () {
    [, , $habit, $appointment] = invitation(accepted: true);

    $this->actingAs($appointment->requester)
        ->delete(route('appointments.destroy', $appointment));

    $habit->delete();

    // Die Notiz stünde sonst ohne ihren einzigen Inhalt da — deshalb eine
    // Kopie und kein Verweis.
    expect(AppointmentNotice::query()->sole()->habit_blueprint)
        ->toHaveKey('title', 'Joggen gehen');
});

test('adopting out of a notice answers it and makes it disappear', function () {
    [, $guest, , $appointment] = invitation(accepted: true);

    $this->actingAs($appointment->requester)
        ->delete(route('appointments.destroy', $appointment));

    $notice = AppointmentNotice::query()->sole();

    $this->actingAs($guest)->post(route('habits.adoptions.store'), [
        'template_key' => HabitTemplate::Joggen->value,
        'target_amount' => 30,
        'schedule_type' => ScheduleType::Dynamic->value,
        'trigger_situation' => 'nach dem Aufstehen',
        'notice_id' => $notice->id,
    ]);

    // Sie stehen zu lassen hieße, dieselbe Frage ein zweites Mal zu stellen.
    expect(AppointmentNotice::query()->count())->toBe(0)
        ->and($guest->habits()->count())->toBe(1);
});

test('a notice of someone else cannot be dismissed by adopting', function () {
    $stranger = User::factory()->create();
    $notice = AppointmentNotice::factory()->for($stranger)->create();

    $this->actingAs(User::factory()->create())
        ->post(route('habits.adoptions.store'), [
            'template_key' => HabitTemplate::Joggen->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Dynamic->value,
            'trigger_situation' => 'nach dem Aufstehen',
            'notice_id' => $notice->id,
        ])
        ->assertForbidden();

    expect(AppointmentNotice::query()->whereKey($notice->id)->exists())->toBeTrue();
});

test('the notice reaches both pages with everything the next step needs', function () {
    [, $guest, , $appointment] = invitation(accepted: true);

    $this->actingAs($appointment->requester)
        ->delete(route('appointments.destroy', $appointment));

    foreach (['dashboard', 'community'] as $route) {
        $this->actingAs($guest)
            ->get(route($route))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('appointmentNotices.0.habitId', null)
                ->where('appointmentNotices.0.blueprint.title', 'Joggen gehen')
            );
    }
});

test('the request leads into the same wizard, with the habit already chosen', function () {
    [, $guest, , $appointment] = invitation();

    $this->actingAs($guest)
        ->get(route('habits.create', ['appointment' => $appointment->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('habits/create')
            ->where('adoption.blueprint.templateKey', HabitTemplate::Joggen->value)
            ->where('adoption.blueprint.scheduledTime', '07:30')
            ->where('adoption.appointmentId', $appointment->id)
            ->where('adoption.noticeId', null)
            // Es ist derselbe Assistent: dieselbe Dauer, dieselben Ketten,
            // dieselben belegten Fenster wie beim Anlegen.
            ->has('durationLimits')
            ->has('chainCandidates')
            ->has('busySlots')
            ->has('sleepWindows')
        );
});

test('a cancellation leads into the wizard as well', function () {
    [, $guest, , $appointment] = invitation(accepted: true);

    $this->actingAs($appointment->requester)
        ->delete(route('appointments.destroy', $appointment));

    $notice = AppointmentNotice::query()->sole();

    $this->actingAs($guest)
        ->get(route('habits.create', ['notice' => $notice->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('adoption.blueprint.templateKey', HabitTemplate::Joggen->value)
            ->where('adoption.noticeId', $notice->id)
            ->where('adoption.appointmentId', null)
        );
});

test('nobody adopts out of a request that was never theirs', function () {
    [$owner, , , $appointment] = invitation();

    $this->actingAs($owner)
        ->get(route('habits.create', ['appointment' => $appointment->id]))
        ->assertForbidden();
});

test('a habit from before the catalog cannot be adopted', function () {
    $owner = User::factory()->create();
    $guest = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'habit_id' => Habit::factory()->for($owner)->legacy()->create()->id,
        'requester_id' => $owner->id,
        'invitee_id' => $guest->id,
        'scheduled_for' => Carbon::tomorrow(),
    ]);

    $this->actingAs($guest)
        ->get(route('habits.create', ['appointment' => $appointment->id]))
        ->assertNotFound();
});

test('a moment that is already taken does not travel along', function () {
    $owner = User::factory()->create();
    $guest = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'habit_id' => Habit::factory()->for($owner)
            ->fromTemplate(HabitTemplate::Joggen)
            ->create(['trigger_situation' => 'nach dem Aufstehen'])->id,
        'requester_id' => $owner->id,
        'invitee_id' => $guest->id,
        'scheduled_for' => Carbon::tomorrow(),
    ]);

    // Der Moment gehört hier schon einer anderen Gewohnheit. Ihn vorzubelegen
    // führte direkt in eine Fehlermeldung, die niemand verursacht hat.
    Habit::factory()->for($guest)->create(['trigger_situation' => 'nach dem Aufstehen']);

    $this->actingAs($guest)
        ->get(route('habits.create', ['appointment' => $appointment->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('adoption.blueprint.triggerSituation', null)
        );
});

test('adopting out of a request answers it as a yes', function () {
    [, $guest, $habit, $appointment] = invitation();

    $this->actingAs($guest)
        ->post(route('habits.adoptions.store'), [
            'template_key' => $habit->template()?->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Dynamic->value,
            'trigger_situation' => 'nach dem Aufstehen',
            'appointment_id' => $appointment->id,
        ])
        ->assertRedirect(route('dashboard'));

    // Wer die Gewohnheit zu seiner macht, macht an dem Tag ohnehin mit — die
    // Frage ein zweites Mal zu stellen wäre eine Frage ohne offene Antwort.
    expect($appointment->refresh()->accepted_at)->not->toBeNull()
        ->and($guest->habits()->count())->toBe(1);
});

test('adopting leaves the request open when that time is taken', function () {
    [, $guest, $habit, $appointment] = invitation();

    // Zur Zeit der Verabredung läuft schon etwas Eigenes.
    Habit::factory()->for($guest)
        ->fixedSchedule('07:30', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(30)
        ->create();

    $this->actingAs($guest)->post(route('habits.adoptions.store'), [
        'template_key' => $habit->template()?->value,
        'target_amount' => 30,
        'schedule_type' => ScheduleType::Dynamic->value,
        'trigger_situation' => 'nach dem Aufstehen',
        'appointment_id' => $appointment->id,
    ]);

    // Die Gewohnheit gehört ihm — die Zusage wäre aber eine Doppelbuchung.
    expect($guest->habits()->count())->toBe(2)
        ->and($appointment->refresh()->accepted_at)->toBeNull();
});

test('a request of someone else cannot be answered by adopting', function () {
    [$owner, , $habit, $appointment] = invitation();

    $this->actingAs($owner)
        ->post(route('habits.adoptions.store'), [
            'template_key' => $habit->template()?->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Dynamic->value,
            'trigger_situation' => 'nach dem Aufstehen',
            'appointment_id' => $appointment->id,
        ])
        ->assertForbidden();

    expect($appointment->refresh()->accepted_at)->toBeNull();
});
