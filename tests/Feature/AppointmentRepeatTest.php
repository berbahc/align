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
 * „Nochmal ausmachen?" — der Wiederholungs-Weg aus community_feature3.md §7.
 *
 * Die Verabredung bleibt einmalig: Eine wiederkehrende wäre faktisch der
 * gemeinsame Kalender, den die Umfrage mit Top-2 46 % und sieben Hard-No-Stimmen
 * am härtesten ablehnt (§9). Statt eines Abos entsteht die nächste Verabredung
 * jedes Mal neu — genau der Unterschied zu Felix' gescheitertem gemeinsamen
 * Sport, der „zu lose" vereinbart war und deshalb erodierte.
 *
 * Vorschlagen darf nur, wem die Gewohnheit gehört
 * ({@see ProposeAppointmentRequest::authorize()}). Der Weg steht deshalb der
 * fragenden Seite immer offen und der gefragten erst, wenn sie übernommen hat.
 *
 * @return array{0: User, 1: User, 2: Habit, 3: Appointment}
 */
function joinedPair(): array
{
    $owner = User::factory()->create(['name' => 'Berkay']);
    $guest = User::factory()->create(['name' => 'Silas']);

    Friendship::factory()->accepted()->create([
        'requester_id' => $owner->id,
        'addressee_id' => $guest->id,
    ]);

    $habit = Habit::factory()->for($owner)
        ->fromTemplate(HabitTemplate::Joggen)
        ->fixedSchedule('17:00', [1, 2, 3, 4, 5, 6, 7])
        ->create();

    $appointment = Appointment::factory()->accepted()->create([
        'habit_id' => $habit->id,
        'requester_id' => $owner->id,
        'invitee_id' => $guest->id,
        'scheduled_for' => Carbon::today(),
    ]);

    return [$owner, $guest, $habit, $appointment];
}

test('the asking side gets the way back only after ticking off itself', function () {
    [$owner, , $habit] = joinedPair();

    // Vorher: nichts zu wiederholen, es hat ja noch nicht stattgefunden.
    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.companion.repeatHabitId', null)
            ->etc());

    $this->actingAs($owner)->post(route('habits.completions.store', $habit));

    // Danach: der Weg zeigt auf die eigene Gewohnheit, mit ihren eigenen Tagen.
    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.companion.repeatHabitId', $habit->id)
            ->has('habits.0.companion.repeatDays', Appointment::DayChoices)
            ->etc());
});

test('the asked side gets it through the habit they adopted', function () {
    [, $guest, $habit, $appointment] = joinedPair();

    $this->actingAs($guest)->post(route('appointments.completion.store', $appointment));

    // Ohne eigene Gewohnheit gibt es nichts vorzuschlagen — wer sie nicht
    // führt, wird gefragt, statt zu fragen.
    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('upcomingAppointments.0.repeatHabitId', null)
            ->etc());

    $this->actingAs($guest)
        ->post(route('habits.adoptions.store'), [
            'template_key' => $habit->template()?->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            // Der eigene Rhythmus, nicht der von Berkay.
            'scheduled_time' => '08:00',
            'scheduled_days' => [1, 2, 3, 4, 5, 6, 7],
        ])
        ->assertRedirect();

    $adopted = $guest->habits()->sole();

    // Seit die Übernahme dieselbe Sache in den eigenen Plan holt, steht die
    // Verabredung nicht mehr als eigene Karte darunter: Sie sitzt in der
    // Zeile, die es jetzt gibt — mit dem Doppel-Zeichen, wie auf der fragenden
    // Seite. Der Weg zurück hängt an derselben Stelle.
    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('upcomingAppointments', 0)
            // Die **eigene**, nicht die fremde.
            ->where('habits.0.companion.repeatHabitId', $adopted->id)
            ->etc());

    expect($adopted->id)->not->toBe($habit->id);
});

/**
 * Heute steht nicht mehr zur Wahl.
 *
 * Für heute steht ja schon die Verabredung, die gerade stattgefunden hat —
 * sie noch einmal anzubieten führte in die Abweisung („Für diesen Tag steht
 * schon eine Verabredung") und wäre die Wiederholung von etwas, das gerade
 * war. Angeboten wird der nächste Termin der Gewohnheit.
 */
test('the way back starts at the next occurrence, never again today', function () {
    [$owner, , $habit] = joinedPair();

    $this->actingAs($owner)->post(route('habits.completions.store', $habit));

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertInertia(function (AssertableInertia $page) {
            $days = collect(
                $page->toArray()['props']['habits'][0]['companion']['repeatDays'],
            );

            expect($days)->toHaveCount(Appointment::DayChoices)
                ->and($days->pluck('value'))
                ->not->toContain(Carbon::today()->toDateString())
                // Die tägliche Gewohnheit steht morgen wieder an.
                ->and($days->first()['value'])
                ->toBe(Carbon::tomorrow()->toDateString());
        });
});

/**
 * Und keiner der angebotenen Tage darf über das Fenster hinausragen.
 *
 * Der Server prüft beim Absenden gegen die Liste **ab heute**. Finge die
 * Wiederholung morgen an und rechnete von dort sieben Tage weiter, läge ihr
 * letzter Vorschlag einen Tag jenseits davon — die Oberfläche böte etwas an,
 * das beim Tippen durchfiele.
 */
test('no offered day lies beyond the window the server checks', function () {
    [$owner, $guest, $habit] = joinedPair();

    $this->actingAs($owner)->post(route('habits.completions.store', $habit));

    $days = collect(Appointment::dayChoicesFor(
        $habit,
        Carbon::tomorrow(),
    ))->pluck('value');

    $last = Carbon::today()->addDays(Appointment::DayHorizon - 1)->toDateString();

    expect($days->every(fn (string $day): bool => $day <= $last))->toBeTrue();

    // Und der Beweis am lebenden Weg: Jeder angebotene Tag geht durch.
    foreach ($days as $day) {
        $this->actingAs($owner)
            ->post(route('appointments.store', $habit), [
                'friend_id' => $guest->id,
                'scheduled_for' => $day,
            ])
            ->assertSessionHasNoErrors();
    }
});

/**
 * Der §6-Test: Der Weg darf nichts über die andere Person verraten.
 *
 * Erschiene er erst, wenn beide abgehakt haben, wäre allein seine Anwesenheit
 * die Auskunft „die andere Person ist fertig" — der Fremdfortschritt, den
 * community_feature3.md §6 ausschließt.
 */
test('the way back never depends on what the other person did', function () {
    [$owner, $guest, , $appointment] = joinedPair();

    // Nur die gefragte Seite hakt ab.
    $this->actingAs($guest)->post(route('appointments.completion.store', $appointment));

    // Bei der fragenden Seite ändert das nichts: Sie war selbst noch nicht.
    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.companion.repeatHabitId', null)
            ->etc());
});

/**
 * Der Weg trägt: eine neue Verabredung für **einen** Tag, kein Abo.
 */
test('repeating creates one more appointment and leaves the old one alone', function () {
    [$owner, $guest, $habit, $appointment] = joinedPair();

    $this->actingAs($owner)->post(route('habits.completions.store', $habit));

    // Der nächste angebotene Tag — dieselbe Wahl, die die Oberfläche zeigt.
    $days = Appointment::dayChoicesFor($habit);
    $next = collect($days)->firstWhere('value', '!=', Carbon::today()->toDateString());

    $this->actingAs($owner)
        ->post(route('appointments.store', $habit), [
            'friend_id' => $guest->id,
            'scheduled_for' => $next['value'],
        ])
        ->assertSessionHasNoErrors();

    expect(Appointment::query()->count())->toBe(2)
        // Die alte bleibt, wie sie war — die neue ist eine eigene Frage.
        ->and($appointment->refresh()->scheduled_for->toDateString())
        ->toBe(Carbon::today()->toDateString());

    $created = Appointment::query()->whereDate('scheduled_for', $next['value'])->sole();

    expect($created->accepted_at)->toBeNull()
        ->and($created->invitee_id)->toBe($guest->id);
});

/** Ohne Katalog-Vorlage lässt sich die eigene Entsprechung nicht finden. */
test('a habit from before the catalog offers no way back to the asked side', function () {
    [, $guest, $habit, $appointment] = joinedPair();

    $habit->update(['template_key' => null]);
    Habit::factory()->for($guest)->create(['template_key' => null]);

    $this->actingAs($guest)->post(route('appointments.completion.store', $appointment));

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('upcomingAppointments.0.repeatHabitId', null)
            ->etc());
});
