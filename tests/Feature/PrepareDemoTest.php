<?php

use App\Enums\CourseKind;
use App\Models\Friendship;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Der Ausgangszustand der Live-Demo.
 *
 * Geprüft wird, was die Vorführung trägt: vier Gewohnheiten mit Verlauf und
 * Platz für eine fünfte, ein Stundenplan, zwei Semester, ein Tagesrahmen —
 * und dass der Befehl nur das eine Konto anfasst und beliebig oft laufen kann.
 */
beforeEach(function () {
    // Mittwoch der Vorführung, 9 Uhr morgens: Der Befehl läuft vor dem Termin.
    Carbon::setTestNow('2026-10-14 09:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('legt den Demo-Zustand für das Konto an', function () {
    $silas = User::factory()->create(['email' => 'silas@example.com']);

    $this->artisan('demo:vorbereiten', ['email' => 'silas@example.com', '--force' => true])
        ->assertSuccessful();

    $silas->refresh();

    expect($silas->habits()->active()->count())->toBe(4)
        ->and($silas->habits()->active()->count())->toBeLessThan(Habit::MaxActivePerUser)
        ->and($silas->habits()->whereNotNull('chained_to_habit_id')->exists())->toBeFalse()
        ->and($silas->sleepSchedules()->count())->toBe(7)
        ->and($silas->semesters()->count())->toBe(1)
        ->and($silas->currentSemester()?->title)->toBe('Wintersemester 2026/27')
        ->and($silas->currentSemester()?->courses()->count())->toBe(5);

    $joggen = $silas->habits()->where('template_key', 'joggen')->firstOrFail();

    // Heute erledigt, mit Verlauf davor.
    expect($joggen->completions()->whereDate('completed_on', Carbon::today())->exists())->toBeTrue()
        ->and($joggen->completions()->count())->toBeGreaterThan(5);

    // Erledigungen nur an Tagen, an denen die Gewohnheit wirklich anstand.
    foreach ($silas->habits()->with('completions')->get() as $habit) {
        $habit->setRelation('user', $silas);

        foreach ($habit->completions as $completion) {
            expect($habit->isScheduledOn(Carbon::parse($completion->completed_on)))->toBeTrue();
        }
    }

    // Meditieren ist gestern verpasst worden.
    $meditieren = $silas->habits()->where('template_key', 'meditieren')->firstOrFail();

    expect($meditieren->completions()->whereDate('completed_on', Carbon::yesterday())->exists())->toBeFalse();
});

it('zeigt jede Seite der Demo mit diesem Zustand fehlerfrei', function () {
    $silas = User::factory()->create(['email' => 'silas@example.com']);

    $this->artisan('demo:vorbereiten', ['email' => 'silas@example.com', '--force' => true])->assertSuccessful();

    foreach (['dashboard', 'habits.index', 'habits.create', 'calendar', 'sleep.show', 'community'] as $page) {
        $this->actingAs($silas->refresh())->get(route($page))->assertOk();
    }

    $this->actingAs($silas)->get(route('calendar', ['date' => '2026-10-14']))->assertOk();
});

it('parkt Joggen, wenn live ein Tutorium auf seine Zeit kommt, und schickt zum Freitag', function () {
    $silas = User::factory()->create(['email' => 'silas@example.com']);

    $this->artisan('demo:vorbereiten', ['email' => 'silas@example.com', '--force' => true])->assertSuccessful();

    // Station 5b: Der Kurs kommt in das laufende Semester, freitags auf 07:30.
    $this->actingAs($silas)->post(route('calendar.semester.courses.store'), [
        'title' => 'Tutorium Analysis',
        'kind' => CourseKind::Uebung->value,
        'weekday' => 5,
        'starts_at' => '07:15',
        'ends_at' => '08:45',
    ])->assertSessionHasNoErrors();

    $joggen = $silas->habits()->where('template_key', 'joggen')->firstOrFail();

    // Geparkt ab sofort, die heutige Erledigung bleibt.
    expect($joggen->displaced_at)->not->toBeNull()
        ->and($joggen->completions()->whereDate('completed_on', Carbon::today())->exists())->toBeTrue();

    // Der Weg zum neuen Platz führt zum Freitag, an dem das Tutorium liegt.
    $this->actingAs($silas)->get(route('calendar'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('displaced', 1)
            ->where('displaced.0.title', 'Joggen gehen')
            ->where('displaced.0.conflictDate', '2026-10-16'));
});

it('lässt andere Konten in Ruhe und ergibt bei jedem Lauf denselben Zustand', function () {
    $silas = User::factory()->create(['email' => 'silas@example.com']);
    $other = User::factory()->create();
    Habit::factory()->for($other)->create();

    $this->artisan('demo:vorbereiten', ['email' => 'silas@example.com', '--force' => true])->assertSuccessful();
    $completions = $silas->habits()->withCount('completions')->pluck('completions_count')->all();

    $this->artisan('demo:vorbereiten', ['email' => 'silas@example.com', '--force' => true])->assertSuccessful();

    expect($silas->habits()->count())->toBe(4)
        ->and($silas->habits()->withCount('completions')->pluck('completions_count')->all())->toBe($completions)
        ->and($other->habits()->count())->toBe(1);
});

it('legt auf Wunsch einen verbundenen Partner an', function () {
    $silas = User::factory()->create(['email' => 'silas@example.com']);

    $this->artisan('demo:vorbereiten', [
        'email' => 'silas@example.com',
        '--partner-name' => 'Berkay',
        '--partner-email' => 'berkay@example.com',
        '--force' => true,
    ])->assertSuccessful();

    $partner = User::query()->where('email', 'berkay@example.com')->firstOrFail();

    expect($partner->onboarded_at)->not->toBeNull()
        ->and(Friendship::query()->accepted()->where('requester_id', $silas->id)->where('addressee_id', $partner->id)->exists())->toBeTrue();
});

it('bricht ab, wenn es das Konto nicht gibt', function () {
    $this->artisan('demo:vorbereiten', ['email' => 'niemand@example.com', '--force' => true])
        ->assertFailed();
});
