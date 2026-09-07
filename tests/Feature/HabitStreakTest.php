<?php

use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
 * Die Konsistenz steht als zwei Zahlen, nicht als Prozentwert.
 *
 * `progress-tracking.md` verlangt „eine Konsistenzrate der letzten 30 Tage",
 * die Interviewauswertung warnt zugleich vor der nackten Prozentzahl: Eine
 * App, die anzeigte, wie weit jemand zurückgefallen war, wirkte
 * demotivierend, obwohl Fortschritt da war. Bei einer Gewohnheit mit wenigen
 * vorgesehenen Tagen im Fenster klingt „50 %" nach Note; „1 von 2 Tagen" sagt
 * dasselbe und lässt sich nicht falsch verstehen.
 *
 * Der Nenner sind die **vorgesehenen** Tage, nicht die Kalendertage — sonst
 * wäre eine Mo–Fr-Gewohnheit dauerhaft bei 71 % gedeckelt.
 */
test('die Gewohnheiten-Liste zählt erledigte gegen vorgesehene Tage', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();

    $habit = existingSince(
        Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5])->create(),
        60,
    );

    complete($habit, [1, 4, 5]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(function (AssertableInertia $page) {
            /** @var array{done: int, scheduled: int} $consistency */
            $consistency = $page->toArray()['props']['habits'][0]['consistency'];

            // Das Fenster reicht vom Freitag, 10.07.2026, bis Samstag, den
            // 08.08.2026 — vier volle Wochen plus Freitag und Samstag. Damit
            // fallen 21 Werktage hinein, nicht 22: Der angebrochene Rest
            // bringt nur einen weiteren Freitag.
            //
            // Die Zahl steht ausgeschrieben, weil sie das ist, was der Nutzer
            // liest. Eine nachgerechnete Erwartung machte denselben Fehler wie
            // der Code, falls er einen hat.
            expect($consistency['scheduled'])->toBe(21)
                ->and($consistency['done'])->toBe(3);
        });
});

/**
 * Eine junge Gewohnheit rechnet nur mit ihren eigenen Tagen.
 *
 * Lally et al. 2010: Konsistenz ist der Anteil genutzter Gelegenheiten, **nicht
 * die absolute Anzahl** der Ausführungen. Ein Tag vor dem Anlegen war keine
 * Gelegenheit; ihn mitzuzählen machte aus der Konsistenz eine verkappte
 * Altersangabe der Gewohnheit.
 *
 * `sinceStart` reist deshalb mit: Ohne diese Auskunft könnte die Oberfläche
 * „1 von 1" nicht erklären, weil die Legende dreißig Tage verspricht.
 */
test('eine junge Gewohnheit zählt nur ihre eigenen Tage', function () {
    // Ein Mittwoch. Die Gewohnheit entsteht am Montag davor, läuft täglich und
    // wird an zwei ihrer drei Tage erfüllt.
    Carbon::setTestNow(Carbon::parse('2026-08-05'));

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)
        ->fixedSchedule(days: [1, 2, 3, 4, 5, 6, 7])
        ->create(['created_at' => Carbon::parse('2026-08-03')]);

    complete($habit, [0, 1]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Drei Tage seit dem Anlegen, nicht dreißig. Ohne die Grenze am
            // Anlegedatum stünden hier 30 vorgesehene Tage, von denen 27 nie
            // eine Gelegenheit waren.
            ->where('habits.0.consistency.scheduled', 3)
            ->where('habits.0.consistency.done', 2)
            ->where('habits.0.consistency.sinceStart', true)
            // Derselbe Tag, an dem auch das Fenster beginnt. Beide stehen im
            // selben Kasten, ein anderes Datum wäre ein Widerspruch in sich.
            ->where('habits.0.startedOn', '03.08.2026')
            ->etc()
        );
});

/**
 * Der Streifen erfindet keine versäumten Tage.
 *
 * `progress-tracking.md`: „fehlende Tage dürfen nie bestraft oder prominent
 * angezeigt werden." Ohne die Grenze am Anlegedatum zeigte eine heute
 * angelegte Mo–Fr-Gewohnheit offene Marken für die ganze Woche davor, als
 * hätte jemand fünf Mal ausgelassen. {@see Habit::recentMisses()} wandte diese
 * Regel längst an, bevor die KI die Tage sieht.
 */
test('der Streifen zeigt keine Tage, an denen es die Gewohnheit nicht gab', function () {
    // Ein Freitag. Die Gewohnheit entsteht am Donnerstag davor.
    Carbon::setTestNow(Carbon::parse('2026-08-07'));

    $user = User::factory()->create();
    Habit::factory()->for($user)
        ->fixedSchedule(days: [1, 2, 3, 4, 5])
        ->create(['created_at' => Carbon::parse('2026-08-06')]);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(function (AssertableInertia $page) {
            /** @var list<array{date: string, scheduled: bool}> $rhythm */
            $rhythm = $page->toArray()['props']['habits'][0]['rhythm'];
            $byDate = collect($rhythm)->keyBy('date');

            // Donnerstag und Freitag: die Gewohnheit gab es, also vorgesehen.
            expect($byDate['2026-08-06']['scheduled'])->toBeTrue()
                ->and($byDate['2026-08-07']['scheduled'])->toBeTrue()
                // Montag bis Mittwoch wären Werktage. Es gab sie nur noch
                // nicht, und damit sind es keine Lücken.
                ->and($byDate['2026-08-03']['scheduled'])->toBeFalse()
                ->and($byDate['2026-08-04']['scheduled'])->toBeFalse()
                ->and($byDate['2026-08-05']['scheduled'])->toBeFalse();
        });
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

/**
 * Alle Zeilen der Gewohnheiten-Seite teilen dieselben sieben Tage.
 *
 * Das Blatt zeichnet die Tagesachse **einmal** über allen Gewohnheiten und
 * hängt jede Zeile an dieselben Spalten. Das trägt nur, solange jede Zeile
 * genau sieben Einträge in derselben Reihenfolge liefert — sonst säße die
 * Marke einer Gewohnheit unter der Beschriftung einer anderen, und das Blatt
 * behauptete Tage, die es nie gab.
 *
 * Der heikle Fall ist die frisch angelegte Gewohnheit: Vor ihrem Anlegedatum
 * ist nichts vorgesehen ({@see Habit::weekOverview()}), und die Reihe darf
 * trotzdem nicht kürzer werden — die Tage davor sind `scheduled: false`, nicht
 * abwesend.
 */
test('jede Gewohnheit liefert dieselben sieben Tage für die Achse', function () {
    // Ein Samstag. Zwei alte Gewohnheiten, eine von heute.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();

    existingSince(
        Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5])->create(),
        60,
    );
    existingSince(
        Habit::factory()->for($user)->fixedSchedule(days: [6, 7])->create(),
        60,
    );
    Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5, 6, 7])->create();

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(function (AssertableInertia $page) {
            /** @var list<array{rhythm: list<array{date: string}>}> $habits */
            $habits = $page->toArray()['props']['habits'];

            expect($habits)->toHaveCount(3);

            $dates = array_column($habits[0]['rhythm'], 'date');

            expect($dates)->toHaveCount(Habit::WeekOverviewDays)
                // Der letzte Eintrag ist heute — die Achse markiert genau ihn.
                ->and(end($dates))->toBe('2026-08-08');

            foreach ($habits as $habit) {
                expect(array_column($habit['rhythm'], 'date'))->toBe($dates);
            }
        });
});

/**
 * Eine Planänderung treibt die Zahl nicht über ihren Nenner.
 *
 * Der Nenner rechnet mit dem **heutigen** Plan
 * ({@see Habit::consistencyWindow()}), der Zähler zählte einmal jeden Haken im
 * Fenster. Wer täglich abhakte und danach auf Sa+So umstellte, las damit „20
 * von 9 Tagen" — eine Zahl, die größer ist als ihre eigene Bezugsgröße und
 * eine Konsistenz von 222 % behauptet.
 *
 * Die Regel selbst ist alt:
 * {@see HabitCompletionController::completionDate()} weist ein Nachtragen an
 * einem nicht vorgesehenen Tag ab, wörtlich weil „eine Erfüllung dort sie über
 * 100 % treiben" würde. Sie galt nur beim Schreiben und nicht beim Lesen, und
 * eine Planänderung reichte, um sie auszuhebeln.
 *
 * Was dabei aus dem Zähler fällt, ist kein Verlust: Ein Montag zählt nicht mehr
 * mit, weil er nach dem heutigen Plan keine Gelegenheit mehr ist — genau wie er
 * im Nenner keine mehr ist.
 */
test('eine Planänderung treibt die Konsistenz nicht über ihren Nenner', function () {
    // Ein Samstag. Die Gewohnheit gibt es seit sechzig Tagen.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    $habit = existingSince(
        Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5, 6, 7])->create(),
        60,
    );

    // Zwanzig Tage am Stück abgehakt, Wochenenden eingeschlossen.
    complete($habit, range(0, 19));

    expect($habit->fresh()->consistency())
        ->toMatchArray(['done' => 20, 'scheduled' => 30]);

    // Jetzt gilt die Gewohnheit nur noch am Wochenende. Die alten Haken der
    // Werktage bleiben liegen — gelöscht wird in dieser App nichts.
    $habit->forceFill(['scheduled_days' => [6, 7]])->save();

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertInertia(function (AssertableInertia $page) {
            /** @var array{done: int, scheduled: int} $consistency */
            $consistency = $page->toArray()['props']['habits'][0]['consistency'];

            // Das Fenster reicht vom Freitag, 10.07.2026, bis Samstag, den
            // 08.08.2026. Darin liegen neun Wochenendtage — und fünf davon
            // sind abgehakt: 25.07., 26.07., 01.08., 02.08. und 08.08.
            expect($consistency['scheduled'])->toBe(9)
                ->and($consistency['done'])->toBe(5);
        });
});

/**
 * Der Wochenstreifen darf keinen Tag als offen zeigen, an dem es den Auslöser
 * gar nicht gab.
 *
 * {@see Habit::hasTriggerOn()} kommt ohne geladenen Nutzer an keinen
 * Stundenplan und antwortet dann mit „ja" — richtig als Vorsicht, falsch als
 * Aussage. Die Gewohnheiten-Seite lud die Beziehung nicht, die Übersicht schon:
 * Dieselbe Woche las sich auf zwei Seiten verschieden, und „nach der Vorlesung"
 * stand mit sieben offenen Tagen da, obwohl in dieser Woche keine Vorlesung war.
 */
test('the week strip marks days without a lecture as not scheduled', function () {
    $user = User::factory()->create();
    Semester::factory()->for($user)->create();
    $habit = Habit::factory()->for($user)->withMeasure(30)->create([
        'title' => 'Vorlesung nachbereiten',
        'schedule_type' => ScheduleType::Dynamic,
        'trigger_situation' => Habit::AfterLecture,
        'scheduled_days' => null,
    ]);

    // Ein Semester ohne einen einzigen Kurs: An keinem Tag gibt es die
    // Vorlesung, an die diese Gewohnheit hängt.
    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.title', 'Vorlesung nachbereiten')
            ->where(
                'habits.0.rhythm',
                fn (Collection $rhythm): bool => $rhythm
                    ->every(fn (array $day): bool => $day['scheduled'] === false),
            )
            ->etc());

    expect($habit->id)->toBeInt();
});
