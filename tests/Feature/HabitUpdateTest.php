<?php

use App\Enums\HabitTemplate;
use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\HabitCompletion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Ein vollständiger, gültiger Satz Formularwerte — einzelne Felder überschreibbar.
 *
 * Bearbeitet wird nur die Planung: Der Wann-Teil, die Dauer, der erste
 * Schritt, der Warum-Satz. Was die Gewohnheit ist, steht im Katalog fest.
 */
function habitFormData(array $overrides = []): array
{
    return [
        'schedule_type' => ScheduleType::Dynamic->value,
        'trigger_situation' => 'nach dem Mittagessen',
        'target_amount' => 20,
        ...$overrides,
    ];
}

test('the edit form opens prefilled with what the habit already is', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fromTemplate(HabitTemplate::Spazieren)->create([
        'trigger_situation' => 'nach dem Mittagessen',
        'motivation' => 'damit ich rauskomme',
    ]);

    $this->actingAs($user)
        ->get(route('habits.edit', $habit))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('habits/edit')
            ->where('habit.title', 'Spazieren gehen')
            ->where('habit.categoryLabel', 'Sport & Bewegung')
            // Über JSON wird aus 20.0 wieder eine 20 — der Stepper rechnet in
            // beiden Fällen dasselbe.
            ->where('habit.durationMinutes', 20)
            ->where('habit.triggerSituation', 'nach dem Mittagessen')
            ->where('habit.motivation', 'damit ich rauskomme')
            // Ohne die Auswahllisten stünde das Formular ohne seine Kacheln da.
            ->has('triggerSuggestions')
            ->has('scheduleTypes', 3)
            ->has('durationLimits')
            ->has('sleepWindows', 7)
        );
});

test('a habit of another user cannot be opened or changed', function () {
    $habit = Habit::factory()->create(['motivation' => null]);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('habits.edit', $habit))
        ->assertForbidden();

    $this->actingAs($stranger)
        ->put(route('habits.update', $habit), habitFormData(['motivation' => 'Fremd']))
        ->assertForbidden();

    expect($habit->refresh()->motivation)->not->toBe('Fremd');
});

test('the planning of a habit can be changed', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fromTemplate(HabitTemplate::Spazieren)->create([
        'trigger_situation' => 'nach dem Mittagessen',
        'smallest_step' => 'Schuhe an die Tür',
        'motivation' => 'damit ich rauskomme',
    ]);

    $this->actingAs($user)
        ->put(route('habits.update', $habit), habitFormData([
            'target_amount' => 35,
            'trigger_situation' => 'vor dem Schlafengehen',
            'smallest_step' => 'Leg die Jacke bereit',
            'motivation' => 'damit ich abends runterkomme',
        ]))
        ->assertRedirect(route('habits.index'));

    $habit->refresh();

    expect($habit->target_amount)->toBe(35.0)
        ->and($habit->target_unit)->toBe(MeasureUnit::Minutes)
        ->and($habit->trigger_situation)->toBe('vor dem Schlafengehen')
        ->and($habit->smallest_step)->toBe('Leg die Jacke bereit')
        ->and($habit->motivation)->toBe('damit ich abends runterkomme');
});

/**
 * Die Identität kommt aus dem Katalog und wechselt beim Bearbeiten nicht:
 * Eine Gewohnheit ändert ihren Zeitpunkt, nicht ihren Namen.
 */
test('title and template survive a change untouched', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fromTemplate(HabitTemplate::Joggen)->create();

    $this->actingAs($user)
        ->put(route('habits.update', $habit), habitFormData([
            // Auch wer die Felder von Hand mitschickt, ändert nichts daran.
            'title' => 'Etwas ganz anderes',
            'template_key' => HabitTemplate::Meditieren->value,
        ]))
        ->assertSessionHasNoErrors();

    $habit->refresh();

    expect($habit->title)->toBe('Joggen gehen')
        ->and($habit->template())->toBe(HabitTemplate::Joggen)
        ->and($habit->behavior_type)->toBe(HabitTemplate::Joggen->behaviorType());
});

/**
 * Der Kern des Ganzen: Bisher blieb nur Beenden und Neuanlegen, und das kostete
 * jedes Mal den Verlauf. Genau das darf das Bearbeiten nicht tun.
 */
test('the history survives a change', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create([
        'created_at' => Carbon::today()->subDays(10),
        'position' => 3,
    ]);

    foreach (range(1, 4) as $offset) {
        HabitCompletion::factory()->for($habit)->create([
            'completed_on' => Carbon::today()->subDays($offset),
        ]);
    }

    $committed = $habit->committed_at;

    $this->actingAs($user)
        ->put(route('habits.update', $habit), habitFormData(['motivation' => 'Anders']));

    $habit->refresh();

    expect($habit->completions()->count())->toBe(4)
        ->and($habit->position)->toBe(3)
        ->and($habit->committed_at->equalTo($committed))->toBeTrue()
        ->and($habit->graduated_at)->toBeNull();
});

test('switching to a situation clears the time and switches the reminder off', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->withReminder()->create();

    expect($habit->reminder_enabled)->toBeTrue();

    $this->actingAs($user)
        ->put(route('habits.update', $habit), habitFormData([
            'schedule_type' => ScheduleType::Dynamic->value,
            'trigger_situation' => 'nach dem Aufstehen',
        ]));

    $habit->refresh();

    expect($habit->scheduled_time)->toBeNull()
        ->and($habit->scheduled_days)->toBeNull()
        // Ohne Zeitpunkt gäbe es nichts zu erinnern — der Schalter dürfte sonst
        // an aussehen und nichts auslösen.
        ->and($habit->reminder_enabled)->toBeFalse()
        ->and($habit->canRemind())->toBeFalse();
});

test('switching to a fixed time clears the situation', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create([
        'trigger_situation' => 'nach dem Mittagessen',
    ]);

    $this->actingAs($user)
        ->put(route('habits.update', $habit), habitFormData([
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_time' => '07:30',
            'scheduled_days' => [1, 3, 5],
        ]));

    $habit->refresh();

    expect($habit->trigger_situation)->toBeNull()
        ->and($habit->scheduled_time->format('H:i'))->toBe('07:30')
        ->and($habit->scheduled_days)->toBe([1, 3, 5]);
});

/**
 * Die Grenze aus progress-tracking.md gilt fürs Anlegen, nicht fürs Ändern —
 * sonst könnte, wer fünf Gewohnheiten hat, keine davon mehr anfassen.
 */
test('the limit of five does not block a change', function () {
    $user = User::factory()->create();
    $habits = Habit::factory()->count(Habit::MaxActivePerUser)->for($user)->create();

    $this->actingAs($user)
        ->put(route('habits.update', $habits->first()), habitFormData([
            'motivation' => 'Trotzdem geändert',
        ]))
        ->assertRedirect(route('habits.index'))
        ->assertSessionHasNoErrors();

    expect($habits->first()->refresh()->motivation)->toBe('Trotzdem geändert');
});

test('a situational habit needs a situation', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('habits.update', $habit), habitFormData(['trigger_situation' => '']))
        ->assertSessionHasErrors('trigger_situation');
});

test('a fixed habit needs a time and at least one weekday', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('habits.update', $habit), habitFormData([
            'schedule_type' => ScheduleType::Fixed->value,
            'scheduled_days' => [],
        ]))
        ->assertSessionHasErrors(['scheduled_time', 'scheduled_days']);
});
