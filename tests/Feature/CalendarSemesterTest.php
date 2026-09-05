<?php

use App\Enums\CourseKind;
use App\Models\Course;
use App\Models\CourseException;
use App\Models\Habit;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Der Stundenplan, wie ihn der Kalender zeigt.
 *
 * Zwei Ebenen, zwei Auskünfte: Der Monat sagt nur, **ob** an einem Tag etwas
 * an der Uni läuft — wie viel, steht im Tag. Und der Tag zeigt Kurse und
 * Gewohnheiten auf derselben Achse, weil sich genau dort entscheidet, ob eine
 * Gewohnheit neben dem Studium noch Platz hat.
 */
it('liefert die Kurse eines Tages als eigene Liste neben den Gewohnheiten', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->create();
    Course::factory()->for($semester)->onWeekday(1)->at('08:00', '09:30')->create([
        'title' => 'Analysis I',
        'location' => 'HS 3',
    ]);

    $monday = Carbon::today()->next(Carbon::MONDAY);

    // Eine Gewohnheit daneben, damit sich beide Arten im selben Raster
    // vergleichen lassen.
    Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

    $this->actingAs($user)
        ->get(route('calendar.day', ['date' => $monday->toDateString()]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('calendar-day')
            // Das Unterscheidungsfeld zuerst: Ohne es hält die Zeichnung den
            // Kurs für eine Gewohnheit, sucht ein Symbol, das es für ihn nicht
            // gibt, und der ganze Tag bleibt weiß. Beide Arten werden hier
            // geprüft, weil das Feld an zwei Stellen vergeben wird.
            ->where('courseBlocks.0.kind', 'course')
            ->where('blocks.0.kind', 'habit')
            ->where('courseBlocks.0.title', 'Analysis I')
            ->where('courseBlocks.0.timeRange', '08:00 – 09:30')
            ->where('courseBlocks.0.kindLabel', 'Vorlesung')
            ->where('courseBlocks.0.location', 'HS 3')
            ->where('courseBlocks.0.startMinute', 480)
            ->etc());
});

it('lässt einen ausgefallenen Kurs an genau diesem Tag weg', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->create();
    $course = Course::factory()->for($semester)->onWeekday(1)->create();

    $monday = Carbon::today()->next(Carbon::MONDAY);
    CourseException::factory()->for($course)->cancelledOn($monday)->create();

    $this->actingAs($user)
        ->get(route('calendar.day', ['date' => $monday->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('courseBlocks', []));

    $this->actingAs($user)
        ->get(route('calendar.day', ['date' => $monday->copy()->addWeek()->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page->count('courseBlocks', 1));
});

it('markiert im Monat die Tage mit Vorlesung und sonst keine', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->create();
    Course::factory()->for($semester)->onWeekday(1)->create();

    $monday = Carbon::today()->next(Carbon::MONDAY);

    $this->actingAs($user)
        ->get(route('calendar', ['month' => $monday->format('Y-m')]))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($monday) {
            /** @var list<array{date: string, hasLectures: bool}> $days */
            $days = $page->toArray()['props']['days'];

            $marked = collect($days)->filter(fn (array $day): bool => $day['hasLectures']);

            expect($marked)->not->toBeEmpty()
                // Nur Montage — der Kurs läuft an keinem anderen Wochentag.
                ->and($marked->every(fn (array $day): bool => Carbon::parse($day['date'])->dayOfWeekIso === 1))->toBeTrue()
                ->and($marked->contains(fn (array $day): bool => $day['date'] === $monday->toDateString()))->toBeTrue();
        });
});

it('gibt dem Monat den Stundenplan mit — für den Knopf oben rechts', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('semester', null)->etc());

    $semester = Semester::factory()->for($user)->create(['title' => 'Wintersemester 25/26']);
    Course::factory()->count(3)->for($semester)->onWeekday(1)->sequence(
        ['starts_at' => '08:00', 'ends_at' => '09:30'],
        ['starts_at' => '10:00', 'ends_at' => '11:30'],
        ['starts_at' => '14:00', 'ends_at' => '15:30'],
    )->create();

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('semester.title', 'Wintersemester 25/26')
            ->where('courseCount', 3)
            ->etc());
});

it('zeigt einem Nutzer ohne Semester denselben Kalender wie zuvor', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('calendar.day', ['date' => Carbon::today()->toDateString()]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('courseBlocks', [])->etc());

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            /** @var list<array{hasLectures: bool}> $days */
            $days = $page->toArray()['props']['days'];

            expect(collect($days)->every(fn (array $day): bool => $day['hasLectures'] === false))->toBeTrue();
        });
});

it('gibt dem Monat alle Kurse für die Übersicht mit — nach Wochentag und Uhrzeit', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->create();
    Course::factory()->for($semester)->onWeekday(3)->at('14:00', '15:30')->create(['title' => 'Mathe Übung']);
    Course::factory()->for($semester)->onWeekday(1)->at('10:00', '11:30')->create(['title' => 'Mathe 1']);
    Course::factory()->for($semester)->onWeekday(1)->at('08:00', '09:00')->create(['title' => 'EC-Hauptseminar']);

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('courses', 3)
            ->where('courses.0.title', 'EC-Hauptseminar')
            ->where('courses.1.title', 'Mathe 1')
            ->where('courses.2.title', 'Mathe Übung')
            ->where('courses.2.weekday', 3)
            ->where('courses.0.timeRange', '08:00 – 09:00')
            ->etc());
});

it('gibt der verdrängten Gewohnheit den Tag zurück, an dem der Kurs ausfällt', function () {
    // Der Kurs nimmt den Platz jede Woche — aber nicht an dem Tag, an dem er
    // ausfällt. Dann liegt die Gewohnheit dort wieder, und nur dort.
    $user = User::factory()->create();
    Semester::factory()->for($user)->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('10:15', [1])->withMeasure(30)
        ->create(['title' => 'Vorlesung nachbereiten']);

    $this->actingAs($user)->post(route('calendar.semester.courses.store'), [
        'title' => 'Mathe 1',
        'kind' => CourseKind::Vorlesung->value,
        'weekday' => 1,
        'starts_at' => '10:00',
        'ends_at' => '11:30',
    ])->assertSessionHasNoErrors();

    $monday = Carbon::today()->next(Carbon::MONDAY);
    $nextMonday = $monday->copy()->addWeek();
    $course = $user->currentSemester()->courses()->sole();

    expect($habit->fresh()->displaced_at)->not->toBeNull();

    $this->actingAs($user)->post(route('calendar.semester.courses.exceptions.store', $course), [
        'on_date' => $monday->toDateString(),
        'cancelled' => true,
    ])->assertSessionHasNoErrors();

    $habit = $habit->fresh();
    $habit->setRelation('user', $user);

    expect($habit->displaced_at)->not->toBeNull()
        // An dem Tag liegt sie wieder da …
        ->and($habit->dayStartMinute($monday))->toBe(615)
        // … und an keinem anderen.
        ->and($habit->dayStartMinute($nextMonday))->toBeNull();

    // Ausfall zurückgenommen: Der Kurs läuft wieder, der geliehene Tag ist weg.
    $this->actingAs($user)->delete(route('calendar.semester.courses.exceptions.destroy', $course), [
        'on_date' => $monday->toDateString(),
    ])->assertSessionHasNoErrors();

    $habit = $habit->fresh();
    $habit->setRelation('user', $user);

    expect($habit->dayStartMinute($monday))->toBeNull();
});
