<?php

use App\Ai\Agents\SuggestNewPlaces;
use App\Enums\CourseKind;
use App\Enums\HabitTemplate;
use App\Enums\ScheduleType;
use App\Models\AiSuggestion;
use App\Models\Habit;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Laravel\Ai\Prompts\AgentPrompt;

/**
 * Die KI holt die Routine über den Semesterwechsel.
 *
 * Der Server rechnet, das Modell wählt, der Server prüft nach — und was durch
 * die Prüfung fällt, geht an die Person zurück, nicht in den Kalender. Vor
 * allem: Ein Frühstück landet nicht mittags, nur weil dort Platz ist.
 */
function studentWithSemester(): User
{
    $user = User::factory()->create();
    Semester::factory()->for($user)->create();

    return $user;
}

/** Ein Kurs, der eintritt und dabei verdrängt, was darunter liegt. */
function enterCourse(User $user, int $weekday, string $from, string $to, string $title = 'Mathe 1'): void
{
    test()->actingAs($user)->post(route('calendar.semester.courses.store'), [
        'title' => $title,
        'kind' => CourseKind::Vorlesung->value,
        'weekday' => $weekday,
        'starts_at' => $from,
        'ends_at' => $to,
    ])->assertSessionHasNoErrors();
}

function parkedHabit(User $user, string $title, string $time, array $days = [1], int $minutes = 30, ?HabitTemplate $template = null): Habit
{
    return Habit::factory()->for($user)->fixedSchedule($time, $days)->withMeasure($minutes)->create([
        'title' => $title,
        'template_key' => $template?->value,
    ]);
}

test('parked habits come back with a place each, remembered as suggestions', function () {
    $user = studentWithSemester();
    $a = parkedHabit($user, 'Vorlesung nachbereiten', '10:15');
    $b = parkedHabit($user, 'Karteikarten', '10:45', minutes: 15);
    enterCourse($user, 1, '10:00', '11:30');

    SuggestNewPlaces::fake([[
        'places' => [
            ['id' => $a->id, 'time' => '11:45', 'days' => [1], 'reason' => 'Direkt nach der Vorlesung.'],
            ['id' => $b->id, 'time' => '12:30', 'days' => [1], 'reason' => 'Kurz vor dem Mittag.'],
        ],
    ]]);

    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertOk()
        ->assertJsonCount(2, 'places')
        ->assertJsonPath('places.0.id', $a->id)
        ->assertJsonPath('places.0.timeRange', '11:45 – 12:15')
        ->assertJsonPath('places.0.label', '11:45 · Mo')
        ->assertJsonPath('places.0.previousLabel', 'braucht einen neuen Platz · lief bisher 10:15')
        ->assertJsonPath('unplaced', []);

    expect(AiSuggestion::count())->toBe(2)
        ->and(AiSuggestion::first()->accepted_at)->toBeNull();

    SuggestNewPlaces::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Lief bisher um 10:15')
        && $prompt->contains('Frei am Mo'));
});

test('breakfast is not moved to noon just because there is room', function () {
    $user = studentWithSemester();
    $breakfast = parkedHabit($user, 'Frühstücken', '09:00', minutes: 15, template: HabitTemplate::Fruehstuecken);
    $other = parkedHabit($user, 'Karteikarten', '08:45', minutes: 15);
    enterCourse($user, 1, '08:30', '09:30');

    SuggestNewPlaces::fake([[
        'places' => [
            ['id' => $breakfast->id, 'time' => '12:30', 'days' => [1], 'reason' => 'Mittags ist Platz.'],
            ['id' => $other->id, 'time' => '12:00', 'days' => [1], 'reason' => 'Passt.'],
        ],
    ]]);

    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertOk()
        ->assertJsonCount(1, 'places')
        ->assertJsonPath('places.0.id', $other->id)
        ->assertJsonPath('unplaced.0.id', $breakfast->id);

    // Das Modell bekam den Morgen als Grenze — nicht als Bitte.
    SuggestNewPlaces::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Gehört in die Zeit von 05:00 bis 10:00 — das ist eine Grenze'));
});

test('two places that overlap each other keep only the first', function () {
    $user = studentWithSemester();
    $a = parkedHabit($user, 'Erste', '10:00');
    $b = parkedHabit($user, 'Zweite', '10:45');
    enterCourse($user, 1, '10:00', '11:30');

    SuggestNewPlaces::fake([[
        'places' => [
            ['id' => $a->id, 'time' => '12:00', 'days' => [1], 'reason' => 'x'],
            ['id' => $b->id, 'time' => '12:15', 'days' => [1], 'reason' => 'y'],
        ],
    ]]);

    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertOk()
        ->assertJsonCount(1, 'places')
        ->assertJsonPath('places.0.id', $a->id)
        ->assertJsonPath('unplaced.0.id', $b->id);
});

test('a place inside a course falls through', function () {
    $user = studentWithSemester();
    $habit = parkedHabit($user, 'Lesen', '10:15');
    enterCourse($user, 1, '10:00', '11:30');
    enterCourse($user, 1, '13:00', '14:00', 'Statistik');

    SuggestNewPlaces::fake([[
        'places' => [['id' => $habit->id, 'time' => '13:15', 'days' => [1], 'reason' => 'x']],
    ]]);

    // Der einzige Vorschlag fällt durch — das ist ein Ausfall, kein halber Erfolg.
    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertStatus(503);
});

test('a habit without any fitting window is never sent to the model', function () {
    $user = studentWithSemester();
    // Ein Abendessen, aber der Tag endet um neunzehn Uhr — kein Fenster im Band.
    $user->sleepSchedules()->create(['weekday' => 1, 'wake_time' => '07:00', 'bedtime' => '17:30', 'alarm_enabled' => false]);
    $dinner = parkedHabit($user, 'Abendessen', '17:00', template: HabitTemplate::Abendessen);
    $reading = parkedHabit($user, 'Lesen', '10:15');
    enterCourse($user, 1, '10:00', '11:30');
    enterCourse($user, 1, '16:30', '17:30', 'Spätvorlesung');

    SuggestNewPlaces::fake([[
        'places' => [['id' => $reading->id, 'time' => '12:00', 'days' => [1], 'reason' => 'x']],
    ]]);

    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertOk()
        ->assertJsonPath('unplaced.0.id', $dinner->id)
        ->assertJsonPath('places.0.id', $reading->id);

    // Als Kandidatin kommt sie nicht vor — im Kontext-Block über die Person
    // darf ihr Name stehen, das ist etwas anderes.
    SuggestNewPlaces::assertPrompted(fn (AgentPrompt $prompt): bool => ! $prompt->contains("[{$dinner->id}] Abendessen")
        && $prompt->contains("[{$reading->id}] Lesen"));
});

test('with nothing parked the model is not asked', function () {
    $user = studentWithSemester();

    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertStatus(422);

    SuggestNewPlaces::assertNeverPrompted();
});

test('a failing model answers plainly instead of inventing a place', function () {
    $user = studentWithSemester();
    parkedHabit($user, 'Lesen', '10:15');
    enterCourse($user, 1, '10:00', '11:30');

    SuggestNewPlaces::fake(function (): never {
        throw new RuntimeException('Anbieter nicht erreichbar');
    });

    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertStatus(503)
        ->assertJsonMissingPath('places');
});

test('applying a place sets the time, frees the mark and accepts the suggestion', function () {
    $user = studentWithSemester();
    $habit = parkedHabit($user, 'Lesen', '10:15');
    enterCourse($user, 1, '10:00', '11:30');

    SuggestNewPlaces::fake([[
        'places' => [['id' => $habit->id, 'time' => '11:45', 'days' => [1], 'reason' => 'x']],
    ]]);

    $suggestionId = $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->json('places.0.suggestionId');

    $this->actingAs($user)
        ->post(route('calendar.semester.places.store'), [
            'places' => [['id' => $habit->id, 'time' => '11:45', 'days' => [1], 'suggestion_id' => $suggestionId]],
        ])
        ->assertSessionHasNoErrors();

    $habit = $habit->fresh();

    expect($habit->displaced_at)->toBeNull()
        ->and($habit->scheduled_time->format('H:i'))->toBe('11:45')
        ->and(AiSuggestion::find($suggestionId)->accepted_at)->not->toBeNull();
});

test('a place taken since the suggestion is refused on apply', function () {
    $user = studentWithSemester();
    $habit = parkedHabit($user, 'Lesen', '10:15');
    enterCourse($user, 1, '10:00', '11:30');

    // Inzwischen liegt dort etwas anderes.
    Habit::factory()->for($user)->fixedSchedule('11:45', [1])->withMeasure(30)->create(['title' => 'Joggen']);

    $this->actingAs($user)
        ->post(route('calendar.semester.places.store'), [
            'places' => [['id' => $habit->id, 'time' => '11:45', 'days' => [1]]],
        ])
        ->assertSessionHasErrors('places.0.time');

    expect($habit->fresh()->displaced_at)->not->toBeNull();
});

test('a weekday habit that only clashes on monday may simply drop monday', function () {
    $user = studentWithSemester();
    $habit = parkedHabit($user, 'Lesen', '10:15', days: [1, 2, 3, 4, 5]);
    enterCourse($user, 1, '10:00', '11:30');

    SuggestNewPlaces::fake([[
        'places' => [['id' => $habit->id, 'time' => '10:15', 'days' => [2, 3, 4, 5], 'reason' => 'Nur montags klemmt es.']],
    ]]);

    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertOk()
        ->assertJsonPath('places.0.label', '10:15 · Di–Fr');

    // Fenster je Tag, nicht als Schnitt — sonst gäbe es den Dienstag nicht.
    SuggestNewPlaces::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Frei am Di') && $prompt->contains('Frei am Fr'));

    $this->actingAs($user)
        ->post(route('calendar.semester.places.store'), [
            'places' => [['id' => $habit->id, 'time' => '10:15', 'days' => [2, 3, 4, 5]]],
        ])
        ->assertSessionHasNoErrors();

    expect($habit->fresh()->scheduled_days)->toBe([2, 3, 4, 5]);
});

test('days cannot be added, only dropped', function () {
    $user = studentWithSemester();
    $habit = parkedHabit($user, 'Lesen', '10:15', days: [1]);
    enterCourse($user, 1, '10:00', '11:30');

    SuggestNewPlaces::fake([[
        'places' => [['id' => $habit->id, 'time' => '11:45', 'days' => [1, 2], 'reason' => 'x']],
    ]]);

    // Der Dienstag fällt weg; der Montag bleibt — der Vorschlag überlebt beschnitten.
    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertOk()
        ->assertJsonPath('places.0.days', [1]);
});

test('a late riser still gets a breakfast, with the band as a hint', function () {
    $user = studentWithSemester();
    $user->sleepSchedules()->create(['weekday' => 1, 'wake_time' => '11:00', 'bedtime' => '01:00', 'alarm_enabled' => false]);
    $breakfast = parkedHabit($user, 'Frühstücken', '11:30', minutes: 15, template: HabitTemplate::Fruehstuecken);
    enterCourse($user, 1, '11:15', '12:00');

    SuggestNewPlaces::fake([[
        'places' => [['id' => $breakfast->id, 'time' => '12:15', 'days' => [1], 'reason' => 'Gleich nach dem Kurs.']],
    ]]);

    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertOk()
        ->assertJsonPath('places.0.time', '12:15');

    SuggestNewPlaces::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('nimm es als Richtung'));
});

test('the suggestion looks at the first lecture day, not just next week', function () {
    // Das Semester beginnt erst nächsten Monat. Nächsten Montag ist 10:45 frei —
    // am ersten Vorlesungsmontag liegt dort Mathe. Ein Vorschlag, der nur
    // nächste Woche kennt, fiele beim Übernehmen an genau diesem Kurs durch.
    $user = User::factory()->create();
    Semester::factory()->for($user)->between(
        Carbon::today()->addMonth()->toDateString(),
        Carbon::today()->addMonths(5)->toDateString(),
    )->create();
    $walk = parkedHabit($user, '20 Minuten spazieren', '10:45', minutes: 20);
    enterCourse($user, 1, '10:00', '11:30');

    expect($walk->fresh()->displaced_at)->not->toBeNull();

    // Die Frage stellt sich erst mit dem Semester — vorher läuft die
    // Gewohnheit weiter, wo sie lief.
    $this->travelTo(Carbon::today()->addMonth()->setTime(9, 0));

    SuggestNewPlaces::fake([[
        'places' => [
            ['id' => $walk->id, 'time' => '10:45', 'days' => [1], 'reason' => 'Gleiche Uhrzeit.'],
        ],
    ]]);

    // Die alte Zeit steht in keinem angebotenen Fenster — der Vorschlag fällt
    // durch, und ohne einen bleibt nur die Absage.
    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertStatus(503);

    SuggestNewPlaces::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('11:45 bis')
        && ! $prompt->contains('10:45 bis'));

    SuggestNewPlaces::fake([[
        'places' => [
            ['id' => $walk->id, 'time' => '11:45', 'days' => [1], 'reason' => 'Direkt nach Mathe.'],
        ],
    ]]);

    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertOk()
        ->assertJsonPath('places.0.timeRange', '11:45 – 12:05');

    $this->travelBack();
});

test('two places may share a weekday when applied together', function () {
    $user = studentWithSemester();
    $walk = parkedHabit($user, 'Spazieren', '10:45', days: [1, 3], minutes: 20);
    $gym = parkedHabit($user, 'Krafttraining', '11:15', days: [1, 3], minutes: 45);
    enterCourse($user, 1, '10:00', '11:30');

    // Beide am Mittwoch — die Regel darf Tage nur innerhalb eines Platzes
    // vergleichen, nicht über alle Plätze hinweg.
    $this->actingAs($user)
        ->post(route('calendar.semester.places.store'), [
            'places' => [
                ['id' => $walk->id, 'time' => '10:45', 'days' => [3]],
                ['id' => $gym->id, 'time' => '11:45', 'days' => [1, 3]],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect($walk->fresh()->displaced_at)->toBeNull()
        ->and($gym->fresh()->displaced_at)->toBeNull()
        ->and($gym->fresh()->scheduled_time->format('H:i'))->toBe('11:45');
});

test('each place names the day on which to look at it — the first lecture day', function () {
    // Die Uhr steht fest, weil der erwartete Tag am Wochentag von „heute"
    // hängt: Das Semester beginnt einen Monat später, und ob dessen erster
    // Montag oder erster Mittwoch näher liegt, entscheidet allein, auf welchen
    // Wochentag der heutige fällt. Ohne diese Zeile bestand der Test an einem
    // Freitag und fiel am Samstag um.
    //
    // Am 05.09.2026 beginnt das Semester am Montag, dem 05.10.2026 — dem
    // frühesten Tag, an dem der Vorschlag überhaupt gilt.
    Carbon::setTestNow(Carbon::parse('2026-09-05'));

    $user = User::factory()->create();
    Semester::factory()->for($user)->between(
        Carbon::today()->addMonth()->toDateString(),
        Carbon::today()->addMonths(5)->toDateString(),
    )->create();
    $walk = parkedHabit($user, 'Spazieren', '10:45', days: [1, 3], minutes: 20);
    enterCourse($user, 1, '10:00', '11:30');

    SuggestNewPlaces::fake([[
        'places' => [['id' => $walk->id, 'time' => '11:45', 'days' => [1, 3], 'reason' => 'Nach Mathe.']],
    ]]);

    // Der **früheste** der beiden Wochentage, nicht der erste Montag: Der
    // Vorschlag gilt montags und mittwochs, und gezeigt wird der Tag, an dem
    // er zuerst greift. Vorher rechnete der Test nur den Montag aus und traf
    // damit nur zufällig das Richtige.
    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertOk()
        ->assertJsonPath('places.0.previewDate', '2026-10-05');
});

test('the day shows a suggested place as a ghost, and only where it would apply', function () {
    $user = studentWithSemester();
    $walk = parkedHabit($user, 'Spazieren', '10:45', days: [1, 3], minutes: 20);
    enterCourse($user, 1, '10:00', '11:30');

    SuggestNewPlaces::fake([[
        'places' => [['id' => $walk->id, 'time' => '11:45', 'days' => [1, 3], 'reason' => 'Nach Mathe.']],
    ]]);

    $response = $this->actingAs($user)->postJson(route('calendar.semester.places.suggestions'))->assertOk();
    $suggestionId = $response->json('places.0.suggestionId');
    $monday = $response->json('places.0.previewDate');

    $this->actingAs($user)
        ->get(route('calendar.day', ['date' => $monday, 'suggestion' => $suggestionId]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('proposal.suggestionId', $suggestionId)
            ->where('proposal.title', 'Spazieren')
            ->where('proposal.time', '11:45')
            ->where('proposal.block.startMinute', 11 * 60 + 45)
            ->where('proposal.block.exact', true)
            ->where('proposal.block.timeRange', '11:45 – 12:05')
            ->etc());

    // Dienstag ist keiner seiner Tage — dort gäbe es nichts zu zeigen.
    $tuesday = Carbon::parse($monday)->addDay()->toDateString();

    $this->actingAs($user)
        ->get(route('calendar.day', ['date' => $tuesday, 'suggestion' => $suggestionId]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('proposal', null)->etc());

    // Übernommen — dann ist der Vorschlag keiner mehr.
    $this->actingAs($user)
        ->post(route('calendar.semester.places.store'), [
            'places' => [['id' => $walk->id, 'time' => '11:45', 'days' => [1, 3], 'suggestion_id' => $suggestionId]],
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->get(route('calendar.day', ['date' => $monday, 'suggestion' => $suggestionId]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('proposal', null)->etc());
});

test('a suggestion of someone else is no proposal', function () {
    $user = studentWithSemester();
    $stranger = studentWithSemester();
    $walk = parkedHabit($stranger, 'Spazieren', '10:45', minutes: 20);
    enterCourse($stranger, 1, '10:00', '11:30');

    SuggestNewPlaces::fake([[
        'places' => [['id' => $walk->id, 'time' => '11:45', 'days' => [1], 'reason' => 'x']],
    ]]);

    $response = $this->actingAs($stranger)->postJson(route('calendar.semester.places.suggestions'))->assertOk();

    $this->actingAs($user)
        ->get(route('calendar.day', ['date' => $response->json('places.0.previewDate'), 'suggestion' => $response->json('places.0.suggestionId')]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('proposal', null)->etc());
});

test('a habit hanging on a situation also gets a new place offered', function () {
    // Ohne Uhrzeit kein `scheduled_time` — die Abfrage filterte sie damit lange
    // weg, und sie hing ohne Vorschlag fest.
    $user = studentWithSemester();
    // Die Vorlage gepinnt: Ohne sie würfelt die Factory eine, und mit ihr das
    // Tagesfenster — ein Vorschlag um 09:00 fiele durch das Fenster von
    // „Abendessen" und der Test wäre zu einem Fünftel rot.
    $habit = Habit::factory()->for($user)->withMeasure(30)->create([
        'title' => 'Spazieren gehen',
        'template_key' => HabitTemplate::Spazieren->value,
        'schedule_type' => ScheduleType::Dynamic,
        'trigger_situation' => 'nach dem Aufstehen',
        'scheduled_time' => null,
        'scheduled_days' => null,
    ]);
    enterCourse($user, 1, '07:00', '08:30', 'Frühseminar');

    expect($habit->fresh()->displaced_at)->not->toBeNull();

    SuggestNewPlaces::fake([[
        'places' => [
            ['id' => $habit->id, 'time' => '09:00', 'days' => [1], 'reason' => 'Direkt nach dem Seminar.'],
        ],
    ]]);

    $this->actingAs($user)
        ->postJson(route('calendar.semester.places.suggestions'))
        ->assertOk()
        ->assertJsonPath('places.0.id', $habit->id)
        ->assertJsonPath('places.0.time', '09:00');

    // Sie geht überhaupt ans Modell — das ist der Punkt. Ihre alte Stelle
    // reist dabei als Stunde ihres Ankers mit; welche das ist, hängt am
    // Schlafplan und gehört deshalb nicht in diese Zusicherung.
    SuggestNewPlaces::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Spazieren gehen'));
});
