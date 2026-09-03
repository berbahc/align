<?php

use App\Enums\CourseKind;
use App\Models\Course;
use App\Models\CourseException;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Der Semesterplan als Rahmen: anlegen, füllen, korrigieren.
 *
 * Die Grenzen stehen im Vordergrund, nicht der glückliche Fall. Ein
 * Stundenplan, der sich selbst widerspricht — zwei Kurse übereinander, ein
 * Termin über Mitternacht —, wäre als Rahmen wertlos, weil die Rechnung, wo im
 * Tag noch Platz ist, ihn ungeprüft weiterträgt.
 */
function semesterFor(User $user): Semester
{
    return Semester::factory()->for($user)->create();
}

it('legt ein Semester an und zeigt es auf der Seite', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('semester.store'), [
            'title' => 'Wintersemester 25/26',
            'starts_on' => Carbon::today()->toDateString(),
            'ends_on' => Carbon::today()->addMonths(4)->toDateString(),
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('semester.show'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('semester')
            ->where('semester.title', 'Wintersemester 25/26')
            ->where('semester.isCurrent', true)
            ->where('courses', []));
});

it('trägt einen Kurs ein und liefert ihn mit seiner Spanne zurück', function () {
    $user = User::factory()->create();
    semesterFor($user);

    $this->actingAs($user)
        ->post(route('semester.courses.store'), [
            'title' => 'Analysis I',
            'kind' => CourseKind::Vorlesung->value,
            'weekday' => 2,
            'starts_at' => '10:00',
            'ends_at' => '11:30',
            'location' => 'HS 3',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->get(route('semester.show'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('courses.0.title', 'Analysis I')
            ->where('courses.0.weekday', 2)
            ->where('courses.0.timeRange', '10:00 – 11:30')
            ->where('courses.0.kindLabel', 'Vorlesung')
            ->where('courses.0.location', 'HS 3'));
});

it('weist einen Kurs ab, der endet, bevor er anfängt', function () {
    $user = User::factory()->create();
    semesterFor($user);

    $this->actingAs($user)
        ->post(route('semester.courses.store'), [
            'title' => 'Analysis I',
            'kind' => CourseKind::Vorlesung->value,
            'weekday' => 1,
            'starts_at' => '12:00',
            'ends_at' => '10:00',
        ])
        ->assertSessionHasErrors('ends_at');

    expect(Course::count())->toBe(0);
});

it('weist eine Veranstaltung über sechs Stunden ab', function () {
    $user = User::factory()->create();
    semesterFor($user);

    $this->actingAs($user)
        ->post(route('semester.courses.store'), [
            'title' => 'Blockseminar',
            'kind' => CourseKind::Seminar->value,
            'weekday' => 1,
            'starts_at' => '09:00',
            'ends_at' => '16:00',
        ])
        ->assertSessionHasErrors('ends_at');
});

it('lässt keinen Kurs vor fünf Uhr und keinen über Mitternacht', function (string $startsAt, string $endsAt, string $field) {
    $user = User::factory()->create();
    semesterFor($user);

    $this->actingAs($user)
        ->post(route('semester.courses.store'), [
            'title' => 'Nachtschicht',
            'kind' => CourseKind::Sonstiges->value,
            'weekday' => 1,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ])
        ->assertSessionHasErrors($field);
})->with([
    'zu früh' => ['04:00', '05:30', 'starts_at'],
    'über Mitternacht' => ['23:30', '00:30', 'ends_at'],
]);

it('weist zwei Kurse ab, die am selben Wochentag übereinanderliegen', function () {
    $user = User::factory()->create();
    $semester = semesterFor($user);

    Course::factory()->for($semester)->onWeekday(3)->at('10:00', '11:30')->create([
        'title' => 'Analysis I',
    ]);

    $this->actingAs($user)
        ->post(route('semester.courses.store'), [
            'title' => 'Lineare Algebra',
            'kind' => CourseKind::Vorlesung->value,
            'weekday' => 3,
            'starts_at' => '11:00',
            'ends_at' => '12:30',
        ])
        ->assertSessionHasErrors(['starts_at' => 'Um diese Zeit läuft an dem Tag schon „Analysis I".']);
});

it('lässt einen Kurs zu, der beginnt, wenn der vorige endet', function () {
    $user = User::factory()->create();
    $semester = semesterFor($user);

    Course::factory()->for($semester)->onWeekday(3)->at('10:00', '11:30')->create();

    $this->actingAs($user)
        ->post(route('semester.courses.store'), [
            'title' => 'Lineare Algebra',
            'kind' => CourseKind::Vorlesung->value,
            'weekday' => 3,
            'starts_at' => '11:30',
            'ends_at' => '13:00',
        ])
        ->assertSessionHasNoErrors();

    expect($semester->courses()->count())->toBe(2);
});

it('lässt denselben Kurs auf einen anderen Wochentag legen, ohne ihn mit sich selbst kollidieren zu lassen', function () {
    $user = User::factory()->create();
    $semester = semesterFor($user);
    $course = Course::factory()->for($semester)->onWeekday(3)->at('10:00', '11:30')->create();

    $this->actingAs($user)
        ->put(route('semester.courses.update', $course), [
            'title' => 'Analysis I',
            'kind' => CourseKind::Vorlesung->value,
            'weekday' => 3,
            'starts_at' => '10:00',
            'ends_at' => '12:00',
        ])
        ->assertSessionHasNoErrors();

    expect($course->fresh()->ends_at->format('H:i'))->toBe('12:00');
});

it('hält die Kursgrenze auch dann ein, wenn das Frontend umgangen wird', function () {
    $user = User::factory()->create();
    $semester = semesterFor($user);

    // Jeder Kurs auf einem eigenen Wochentag zu einer eigenen Zeit — sonst
    // scheiterte die Grenze an der Überschneidung statt an sich selbst.
    foreach (range(1, Course::MaxPerSemester) as $index) {
        Course::factory()->for($semester)
            ->onWeekday(($index % 7) + 1)
            ->at(sprintf('%02d:00', 8 + intdiv($index, 7) * 2), sprintf('%02d:00', 9 + intdiv($index, 7) * 2))
            ->create();
    }

    $this->actingAs($user)
        ->post(route('semester.courses.store'), [
            'title' => 'Einer zu viel',
            'kind' => CourseKind::Vorlesung->value,
            'weekday' => 1,
            'starts_at' => '20:00',
            'ends_at' => '21:00',
        ])
        ->assertSessionHasErrors('title');

    expect($semester->courses()->count())->toBe(Course::MaxPerSemester);
});

it('lässt niemanden den Kurs eines anderen ändern oder löschen', function () {
    $course = Course::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->put(route('semester.courses.update', $course), [
            'title' => 'Fremd',
            'kind' => CourseKind::Vorlesung->value,
            'weekday' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:30',
        ])
        ->assertForbidden();

    $this->actingAs($stranger)
        ->delete(route('semester.courses.destroy', $course))
        ->assertForbidden();

    expect($course->fresh()->title)->toBe('Analysis I');
});

it('vermerkt einen Ausfall und nimmt ihn wieder zurück', function () {
    $user = User::factory()->create();
    $semester = semesterFor($user);
    $course = Course::factory()->for($semester)->onWeekday(1)->create();

    $monday = Carbon::today()->next(Carbon::MONDAY);

    $this->actingAs($user)
        ->post(route('semester.courses.exceptions.store', $course), [
            'on_date' => $monday->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    expect($course->exceptions()->sole()->isCancellation())->toBeTrue();

    $this->actingAs($user)
        ->delete(route('semester.courses.exceptions.destroy', $course), [
            'on_date' => $monday->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    expect($course->exceptions()->count())->toBe(0);
});

it('ersetzt eine Absage durch den Ersatztermin desselben Tages statt beide zu behalten', function () {
    $user = User::factory()->create();
    $semester = semesterFor($user);
    $course = Course::factory()->for($semester)->onWeekday(1)->create();

    $monday = Carbon::today()->next(Carbon::MONDAY)->toDateString();

    $this->actingAs($user)->post(route('semester.courses.exceptions.store', $course), [
        'on_date' => $monday,
    ]);

    $this->actingAs($user)->post(route('semester.courses.exceptions.store', $course), [
        'on_date' => $monday,
        'starts_at' => '14:00',
        'ends_at' => '15:30',
    ])->assertSessionHasNoErrors();

    $exception = $course->exceptions()->sole();

    expect($course->exceptions()->count())->toBe(1)
        ->and($exception->isCancellation())->toBeFalse()
        ->and($exception->starts_at->format('H:i'))->toBe('14:00');
});

it('weist einen Ausfall an einem Tag ab, an dem der Kurs ohnehin nicht läuft', function () {
    $user = User::factory()->create();
    $semester = semesterFor($user);
    $course = Course::factory()->for($semester)->onWeekday(1)->create();

    $this->actingAs($user)
        ->post(route('semester.courses.exceptions.store', $course), [
            'on_date' => Carbon::today()->next(Carbon::THURSDAY)->toDateString(),
        ])
        ->assertSessionHasErrors('on_date');
});

it('weist eine Ausnahme außerhalb des Semesters ab', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)
        ->between(Carbon::today()->toDateString(), Carbon::today()->addMonths(2)->toDateString())
        ->create();
    $course = Course::factory()->for($semester)->onWeekday(1)->create();

    $this->actingAs($user)
        ->post(route('semester.courses.exceptions.store', $course), [
            'on_date' => Carbon::today()->addYear()->next(Carbon::MONDAY)->toDateString(),
        ])
        ->assertSessionHasErrors('on_date');
});

it('räumt beim Löschen des Semesters Kurse und Ausnahmen mit ab', function () {
    $user = User::factory()->create();
    $semester = semesterFor($user);
    $course = Course::factory()->for($semester)->create();
    CourseException::factory()->for($course)->create();

    $this->actingAs($user)
        ->delete(route('semester.destroy'))
        ->assertRedirect();

    expect(Semester::count())->toBe(0)
        ->and(Course::count())->toBe(0)
        ->and(CourseException::count())->toBe(0);
});

it('weist ein Semester ab, das nur drei Tage dauert', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('semester.store'), [
            'title' => 'Zu kurz',
            'starts_on' => Carbon::today()->toDateString(),
            'ends_on' => Carbon::today()->addDays(3)->toDateString(),
        ])
        ->assertSessionHasErrors('ends_on');
});

it('zeigt ein abgelaufenes Semester weiter an, aber nicht mehr als laufendes', function () {
    $user = User::factory()->create();
    Semester::factory()->for($user)->past()->create();

    $this->actingAs($user)
        ->get(route('semester.show'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('semester.title', 'Sommersemester 25')
            ->where('semester.isCurrent', false));
});
