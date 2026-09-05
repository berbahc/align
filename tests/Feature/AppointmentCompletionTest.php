<?php

use App\Enums\HabitTemplate;
use App\Enums\ScheduleType;
use App\Models\Appointment;
use App\Models\Friendship;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Die gefragte Seite hakt ihre Zusage ab.
 *
 * Bis hierher konnte sie das nirgends: Eine Erfüllung hängt an einer
 * Gewohnheit, und die gehört der fragenden Person. Wer zugesagt hatte, machte
 * mit und stand danach vor einem Eintrag ohne Haken — auf der Übersicht wie im
 * Kalender.
 *
 * Der Haken hängt deshalb an der Verabredung. Er meldet den eigenen Teil und
 * sonst nichts: Die fragende Seite sieht ihn nicht (community_feature3.md §6),
 * und die fremde Gewohnheit bleibt unberührt.
 *
 * @return array{0: User, 1: User, 2: Habit, 3: Appointment}
 */
function joined(?Carbon $day = null): array
{
    $owner = User::factory()->create(['name' => 'Berkay']);
    $guest = User::factory()->create(['name' => 'Silas']);

    Friendship::factory()->accepted()->create([
        'requester_id' => $owner->id,
        'addressee_id' => $guest->id,
    ]);

    $habit = Habit::factory()->for($owner)
        ->fixedSchedule('17:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(30)
        ->create(['title' => 'Laufen gehen']);

    $appointment = Appointment::factory()->accepted()->create([
        'habit_id' => $habit->id,
        'requester_id' => $owner->id,
        'invitee_id' => $guest->id,
        'scheduled_for' => $day ?? Carbon::today(),
    ]);

    return [$owner, $guest, $habit, $appointment];
}

test('the invited person can tick off what they joined', function () {
    [, $guest, $habit, $appointment] = joined();

    $this->actingAs($guest)
        ->post(route('appointments.completion.store', $appointment))
        ->assertRedirect();

    expect($appointment->refresh()->completed_at)->not->toBeNull()
        // Die fremde Gewohnheit bleibt unberührt — der Haken meldet den
        // eigenen Teil, nicht ihre Erfüllung.
        ->and($habit->completions()->count())->toBe(0);
});

test('the tick can be taken back', function () {
    [, $guest, , $appointment] = joined();

    $this->actingAs($guest)->post(route('appointments.completion.store', $appointment));
    $this->actingAs($guest)
        ->delete(route('appointments.completion.destroy', $appointment))
        ->assertRedirect();

    expect($appointment->refresh()->completed_at)->toBeNull();
});

/**
 * Der Haken gehört der gefragten Seite. Die fragende hakt ihre eigene
 * Gewohnheit ab — hier wäre es ein zweiter Haken für dieselbe Sache.
 */
test('the asking person cannot tick off the appointment', function () {
    [$owner, , , $appointment] = joined();

    $this->actingAs($owner)
        ->post(route('appointments.completion.store', $appointment))
        ->assertForbidden();

    expect($appointment->refresh()->completed_at)->toBeNull();
});

test('nobody ticks off an appointment that was never theirs', function () {
    [, , , $appointment] = joined();

    $this->actingAs(User::factory()->create())
        ->post(route('appointments.completion.store', $appointment))
        ->assertForbidden();
});

/** Eine Frage ist kein Termin — abhaken lässt sich erst, was zugesagt ist. */
test('an open request cannot be ticked off', function () {
    [, $guest, , $appointment] = joined();

    $appointment->accepted_at = null;
    $appointment->save();

    $this->actingAs($guest)
        ->post(route('appointments.completion.store', $appointment))
        ->assertForbidden();
});

/** Die Zukunft ist nicht abhakbar — dieselbe Regel wie bei einer Gewohnheit. */
test('a future appointment cannot be ticked off yet', function () {
    [, $guest, , $appointment] = joined(Carbon::tomorrow());

    $this->actingAs($guest)
        ->post(route('appointments.completion.store', $appointment))
        ->assertForbidden();
});

test('the overview carries the tick and who may set it', function () {
    [$owner, $guest, , $appointment] = joined();

    // Die gefragte Seite: Haken vorhanden und bedienbar.
    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('upcomingAppointments.0.completed', false)
            ->where('upcomingAppointments.0.canComplete', true)
            ->etc());

    $this->actingAs($guest)->post(route('appointments.completion.store', $appointment));

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('upcomingAppointments.0.completed', true)
            ->etc());

    // Die fragende Seite sieht davon nichts — weder Haken noch Knopf. Ihre
    // eigene Zeile trägt die Verabredung, deshalb steht dort gar keine Karte.
    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('upcomingAppointments', 0)
            ->etc());

    // Und die Zeile der fragenden Person trägt über die andere genau drei
    // Angaben: wer, welche Initiale, zugesagt oder nicht. Kein Haken, kein
    // Fortschritt — das wäre der Dauerstatus aus §6. Der Vergleich ist
    // bewusst vollständig: Ein `where` je Feld würde ein viertes übersehen.
    //
    // `repeatHabitId` und `repeatDays` gehören dazu, sagen aber nichts über
    // Silas: Es sind die **eigene** Gewohnheit und ihre eigenen Tage. Hier
    // stehen sie auf null, weil der Besitzer noch nicht abgehakt hat.
    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.companion', [
                'name' => 'Silas',
                'initial' => 'S',
                'pending' => false,
                'repeatHabitId' => null,
                'repeatDays' => [],
            ])
            ->etc());
});

test('the calendar block carries the tick as well', function () {
    $day = Carbon::today();
    [, $guest, , $appointment] = joined($day);

    $this->actingAs($guest)
        ->get(route('calendar.day', $day->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentBlocks.0.completed', false)
            ->where('appointmentBlocks.0.canComplete', true)
            ->etc());

    $this->actingAs($guest)->post(route('appointments.completion.store', $appointment));

    $this->actingAs($guest)
        ->get(route('calendar.day', $day->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentBlocks.0.completed', true)
            ->etc());
});

/**
 * Der Haken an einer Verabredung zählt nicht in die Tagesquote.
 *
 * Der Nenner ist „was ich mir vorgenommen habe", und eine Verabredung ist die
 * Gewohnheit einer anderen Person. Sie mitzuzählen hieße, den eigenen Tag mit
 * fremden Vorsätzen zu füllen — und an einem Tag ohne eigene Gewohnheit stünde
 * plötzlich „100 % erledigt" für etwas, das man gar nicht führt.
 */
test('a ticked appointment does not count towards the daily quota', function () {
    [, $guest, , $appointment] = joined();

    // Eine eigene Gewohnheit, damit die Quote überhaupt einen Nenner hat.
    Habit::factory()->for($guest)
        ->fixedSchedule('08:00', [1, 2, 3, 4, 5, 6, 7])
        ->create(['title' => 'Wasser trinken']);

    $this->actingAs($guest)->post(route('appointments.completion.store', $appointment));

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Eine eigene Gewohnheit, keine erledigt: Der Haken an der
            // Verabredung rührt die Quote nicht an.
            ->where('todayProgress.total', 1)
            ->where('todayProgress.completed', 0)
            ->where('todayProgress.percentage', 0)
            // Und die fremde Gewohnheit steht in keiner Tagesliste.
            ->has('habits', 1)
            ->etc());
});

/**
 * Übernommen zählt sie sehr wohl — dann ist sie eine eigene.
 *
 * Genau die Grenze, die der Nutzer gezogen hat: mitmachen ist ein Tag,
 * übernehmen ist ein Vorsatz. Erst der zweite gehört in die Quote.
 */
test('an adopted habit counts towards the daily quota like any other', function () {
    $owner = User::factory()->create();
    $guest = User::factory()->create();

    Friendship::factory()->accepted()->create([
        'requester_id' => $owner->id,
        'addressee_id' => $guest->id,
    ]);

    $habit = Habit::factory()->for($owner)
        ->fromTemplate(HabitTemplate::Joggen)
        ->fixedSchedule('07:30', [1, 3])
        ->create();

    $this->actingAs($guest)
        ->post(route('habits.adoptions.store'), [
            'template_key' => $habit->template()?->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '18:30',
            // Der eigene Rhythmus: täglich, damit sie heute ansteht.
            'scheduled_days' => [1, 2, 3, 4, 5, 6, 7],
        ])
        ->assertRedirect();

    $adopted = $guest->habits()->sole();

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('todayProgress.total', 1)
            ->where('todayProgress.completed', 0)
            ->etc());

    $this->actingAs($guest)->post(route('habits.completions.store', $adopted));

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('todayProgress.total', 1)
            ->where('todayProgress.completed', 1)
            ->where('todayProgress.percentage', 100)
            ->etc());
});
