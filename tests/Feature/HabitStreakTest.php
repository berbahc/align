<?php

use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * Trägt eine Gewohnheit an den genannten Tagen als erfüllt ein.
 *
 * @param  list<int>  $daysAgo  Abstand zu heute, 0 = heute.
 */
function complete(Habit $habit, array $daysAgo): void
{
    foreach ($daysAgo as $offset) {
        $date = Carbon::today()->subDays($offset);

        $habit->completions()->create([
            'completed_on' => $date,
            'completed_at' => $date->copy()->setTime(7, 30),
        ]);
    }
}

/**
 * Legt die Gewohnheit weit genug in die Vergangenheit, damit der Rückblick
 * nicht am Anlegedatum endet.
 */
function existingSince(Habit $habit, int $daysAgo): Habit
{
    $habit->forceFill(['created_at' => Carbon::today()->subDays($daysAgo)])->save();

    return $habit->fresh();
}

test('eine tägliche Gewohnheit zählt aufeinanderfolgende Tage', function () {
    $habit = existingSince(Habit::factory()->create(), 30);
    complete($habit, [0, 1, 2, 3, 4]);

    expect($habit->currentStreak())->toBe(5);
});

test('eine Mo–Fr-Gewohnheit bricht am Wochenende nicht', function () {
    // Samstag, 8. August 2026 — die Gewohnheit steht heute nicht an.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $habit = existingSince(
        Habit::factory()->fixedSchedule(days: [1, 2, 3, 4, 5])->create(),
        30,
    );

    // Mo bis Fr dieser Woche: 3.–7. August, also 5 bis 1 Tage vor heute.
    complete($habit, [1, 2, 3, 4, 5]);

    expect($habit->currentStreak())->toBe(5);
});

test('ein ausgelassener vorgesehener Tag unterbricht die Serie nicht', function () {
    $habit = existingSince(Habit::factory()->create(), 30);

    // Vorgestern fehlt.
    complete($habit, [0, 1, 3, 4]);

    // Der ausgelassene Tag ist kein Glied — er hält die Kette nur zusammen.
    expect($habit->currentStreak())->toBe(4);
});

test('der zweite ausgelassene Tag beendet die Serie', function () {
    $habit = existingSince(Habit::factory()->create(), 30);

    // Es fehlen der zweite und der vierte Tag zurück.
    complete($habit, [0, 2, 4, 5, 6]);

    // Kulanztag auf Tag 1, Ende auf Tag 3: gezählt werden heute und Tag 2.
    expect($habit->currentStreak())->toBe(2);
});

test('ein heute noch offener Tag bricht die Serie nicht', function () {
    $habit = existingSince(Habit::factory()->create(), 30);

    // Heute ist offen, davor vier Tage am Stück.
    complete($habit, [1, 2, 3, 4]);

    // Heute kostet auch keinen Kulanztag — der wäre sonst schon verbraucht und
    // ein echter Aussetzer davor würde fälschlich abbrechen.
    expect($habit->currentStreak())->toBe(4);
});

test('der heutige Tag zählt mit, sobald er abgehakt ist', function () {
    $habit = existingSince(Habit::factory()->create(), 30);
    complete($habit, [0, 1, 2, 3]);

    expect($habit->currentStreak())->toBe(4);
});

test('Tage vor dem Anlegen zählen nicht als Aussetzer', function () {
    // Die Gewohnheit gibt es seit drei Tagen und sie wurde jeden Tag erfüllt.
    $habit = existingSince(Habit::factory()->create(), 2);
    complete($habit, [0, 1, 2]);

    // Ohne die Grenze am Anlegedatum liefe der Rückblick in leere Tage und
    // verbrauchte dort den Kulanztag.
    expect($habit->currentStreak())->toBe(3);
});

test('die Einheit unterscheidet tägliche von festen Gewohnheiten', function () {
    $daily = Habit::factory()->create();
    $everyDay = Habit::factory()->fixedSchedule(days: [1, 2, 3, 4, 5, 6, 7])->create();
    $workdays = Habit::factory()->fixedSchedule(days: [1, 2, 3, 4, 5])->create();

    expect($daily->streakUnit())->toBe('Tage')
        ->and($everyDay->streakUnit())->toBe('Tage')
        ->and($workdays->streakUnit())->toBe('Mal')
        ->and($daily->streakLabel(12))->toBe('12 Tage in Folge')
        ->and($workdays->streakLabel(12))->toBe('12× in Folge');
});

test('die Übersicht liefert die stärkste laufende Serie', function () {
    $user = User::factory()->create();

    $short = existingSince(Habit::factory()->for($user)->create(['title' => 'Trinken', 'position' => 0]), 30);
    complete($short, [0, 1, 2]);

    $long = existingSince(Habit::factory()->for($user)->create(['title' => 'Lesen', 'position' => 1]), 30);
    complete($long, [0, 1, 2, 3, 4, 5]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('streak.count', 6)
            ->where('streak.title', 'Lesen')
            ->where('streak.unit', 'Tage')
        );
});

test('unter der Mindestlänge gibt es keine Streak-Karte', function () {
    $user = User::factory()->create();

    $habit = existingSince(Habit::factory()->for($user)->create(), 30);
    complete($habit, [0, 1]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('streak', null));
});

test('eine heute nicht vorgesehene Gewohnheit trägt ihre Serie trotzdem zur Karte bei', function () {
    // Samstag: die Mo–Fr-Gewohnheit steht nicht in der Tagesliste.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    $habit = existingSince(
        Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5])->create(['title' => 'Sport']),
        30,
    );
    complete($habit, [1, 2, 3, 4, 5]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Sie steht nicht in `habits` …
            ->has('habits', 0)
            // … aber ihre Serie läuft und gehört auf die Karte.
            ->where('streak.count', 5)
            ->where('streak.unit', 'Mal')
            ->where('streak.title', 'Sport')
        );
});

test('die Gewohnheiten-Liste zeigt die Serie je Gewohnheit als fertige Zeile', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();

    $running = existingSince(
        Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5])->create(['position' => 0]),
        30,
    );
    complete($running, [1, 2, 3, 4, 5]);

    // Zu kurz für eine Serie — die Zeile bleibt weg.
    $fresh = existingSince(Habit::factory()->for($user)->create(['position' => 1]), 30);
    complete($fresh, [0]);

    // Die Liste ist nach dem nächsten Termin sortiert: Die situative
    // Gewohnheit steht an diesem Samstag an, die Mo–Fr-Gewohnheit erst wieder
    // am Montag — deshalb steht sie hier hinten, trotz der längeren Serie.
    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.streak', null)
            ->where('habits.1.streak', '5× in Folge')
        );
});

/**
 * Der Rhythmusstreifen ist ausdrücklich **keine** Serie.
 *
 * Der Unterschied hängt an einem einzigen Feld: Ein Samstag ohne
 * Mo–Fr-Gewohnheit trägt `scheduled: false` und ist damit keine Lücke, sondern
 * ein Tag, an dem nie etwas vorgesehen war. Fiele diese Unterscheidung weg,
 * sähe die Woche jeder Mo–Fr-Gewohnheit aus wie zwei versäumte Tage — genau
 * die Bestrafung, die `time-blocking.md` ausschließt.
 */
test('die Gewohnheiten-Liste trägt sieben Tage Rhythmus je Gewohnheit', function () {
    // Ein Samstag: Der Rückblick endet heute und reicht bis zum Sonntag davor,
    // enthält also genau ein Wochenende.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();

    $habit = existingSince(
        Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5])->create(),
        30,
    );

    // Gestern war Freitag und wurde erfüllt.
    complete($habit, [1]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(function (AssertableInertia $page) {
            /** @var list<array{date: string, label: string, scheduled: bool, completed: bool}> $rhythm */
            $rhythm = $page->toArray()['props']['habits'][0]['rhythm'];

            expect($rhythm)->toHaveCount(Habit::WeekOverviewDays);

            $byDate = collect($rhythm)->keyBy('date');

            // Der erfüllte Freitag.
            expect($byDate['2026-08-07']['completed'])->toBeTrue()
                ->and($byDate['2026-08-07']['scheduled'])->toBeTrue()
                // Heute, Samstag: nicht vorgesehen — und deshalb auch keine
                // Lücke. Das ist der Zustand, den ein Streak nicht kennt.
                ->and($byDate['2026-08-08']['scheduled'])->toBeFalse()
                ->and($byDate['2026-08-08']['completed'])->toBeFalse()
                // Der Sonntag am anderen Ende genauso.
                ->and($byDate['2026-08-02']['scheduled'])->toBeFalse()
                // Und ein vorgesehener Tag ohne Erfüllung bleibt offen, statt
                // aus der Reihe zu fallen.
                ->and($byDate['2026-08-06']['scheduled'])->toBeTrue()
                ->and($byDate['2026-08-06']['completed'])->toBeFalse();

            // Der letzte Eintrag ist heute — darauf setzt der Streifen die
            // Markierung „hier stehst du".
            expect(collect($rhythm)->last()['date'])->toBe('2026-08-08');
        });
});

/**
 * Der Stundenplan nimmt den Platz — er darf nicht auch noch die Serie nehmen.
 *
 * `time-blocking.md`: „Verpasste Tage führen nicht zur Bestrafung." Ein Tag,
 * an dem eine Vorlesung den Platz hatte, ist kein verpasster Tag.
 */
test('a parked habit neither breaks the streak nor counts as a miss', function () {
    $user = User::factory()->create();
    // Jeden Tag, damit die Rechnung nicht am Wochenende hängt.
    $habit = Habit::factory()->for($user)->fixedSchedule('10:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(30)->create(['created_at' => Carbon::today()->subDays(20)]);

    // Die letzten fünf Tage lief sie nicht — weil sie seit sechs Tagen geparkt
    // ist. Davor lief sie durch.
    complete($habit, [6, 7, 8, 9, 10]);
    $habit->forceFill(['displaced_at' => Carbon::today()->subDays(5)])->save();

    $habit = $habit->fresh();
    $habit->setRelation('user', $user);
    $habit->load('completions', 'completionDates');

    expect($habit->currentStreak())->toBe(5)
        // Die sechs geparkten Tage stehen in keinem Rückblick — die KI
        // bekäme sonst „6× verpasst" als Beleg für einen Vorschlag.
        ->and($habit->recentMisses(6))->toBe([])
        // Der Wochenstreifen zeigt die geparkten Tage als nicht vorgesehen,
        // nicht als offene Lücke.
        // Sieben Tage im Rückblick, sechs davon geparkt: Nur der siebte ist
        // eine Zeile, an der etwas anstand.
        ->and(collect($habit->weekOverview(Carbon::today(), 7))->where('scheduled', true)->count())->toBe(1);
});

test('a mark that only starts with the semester leaves earlier days untouched', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule('10:00', [1, 2, 3, 4, 5, 6, 7])
        ->withMeasure(30)->create(['created_at' => Carbon::today()->subDays(20)]);

    // Der Vermerk gilt erst nächsten Monat — bis dahin steht sie an, und ein
    // versäumter Tag bleibt ein versäumter Tag.
    $habit->forceFill(['displaced_at' => Carbon::today()->addMonth()])->save();

    $habit = $habit->fresh();
    $habit->setRelation('user', $user);
    $habit->load('completions', 'completionDates');

    expect($habit->isDueOn(Carbon::today()))->toBeTrue()
        ->and($habit->recentMisses(3))->not->toBe([]);
});
