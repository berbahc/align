<?php

use App\Enums\HabitTemplate;
use App\Models\Appointment;
use App\Models\Friendship;
use App\Models\Habit;
use App\Models\HabitDayShift;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Zwei Menschen, eine Anfrage für morgen um 07:30.
 *
 * Eigene Fassung statt einer geteilten Hilfsfunktion: Pest lädt Testdateien
 * einzeln, eine Funktion aus einer anderen Datei wäre von der Reihenfolge
 * abhängig — dieselbe Begründung wie in AppointmentNoticeTest.php.
 *
 * @return array{0: User, 1: User, 2: Appointment}
 */
function morningInvitation(): array
{
    // Ein Montag, damit die Wochentage der Gewohnheiten feststehen.
    Carbon::setTestNow(Carbon::parse('2026-09-07 10:00'));

    $owner = User::factory()->create(['name' => 'Berkay']);
    $guest = User::factory()->create(['name' => 'Silas']);

    Friendship::factory()->accepted()->create([
        'requester_id' => $owner->id,
        'addressee_id' => $guest->id,
    ]);

    $habit = Habit::factory()->for($owner)
        ->fromTemplate(HabitTemplate::Joggen)
        ->fixedSchedule('07:30', [1, 2, 3, 4, 5])
        ->withMeasure(30)
        ->create();

    $appointment = Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $owner->id,
        'invitee_id' => $guest->id,
        'scheduled_for' => Carbon::tomorrow(),
    ]);

    return [$owner, $guest, $appointment];
}

/**
 * Eine eigene Gewohnheit der gefragten Person, die im Weg steht.
 */
function collidingHabit(User $guest, string $time = '07:40'): Habit
{
    return Habit::factory()->for($guest)
        ->fromTemplate(HabitTemplate::Lesen)
        ->fixedSchedule($time, [1, 2, 3, 4, 5])
        ->withMeasure(30)
        ->create();
}

test('a free day carries no conflict', function () {
    [, $guest] = morningInvitation();

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict', null)
        );
});

test('an overlapping habit reaches the card with its span and a way out', function () {
    [, $guest] = morningInvitation();
    $reading = collidingHabit($guest);

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict.habitId', $reading->id)
            ->where('appointmentRequests.0.conflict.title', 'Lesen')
            ->where('appointmentRequests.0.conflict.from', '07:40')
            ->where('appointmentRequests.0.conflict.to', '08:10')
            // Der Ausweg gehört zum Hinweis: Ein Konflikt ohne Weg daraus
            // wäre nur eine Absage mit mehr Worten.
            ->has('appointmentRequests.0.conflict.options')
        );
});

test('a habit next to the appointment is no conflict', function () {
    [, $guest] = morningInvitation();

    // 08:15 beginnt nach dem Ende der Verabredung um 08:00.
    collidingHabit($guest, '08:15');

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict', null)
        );
});

test('a habit that hangs on a situation never blocks a promise', function () {
    [, $guest] = morningInvitation();

    // „Nach dem Aufstehen" hat keinen Zeitpunkt, mit dem sich kollidieren
    // ließe — sie zu verschieben hieße, eine Uhrzeit zu erfinden.
    Habit::factory()->for($guest)->create(['trigger_situation' => 'nach dem Aufstehen']);

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict', null)
        );
});

test('a day the habit does not run on stays free', function () {
    [, $guest, $appointment] = morningInvitation();

    // Die Verabredung liegt am Dienstag; die eigene Gewohnheit läuft nur
    // am Wochenende.
    Habit::factory()->for($guest)
        ->fixedSchedule('07:40', [6, 7])
        ->withMeasure(30)
        ->create();

    expect($appointment->scheduled_for->dayOfWeekIso)->toBe(2);

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict', null)
        );
});

test('accepting is refused while something else runs at that time', function () {
    [, $guest, $appointment] = morningInvitation();
    collidingHabit($guest);

    $this->actingAs($guest)
        ->from(route('dashboard'))
        ->patch(route('appointments.update', $appointment))
        ->assertSessionHasErrors('appointment');

    // Der Riegel steht im Server, nicht nur im ausgegrauten Knopf.
    expect($appointment->refresh()->accepted_at)->toBeNull();
});

test('making room for one day opens the way to a promise', function () {
    [, $guest, $appointment] = morningInvitation();
    $reading = collidingHabit($guest);

    $this->actingAs($guest)->post(route('habits.shifts.store', $reading), [
        'date' => $appointment->scheduled_for->toDateString(),
        'scheduled_time' => '18:00',
    ])->assertRedirect();

    $this->actingAs($guest)
        ->patch(route('appointments.update', $appointment))
        ->assertRedirect();

    expect($appointment->refresh()->accepted_at)->not->toBeNull();
});

test('the shift holds for its day and for no other', function () {
    [, $guest, $appointment] = morningInvitation();
    $reading = collidingHabit($guest);

    $this->actingAs($guest)->post(route('habits.shifts.store', $reading), [
        'date' => $appointment->scheduled_for->toDateString(),
        'scheduled_time' => '18:00',
    ]);

    // Am verschobenen Tag steht die Ausnahme — und sie sagt dazu, dass sie
    // nur für ihn gilt.
    $this->actingAs($guest)
        ->get(route('calendar', ['date' => $appointment->scheduled_for->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.anchor', '18:00 · nur an diesem Tag')
            ->where('blocks.0.timeRange', '18:00 – 18:30')
        );

    // Am Tag darauf liegt sie wieder, wo sie lag.
    $this->actingAs($guest)
        ->get(route('calendar', ['date' => $appointment->scheduled_for->copy()->addDay()->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.anchor', '07:40 · Mo–Fr')
        );

    // Und die Gewohnheit selbst ist unberührt: Verschoben wurde ein Tag,
    // nicht der Plan.
    expect($reading->refresh()->scheduled_time->format('H:i'))->toBe('07:40');
});

test('a shift cannot land on another habit', function () {
    [, $guest, $appointment] = morningInvitation();
    $reading = collidingHabit($guest);

    Habit::factory()->for($guest)
        ->fixedSchedule('18:00', [1, 2, 3, 4, 5])
        ->withMeasure(30)
        ->create();

    $this->actingAs($guest)
        ->from(route('dashboard'))
        ->post(route('habits.shifts.store', $reading), [
            'date' => $appointment->scheduled_for->toDateString(),
            'scheduled_time' => '18:00',
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect(HabitDayShift::query()->count())->toBe(0);
});

test('a shift cannot land in the night', function () {
    [, $guest, $appointment] = morningInvitation();
    $reading = collidingHabit($guest);

    $this->actingAs($guest)
        ->from(route('dashboard'))
        ->post(route('habits.shifts.store', $reading), [
            'date' => $appointment->scheduled_for->toDateString(),
            'scheduled_time' => '03:00',
        ])
        ->assertSessionHasErrors('scheduled_time');
});

test('a situation cannot be shifted', function () {
    [, $guest, $appointment] = morningInvitation();

    $situational = Habit::factory()->for($guest)
        ->create(['trigger_situation' => 'nach dem Aufstehen']);

    $this->actingAs($guest)
        ->from(route('dashboard'))
        ->post(route('habits.shifts.store', $situational), [
            'date' => $appointment->scheduled_for->toDateString(),
            'scheduled_time' => '18:00',
        ])
        ->assertSessionHasErrors('scheduled_time');
});

test('a day that is gone cannot be rearranged', function () {
    [, $guest] = morningInvitation();
    $reading = collidingHabit($guest);

    $this->actingAs($guest)
        ->from(route('dashboard'))
        ->post(route('habits.shifts.store', $reading), [
            'date' => Carbon::yesterday()->toDateString(),
            'scheduled_time' => '18:00',
        ])
        ->assertSessionHasErrors('date');
});

test('nobody rearranges someone else\'s day', function () {
    [$owner, $guest, $appointment] = morningInvitation();
    $reading = collidingHabit($guest);

    $this->actingAs($owner)
        ->post(route('habits.shifts.store', $reading), [
            'date' => $appointment->scheduled_for->toDateString(),
            'scheduled_time' => '18:00',
        ])
        ->assertForbidden();

    expect(HabitDayShift::query()->count())->toBe(0);
});

test('the shifted habit stands where it was moved to on the overview', function () {
    [, $guest] = morningInvitation();
    $reading = collidingHabit($guest);

    HabitDayShift::factory()->create([
        'habit_id' => $reading->id,
        'shifted_on' => Carbon::today(),
        'scheduled_time' => '20:00',
    ]);

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.scheduleLabel', '20:00 · nur an diesem Tag')
        );
});

test('the reminder follows the habit to where it was moved', function () {
    [, $guest] = morningInvitation();

    $reading = collidingHabit($guest);
    $reading->update(['reminder_enabled' => true]);

    HabitDayShift::factory()->create([
        'habit_id' => $reading->id,
        'shifted_on' => Carbon::today(),
        'scheduled_time' => '20:00',
    ]);

    // Ein Wecker zur alten Uhrzeit wäre eine Erinnerung an einen Block, der
    // heute nicht dort steht.
    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habitReminders.0.scheduledTime', '20:00')
        );
});
