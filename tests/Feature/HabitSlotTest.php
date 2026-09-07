<?php

use App\Ai\Agents\SuggestBetterAnchor;
use App\Enums\CourseKind;
use App\Enums\HabitTemplate;
use App\Enums\ScheduleType;
use App\Models\Course;
use App\Models\Habit;
use App\Models\Semester;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\Timetable;
use Illuminate\Support\Carbon;

/**
 * Eine Gewohnheit darf nicht dort liegen, wo schon etwas liegt.
 *
 * Bis hierher galt das nur beim Ziehen im Raster: Wer einen Block auf eine
 * Vorlesung zog, bekam eine Absage — wer dieselbe Uhrzeit ins Formular tippte,
 * kam durch. Der Plan widersprach sich damit an genau der Stelle, an der die
 * App ihr Versprechen einlöst: Time-Blocking heißt, um das Feste herum zu
 * planen.
 *
 * Geprüft werden hier die drei Wege, auf denen eine Uhrzeit gesetzt wird —
 * anlegen, bearbeiten, einen KI-Vorschlag übernehmen.
 */
function studentWithCourse(string $from = '10:00', string $to = '11:30', int $weekday = 1): User
{
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->create();

    Course::factory()->for($semester)->onWeekday($weekday)->at($from, $to)->create([
        'title' => 'Mathe 1',
    ]);

    return $user;
}

/** Die Felder, die das Formular für eine feste Uhrzeit schickt. */
function fixedHabitPayload(string $time, array $days, int $minutes = 30): array
{
    return [
        'template_key' => HabitTemplate::Lesen->value,
        'target_amount' => $minutes,
        'schedule_type' => ScheduleType::Fixed->value,
        'scheduled_time' => $time,
        'scheduled_days' => $days,
    ];
}

test('a new habit cannot be planned into a lecture', function () {
    $user = studentWithCourse();

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('10:30', [1]))
        ->assertSessionHasErrors('scheduled_time');

    $message = session('errors')->first('scheduled_time');

    expect($message)->toContain('Mathe 1')
        // Ein Kurs rückt nicht — der Satz darf nichts anderes anbieten.
        ->toContain('der Kurs rückt nicht')
        ->not->toContain('Verschiebe die zuerst')
        ->and($user->habits()->count())->toBe(0);
});

test('a habit on a weekday without lectures is planned as before', function () {
    $user = studentWithCourse(weekday: 1);

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('10:30', [3]))
        ->assertSessionHasNoErrors();

    expect($user->habits()->count())->toBe(1);
});

test('a habit needs a quarter hour of air after the lecture', function () {
    // Die Viertelstunde Luft gilt für jede Hand, nicht nur für die KI: Was
    // ein Vorschlag nie täte — Rücken an Rücken mit der Vorlesung —, soll
    // sich auch von Hand nicht eintragen lassen. Sonst hätte der Tag zwei
    // Maßstäbe.
    $user = studentWithCourse('10:00', '11:30');

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('11:30', [1]))
        ->assertSessionHasErrors('scheduled_time');

    expect(session('errors')->first('scheduled_time'))->toContain('eine Viertelstunde Luft');

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('11:45', [1]))
        ->assertSessionHasNoErrors();

    expect($user->habits()->sole()->scheduled_time->format('H:i'))->toBe('11:45');
});

test('one colliding weekday out of five is enough to refuse', function () {
    $user = studentWithCourse(weekday: 4);

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('10:30', [1, 2, 3, 4, 5]))
        ->assertSessionHasErrors('scheduled_time');

    // Der Tag steht im Satz, damit man weiß, welcher der fünf klemmt.
    expect(session('errors')->first('scheduled_time'))->toContain('Donnerstags');
});

/**
 * Der Fall, den die App vorher nur als Hinweis kannte — im Raster war er
 * längst eine Sperre.
 */
test('two habits cannot share the same span either', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('17:00', [1])->withMeasure(30)
        ->create(['title' => 'Joggen gehen']);

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('17:15', [1]))
        ->assertSessionHasErrors('scheduled_time');

    expect(session('errors')->first('scheduled_time'))
        ->toContain('Joggen gehen')
        // Eine eigene Gewohnheit lässt sich verschieben — hier gibt es einen Ausweg.
        ->toContain('Verschiebe die zuerst');
});

test('a habit keeps its own time when only its duration changes', function () {
    $user = studentWithCourse();
    $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

    $this->actingAs($user)
        ->put(route('habits.update', $habit), [
            'target_amount' => 45,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '14:00',
            'scheduled_days' => [1],
        ])
        ->assertSessionHasNoErrors();

    expect($habit->fresh()->durationMinutes())->toBe(45);
});

test('an edit into a lecture is refused', function () {
    $user = studentWithCourse();
    $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

    $this->actingAs($user)
        ->put(route('habits.update', $habit), [
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '10:15',
            'scheduled_days' => [1],
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect($habit->fresh()->scheduled_time->format('H:i'))->toBe('14:00');
});

/**
 * Eine Kette rückt mit. Der Vorgänger passt hier noch vor die Vorlesung — sein
 * Nachfolger läge mitten darin, und das zählt.
 */
test('a follower that would land in a lecture stops the move', function () {
    $user = studentWithCourse('10:00', '11:30');

    $first = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)
        ->create(['title' => 'Joggen gehen']);
    Habit::factory()->for($user)->withMeasure(30)->create([
        'title' => 'Dehnen',
        'schedule_type' => ScheduleType::Chained,
        'trigger_situation' => null,
        'chained_to_habit_id' => $first->id,
    ]);

    $this->actingAs($user)
        ->put(route('habits.update', $first), [
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '09:30',
            'scheduled_days' => [1],
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect($first->fresh()->scheduled_time->format('H:i'))->toBe('14:00');
});

/**
 * Eine Situation ist keine Uhrzeit — sie darf ausweichen.
 *
 * „Nach der Vorlesung" heißt irgendwann am frühen Nachmittag. Liegt an der
 * Stelle, an die der Kalender sie zuerst legt, schon etwas, rutscht sie
 * innerhalb ihrer Spanne weiter, statt abgewiesen zu werden oder sich mit dem
 * anderen die Minute zu teilen. Der Nutzer sieht die Spanne nie; er hat eine
 * Situation gewählt, und die bedeutet ohnehin einen Zeitraum.
 */
test('a situational habit slides past what is already there', function () {
    // Mathe liegt 10:00–11:30; „nach der Vorlesung" fängt bei 11:00 an.
    $user = studentWithCourse('10:00', '11:30');

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Lesen->value,
            'target_amount' => 30,
            'trigger_situation' => 'nach der Vorlesung',
        ])
        ->assertSessionHasNoErrors();

    $habit = $user->habits()->sole();
    $habit->setRelation('user', $user);

    $monday = Carbon::today()->next(Carbon::MONDAY);
    $plan = DayPlan::forDate(
        collect([$habit]),
        $monday,
        $user->sleepWindows(),
        Timetable::for($user)->blocksOn($monday),
    );

    // Hinter die Vorlesung, mit der Viertelstunde Luft — nicht auf 11:00.
    expect($plan->startOf($habit))->toBe(11 * 60 + 45);
});

test('a situation slides past a fixed habit in the same hour', function () {
    // Genau der Fall aus der Praxis: Joggen um 17:00, und „wenn ich nach Hause
    // komme" legt das Mittagessen auf dieselbe Stunde.
    $user = User::factory()->create();
    // Der Abendblock liegt bei Schlafenszeit 23:00 zwischen 21:30 und 23:00 —
    // Joggen um 22:00 liegt mitten darin.
    $jogging = Habit::factory()->for($user)->fixedSchedule('22:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(20)->create(['title' => 'Joggen gehen']);

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Mittagessen->value,
            'target_amount' => 20,
            'trigger_situation' => 'vor dem Schlafengehen',
        ])
        ->assertSessionHasNoErrors();

    $lunch = $user->habits()->where('title', '!=', 'Joggen gehen')->sole();
    $habits = collect([$jogging, $lunch]);
    $habits->each(fn (Habit $h) => $h->setRelation('user', $user));

    $monday = Carbon::today()->next(Carbon::MONDAY);
    $plan = DayPlan::forDate($habits, $monday, $user->sleepWindows());

    expect($plan->startOf($jogging))->toBe(22 * 60)
        // Nicht auf Joggen, sondern daneben — irgendwo im Abendfenster.
        ->and($plan->startOf($lunch))->not->toBe(22 * 60);

    // Und damit liegt nichts mehr übereinander.
    $blocks = $plan->occupied();

    foreach ($blocks as $i => $block) {
        foreach (array_slice($blocks, $i + 1) as $other) {
            expect($block['from'] < $other['to'] && $block['to'] > $other['from'])->toBeFalse();
        }
    }
});

test('a situation is refused when its whole window is full', function () {
    // „Vor dem Schlafengehen" reicht bei Schlafenszeit 23:00 von 21:30 bis
    // 23:00. Ist das zu, gibt es keine Stelle mehr, an die die Gewohnheit
    // ausweichen könnte.
    // (Für „nach der Vorlesung" ginge dieser Test nicht: Die Situation hängt
    // an den Kursen und rutscht mit ihnen hinter den letzten.)
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->create();

    foreach ([['21:15', '22:15'], ['22:15', '23:00']] as [$from, $to]) {
        Course::factory()->for($semester)->onWeekday(1)->at($from, $to)->create(['title' => 'Blockseminar']);
    }

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Lesen->value,
            'target_amount' => 30,
            'trigger_situation' => 'vor dem Schlafengehen',
        ])
        ->assertSessionHasErrors('trigger_situation');

    expect(session('errors')->first('trigger_situation'))
        ->toContain('nichts mehr frei')
        ->and($user->habits()->count())->toBe(0);
});

/**
 * Zwischen dem Vorschlag und dem Übernehmen liegt eine Entscheidung — und in
 * der Zeit kann ein Kurs dazugekommen sein.
 */
test('an accepted suggestion is measured against the timetable too', function () {
    SuggestBetterAnchor::fake([[
        'alternatives' => [['situation' => '', 'time' => '10:30', 'days' => [1], 'reason' => 'Passt.']],
    ]]);

    $user = studentWithCourse();
    $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create([
        'created_at' => Carbon::today()->subDays(20),
    ]);

    $this->actingAs($user)
        ->post(route('habits.adjustment.store', $habit), [
            'scheduled_time' => '10:30',
            'scheduled_days' => [1],
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect($habit->fresh()->scheduled_time->format('H:i'))->toBe('14:00');
});

test('a lecture outside the semester blocks nothing', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->past()->create();
    Course::factory()->for($semester)->onWeekday(1)->at('10:00', '11:30')->create();

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('10:30', [1]))
        ->assertSessionHasNoErrors();

    expect($user->habits()->count())->toBe(1);
});

/**
 * Der Weg über die Verabredung: Wer Platz für jemanden macht, wählt eine neue
 * Zeit — und dabei gilt dasselbe.
 */
test('making room for an appointment cannot land in a lecture either', function () {
    $user = studentWithCourse('10:00', '11:30');
    $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

    $monday = Carbon::today()->next(Carbon::MONDAY);

    $this->actingAs($user)
        ->post(route('habits.shifts.store', $habit), [
            'date' => $monday->toDateString(),
            'scheduled_time' => '10:30',
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect(session('errors')->first('scheduled_time'))
        ->toContain('Mathe 1')
        ->toContain('der Kurs rückt nicht')
        ->and($habit->dayShifts()->count())->toBe(0);
});

/**
 * Und die Gegenprobe: Bei einer eigenen Gewohnheit bleibt der Ausweg stehen,
 * denn den gibt es dort.
 */
test('a habit in the way is named without pretending a lecture could move', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('10:00', [1])->withMeasure(60)
        ->create(['title' => 'Essen vorkochen']);
    $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

    $monday = Carbon::today()->next(Carbon::MONDAY);

    $this->actingAs($user)
        ->post(route('habits.shifts.store', $habit), [
            'date' => $monday->toDateString(),
            'scheduled_time' => '10:30',
        ])
        ->assertSessionHasErrors('scheduled_time');

    expect(session('errors')->first('scheduled_time'))
        ->toContain('Essen vorkochen')
        ->not->toContain('rückt nicht');
});

/**
 * Ein Montag, der zugleich heute ist.
 *
 * Die Tagesordnung nimmt nur den heutigen Tag an ({@see DayOrderController}).
 * Diese Tests brauchen aber einen Wochentag, an dem ihre Gewohnheiten
 * anstehen — also wird die Uhr auf einen Montag gestellt, statt in den
 * nächsten zu springen.
 */
function orderableMonday(): Carbon
{
    Carbon::setTestNow(Carbon::parse('2026-09-07 09:00'));

    return Carbon::parse('2026-09-07');
}

/**
 * Die Wege, auf denen sich bis hierher ein Überlapp erzeugen ließ.
 *
 * Alle vier sind erst nach der Frage „darf es einen Zustand geben, in dem auf
 * einem Zeitpunkt zwei Sachen liegen?" aufgefallen — die Prüfung hing an den
 * Formularen, nicht am Tag.
 */
test('applying a day order cannot drop a habit into a lecture', function () {
    // Geordnet wird nur heute, also ist heute der Montag, an dem diese
    // Gewohnheiten anstehen — sonst wiese schon das Datum die Anfrage ab und
    // der Überlapp käme nie zur Sprache.
    $monday = orderableMonday();

    $user = studentWithCourse('10:00', '11:30');
    $a = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create(['title' => 'A']);
    $b = Habit::factory()->for($user)->fixedSchedule('16:00', [1])->withMeasure(30)->create(['title' => 'B']);

    $this->actingAs($user)
        ->post(route('calendar.order.store'), [
            'date' => $monday->toDateString(),
            'order' => [
                ['id' => $a->id, 'time' => '10:15'],
                ['id' => $b->id, 'time' => '16:00'],
            ],
        ])
        ->assertSessionHasErrors('order');

    expect($a->fresh()->scheduled_time->format('H:i'))->toBe('14:00');
});

test('applying a day order cannot stack two habits on each other', function () {
    $monday = orderableMonday();

    $user = User::factory()->create();
    $a = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create(['title' => 'A']);
    $b = Habit::factory()->for($user)->fixedSchedule('16:00', [1])->withMeasure(30)->create(['title' => 'B']);

    $this->actingAs($user)
        ->post(route('calendar.order.store'), [
            'date' => $monday->toDateString(),
            'order' => [
                ['id' => $a->id, 'time' => '09:00'],
                ['id' => $b->id, 'time' => '09:15'],
            ],
        ])
        ->assertSessionHasErrors('order');

    expect($a->fresh()->scheduled_time->format('H:i'))->toBe('14:00');
});

/**
 * Die eine Stelle, an der die Richtung umgekehrt ist: Der Kurs ist die
 * Tatsache, die Gewohnheit das Bewegliche. Sie wird geparkt, nicht der Kurs
 * abgewiesen — und behält ihre Zeit als Erinnerung.
 */
test('a course over an existing habit is entered and parks the habit', function () {
    $user = User::factory()->create();
    Semester::factory()->for($user)->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('10:00', [1])->withMeasure(60)
        ->create(['title' => 'Lesen']);

    $this->actingAs($user)
        ->post(route('calendar.semester.courses.store'), [
            'title' => 'Mathe 1',
            'kind' => CourseKind::Vorlesung->value,
            'weekday' => 1,
            'starts_at' => '10:00',
            'ends_at' => '11:30',
        ])
        ->assertSessionHasNoErrors();

    expect(Course::count())->toBe(1)
        ->and($habit->fresh()->displaced_at)->not->toBeNull()
        ->and($habit->fresh()->scheduled_time->format('H:i'))->toBe('10:00');
});

test('a course beside an existing habit is entered as before', function () {
    $user = User::factory()->create();
    Semester::factory()->for($user)->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('08:00', [1])->withMeasure(30)->create();

    $this->actingAs($user)
        ->post(route('calendar.semester.courses.store'), [
            'title' => 'Mathe 1',
            'kind' => CourseKind::Vorlesung->value,
            'weekday' => 1,
            'starts_at' => '10:00',
            'ends_at' => '11:30',
        ])
        ->assertSessionHasNoErrors();

    expect(Course::count())->toBe(1)
        ->and($habit->fresh()->displaced_at)->toBeNull();
});

test('a graduated habit cannot be resumed into an occupied slot', function () {
    $user = studentWithCourse('10:00', '11:30');
    $habit = Habit::factory()->for($user)->fixedSchedule('10:15', [1])->withMeasure(30)
        ->create(['title' => 'Alt', 'graduated_at' => Carbon::now()]);

    $this->actingAs($user)
        ->delete(route('habits.graduation.destroy', $habit))
        ->assertSessionHasErrors('habit');

    expect($habit->fresh()->graduated_at)->not->toBeNull();
});

test('a graduated habit whose slot is still free comes back', function () {
    $user = studentWithCourse('10:00', '11:30');
    $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)
        ->create(['graduated_at' => Carbon::now()]);

    $this->actingAs($user)
        ->delete(route('habits.graduation.destroy', $habit))
        ->assertSessionHasNoErrors();

    expect($habit->fresh()->graduated_at)->toBeNull();
});

/**
 * Die Hintertür: Nicht der Kurs zieht um, sondern der Zeitraum um ihn herum.
 */
test('moving the semester range parks the habits its courses now cover', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)
        ->between(
            Carbon::today()->addMonths(2)->toDateString(),
            Carbon::today()->addMonths(6)->toDateString(),
        )
        ->create();
    Course::factory()->for($semester)->onWeekday(1)->at('10:00', '11:30')
        ->create(['title' => 'Mathe 1']);

    $habit = Habit::factory()->for($user)->fixedSchedule('10:15', [1])->withMeasure(30)
        ->create(['title' => 'Lesen']);

    $this->actingAs($user)
        ->put(route('calendar.semester.update'), [
            'title' => $semester->title,
            'starts_on' => Carbon::today()->subDay()->toDateString(),
            'ends_on' => Carbon::today()->addMonths(4)->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    // Der Zeitraum ist umgezogen — und die Gewohnheit darunter geparkt.
    expect($semester->fresh()->starts_on->toDateString())
        ->toBe(Carbon::today()->subDay()->toDateString())
        ->and($habit->fresh()->displaced_at)->not->toBeNull();
});

test('a semester range without clashes still moves', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->create();
    Course::factory()->for($semester)->onWeekday(1)->at('10:00', '11:30')->create();
    Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

    $this->actingAs($user)
        ->put(route('calendar.semester.update'), [
            'title' => 'Sommersemester 26',
            'starts_on' => Carbon::today()->toDateString(),
            'ends_on' => Carbon::today()->addMonths(5)->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    expect($semester->fresh()->title)->toBe('Sommersemester 26');
});

/**
 * Eine Kette ist eine Reihe, kein Fächer.
 *
 * Hingen zwei Gewohnheiten an derselben, begannen beide, wenn die vorige
 * endet — zwei Dinge auf einer Minute, und zwar schon vor jedem Auflösen.
 * `Habit::spansFrom()` folgte ohnehin nur der ersten; die Regel schreibt fest,
 * wovon die Rechnung längst ausging.
 */
test('a habit cannot chain onto one that already has a follower', function () {
    $user = User::factory()->create();
    $anchor = Habit::factory()->for($user)->fixedSchedule('10:00', [1])->withMeasure(30)
        ->create(['title' => 'Anker']);
    Habit::factory()->for($user)->withMeasure(30)->create([
        'title' => 'Erster',
        'schedule_type' => ScheduleType::Chained,
        'trigger_situation' => null,
        'chained_to_habit_id' => $anchor->id,
    ]);

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Lesen->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Chained->value,
            'chained_to_habit_id' => $anchor->id,
        ])
        ->assertSessionHasErrors('chained_to_habit_id');

    expect(session('errors')->first('chained_to_habit_id'))->toContain('Anker');
});

test('releasing a chain lays its followers in a row, not on each other', function () {
    $user = User::factory()->create();
    $anchor = Habit::factory()->for($user)->fixedSchedule('10:00', [1])->withMeasure(30)
        ->create(['title' => 'Anker']);

    // Von Hand angelegt, wie es aus der Zeit vor der Regel noch dastehen kann.
    $first = Habit::factory()->for($user)->withMeasure(30)->create([
        'title' => 'Erster', 'position' => 1,
        'schedule_type' => ScheduleType::Chained,
        'trigger_situation' => null, 'chained_to_habit_id' => $anchor->id,
    ]);
    $second = Habit::factory()->for($user)->withMeasure(30)->create([
        'title' => 'Zweiter', 'position' => 2,
        'schedule_type' => ScheduleType::Chained,
        'trigger_situation' => null, 'chained_to_habit_id' => $anchor->id,
    ]);

    $this->actingAs($user)->post(route('habits.graduation.store', $anchor));

    expect($first->fresh()->scheduled_time->format('H:i'))->toBe('10:00')
        ->and($second->fresh()->chained_to_habit_id)->toBe($first->id)
        ->and($second->fresh()->startsAt()->format('H:i'))->toBe('10:35');
});

test('a course in a semester that has not started yet already blocks its slot', function () {
    // Das Semester beginnt erst nächsten Monat. Der nächste Montag liegt davor
    // und ist frei — der erste Vorlesungsmontag ist es nicht, und der zählt.
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->between(
        Carbon::today()->addMonth()->toDateString(),
        Carbon::today()->addMonths(5)->toDateString(),
    )->create();
    Course::factory()->for($semester)->onWeekday(1)->at('10:00', '11:30')->create(['title' => 'Mathe 1']);

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('10:45', [1]))
        ->assertSessionHasErrors('scheduled_time');

    expect(session('errors')->first('scheduled_time'))->toContain('Mathe 1')
        ->and($user->habits()->count())->toBe(0);

    // Dienstags gilt derselbe Stundenplan nicht — dort geht es.
    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('10:45', [2]))
        ->assertSessionHasNoErrors();
});

test('a habit needs a quarter hour of air next to another habit', function () {
    // Ein Mittwoch. Der Tag muss festgenagelt sein, weil die Meldung ihn nennt:
    // `SlotConflict::weekdayLabel()` schreibt „heute", wenn der Konflikt auf den
    // heutigen Tag fällt, und sonst den Wochentag. Ohne diese Zeile prüfte der
    // Test montags „heute" gegen „montags" und fiel um — an einem einzigen
    // Wochentag pro Woche.
    Carbon::setTestNow(Carbon::parse('2026-08-05'));

    $user = User::factory()->create();
    // Vorlage statt bloßem Titel: Die Factory vergibt ihre Vorlagen reihum, und
    // ein überschriebener Titel ändert daran nichts — die Gewohnheit hieß
    // „Essen vorkochen" und war innen „Lesen", also genau das, was unten
    // angelegt werden soll. Eine Vorlage trägt eine laufende Gewohnheit.
    Habit::factory()->for($user)->fromTemplate(HabitTemplate::EssenVorkochen)
        ->fixedSchedule('14:00', [1])->withMeasure(30)->create();

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('14:30', [1]))
        ->assertSessionHasErrors('scheduled_time');

    expect(session('errors')->first('scheduled_time'))
        ->toContain('„Essen vorkochen" liegt montags schon um 14:00 bis 14:30')
        ->toContain('eine Viertelstunde Luft');

    $this->actingAs($user)
        ->post(route('habits.store'), fixedHabitPayload('14:45', [1]))
        ->assertSessionHasNoErrors();
});

test('two courses may follow each other without air', function () {
    $user = studentWithCourse('10:00', '11:30');

    $this->actingAs($user)->post(route('calendar.semester.courses.store'), [
        'title' => 'Statistik',
        'kind' => CourseKind::Vorlesung->value,
        'weekday' => 1,
        'starts_at' => '11:30',
        'ends_at' => '13:00',
    ])->assertSessionHasNoErrors();

    expect($user->currentSemester()?->courses()->count())->toBe(2);
});
