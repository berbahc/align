<?php

use App\Models\Course;
use App\Models\CourseException;
use App\Models\Semester;
use App\Models\User;
use App\Support\Timetable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aus wöchentlichen Kursen und datierten Ausnahmen wird die Belegung eines
 * Tages.
 *
 * Das ist die einzige Stelle, an der diese Umrechnung stattfindet — und
 * deshalb die einzige, an der sie falsch sein kann. Sechs Aufrufer verlassen
 * sich darauf; gäbe einer eine andere Belegung heraus als die übrigen, säße
 * dieselbe Gewohnheit auf zwei Wegen an zwei Minuten.
 */
function timetableUser(): User
{
    return User::factory()->create();
}

/** Der nächste Montag innerhalb des Semesters aus der Factory. */
function nextMonday(): Carbon
{
    return Carbon::today()->next(Carbon::MONDAY);
}

it('legt einen Montagskurs auf den Montag und auf keinen anderen Tag', function () {
    $user = timetableUser();
    $semester = Semester::factory()->for($user)->create();
    Course::factory()->for($semester)->onWeekday(1)->at('08:00', '09:30')->create([
        'title' => 'Analysis I',
    ]);

    $timetable = Timetable::for($user);
    $monday = nextMonday();

    expect($timetable->blocksOn($monday))->toBe([
        ['id' => -1, 'title' => 'Analysis I', 'from' => 480, 'to' => 570],
    ]);

    expect($timetable->blocksOn($monday->copy()->addDay()))->toBe([]);
});

it('belegt außerhalb der Vorlesungszeit nichts mehr', function () {
    $user = timetableUser();
    $semester = Semester::factory()->for($user)->past()->create();
    Course::factory()->for($semester)->onWeekday(1)->create();

    // Ein Montag nach dem Ende des Semesters — der Plan steht noch, wirkt aber
    // nicht mehr.
    expect(Timetable::for($user)->blocksOn(nextMonday()))->toBe([]);
});

it('streicht genau das Datum, an dem der Kurs ausfällt', function () {
    $user = timetableUser();
    $semester = Semester::factory()->for($user)->create();
    $course = Course::factory()->for($semester)->onWeekday(1)->create();

    $monday = nextMonday();
    CourseException::factory()->for($course)->cancelledOn($monday)->create();

    $timetable = Timetable::for($user);

    expect($timetable->blocksOn($monday))->toBe([])
        ->and($timetable->blocksOn($monday->copy()->addWeek()))->toHaveCount(1);
});

it('verlegt genau das Datum, an dem der Kurs woanders liegt', function () {
    $user = timetableUser();
    $semester = Semester::factory()->for($user)->create();
    $course = Course::factory()->for($semester)->onWeekday(1)->at('08:00', '09:30')->create();

    $monday = nextMonday();
    CourseException::factory()->for($course)->movedTo($monday, '14:00', '15:30')->create();

    $timetable = Timetable::for($user);

    expect($timetable->blocksOn($monday)[0]['from'])->toBe(840)
        ->and($timetable->blocksOn($monday->copy()->addWeek())[0]['from'])->toBe(480);
});

it('zieht einen Nachholtermin an den fremden Wochentag, ohne ihn dort zu ersetzen', function () {
    $user = timetableUser();
    $semester = Semester::factory()->for($user)->create();

    $moved = Course::factory()->for($semester)->onWeekday(1)->at('08:00', '09:30')->create([
        'title' => 'Analysis I',
    ]);
    Course::factory()->for($semester)->onWeekday(4)->at('10:00', '11:30')->create([
        'title' => 'Statistik',
    ]);

    $thursday = Carbon::today()->next(Carbon::THURSDAY);
    CourseException::factory()->for($moved)->movedTo($thursday, '16:00', '17:30')->create();

    $blocks = Timetable::for($user)->blocksOn($thursday);

    expect($blocks)->toHaveCount(2)
        // Sortiert nach Beginn: erst die reguläre Statistik, dann der Nachhol-
        // termin am Nachmittag.
        ->and($blocks[0]['title'])->toBe('Statistik')
        ->and($blocks[1]['title'])->toBe('Analysis I')
        ->and($blocks[1]['from'])->toBe(960);
});

it('gibt Kursen negative Kennungen, damit sie nie mit Gewohnheiten kollidieren', function () {
    $user = timetableUser();
    $semester = Semester::factory()->for($user)->create();
    $course = Course::factory()->for($semester)->onWeekday(1)->create();

    $block = Timetable::for($user)->blocksOn(nextMonday())[0];

    expect($block['id'])->toBe(-$course->id)
        ->and(Timetable::isCourseBlock($block))->toBeTrue()
        ->and(Timetable::isCourseBlock(['id' => $course->id, 'title' => 'x', 'from' => 0, 'to' => 1]))->toBeFalse()
        // Die Verabredung trägt die 0 und ist ebenfalls kein Kurs.
        ->and(Timetable::isCourseBlock(['id' => 0, 'title' => 'Verabredung', 'from' => 0, 'to' => 1]))->toBeFalse();
});

it('kostet den ganzen Monat keine Abfrage je Zelle', function () {
    $user = timetableUser();
    $semester = Semester::factory()->for($user)->create();

    foreach (range(1, 5) as $weekday) {
        Course::factory()->for($semester)->onWeekday($weekday)
            ->at('08:00', '09:30')->create();
    }

    $timetable = Timetable::for($user);

    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    // Zweiundvierzig Zellen, wie im Monatsraster.
    $from = Carbon::today()->startOfMonth()->startOfWeek(Carbon::MONDAY);
    $days = $timetable->lectureDays($from, $from->copy()->addDays(41));

    expect($queries)->toBe(0)
        ->and($days)->not->toBeEmpty();
});

it('liefert für einen Nutzer ohne Semester einen leeren Plan', function () {
    $timetable = Timetable::for(timetableUser());

    expect($timetable->courseCount())->toBe(0)
        ->and($timetable->semester())->toBeNull()
        ->and($timetable->blocksOn(nextMonday()))->toBe([])
        ->and($timetable->coursesOn(nextMonday()))->toBe([]);
});

it('reicht Art, Ort und Spanne für die Oberfläche durch', function () {
    $user = timetableUser();
    $semester = Semester::factory()->for($user)->create();
    Course::factory()->for($semester)->onWeekday(1)->at('08:00', '09:30')->create([
        'title' => 'Analysis I',
        'location' => 'HS 3',
    ]);

    $course = Timetable::for($user)->coursesOn(nextMonday())[0];

    expect($course['kindLabel'])->toBe('Vorlesung')
        ->and($course['timeRange'])->toBe('08:00 – 09:30')
        ->and($course['location'])->toBe('HS 3')
        ->and($course['durationMinutes'])->toBe(90)
        ->and($course['moved'])->toBeFalse();
});

/**
 * Das Unterscheidungsfeld, das der Zeichnung sagt, welcher Art ein Block ist.
 *
 * Es wird an zwei Stellen vergeben — hier für den Kurs, in
 * `CalendarController::block()` für die Gewohnheit. Fehlt es an einer, hält
 * das Raster den Block für die andere Art, sucht ein Symbol, das es für ihn
 * nicht gibt, und die ganze Tagesansicht bleibt weiß. TypeScript fängt das
 * nicht: Die Props kommen als JSON, die Schnittstelle ist ein Versprechen.
 */
it('gibt jedem Kursblock seine Art mit', function () {
    $user = timetableUser();
    $semester = Semester::factory()->for($user)->create();
    Course::factory()->for($semester)->onWeekday(1)->create();

    $course = Timetable::for($user)->coursesOn(nextMonday())[0];

    expect($course['kind'])->toBe('course');
});
