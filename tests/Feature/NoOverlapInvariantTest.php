<?php

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
 * Die eine Regel, die über allen anderen steht: Auf einer Minute liegt
 * höchstens eine Sache.
 *
 * Die übrigen Tests prüfen je einen Weg. Dieser prüft den **Zustand**: Er
 * fährt jeden Weg ab, auf dem sich eine Uhrzeit setzen lässt, und sieht danach
 * im Tag nach, ob sich zwei Blöcke überschneiden. Das ist der Unterschied
 * zwischen „diese Abweisung funktioniert" und „es gibt keinen Weg vorbei" —
 * und beim Bauen kam heraus, dass es sechs davon gab, von denen vier nicht auf
 * meiner Liste standen.
 *
 * Wer einen neuen Weg baut, auf dem eine Zeit gesetzt wird, trägt ihn hier
 * ein. Dann fällt die Lücke auf, bevor sie jemand im Kalender findet.
 */
function overlapOn(User $user, Carbon $date): ?string
{
    $habits = $user->habits()->active()->with('chainedTo.chainedTo')->get();
    $habits->each(fn (Habit $habit) => $habit->setRelation('user', $user));

    $blocks = DayPlan::forDate(
        $habits->filter(fn (Habit $habit): bool => $habit->isScheduledOn($date))->values(),
        $date,
        $user->sleepWindows(),
        Timetable::for($user)->blocksOn($date),
    )->occupied();

    // Wer zu wem gehört: Glieder derselben Kette dürfen enger liegen, weil
    // zwischen ihnen kein Weg und kein Wechsel steht.
    $chain = $habits->mapWithKeys(fn (Habit $habit): array => [
        $habit->id => $habit->anchorHabit()?->id ?? $habit->id,
    ])->all();

    foreach ($blocks as $index => $block) {
        foreach (array_slice($blocks, $index + 1) as $other) {
            // Mit der Viertelstunde Luft, die überall gilt: Zwei Blöcke Rücken
            // an Rücken sind zu eng, und ein Weg, der das durchlässt, ist ein
            // Loch in derselben Regel. Nur Kurse untereinander dürfen sich
            // berühren — so legt die Uni sie.
            $sameChain = isset($chain[$block['id']], $chain[$other['id']])
                && $chain[$block['id']] === $chain[$other['id']];

            $air = match (true) {
                Timetable::isCourseBlock($block) && Timetable::isCourseBlock($other) => 0,
                $sameChain => DayPlan::ChainBreatherMinutes,
                default => DayPlan::BreatherMinutes,
            };

            if ($block['from'] < $other['to'] + $air && $other['from'] < $block['to'] + $air) {
                return sprintf(
                    '„%s" (%s–%s) zu nah an „%s" (%s–%s)',
                    $block['title'], DayPlan::toTime($block['from']), DayPlan::toTime($block['to']),
                    $other['title'], DayPlan::toTime($other['from']), DayPlan::toTime($other['to']),
                );
            }
        }
    }

    return null;
}

/** Ein Montag innerhalb des Semesters aus der Factory. */
function invariantMonday(): Carbon
{
    return Carbon::today()->next(Carbon::MONDAY);
}

function studentWithMathe(): User
{
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->create();

    Course::factory()->for($semester)->onWeekday(1)->at('10:00', '11:30')
        ->create(['title' => 'Mathe 1']);

    return $user;
}

/**
 * Ein Versuch je Weg, benannt statt als Closure übergeben.
 *
 * Closures im Datensatz sahen kürzer aus und waren eine Falle: Pest reicht sie
 * durch, `$attempt($this, $user)` rief die äußere auf, bekam die innere
 * zurück — und führte nie etwas aus. Zehn grüne Fälle, die nichts prüften.
 * Ein `match` über Namen kann das nicht.
 */
function attemptOverlap(object $test, User $user, string $way): void
{
    match ($way) {
        'anlegen' => $test->actingAs($user)->post(route('habits.store'), [
            'template_key' => HabitTemplate::Lesen->value,
            'target_amount' => 30,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '10:30',
            'scheduled_days' => [1],
        ]),

        'bearbeiten' => (function () use ($test, $user) {
            $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

            $test->actingAs($user)->put(route('habits.update', $habit), [
                'target_amount' => 30,
                'schedule_type' => ScheduleType::Fixed->value,
                'scheduled_time' => '10:30',
                'scheduled_days' => [1],
            ]);
        })(),

        'ki-vorschlag' => (function () use ($test, $user) {
            $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

            $test->actingAs($user)->post(route('habits.adjustment.store', $habit), [
                'scheduled_time' => '10:30',
                'scheduled_days' => [1],
            ]);
        })(),

        'ziehen' => (function () use ($test, $user) {
            $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

            $test->actingAs($user)->put(route('habits.shifts.move', $habit), [
                'date' => invariantMonday()->toDateString(),
                'start_minute' => 10 * 60 + 30,
                'scope' => 'always',
            ]);
        })(),

        'verabredung' => (function () use ($test, $user) {
            $habit = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

            $test->actingAs($user)->post(route('habits.shifts.store', $habit), [
                'date' => invariantMonday()->toDateString(),
                'scheduled_time' => '10:30',
            ]);
        })(),

        'tagesordnung' => (function () use ($test, $user) {
            $first = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();
            $second = Habit::factory()->for($user)->fixedSchedule('16:00', [1])->withMeasure(30)->create();

            $test->actingAs($user)->post(route('calendar.order.store'), [
                'date' => invariantMonday()->toDateString(),
                'order' => [
                    ['id' => $first->id, 'time' => '10:15'],
                    ['id' => $second->id, 'time' => '10:20'],
                ],
            ]);
        })(),

        'kurs-auf-gewohnheit' => (function () use ($test, $user) {
            Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(60)->create();

            $test->actingAs($user)->post(route('calendar.semester.courses.store'), [
                'title' => 'Statistik',
                'kind' => CourseKind::Uebung->value,
                'weekday' => 1,
                'starts_at' => '14:00',
                'ends_at' => '15:30',
            ])->assertSessionHasNoErrors();

            // Der Kurs ist wirklich da — der Fall wäre sonst leer, weil nichts
            // geschrieben wurde. Kein Überlapp heißt hier: Die Gewohnheit ist
            // geparkt, nicht der Kurs abgewiesen.
            expect(Course::where('title', 'Statistik')->exists())->toBeTrue();
        })(),

        'neue-plaetze-uebernehmen' => (function () use ($test, $user) {
            $habit = Habit::factory()->for($user)->fixedSchedule('10:15', [1])->withMeasure(30)->create();
            $habit->forceFill(['displaced_at' => Carbon::now()])->save();
            Habit::factory()->for($user)->fixedSchedule('12:00', [1])->withMeasure(30)->create(['title' => 'Belegt']);

            // Der Platz um zwölf ist vergeben — das Übernehmen muss das sehen.
            $test->actingAs($user)->post(route('calendar.semester.places.store'), [
                'places' => [['id' => $habit->id, 'time' => '12:15', 'days' => [1]]],
            ]);
        })(),

        'kette-aufloesen-nach-verdraengung' => (function () use ($test, $user) {
            $anchor = Habit::factory()->for($user)->fixedSchedule('10:15', [1])->withMeasure(30)->create();
            Habit::factory()->for($user)->withMeasure(20)->create([
                'schedule_type' => ScheduleType::Chained,
                'trigger_situation' => null,
                'chained_to_habit_id' => $anchor->id,
            ]);
            $anchor->forceFill(['displaced_at' => Carbon::now()])->save();

            // Der Anker geht — der Nachfolger darf seine Zeit nicht als
            // lebendige erben, sie liegt unter Mathe.
            $test->actingAs($user)->post(route('habits.graduation.store', $anchor));
        })(),

        'wiederaufnehmen' => (function () use ($test, $user) {
            $habit = Habit::factory()->for($user)->fixedSchedule('10:15', [1])->withMeasure(30)
                ->create(['graduated_at' => Carbon::now()]);

            $test->actingAs($user)->delete(route('habits.graduation.destroy', $habit));
        })(),

        'kette-verzweigen' => (function () use ($test, $user) {
            $anchor = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();
            Habit::factory()->for($user)->withMeasure(30)->create([
                'schedule_type' => ScheduleType::Chained,
                'trigger_situation' => null,
                'chained_to_habit_id' => $anchor->id,
            ]);

            $test->actingAs($user)->post(route('habits.store'), [
                'template_key' => HabitTemplate::Lesen->value,
                'target_amount' => 30,
                'schedule_type' => ScheduleType::Chained->value,
                'chained_to_habit_id' => $anchor->id,
            ]);
        })(),
        'situation-anlegen' => (function () use ($test, $user) {
            // Eine Situation hat keine Uhrzeit, bekommt im Tag aber eine
            // Stelle — und die muss um das Feste herum gefunden werden.
            Habit::factory()->for($user)->fixedSchedule('11:45', [1])->withMeasure(30)->create();

            $test->actingAs($user)->post(route('habits.store'), [
                'template_key' => HabitTemplate::Lesen->value,
                'target_amount' => 30,
                'trigger_situation' => 'nach der Vorlesung',
            ]);
        })(),

        'kette-an-situation' => (function () use ($user) {
            // Der Anker weicht aus; die Nachfolgerin muss mit, sonst bleibt sie
            // in dem liegen, dem er gerade ausgewichen ist.
            Habit::factory()->for($user)->fixedSchedule('08:00', [1])->withMeasure(45)->create();

            $anchor = Habit::factory()->for($user)->withMeasure(20)->create([
                'schedule_type' => ScheduleType::Dynamic,
                'trigger_situation' => 'nach dem Aufstehen',
                'scheduled_time' => null,
                'scheduled_days' => null,
            ]);

            Habit::factory()->for($user)->withMeasure(15)->create([
                'schedule_type' => ScheduleType::Chained,
                'chained_to_habit_id' => $anchor->id,
                'trigger_situation' => null,
                'scheduled_time' => null,
                'scheduled_days' => null,
            ]);
        })(),

        'kurs-verlegen' => (function () use ($test, $user) {
            // Nicht der Kurs zieht dauerhaft um, sondern er liegt an einem
            // einzigen Datum woanders — genau dort, wo etwas läuft.
            Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();

            $course = $user->currentSemester()->courses()->sole();

            $test->actingAs($user)->post(route('calendar.semester.courses.exceptions.store', $course), [
                'on_date' => invariantMonday()->toDateString(),
                'starts_at' => '14:00',
                'ends_at' => '15:30',
            ]);
        })(),

        'verabredung-mit-kette' => (function () use ($test, $user) {
            // Die verschobene Gewohnheit passt, ihre Nachfolgerin nicht: Sie
            // rutscht mit und landet in der Vorlesung.
            $anchor = Habit::factory()->for($user)->fixedSchedule('14:00', [1])->withMeasure(30)->create();
            Habit::factory()->for($user)->withMeasure(30)->create([
                'schedule_type' => ScheduleType::Chained,
                'chained_to_habit_id' => $anchor->id,
                'trigger_situation' => null,
            ]);

            $test->actingAs($user)->post(route('habits.shifts.store', $anchor), [
                'date' => invariantMonday()->toDateString(),
                'scheduled_time' => '08:30',
            ]);
        })(),
    };
}

test('no path leaves two things on the same minute', function (string $way) {
    $user = studentWithMathe();

    attemptOverlap($this, $user, $way);

    expect(overlapOn($user, invariantMonday()))->toBeNull();
})->with([
    'Gewohnheit anlegen' => 'anlegen',
    'Gewohnheit bearbeiten' => 'bearbeiten',
    'KI-Vorschlag übernehmen' => 'ki-vorschlag',
    'im Raster ziehen' => 'ziehen',
    'für eine Verabredung Platz machen' => 'verabredung',
    'Tagesordnung übernehmen' => 'tagesordnung',
    'Kurs über eine Gewohnheit legen' => 'kurs-auf-gewohnheit',
    'beendete Gewohnheit wieder aufnehmen' => 'wiederaufnehmen',
    'Kette an dieselbe Gewohnheit hängen' => 'kette-verzweigen',
    'neue Plätze übernehmen' => 'neue-plaetze-uebernehmen',
    'Kette auflösen nach Verdrängung' => 'kette-aufloesen-nach-verdraengung',
    'Gewohnheit an einer Situation anlegen' => 'situation-anlegen',
    'Kette an einer ausweichenden Situation' => 'kette-an-situation',
    'Kurs an einem Datum verlegen' => 'kurs-verlegen',
    'für eine Verabredung Platz machen, mit Kette' => 'verabredung-mit-kette',
]);

/**
 * Der Semesterzeitraum ist die Hintertür: Nicht der Kurs zieht um, sondern der
 * Zeitraum um ihn herum. Eigener Test, weil er ein Semester braucht, das noch
 * nicht gilt.
 */
test('moving the semester range leaves no overlap either', function () {
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)
        ->between(
            Carbon::today()->addMonths(2)->toDateString(),
            Carbon::today()->addMonths(6)->toDateString(),
        )
        ->create();
    Course::factory()->for($semester)->onWeekday(1)->at('10:00', '11:30')->create();
    Habit::factory()->for($user)->fixedSchedule('10:15', [1])->withMeasure(30)->create();

    $this->actingAs($user)->put(route('calendar.semester.update'), [
        'title' => $semester->title,
        'starts_on' => Carbon::today()->subDay()->toDateString(),
        'ends_on' => Carbon::today()->addMonths(4)->toDateString(),
    ])->assertSessionHasNoErrors();

    // Der Zeitraum ist wirklich umgezogen — sonst prüfte der Fall nichts.
    expect($semester->fresh()->starts_on->toDateString())
        ->toBe(Carbon::today()->subDay()->toDateString())
        ->and(overlapOn($user, invariantMonday()))->toBeNull();
});
