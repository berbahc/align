<?php

use App\Enums\ScheduleType;
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

test('a day is accepted as far as the week reaches', function (int $offset, bool $allowed) {
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
    // Die Oberfläche stellt drei Tage zur Wahl, zulässig ist die ganze Woche
    // ({@see Appointment::DayHorizon}). Die beiden Zahlen dürfen nicht
    // dieselbe sein: „Nochmal ausmachen?" fängt einen Tag später an, und sein
    // dritter Vorschlag läge sonst jenseits der Prüfung — die Oberfläche böte
    // einen Tag an, der beim Tippen durchfiele.
    'in three days' => [3, true],
    'the far end of the week' => [Appointment::DayHorizon - 1, true],
    // Und dahinter beginnt das Vorausplanen.
    'a week out' => [Appointment::DayHorizon, false],
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

/**
 * Die eigene offene Anfrage von heute steht in der Zeile, nicht daneben.
 *
 * Vorher stand dieselbe Gewohnheit zweimal auf der Übersicht: einmal unter
 * „Heutige Gewohnheiten" und zwei Zentimeter darunter noch einmal als Karte
 * unter „Zusammen". Die Karte sagte nichts, was die Zeile nicht schon sagte.
 */
/**
 * Der Tag in einer Woche heißt nicht wie heute.
 *
 * Die Auswahl reicht sieben Tage weit, und der siebte trägt denselben
 * Wochentagsnamen wie heute. Wer samstags gefragt wurde, las „Samstag" — und
 * direkt daneben stand eine zweite Anfrage mit „Heute". Zwei Namen für
 * denselben Wochentag, und keiner sagte, welcher gemeint ist.
 */
test('a day one week out is not called like today', function () {
    $saturday = Carbon::today()->startOfWeek()->addDays(5);
    Carbon::setTestNow($saturday);

    expect(Appointment::dayLabel($saturday))->toBe('heute')
        ->and(Appointment::dayLabel($saturday->copy()->addDay()))->toBe('morgen')
        ->and(Appointment::dayLabel($saturday->copy()->addDays(2)))->toBe('Montag')
        ->and(Appointment::dayLabel($saturday->copy()->addDays(7)))->toBe('nächsten Samstag');

    Carbon::setTestNow();
});

/**
 * Fragt dieselbe Person zweimal, steht ihr Name einmal darüber.
 *
 * Die Oberfläche bündelt nur die Kopfzeile — die Fragen bleiben getrennt, denn
 * es sind verschiedene Gewohnheiten an verschiedenen Tagen. Damit sie sich
 * bündeln lassen, muss die fragende Person mitreisen; über den Namen ginge es
 * auch, aber zwei Freunde dürfen gleich heißen.
 */
test('every request says who asked, as an id', function () {
    [$me, $friend, $habit] = pair();

    $second = Habit::factory()->for($me)->create([
        'title' => 'Vorlesung nachbereiten',
        'trigger_situation' => 'nach dem Mittagessen',
    ]);

    foreach ([$habit, $second] as $index => $subject) {
        Appointment::factory()->create([
            'habit_id' => $subject->id,
            'requester_id' => $me->id,
            'invitee_id' => $friend->id,
            'scheduled_for' => Carbon::today()->addDays($index),
        ]);
    }

    $this->actingAs($friend)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('appointmentRequests', 2, fn (AssertableInertia $row) => $row
                ->where('requesterId', $me->id)
                ->etc())
            ->etc());
});

/**
 * Das Nächste steht oben — auch wenn es später angelegt wurde.
 *
 * Vorher stand die Reihenfolge der Anlage da: Wer am Montag für nächste Woche
 * gefragt wurde und am Samstag noch einmal für denselben Tag, las die ferne
 * Frage zuerst. Heute ist aber die einzige, die keinen Aufschub duldet.
 */
test('the nearest request stands first, whatever the order it was made in', function () {
    [$me, $friend, $habit] = pair();

    $second = Habit::factory()->for($me)->create([
        'title' => 'Vorlesung nachbereiten',
        'trigger_situation' => 'nach dem Mittagessen',
    ]);

    // Zuerst angelegt, aber der fernere Tag.
    Appointment::factory()->create([
        'habit_id' => $second->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::today()->addDays(7),
    ]);

    // Danach angelegt, aber heute.
    Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::today(),
    ]);

    $this->actingAs($friend)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('appointmentRequests', 2)
            ->where('appointmentRequests.0.title', 'Laufen gehen')
            ->where('appointmentRequests.0.day', 'heute')
            ->where('appointmentRequests.1.title', 'Vorlesung nachbereiten')
            ->etc());
});

/**
 * Die Wochentage des Fragers gehen die gefragte Person nichts an.
 *
 * Eine Verabredung gilt für **einen** Tag, und der steht daneben („heute").
 * „17:00 · Mo, Mi" daneben las sich wie eine Verpflichtung für Montag und
 * Mittwoch — es ist aber nur der Rhythmus der anderen Person. Was bleibt, ist
 * der Moment im Tag: die Uhrzeit oder die Situation. Der eigene Rhythmus
 * entsteht erst beim Übernehmen, und den wählt man dort selbst.
 */
test('an appointment names the moment, never the other persons weekdays', function () {
    $me = User::factory()->create(['name' => 'Berkay']);
    $friend = User::factory()->create(['name' => 'Silas']);

    Friendship::factory()->accepted()->create([
        'requester_id' => $me->id,
        'addressee_id' => $friend->id,
    ]);

    $habit = Habit::factory()->for($me)
        ->fixedSchedule('17:00', [1, 3])
        ->create(['title' => 'Fokussiert lernen']);

    // Die Gewohnheit selbst nennt ihre Tage weiterhin — dort gehören sie hin.
    expect($habit->scheduleLabel())->toBe('17:00 · Mo, Mi')
        ->and($habit->momentLabel())->toBe('17:00');

    $appointment = Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::today(),
    ]);

    // Die offene Anfrage bei der gefragten Person …
    $this->actingAs($friend)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.anchor', '17:00')
            ->etc());

    // … und dieselbe Verabredung, nachdem sie zugesagt hat. Nicht über
    // `update()`: `accepted_at` steht bewusst nicht in `#[Fillable]`, und die
    // Zusage liefe still ins Leere.
    $appointment->accepted_at = now();
    $appointment->save();

    $this->actingAs($friend)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('upcomingAppointments.0.anchor', '17:00')
            ->etc());
});

/** Eine Situation ist der Moment — sie bleibt unverändert stehen. */
test('a situational habit keeps its situation as the moment', function () {
    $habit = Habit::factory()->make([
        'trigger_situation' => 'nach der Vorlesung',
    ]);

    expect($habit->momentLabel())->toBe('nach der Vorlesung');
});

test('an open request you sent for today merges into the habit row', function () {
    [$me, $friend, $habit] = pair();

    Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::today(),
    ]);

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 1, fn (AssertableInertia $row) => $row
                ->where('companion.name', 'Silas')
                ->where('companion.initial', 'S')
                // Gefragt, nicht zugesagt — die Zeile zeichnet daraus einen
                // gestrichelten zweiten Kreis ohne Initiale.
                ->where('companion.pending', true)
                ->etc())
            // Und keine zweite Nennung darunter.
            ->has('upcomingAppointments', 0)
            ->etc());
});

test('an accepted appointment for today says so in the row', function () {
    [$me, $friend, $habit] = pair();

    Appointment::factory()->accepted()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::today(),
    ]);

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 1, fn (AssertableInertia $row) => $row
                ->where('companion.pending', false)
                ->etc())
            ->etc());
});

/**
 * Die Grenze der Zusammenführung: Ohne Zeile kein Platz, in dem etwas stehen
 * könnte.
 *
 * Eine Mo–Fr-Gewohnheit steht am Samstag in keiner Tagesliste. Verschwände die
 * Verabredung trotzdem aus „Zusammen", wäre sie nirgends mehr — genau die
 * Lücke, die das Zusammenführen schließen sollte.
 */
test('a request for a habit that is not due today keeps its own card', function () {
    // Ein fester Samstag, damit der Test nicht vom Wochentag des Laufs abhängt.
    $saturday = Carbon::today()->startOfWeek()->addDays(5);
    Carbon::setTestNow($saturday);

    [$me, $friend, $habit] = pair();
    $habit->update([
        'schedule_type' => ScheduleType::Fixed,
        'trigger_situation' => null,
        'scheduled_time' => '17:00',
        'scheduled_days' => [1, 2, 3, 4, 5],
    ]);

    Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => $saturday,
    ]);

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 0)
            ->has('upcomingAppointments', 1)
            ->etc());

    Carbon::setTestNow();
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
            // Die Tage hängen an der Gewohnheit, nicht an der Seite: Jede
            // Zeile bringt die ihren mit.
            ->has('habits.0.appointmentDays', 3)
            ->where('habits.0.appointmentDays.0.label', 'heute')
            ->where('habits.0.appointmentDays.1.label', 'morgen')
            ->has('friends', 1)
            ->where('appointmentsEnabled', true)
            ->etc());
});

test('creating a habit leads to the companion step when there is someone to ask', function () {
    [$me] = pair();

    $this->actingAs($me)
        ->post(route('habits.store'), [
            'template_key' => 'krafttraining',
            'target_amount' => 45,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        // Zurück in den Assistenten — dort steht jetzt der letzte Schritt.
        ->assertRedirect(route('habits.create'))
        // Die Kennung trägt den Schritt: die Verabredung hängt daran.
        ->assertInertiaFlash('habitCreated.title', 'Krafttraining')
        ->assertInertiaFlash('habitCreated.anchor', 'nach dem Aufstehen')
        // Die Tage reisen mit der angelegten Gewohnheit, nicht mit der Seite:
        // Beim Aufruf des Formulars gab es sie noch gar nicht.
        ->assertInertiaFlash('habitCreated.days.0.label', 'heute');

    $this->actingAs($me)
        ->get(route('habits.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('habits/create')
            ->has('friends', 1)
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
            'template_key' => 'krafttraining',
            'target_amount' => 45,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertRedirect(route('dashboard'));
})->with(['no circle', 'appointments switched off']);

test('the companion step can create the appointment right away', function () {
    [$me, $friend] = pair();

    $this->actingAs($me)->post(route('habits.store'), [
        'template_key' => 'krafttraining',
        'target_amount' => 45,
        'trigger_situation' => 'nach dem Aufstehen',
    ]);

    $habit = $me->habits()->where('title', 'Krafttraining')->sole();

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

/**
 * Die Tage der Verabredung kommen aus der Gewohnheit, nicht aus dem Kalender.
 *
 * §4 hält für die Uhrzeit fest, dass die Verabredung keine eigene Zeitlogik
 * erfindet, sondern die vorhandene nutzt. Für die Tage galt das bis hierher
 * nicht: Angeboten wurden immer heute, morgen und übermorgen — auch für eine
 * Mo–Fr-Gewohnheit am Samstag, an drei Tagen also, an denen sie nicht stattfand.
 */
test('the offered days are the next occurrences of the habit, not the next calendar days', function () {
    // Ein Samstag: Die Mo–Fr-Gewohnheit steht an diesem und am nächsten Tag
    // nicht an, der nächste Termin ist Montag.
    Carbon::setTestNow(Carbon::parse('2026-08-15 10:00'));

    [$me, , $habit] = pair();
    $habit->forceFill(['schedule_type' => 'fixed', 'scheduled_time' => '17:00', 'scheduled_days' => [1, 2, 3, 4, 5]])->save();

    $days = Appointment::dayChoicesFor($habit);

    expect(array_column($days, 'label'))->toBe(['Montag', 'Dienstag', 'Mittwoch'])
        ->and($days[0]['value'])->toBe('2026-08-17');
});

test('a habit that comes around once a week offers the one day it has', function () {
    // Montag; die Gewohnheit steht sonntags an, also in sechs Tagen — noch
    // innerhalb der Woche, die der Horizont zulässt.
    Carbon::setTestNow(Carbon::parse('2026-08-17 10:00'));

    [$me, , $habit] = pair();
    $habit->forceFill(['schedule_type' => 'fixed', 'scheduled_time' => '09:00', 'scheduled_days' => [7]])->save();

    $days = Appointment::dayChoicesFor($habit);

    // Lieber eine ehrliche Wahl als drei erfundene: Der zweite Termin läge in
    // zwei Wochen und wäre Terminplanung, keine Verabredung.
    expect($days)->toHaveCount(1)
        ->and($days[0]['value'])->toBe('2026-08-23');
});

test('a slot that is over today is not offered for today', function () {
    // 18:30 an einem Dienstag, die Gewohnheit läuft um 17:00 — heute ist
    // vorbei, also fängt die Wahl morgen an.
    Carbon::setTestNow(Carbon::parse('2026-08-18 18:30'));

    [$me, , $habit] = pair();
    $habit->forceFill(['schedule_type' => 'fixed', 'scheduled_time' => '17:00', 'scheduled_days' => [1, 2, 3, 4, 5]])->save();

    expect(array_column(Appointment::dayChoicesFor($habit), 'label'))
        ->toBe(['morgen', 'Donnerstag', 'Freitag']);
});

test('a situation has no time that could be over', function () {
    // Dieselbe späte Stunde, aber „nach der Vorlesung" ist kein Zeitpunkt, den
    // die Uhr widerlegen könnte.
    Carbon::setTestNow(Carbon::parse('2026-08-18 23:30'));

    [$me, , $habit] = pair();

    expect(array_column(Appointment::dayChoicesFor($habit), 'label'))
        ->toBe(['heute', 'morgen', 'Donnerstag']);
});

test('a habit without a day of its own falls back to the next three days', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-17 10:00'));

    [$me, , $habit] = pair();
    // Über die Validierung ginge das nicht — im Bestand kann eine feste
    // Gewohnheit ohne gewählte Wochentage existieren. Ohne diesen Rückfall
    // bliebe die Wahl leer.
    $habit->forceFill([
        'schedule_type' => 'fixed',
        'trigger_situation' => null,
        'scheduled_time' => '17:00',
        'scheduled_days' => [],
    ])->save();

    expect(array_column(Appointment::dayChoicesFor($habit), 'label'))
        ->toBe(['heute', 'morgen', 'Mittwoch']);
});

test('a chained habit is offered on the days of the habit it hangs on', function () {
    // Samstag; der Anker läuft Mo–Fr, die gekoppelte Gewohnheit also auch.
    Carbon::setTestNow(Carbon::parse('2026-08-15 10:00'));

    [$me, , $anchor] = pair();
    $anchor->forceFill(['schedule_type' => 'fixed', 'scheduled_time' => '17:00', 'scheduled_days' => [1, 2, 3, 4, 5]])->save();

    $chained = Habit::factory()->for($me)->create([
        'title' => 'Dehnen',
        'schedule_type' => 'chained',
        'chained_to_habit_id' => $anchor->id,
        'trigger_situation' => null,
        'scheduled_time' => null,
        'scheduled_days' => null,
    ]);

    expect(array_column(Appointment::dayChoicesFor($chained), 'label'))
        ->toBe(['Montag', 'Dienstag', 'Mittwoch']);
});

test('a day the habit does not run on is refused even when it is tomorrow', function () {
    // Freitag: Morgen ist Samstag, und samstags läuft die Gewohnheit nicht.
    Carbon::setTestNow(Carbon::parse('2026-08-21 10:00'));

    [$me, $friend, $habit] = pair();
    $habit->forceFill(['schedule_type' => 'fixed', 'scheduled_time' => '17:00', 'scheduled_days' => [1, 2, 3, 4, 5]])->save();

    // Die Prüfung liest dieselbe Liste, die die Oberfläche anbietet — ein Tag
    // ohne Termin kommt auch dann nicht durch, wenn er nah genug läge.
    $this->actingAs($me)
        ->post(route('appointments.store', $habit), [
            'friend_id' => $friend->id,
            'scheduled_for' => Carbon::tomorrow()->toDateString(),
        ])
        ->assertSessionHasErrors('scheduled_for');

    $this->actingAs($me)
        ->post(route('appointments.store', $habit), [
            'friend_id' => $friend->id,
            'scheduled_for' => '2026-08-24',
        ])
        ->assertSessionHasNoErrors();
});

test('an appointment further out than three days still shows up as upcoming', function () {
    // Montag, Verabredung am Sonntag: Ohne das größere Fenster stünde sie
    // nirgends — weder auf der Übersicht noch im Community-Bereich.
    Carbon::setTestNow(Carbon::parse('2026-08-17 10:00'));

    [$me, $friend, $habit] = pair();

    Appointment::factory()->accepted()->create([
        'habit_id' => $habit->id,
        'requester_id' => $me->id,
        'invitee_id' => $friend->id,
        'scheduled_for' => Carbon::parse('2026-08-23'),
    ]);

    $this->actingAs($me)
        ->get(route('community'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('upcomingAppointments', 1)
            ->where('upcomingAppointments.0.day', 'Sonntag')
            ->etc());
});
