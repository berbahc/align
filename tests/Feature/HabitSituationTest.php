<?php

use App\Enums\HabitTemplate;
use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use App\Models\Course;
use App\Models\Habit;
use App\Models\Semester;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\Timetable;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Eine Situation trägt genau eine Gewohnheit.
 *
 * „Nach dem Aufstehen" zweimal zu vergeben hieße, zwei Dinge im selben Moment
 * zu tun. Der Kalender zeigte sie untereinander, als gäbe es eine Reihenfolge
 * — die es nicht gibt. Das unterläuft das Time-Blocking, dem die ganze
 * Planung dient.
 */
test('a situation that is taken cannot be taken again', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->fromTemplate(HabitTemplate::Joggen)
        ->create(['trigger_situation' => 'nach dem Aufstehen']);

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Aufraeumen->value,
            'target_amount' => 15,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertSessionHasErrors('trigger_situation');

    expect($user->habits()->count())->toBe(1);
});

test('the message names the habit that holds the moment', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->fromTemplate(HabitTemplate::Joggen)
        ->create(['trigger_situation' => 'nach dem Aufstehen']);

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Aufraeumen->value,
            'target_amount' => 15,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertSessionHasErrors([
            // Der Satz sagt, wem der Moment gehört — sonst müsste man raten,
            // welche Gewohnheit im Weg steht.
            'trigger_situation' => '„Joggen gehen" hängt schon an diesem Moment. Zwei Gewohnheiten zur selben Zeit sind kein Plan — wähle einen anderen Auslöser.',
        ]);
});

/**
 * Sonst wäre die Regel über „Eigene Situation" mit einem großen
 * Anfangsbuchstaben umgehbar.
 */
test('the rule holds for free text, regardless of case and spacing', function (string $written) {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create(['trigger_situation' => 'wenn ich aus der Bib komme']);

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Lesen->value,
            'target_amount' => 20,
            'trigger_situation' => $written,
        ])
        ->assertSessionHasErrors('trigger_situation');
})->with([
    'wortgleich' => 'wenn ich aus der Bib komme',
    'groß geschrieben' => 'Wenn ich aus der Bib komme',
    'mit Leerraum' => '  wenn ich aus der Bib komme  ',
]);

test('a habit keeps its own situation when it is edited', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create(['trigger_situation' => 'nach dem Aufstehen']);

    // Die eigene Situation ist für die eigene Gewohnheit nicht belegt —
    // sonst ließe sich keine Gewohnheit mehr bearbeiten.
    $this->actingAs($user)
        ->put(route('habits.update', $habit), [
            'schedule_type' => ScheduleType::Dynamic->value,
            'trigger_situation' => 'nach dem Aufstehen',
            'target_amount' => 25,
            'motivation' => 'unverändert am selben Moment',
        ])
        ->assertSessionHasNoErrors();

    expect($habit->refresh()->target_amount)->toBe(25.0);
});

test('a graduated habit releases its moment', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->graduated()
        ->create(['trigger_situation' => 'nach dem Aufstehen']);

    // Was beendet ist, findet nicht mehr statt — der Moment ist frei.
    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Meditieren->value,
            'target_amount' => 10,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertSessionHasNoErrors();
});

test('a moment taken by someone else stays free for me', function () {
    $stranger = User::factory()->create();
    Habit::factory()->for($stranger)->create(['trigger_situation' => 'nach dem Aufstehen']);

    $this->actingAs(User::factory()->create())
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Meditieren->value,
            'target_amount' => 10,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertSessionHasNoErrors();
});

test('a fixed time is unaffected by the rule', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->create(['trigger_situation' => 'nach dem Aufstehen']);

    // Eine feste Uhrzeit hat keinen Situationstext — für sie greift stattdessen
    // der Überschneidungshinweis.
    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Meditieren->value,
            'target_amount' => 10,
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '17:00',
            'scheduled_days' => [1, 2],
        ])
        ->assertSessionHasNoErrors();
});

test('the picker marks which moments are taken and by whom', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->fromTemplate(HabitTemplate::Joggen)
        ->create(['trigger_situation' => 'nach dem Aufstehen']);

    $this->actingAs($user)
        ->get(route('habits.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('triggerSuggestions', count(Habit::TriggerSuggestions))
            ->where('triggerSuggestions.0.situation', 'nach dem Aufstehen')
            ->where('triggerSuggestions.0.takenBy', 'Joggen gehen')
            // Der zweite Moment ist frei und trägt niemanden.
            ->where('triggerSuggestions.1.takenBy', null)
        );
});

test('the edit form does not report the habit blocking itself', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create(['trigger_situation' => 'nach dem Aufstehen']);

    $this->actingAs($user)
        ->get(route('habits.edit', $habit))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('triggerSuggestions.0.situation', 'nach dem Aufstehen')
            ->where('triggerSuggestions.0.takenBy', null)
        );
});

/**
 * Eine gekettete Gewohnheit belegt keinen Moment.
 *
 * Ihr Anker ist die Gewohnheit davor; was in ihrer `trigger_situation` steht,
 * liest niemand — der Kalender zeigt „nach ‚Vorgänger'". Ein Altwert dort wäre
 * unsichtbar und trotzdem eine Sperre: genau das hatte die alte
 * Anpassungs-Strecke hinterlassen.
 */
test('a chained habit blocks no moment', function () {
    $user = User::factory()->create();
    $walk = Habit::factory()->for($user)->fixedSchedule('17:00')->withMeasure(20)->create();
    Habit::factory()->for($user)->withoutMeasure()->create([
        'schedule_type' => ScheduleType::Chained,
        'chained_to_habit_id' => $walk->id,
        'trigger_situation' => 'nach dem Aufstehen',
    ]);

    $this->actingAs($user)
        ->get(route('habits.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('triggerSuggestions.0.situation', 'nach dem Aufstehen')
            ->where('triggerSuggestions.0.takenBy', null)
        );

    $this->actingAs($user)
        ->post(route('habits.store'), [
            'template_key' => HabitTemplate::Meditieren->value,
            'target_amount' => 10,
            'trigger_situation' => 'nach dem Aufstehen',
        ])
        ->assertSessionHasNoErrors();
});

/**
 * „Nach der Vorlesung" hängt an der Vorlesung, nicht an einer Uhrzeit.
 *
 * Wer seinen Stundenplan gepflegt hat, meint den Moment, an dem der Uni-Tag
 * vorbei ist. Ohne Vorlesung an dem Tag gibt es den Auslöser nicht — und ohne
 * Auslöser keine Gewohnheit (time-blocking.md).
 */
function studentWithLectures(): array
{
    $user = User::factory()->create();
    $semester = Semester::factory()->for($user)->create();

    // Montags zwei Kurse, der letzte endet um 15:30. Dienstags keiner.
    Course::factory()->for($semester)->onWeekday(1)->at('08:00', '09:30')->create(['title' => 'Mathe 1']);
    Course::factory()->for($semester)->onWeekday(1)->at('14:00', '15:30')->create(['title' => 'Statistik']);

    $habit = Habit::factory()->for($user)->withMeasure(30)->create([
        'title' => 'Vorlesung nachbereiten',
        'template_key' => HabitTemplate::VorlesungNachbereiten->value,
        'schedule_type' => ScheduleType::Dynamic,
        'trigger_situation' => Habit::AfterLecture,
        'scheduled_time' => null,
        'scheduled_days' => null,
    ]);

    $habit->setRelation('user', $user);

    return [$user, $habit];
}

test('after the lecture means after the last one of that day', function () {
    [$user, $habit] = studentWithLectures();

    $monday = Carbon::today()->next(Carbon::MONDAY);
    $plan = DayPlan::forDate(
        collect([$habit]),
        $monday,
        $user->sleepWindows(),
        Timetable::for($user)->blocksOn($monday),
    );

    // Statistik endet 15:30, plus die Viertelstunde Luft.
    expect($plan->startOf($habit))->toBe(15 * 60 + 45)
        ->and($habit->hasTriggerOn($monday))->toBeTrue();
});

test('without a lecture that day the habit does not stand at all', function () {
    [$user, $habit] = studentWithLectures();

    $tuesday = Carbon::today()->next(Carbon::TUESDAY);

    expect($habit->hasTriggerOn($tuesday))->toBeFalse()
        ->and($habit->isDueOn($tuesday))->toBeFalse();

    // Und der Tag zeigt sie nicht — auch nicht als Zeile ohne Platz.
    $this->actingAs($user)
        ->get(route('calendar.day', ['date' => $tuesday->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('blocks', [])->etc());
});

test('without a semester the situation keeps its plain window', function () {
    // Wer keinen Stundenplan pflegt, soll die Situation trotzdem nutzen können
    // — die App weiß dann nichts über Vorlesungen, und Schweigen ist kein Nein.
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->withMeasure(30)->create([
        'template_key' => HabitTemplate::VorlesungNachbereiten->value,
        'schedule_type' => ScheduleType::Dynamic,
        'trigger_situation' => Habit::AfterLecture,
        'scheduled_time' => null,
        'scheduled_days' => null,
    ]);
    $habit->setRelation('user', $user);

    $monday = Carbon::today()->next(Carbon::MONDAY);
    $plan = DayPlan::forDate(collect([$habit]), $monday, $user->sleepWindows());

    expect($habit->hasTriggerOn($monday))->toBeTrue()
        ->and($plan->startOf($habit))->toBe(11 * 60);
});

test('before sleeping stays within the hour before bedtime', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->withMeasure(30)->create([
        'schedule_type' => ScheduleType::Dynamic,
        'trigger_situation' => 'vor dem Schlafengehen',
        'scheduled_time' => null,
        'scheduled_days' => null,
    ]);
    $habit->setRelation('user', $user);

    // Schlafenszeit 23:00 in der Vorgabe: Der Block endet dort, und eine
    // Stunde davor ist die Grenze — nicht drei.
    $window = $habit->situationWindow();

    expect($window['from'])->toBe(21 * 60 + 30)
        ->and($window['to'])->toBe(23 * 60);
});

/**
 * Jede der sechs Situationen hängt an ihrem Anlass, nicht an einer Uhr.
 *
 * Das zahlt sich bei Spätaufstehern aus: Wer um zehn aufsteht, frühstückt
 * nicht um acht. Vorher lag „nach dem Frühstück" fest auf 08:00 — also vor
 * dem Aufstehen.
 */
test('the situations follow the sleep plan instead of a fixed clock', function () {
    $user = User::factory()->create();
    $user->sleepSchedules()->create([
        'weekday' => Carbon::today()->next(Carbon::MONDAY)->dayOfWeekIso,
        'wake_time' => '10:00',
        'bedtime' => '01:00',
        'alarm_enabled' => false,
    ]);

    $monday = Carbon::today()->next(Carbon::MONDAY);

    $windows = collect(['nach dem Aufstehen', 'nach dem Frühstück', 'vor dem Schlafengehen'])
        ->mapWithKeys(function (string $situation) use ($user, $monday): array {
            $habit = new Habit([
                'schedule_type' => ScheduleType::Dynamic,
                'trigger_situation' => $situation,
                'target_amount' => 30,
                'target_unit' => MeasureUnit::Minutes->value,
            ]);
            $habit->setRelation('user', $user->fresh());

            return [$situation => $habit->situationWindow($monday)];
        });

    expect($windows['nach dem Aufstehen']['from'])->toBe(10 * 60)
        // Dreiviertelstunde fürs Frühstück, dann geht es los — und nicht um 08:00.
        ->and($windows['nach dem Frühstück']['from'])->toBe(10 * 60 + 45)
        // Eine Stunde vor der Schlafenszeit um 01:00, also ab 23:30.
        ->and($windows['vor dem Schlafengehen']['from'])->toBe(23 * 60 + 30);
});

test('coming home follows the last lecture, not a fixed five', function () {
    [$user, $habit] = studentWithLectures();

    $home = Habit::factory()->for($user)->withMeasure(30)->create([
        'schedule_type' => ScheduleType::Dynamic,
        'trigger_situation' => 'wenn ich nach Hause komme',
        'scheduled_time' => null,
        'scheduled_days' => null,
    ]);
    $home->setRelation('user', $user);

    $monday = Carbon::today()->next(Carbon::MONDAY);
    $tuesday = $monday->copy()->addDay();

    // Montags endet der letzte Kurs 15:30 — plus Heimweg.
    expect($home->situationWindow($monday)['from'])->toBe(16 * 60)
        // Dienstags ohne Kurs bleibt es beim späten Nachmittag.
        ->and($home->situationWindow($tuesday)['from'])->toBe(17 * 60)
        ->and($habit->title)->toBe('Vorlesung nachbereiten');
});

test('a chain follows the situation it hangs on', function () {
    // Der Anker weicht aus — die Nachfolgerin blieb früher an der alten Stelle
    // liegen und landete mitten in dem, was den Anker verdrängt hatte.
    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('08:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(45)->create(['title' => 'Blockade']);

    $anchor = Habit::factory()->for($user)->withMeasure(20)->create([
        'title' => 'Spazieren',
        'schedule_type' => ScheduleType::Dynamic,
        'trigger_situation' => 'nach dem Frühstück',
        'scheduled_time' => null,
        'scheduled_days' => null,
    ]);
    $follower = Habit::factory()->for($user)->withMeasure(15)->create([
        'title' => 'Lesen',
        'schedule_type' => ScheduleType::Chained,
        'chained_to_habit_id' => $anchor->id,
        'trigger_situation' => null,
        'scheduled_time' => null,
        'scheduled_days' => null,
    ]);

    $habits = $user->habits()->active()->with('chainedTo.chainedTo')->get();
    $habits->each(fn (Habit $h) => $h->setRelation('user', $user));

    $monday = Carbon::today()->next(Carbon::MONDAY);
    $plan = DayPlan::forDate($habits, $monday, $user->sleepWindows());

    expect($plan->startOf($anchor))->toBe(9 * 60)
        // Zwanzig Minuten später plus das kurze Atemholen der Kette.
        ->and($plan->startOf($follower))->toBe(9 * 60 + 25);

    $blocks = $plan->occupied();

    foreach ($blocks as $i => $block) {
        foreach (array_slice($blocks, $i + 1) as $other) {
            expect($block['from'] < $other['to'] && $block['to'] > $other['from'])->toBeFalse();
        }
    }
});
