<?php

use App\Enums\HabitTemplate;
use App\Enums\ScheduleType;
use App\Models\Appointment;
use App\Models\Course;
use App\Models\Friendship;
use App\Models\Habit;
use App\Models\HabitDayShift;
use App\Models\Semester;
use App\Models\SleepSchedule;
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

/**
 * Ein Kurs der gefragten Person, der im Weg steht.
 *
 * Am Dienstag, weil die Verabredung dort liegt, und großzügig um sie herum:
 * Der Fall soll an der Überschneidung scheitern, nicht an der Viertelstunde
 * Luft daneben.
 */
function collidingCourse(User $guest, string $from = '07:00', string $to = '08:30'): Course
{
    $semester = Semester::factory()->for($guest)->create();

    return Course::factory()->for($semester)
        ->onWeekday(2)
        ->at($from, $to)
        ->create(['title' => 'Mathe 1']);
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

test('a course reaches the card, and it names that a course does not move', function () {
    [, $guest] = morningInvitation();
    collidingCourse($guest);

    // Lange sah nur `options()` den Stundenplan: Die Ausweichzeiten mieden die
    // Vorlesung, die Prüfung darüber kannte sie nicht. Wer um sieben Mathe
    // hatte und für halb acht gefragt wurde, sagte zu — und hatte danach zwei
    // Dinge zur selben Zeit.
    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict.title', 'Mathe 1')
            ->where('appointmentRequests.0.conflict.from', '07:00')
            ->where('appointmentRequests.0.conflict.to', '08:30')
            // Kein Ausweg und nichts, was ihn tragen könnte: Ein Kurs rückt
            // nicht, und drei Zeiten anzubieten, die nichts bewirken, wäre
            // schlimmer als keine.
            ->where('appointmentRequests.0.conflict.kind', 'course')
            ->where('appointmentRequests.0.conflict.habitId', null)
            ->where('appointmentRequests.0.conflict.options', [])
        );
});

test('accepting is refused while a course runs at that time', function () {
    [, $guest, $appointment] = morningInvitation();
    collidingCourse($guest);

    $this->actingAs($guest)
        ->from(route('dashboard'))
        ->patch(route('appointments.update', $appointment))
        ->assertSessionHasErrors('appointment');

    expect($appointment->refresh()->accepted_at)->toBeNull();
});

test('a course on another day leaves the promise alone', function () {
    [, $guest] = morningInvitation();

    // Derselbe Kurs, nur mittwochs — die Verabredung liegt am Dienstag.
    $semester = Semester::factory()->for($guest)->create();
    Course::factory()->for($semester)->onWeekday(3)->at('07:00', '08:30')->create();

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict', null)
        );
});

test('a cancelled lecture is a free morning', function () {
    [, $guest, $appointment] = morningInvitation();
    $course = collidingCourse($guest);

    // An diesem einen Dienstag fällt sie aus. Ein Kurs, der nicht
    // stattfindet, belegt auch keine Zeit — sonst stünde eine ausgefallene
    // Vorlesung einer Zusage im Weg.
    $course->exceptions()->create([
        'on_date' => $appointment->scheduled_for,
        'starts_at' => null,
        'ends_at' => null,
    ]);

    $this->actingAs($guest)
        ->from(route('dashboard'))
        ->patch(route('appointments.update', $appointment))
        ->assertSessionHasNoErrors();

    expect($appointment->refresh()->accepted_at)->not->toBeNull();
});

test('an own habit still wins the card when both are in the way', function () {
    [, $guest] = morningInvitation();

    // Der Kurs liegt später am Tag und trotzdem noch im Weg; genannt gehört
    // das Erste im Tag — sonst entschiede die Reihenfolge der Datenbank,
    // welcher von zwei Konflikten auf der Karte steht.
    $reading = collidingHabit($guest, '07:20');
    collidingCourse($guest, '08:05', '09:30');

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict.habitId', $reading->id)
            ->where('appointmentRequests.0.conflict.kind', 'habit')
        );
});

test('the night is no place for a promise', function () {
    [$owner, $guest, $appointment] = morningInvitation();

    // Die fragende Gewohnheit rückt auf drei Uhr nachts. Beim Gefragten steht
    // dort nichts — genau deshalb ging die Zusage lange durch. Der Rahmen des
    // Tages gilt überall sonst ({@see HabitDayShiftController::guard()}); die
    // Zusage war der eine Weg, auf dem er nicht galt.
    $owner->habits()->first()->update(['scheduled_time' => '03:00']);

    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict.kind', 'night')
            // Nichts liegt im Weg — der Rahmen ist es, der nicht so weit reicht.
            ->where('appointmentRequests.0.conflict.title', null)
            ->where('appointmentRequests.0.conflict.habitId', null)
            ->where('appointmentRequests.0.conflict.options', [])
            ->where('appointmentRequests.0.conflict.from', SleepSchedule::DefaultWakeTime)
            ->where('appointmentRequests.0.conflict.to', SleepSchedule::DefaultBedtime)
        );

    $this->actingAs($guest)
        ->from(route('dashboard'))
        ->patch(route('appointments.update', $appointment))
        ->assertSessionHasErrors('appointment');

    expect($appointment->refresh()->accepted_at)->toBeNull();
});

test('a later riser can be asked later, and only then', function () {
    [$owner, $guest, $appointment] = morningInvitation();

    // Um sechs, eine Stunde vor dem üblichen Aufstehen.
    $owner->habits()->first()->update(['scheduled_time' => '06:00']);

    $this->actingAs($guest)
        ->from(route('dashboard'))
        ->patch(route('appointments.update', $appointment))
        ->assertSessionHasErrors('appointment');

    // Und mit einem Schlafplan, der an diesem Wochentag früher beginnt, geht
    // dieselbe Zusage durch. Der Rahmen entscheidet, nicht die Uhrzeit.
    $guest->sleepSchedules()->create([
        'weekday' => $appointment->scheduled_for->dayOfWeekIso,
        'wake_time' => '05:30',
        'bedtime' => '22:00',
        'alarm_enabled' => false,
    ]);

    $this->actingAs($guest->refresh())
        ->from(route('dashboard'))
        ->patch(route('appointments.update', $appointment))
        ->assertSessionHasNoErrors();

    expect($appointment->refresh()->accepted_at)->not->toBeNull();
});

test('the frame of that one day counts, not the one of that weekday', function () {
    [$owner, $guest, $appointment] = morningInvitation();

    $owner->habits()->first()->update(['scheduled_time' => '06:00']);

    // An diesem einen Tag steht der Gefragte um fünf auf — als Ausnahme, nicht
    // im Wochenplan. Die Ausweichzeiten lasen lange den Wochenplan und hätten
    // den Tag hier zu spät beginnen lassen.
    $guest->sleepDayOverrides()->create([
        'on_date' => $appointment->scheduled_for,
        'wake_time' => '05:00',
        'bedtime' => null,
    ]);

    $this->actingAs($guest->refresh())
        ->from(route('dashboard'))
        ->patch(route('appointments.update', $appointment))
        ->assertSessionHasNoErrors();

    expect($appointment->refresh()->accepted_at)->not->toBeNull();
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
        ->get(route('calendar.day', $appointment->scheduled_for->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.anchor', '18:00 · nur an diesem Tag')
            ->where('blocks.0.timeRange', '18:00 – 18:30')
        );

    // Am Tag darauf liegt sie wieder, wo sie lag.
    $this->actingAs($guest)
        ->get(route('calendar.day', $appointment->scheduled_for->copy()->addDay()->toDateString()))
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

/**
 * Zwei Freunde, dieselbe Minute.
 *
 * Eine fremde Gewohnheit steht in keinem eigenen Plan — deshalb sah die
 * Prüfung sie nicht, und beide Zusagen gingen durch. Danach lagen zwei
 * gemeinsame Frühstücke übereinander, jedes mit jemand anderem.
 */
test('a promise already given blocks the next one', function () {
    [, $guest, $appointment] = morningInvitation();

    // Eine zweite Anfrage von einer dritten Person, zur selben Zeit.
    $other = User::factory()->create(['name' => 'Aylin']);
    Friendship::factory()->accepted()->create([
        'requester_id' => $other->id,
        'addressee_id' => $guest->id,
    ]);

    $theirs = Habit::factory()->for($other)
        ->fromTemplate(HabitTemplate::Spazieren)
        ->fixedSchedule('07:30', [1, 2, 3, 4, 5])
        ->withMeasure(30)
        ->create();

    $second = Appointment::factory()->create([
        'habit_id' => $theirs->id,
        'requester_id' => $other->id,
        'invitee_id' => $guest->id,
        'scheduled_for' => $appointment->scheduled_for,
    ]);

    // Die erste geht durch — der Tag ist frei.
    $this->actingAs($guest)
        ->patch(route('appointments.update', $appointment))
        ->assertSessionHasNoErrors();

    // Die zweite nicht mehr.
    $this->actingAs($guest)
        ->from(route('dashboard'))
        ->patch(route('appointments.update', $second))
        ->assertSessionHasErrors('appointment');

    expect($second->refresh()->accepted_at)->toBeNull();

    // Und die Karte sagt, warum — mit dem Namen, nicht nur mit der Uhrzeit.
    $this->actingAs($guest)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('appointmentRequests.0.conflict.kind', 'appointment')
            ->where('appointmentRequests.0.conflict.title', 'Joggen gehen mit Berkay')
            // Absagen ist der einzige Weg — Ausweichzeiten gäbe es nicht.
            ->where('appointmentRequests.0.conflict.options', [])
            ->etc()
        );
});

/**
 * Und die Gegenrichtung: eine neue Gewohnheit auf eine Zusage legen.
 *
 * `SlotConflict` baute den Tag aus Gewohnheiten und Stundenplan — die Zusage
 * fehlte darin. Man konnte also anlegen, was gleich neben dem gemeinsamen
 * Frühstück lag, und niemand widersprach.
 */
test('a new habit cannot be laid on a promise', function () {
    [, $guest, $appointment] = morningInvitation();

    $this->actingAs($guest)
        ->patch(route('appointments.update', $appointment))
        ->assertSessionHasNoErrors();

    // Die Verabredung läuft 07:30 bis 08:00 an einem Dienstag.
    expect($appointment->scheduled_for->dayOfWeekIso)->toBe(2);

    $this->actingAs($guest)
        ->from(route('habits.create'))
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Lesen->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '07:45',
            'scheduled_days' => [2],
        ])
        ->assertSessionHasErrors();

    expect($guest->habits()->count())->toBe(0);
});
