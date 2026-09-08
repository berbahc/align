<?php

use App\Models\Appointment;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Eine zugesagte Verabredung im Kalender — beide Seiten.
 *
 * Der Kalender kannte Verabredungen lange gar nicht: Er lädt die eigenen
 * Gewohnheiten, und wer zusagt, führt keine. Der gemeinsame Tag stand damit in
 * keinem der beiden Kalender — auf der gefragten Seite fehlte er ganz, auf der
 * fragenden fehlte, dass jemand mitmacht.
 *
 * Die Uhrzeiten sind überall festgenagelt: `HabitFactory` würfelt die Situation
 * aus, und ein Test über die Stelle im Tag wäre sonst von Lauf zu Lauf ein
 * anderer.
 */

/**
 * Legt eine zugesagte Verabredung an — die Gewohnheit gehört der fragenden Seite.
 *
 * @return array{0: User, 1: User, 2: Habit, 3: Appointment}
 */
function acceptedAppointment(Carbon $day, string $time = '17:00'): array
{
    $requester = User::factory()->create(['name' => 'Silas Weber']);
    $invitee = User::factory()->create(['name' => 'Berkay Bahcekapili']);

    $habit = Habit::factory()
        ->for($requester)
        ->fixedSchedule($time, [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(30)
        ->create(['title' => 'Laufen gehen']);

    $appointment = Appointment::factory()->accepted()->create([
        'habit_id' => $habit->id,
        'requester_id' => $requester->id,
        'invitee_id' => $invitee->id,
        'scheduled_for' => $day,
    ]);

    return [$requester, $invitee, $habit, $appointment];
}

test('an accepted appointment appears in the calendar of the invited person', function () {
    $day = Carbon::today();
    [, $invitee, $habit, $appointment] = acceptedAppointment($day);

    $this->actingAs($invitee)
        ->get(route('calendar.day', $day->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('calendar-day')
            // Die gefragte Person führt die Gewohnheit nicht — sie hat keinen
            // eigenen Block an diesem Tag.
            ->has('blocks', 0)
            ->has('appointmentBlocks', 1)
            ->where('appointmentBlocks.0.kind', 'appointment')
            ->where('appointmentBlocks.0.id', $appointment->id)
            ->where('appointmentBlocks.0.habitId', $habit->id)
            ->where('appointmentBlocks.0.title', 'Laufen gehen')
            ->where('appointmentBlocks.0.name', 'Silas Weber')
            ->where('appointmentBlocks.0.initial', 'S')
            // Der Moment, nicht die Wiederholung: „Mo, Di …" gehört zur
            // Gewohnheit der fragenden Person, nicht zu diesem einen Tag.
            ->where('appointmentBlocks.0.anchor', 'um 17:00')
            ->where('appointmentBlocks.0.startMinute', 17 * 60)
            ->where('appointmentBlocks.0.exact', true)
            ->etc()
        );
});

test('the appointment appears on its day and on no other', function () {
    $day = Carbon::today()->addDays(2);
    [, $invitee] = acceptedAppointment($day);

    foreach ([$day->copy()->subDay(), $day->copy()->addDay()] as $other) {
        $this->actingAs($invitee)
            ->get(route('calendar.day', $other->toDateString()))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('appointmentBlocks', 0));
    }

    $this->actingAs($invitee)
        ->get(route('calendar.day', $day->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('appointmentBlocks', 1));
});

/**
 * Eine Frage ist kein Termin. Sie im Raster zu zeigen hieße, einen Platz zu
 * belegen, den niemand zugesagt hat.
 */
test('an appointment that is still open appears in no calendar', function () {
    $day = Carbon::today();
    $requester = User::factory()->create();
    $invitee = User::factory()->create();

    $habit = Habit::factory()->for($requester)->fixedSchedule('17:00', [1, 2, 3, 4, 5, 6, 7])->create();

    Appointment::factory()->create([
        'habit_id' => $habit->id,
        'requester_id' => $requester->id,
        'invitee_id' => $invitee->id,
        'scheduled_for' => $day,
    ]);

    $this->actingAs($invitee)
        ->get(route('calendar.day', $day->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('appointmentBlocks', 0));

    $this->actingAs($requester)
        ->get(route('calendar.day', $day->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('blocks', 1)
            ->where('blocks.0.companion', null)
            ->etc()
        );
});

/**
 * Auf der fragenden Seite steht der Block ohnehin — es fehlte nur das Zeichen,
 * dass jemand mitmacht. Dasselbe Feld wie in der Zeile auf der Übersicht.
 */
test('the asking person sees the companion on their own block', function () {
    $day = Carbon::today();
    [$requester] = acceptedAppointment($day);

    $this->actingAs($requester)
        ->get(route('calendar.day', $day->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Die eigene Gewohnheit, kein zweiter Block.
            ->has('blocks', 1)
            ->has('appointmentBlocks', 0)
            ->where('blocks.0.companion.name', 'Berkay Bahcekapili')
            ->where('blocks.0.companion.initial', 'B')
            ->etc()
        );
});

test('the companion stands only on the day of the appointment', function () {
    $day = Carbon::today()->addDays(3);
    [$requester] = acceptedAppointment($day);

    $this->actingAs($requester)
        ->get(route('calendar.day', $day->copy()->subDay()->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('blocks', 1)
            ->where('blocks.0.companion', null)
            ->etc()
        );
});

/**
 * Der eigentliche Punkt: Die Verabredung belegt den Tag wie ein Kurs.
 *
 * Ohne sie im Tagesplan rutschte eine eigene Situation genau dorthin, wo
 * gleich gemeinsam gelaufen wird — und der Kalender zeigte eine
 * Doppelbuchung, die `AppointmentFit` sonst überall verhindert.
 *
 * Der Gegenprobe wegen zweimal derselbe Tag: einmal mit zugesagter
 * Verabredung, einmal ohne. Bewegt sich die Gewohnheit auch ohne sie, misst
 * der Test nicht die Verabredung, sondern irgendetwas anderes.
 */
test('the appointment pushes an own situation out of its place', function () {
    // Ein fester Montag, damit weder Wochentag noch Schlafplan den Test tragen
    // — und der nächste, weil eine Gewohnheit vor ihrem Anlegen nicht anstand.
    $day = Carbon::today()->startOfWeek()->addWeek();

    // „nach dem Aufstehen" hängt am Schlafplan und liegt ohne eigene Angabe
    // auf SleepSchedule::DefaultWakeTime — genau auf der Verabredung.
    //
    // Vorher stand hier „wenn ich nach Hause komme" auf 17 Uhr. Die Situation
    // ist weg: Sie ruhte auf einer geratenen Uhrzeit, und ein Kalender, der
    // eine Gewohnheit auf eine erfundene Stunde legt, ist genau dort
    // unzuverlässig, wo er verlässlich sein muss. Was der Test prüft, ist
    // davon unberührt — eine Verabredung verdrängt eine eigene Situation.
    $situation = 'nach dem Aufstehen';
    $anchor = 7 * 60;

    $undisturbed = User::factory()->create();
    Habit::factory()->for($undisturbed)->withMeasure(30)->create([
        'title' => 'Lesen',
        'trigger_situation' => $situation,
    ]);

    $this->actingAs($undisturbed)
        ->get(route('calendar.day', $day->toDateString()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('blocks.0.startMinute', $anchor)
            ->etc()
        );

    [, $invitee] = acceptedAppointment($day, '07:00');

    Habit::factory()->for($invitee)->withMeasure(30)->create([
        'title' => 'Lesen',
        'trigger_situation' => $situation,
    ]);

    $this->actingAs($invitee)
        ->get(route('calendar.day', $day->toDateString()))
        ->assertInertia(function (AssertableInertia $page) {
            $props = $page->toArray()['props'];

            expect($props['appointmentBlocks'])->toHaveCount(1)
                ->and($props['blocks'])->toHaveCount(1);

            $mine = $props['blocks'][0];
            $theirs = $props['appointmentBlocks'][0];

            $theirFrom = $theirs['startMinute'];
            $theirTo = $theirFrom + $theirs['durationMinutes'];
            $myFrom = $mine['startMinute'];
            $myTo = $myFrom + $mine['durationMinutes'];

            expect($theirFrom)->toBe(7 * 60)
                // Nicht mehr an seiner Ankerstunde …
                ->and($myFrom)->not->toBe(7 * 60)
                // … und ohne Überschneidung mit der Verabredung.
                ->and($myFrom < $theirTo && $myTo > $theirFrom)->toBeFalse();
        });
});

test('the month marks the day of an appointment without counting it', function () {
    $day = Carbon::today();
    [, $invitee] = acceptedAppointment($day);

    $this->actingAs($invitee)
        ->get(route('calendar', ['month' => $day->format('Y-m')]))
        ->assertInertia(function (AssertableInertia $page) use ($day) {
            $days = collect($page->toArray()['props']['days']);

            $marked = $days->firstWhere('date', $day->toDateString());

            expect($marked['hasAppointment'])->toBeTrue()
                // Nicht mitgezählt: Die gefragte Person kann die fremde
                // Gewohnheit nie abhaken — als Punkt sähe der Tag für immer
                // unerledigt aus.
                ->and($marked['planned'])->toBe(0)
                ->and($marked['done'])->toBe(0);

            $others = $days->reject(fn (array $cell): bool => $cell['date'] === $day->toDateString());

            expect($others->every(fn (array $cell): bool => $cell['hasAppointment'] === false))->toBeTrue();
        });
});
