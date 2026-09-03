<?php

use App\Enums\HabitTemplate;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\User;
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
