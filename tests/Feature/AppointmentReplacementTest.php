<?php

use App\Enums\HabitTemplate;
use App\Models\Appointment;
use App\Models\Course;
use App\Models\Friendship;
use App\Models\Habit;
use App\Models\Semester;
use App\Models\User;
use App\Support\DayPlan;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Dieselbe Sache steht einmal im Tag, nicht zweimal.
 *
 * Wer mit Aileen frühstückt und selbst Frühstück im Plan hat, frühstückt an
 * dem Tag einmal. Die eigene Zeile fällt weg, der gemeinsame Eintrag nimmt
 * ihren Platz, und ein Haken zählt für beides — sonst bestraft die App genau
 * das Verhalten, für das dieses Feature gebaut wurde.
 *
 * Woran „dieselbe Sache" hängt, ist die Vorlage aus dem Katalog und nicht der
 * Titel: „Frühstücken" und „Frühstück" wären zwei Zeichenketten und dasselbe
 * Essen.
 *
 * Eigene Fassung statt einer geteilten Hilfsfunktion — dieselbe Begründung
 * wie in AppointmentConflictTest.php: Pest lädt Testdateien einzeln.
 *
 * @return array{0: User, 1: User, 2: Appointment}
 */
function sharedMeal(HabitTemplate $theirs = HabitTemplate::Fruehstuecken, string $time = '09:00'): array
{
    Carbon::setTestNow(Carbon::parse('2026-09-07 06:00'));

    $aileen = User::factory()->create(['name' => 'Aileen']);
    $me = User::factory()->create(['name' => 'Berkay']);

    Friendship::factory()->accepted()->create([
        'requester_id' => $aileen->id,
        'addressee_id' => $me->id,
    ]);

    $habit = Habit::factory()->for($aileen)
        ->fromTemplate($theirs)
        ->fixedSchedule($time, [1, 2, 3, 4, 5])
        ->withMeasure(20)
        ->create();

    $appointment = Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $aileen->id,
        'invitee_id' => $me->id,
        'scheduled_for' => Carbon::today(),
    ]);

    return [$aileen, $me, $appointment];
}

/**
 * Die eigene Gewohnheit — dieselbe Sache, eigene Uhrzeit.
 */
function ownHabit(User $me, HabitTemplate $template = HabitTemplate::Fruehstuecken, string $time = '08:00'): Habit
{
    return Habit::factory()->for($me)
        ->fromTemplate($template)
        ->fixedSchedule($time, [1, 2, 3, 4, 5])
        ->withMeasure(20)
        ->create();
}

test('the same thing at another time is no conflict', function () {
    [, $me] = sharedMeal();
    ownHabit($me);

    // Acht gegen neun: Als Überschneidung gelesen wäre das ein Konflikt mit
    // Ausweichzeiten. Es ist aber keiner — es ist zweimal Frühstück.
    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict', null)
            ->where('appointmentRequests.0.replaces.title', 'Frühstücken')
            ->where('appointmentRequests.0.replaces.moment', '08:00')
        );
});

test('the same thing at the same time is no conflict either', function () {
    [, $me] = sharedMeal(time: '08:00');
    ownHabit($me, time: '08:00');

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict', null)
        );
});

test('a different thing at the same time stays a conflict', function () {
    [, $me] = sharedMeal(time: '08:00');

    // Radfahren um acht gegen Frühstück um acht: verschiedene Dinge, dieselbe
    // Minute. Daran ändert das Ersetzen nichts.
    ownHabit($me, HabitTemplate::Fahrrad, '08:00');

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict.kind', 'habit')
            ->where('appointmentRequests.0.replaces', null)
        );
});

test('it works for anything from the catalogue, not only breakfast', function () {
    [, $me] = sharedMeal(HabitTemplate::Fahrrad, '17:00');
    ownHabit($me, HabitTemplate::Fahrrad, '18:00');

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict', null)
            ->where('appointmentRequests.0.replaces.title', 'Fahrrad fahren')
        );
});

test('the own row stays and carries the second circle', function () {
    [, $me, $appointment] = sharedMeal();
    $breakfast = ownHabit($me);

    $this->actingAs($me)->patch(route('appointments.update', $appointment))
        ->assertSessionHasNoErrors();

    // Frühstücken bleibt in der Tagesliste — es ist eine tägliche Gewohnheit,
    // an ihr hängen Ketten, und sie zählt in die Serie. Nur der zweite Kreis
    // kommt dazu, genau wie bei der fragenden Person.
    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.id', $breakfast->id)
            ->where('habits.0.companion.name', 'Aileen')
            ->where('habits.0.companion.pending', false)
            // Und die zweite Zeile darunter fällt weg: Sie sagte nur noch
            // einmal, was zwei Zentimeter darüber steht.
            ->has('upcomingAppointments', 0)
            ->etc()
        );
});

test('the day moves to the shared time, and says so', function () {
    [, $me, $appointment] = sharedMeal();
    $breakfast = ownHabit($me);

    $this->actingAs($me)->patch(route('appointments.update', $appointment));

    // Um neun statt um acht — für diesen einen Tag. Dieselbe Ausnahme, die
    // auch das Platzmachen schreibt; deshalb stimmt der Kalender von selbst.
    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.timeLabel', '09:00')
            ->where('habits.0.companion.insteadOf', '08:00')
            ->etc()
        );

    $this->actingAs($me)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.id', $breakfast->id)
            ->where('blocks.0.startMinute', 9 * 60)
            ->where('blocks.0.companion.name', 'Aileen')
            // Und kein zweiter Block daneben: derselbe Morgen, einmal.
            ->has('appointmentBlocks', 0)
            ->etc()
        );

    expect($breakfast->dayShifts()->whereDate('shifted_on', Carbon::today())->sole()->scheduled_time->format('H:i'))
        ->toBe('09:00');
});

test('saying no gives the own time back', function () {
    [, $me, $appointment] = sharedMeal();
    $breakfast = ownHabit($me);

    $this->actingAs($me)->patch(route('appointments.update', $appointment));
    $this->actingAs($me)->delete(route('appointments.destroy', $appointment));

    // Ohne das gemeinsame Frühstück gilt wieder die eigene Zeit.
    expect($breakfast->dayShifts()->whereDate('shifted_on', Carbon::today())->exists())->toBeFalse();

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.timeLabel', '08:00')
            ->where('habits.0.companion', null)
            ->etc()
        );
});

test('one tick, on the own row', function () {
    [, $me, $appointment] = sharedMeal();
    $breakfast = ownHabit($me);

    $this->actingAs($me)->patch(route('appointments.update', $appointment));

    // Abgehakt wird die eigene Zeile — die Zusage trägt keinen zweiten Haken,
    // sonst wäre derselbe Morgen zweimal zu erledigen.
    expect($appointment->refresh()->isCompletableBy($me->refresh()))->toBeFalse();

    $this->actingAs($me)->post(route('habits.completions.store', $breakfast));

    // Die Serie läuft weiter, und die andere Seite sieht die Zusage als
    // eingelöst.
    expect($breakfast->completions()->whereDate('completed_on', Carbon::today())->exists())->toBeTrue()
        ->and($appointment->refresh()->wasDoneBy($me->refresh()))->toBeTrue();
});

test('a habit that does not run that day is not replaced', function () {
    [, $me] = sharedMeal();

    // Frühstück nur am Wochenende; die Verabredung liegt an einem Montag.
    Habit::factory()->for($me)
        ->fromTemplate(HabitTemplate::Fruehstuecken)
        ->fixedSchedule('08:00', [6, 7])
        ->withMeasure(20)
        ->create();

    expect(Carbon::today()->dayOfWeekIso)->toBe(1);

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.replaces', null)
        );
});

test('a habit without a template is never the same thing', function () {
    [, $me] = sharedMeal();

    // Alte Zeilen aus der Zeit der freien Eingabe tragen keine Vorlage. Ohne
    // sie gibt es nichts zu vergleichen — raten wäre schlimmer als fragen.
    Habit::factory()->for($me)
        ->fixedSchedule('08:00', [1, 2, 3, 4, 5])
        ->withMeasure(20)
        ->create(['template_key' => null, 'title' => 'Frühstück']);

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.replaces', null)
        );
});

test('the asking side keeps its own entry', function () {
    [$aileen, , $appointment] = sharedMeal();

    // Aileen führt die Gewohnheit selbst — bei ihr ersetzt nichts etwas, ihr
    // Block bekommt nur den zweiten Kreis.
    $this->actingAs($aileen)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.id', $appointment->habit_id)
        );
});

/**
 * Berkay frühstückt „nach dem Aufstehen", Aylin um neun.
 *
 * Der Fall, der das Festnageln erzwungen hat: Eine Situation hat keine
 * Uhrzeit, sondern eine Stelle im Tag — und die rechnet jeder aus seinem
 * eigenen Schlafplan aus. Beide Kalender zeichneten korrekt ihren eigenen Tag,
 * und in den beiden Tagen stand derselbe Morgen an zwei Stellen.
 */
test('a situation gets one clock time, and both sides read the same', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-07 05:00'));

    $berkay = User::factory()->create(['name' => 'Berkay']);
    $aylin = User::factory()->create(['name' => 'Aylin']);

    Friendship::factory()->accepted()->create([
        'requester_id' => $berkay->id,
        'addressee_id' => $aylin->id,
    ]);

    // Seine hängt an der Situation, ihre an der Uhr.
    $his = Habit::factory()->for($berkay)
        ->fromTemplate(HabitTemplate::Fruehstuecken)
        ->withMeasure(30)
        ->create(['trigger_situation' => 'nach dem Aufstehen']);

    $hers = ownHabit($aylin, HabitTemplate::Fruehstuecken, '09:00');

    $this->actingAs($berkay)->post(route('appointments.store', $his), [
        'friend_id' => $aylin->id,
        'scheduled_for' => Carbon::today()->toDateString(),
    ])->assertSessionHasNoErrors();

    $appointment = Appointment::query()->sole();

    // Die Stelle in Berkays Tag, einmal als Uhrzeit festgehalten.
    $his->setRelation('user', $berkay);
    expect($appointment->starts_at)->not->toBeNull()
        ->and($appointment->startMinute())->toBe($his->dayStartMinute(Carbon::today()));

    // Und die Anfrage nennt sie, statt eines Ankers, den Aylin auf ihr eigenes
    // Aufstehen bezöge.
    $this->actingAs($aylin)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.anchor', $appointment->timeLabel())
            ->etc());

    $this->actingAs($aylin)->patch(route('appointments.update', $appointment))
        ->assertSessionHasNoErrors();

    // Dieselbe Minute in beiden Kalendern: bei ihm der eigene Block, bei ihr
    // die Zeile, die an dem Tag mitgezogen ist.
    $this->actingAs($berkay)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.startMinute', $appointment->startMinute())
            ->etc());

    $this->actingAs($aylin)
        ->get(route('calendar.day', Carbon::today()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.id', $hers->id)
            ->where('blocks.0.startMinute', $appointment->startMinute())
            ->etc());
});

/**
 * Und die Lücke, die dabei mit aufging: Für eine Verabredung an einer
 * Situation lief vorher **gar keine** Konfliktprüfung — `windowOf()` gab null
 * zurück, weil es keine Uhrzeit gab.
 */
test('a situation is checked against the day like anything else', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-07 05:00'));

    $berkay = User::factory()->create(['name' => 'Berkay']);
    $aylin = User::factory()->create(['name' => 'Aylin']);

    Friendship::factory()->accepted()->create([
        'requester_id' => $berkay->id,
        'addressee_id' => $aylin->id,
    ]);

    $his = Habit::factory()->for($berkay)
        ->fromTemplate(HabitTemplate::Joggen)
        ->withMeasure(30)
        ->create(['trigger_situation' => 'nach dem Aufstehen']);

    $this->actingAs($berkay)->post(route('appointments.store', $his), [
        'friend_id' => $aylin->id,
        'scheduled_for' => Carbon::today()->toDateString(),
    ])->assertSessionHasNoErrors();

    $appointment = Appointment::query()->sole();

    // Bei Aylin liegt zu genau dieser Minute ein Kurs.
    $semester = Semester::factory()->for($aylin)->create();
    Course::factory()->for($semester)
        ->onWeekday(Carbon::today()->dayOfWeekIso)
        ->at(
            DayPlan::toTime($appointment->startMinute()),
            DayPlan::toTime($appointment->startMinute() + 90),
        )
        ->create(['title' => 'Mathe 1']);

    $this->actingAs($aylin)
        ->from(route('dashboard'))
        ->patch(route('appointments.update', $appointment))
        ->assertSessionHasErrors('appointment');

    expect($appointment->refresh()->accepted_at)->toBeNull();
});

/**
 * „Nochmal ausmachen?" — wer drückt, bringt seine eigene Zeit mit.
 *
 * Die Wiederholung ist keine Kopie der alten Verabredung, sondern eine neue
 * Frage: Sie hängt an der Gewohnheit dessen, der fragt
 * ({@see Appointment::repeatableHabitFor()}). Bei Berkay ist das eine
 * Situation, bei Aylin eine Uhr — und beide Male gilt, was in **seinem** Tag
 * steht, nicht was beim letzten Mal galt.
 */
test('asking again brings the asker own time, situation or clock', function () {
    // Ein Montag. Die Wiederholung zielt auf den Mittwoch.
    Carbon::setTestNow(Carbon::parse('2026-09-07 05:00'));

    $berkay = User::factory()->create(['name' => 'Berkay']);
    $aylin = User::factory()->create(['name' => 'Aylin']);

    Friendship::factory()->accepted()->create([
        'requester_id' => $berkay->id,
        'addressee_id' => $aylin->id,
    ]);

    // Berkay steht mittwochs später auf als montags. Genau das muss die
    // Situation am gefragten Tag lesen — nicht die Aufstehzeit von heute.
    foreach ([1 => '06:00', 3 => '09:00'] as $weekday => $wake) {
        $berkay->sleepSchedules()->create([
            'weekday' => $weekday,
            'wake_time' => $wake,
            'bedtime' => '23:00',
            'alarm_enabled' => false,
        ]);
    }

    $his = Habit::factory()->for($berkay)
        ->fromTemplate(HabitTemplate::Fruehstuecken)
        ->withMeasure(30)
        ->create(['trigger_situation' => 'nach dem Aufstehen']);

    $hers = ownHabit($aylin, HabitTemplate::Fruehstuecken, '08:00');

    $wednesday = Carbon::parse('2026-09-09');

    // Berkay fragt: seine Situation, aufgelöst am Mittwoch.
    $this->actingAs($berkay->refresh())
        ->post(route('appointments.store', $his), [
            'friend_id' => $aylin->id,
            'scheduled_for' => $wednesday->toDateString(),
        ])->assertSessionHasNoErrors();

    expect(Appointment::query()->sole()->starts_at->format('H:i'))->toBe('09:00');

    Appointment::query()->delete();

    // Aylin fragt: ihre feste Uhrzeit, unberührt von Berkays Anker.
    $this->actingAs($aylin)
        ->post(route('appointments.store', $hers), [
            'friend_id' => $berkay->id,
            'scheduled_for' => $wednesday->toDateString(),
        ])->assertSessionHasNoErrors();

    expect(Appointment::query()->sole()->starts_at->format('H:i'))->toBe('08:00');
});

/**
 * Und die Ausnahme eines einzelnen Tages schlägt den Wochenplan.
 *
 * Wer am Mittwoch ausnahmsweise um halb elf aufsteht, frühstückt an dem
 * Mittwoch um halb elf — auch wenn sein Wochenplan neun sagt.
 */
test('a slept-in day moves the situation with it', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-07 05:00'));

    $berkay = User::factory()->create(['name' => 'Berkay']);
    $aylin = User::factory()->create(['name' => 'Aylin']);

    Friendship::factory()->accepted()->create([
        'requester_id' => $berkay->id,
        'addressee_id' => $aylin->id,
    ]);

    $berkay->sleepSchedules()->create([
        'weekday' => 3,
        'wake_time' => '09:00',
        'bedtime' => '23:00',
        'alarm_enabled' => false,
    ]);

    $wednesday = Carbon::parse('2026-09-09');

    $berkay->sleepDayOverrides()->create([
        'on_date' => $wednesday,
        'wake_time' => '10:30',
        'bedtime' => null,
    ]);

    $his = Habit::factory()->for($berkay)
        ->fromTemplate(HabitTemplate::Fruehstuecken)
        ->withMeasure(30)
        ->create(['trigger_situation' => 'nach dem Aufstehen']);

    $this->actingAs($berkay->refresh())
        ->post(route('appointments.store', $his), [
            'friend_id' => $aylin->id,
            'scheduled_for' => $wednesday->toDateString(),
        ])->assertSessionHasNoErrors();

    expect(Appointment::query()->sole()->starts_at->format('H:i'))->toBe('10:30');
});
