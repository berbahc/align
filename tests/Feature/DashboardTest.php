<?php

use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard lists the active habits of the current user', function () {
    $user = User::factory()->create();
    // Gleicher Anker für beide: die Tagesliste sortiert nach Tageszeit, hier
    // soll aber geprüft werden, wer überhaupt in ihr steht.
    Habit::factory()->for($user)->create(['title' => 'Morgentraining', 'trigger_situation' => 'nach dem Aufstehen', 'position' => 0]);
    Habit::factory()->for($user)->create(['title' => '10 Seiten lesen', 'trigger_situation' => 'nach dem Aufstehen', 'position' => 1]);
    Habit::factory()->for($user)->graduated()->create(['title' => 'Trinken']);
    Habit::factory()->create(['title' => 'Fremde Gewohnheit']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->has('habits', 2)
            ->where('habits.0.title', 'Morgentraining')
            ->where('habits.1.title', '10 Seiten lesen')
        );
});

test('the daily list runs from morning to evening, not by creation order', function () {
    // Ein Montag, damit auch die Mo–Fr-Gewohnheit heute ansteht.
    Carbon::setTestNow(Carbon::parse('2026-08-03'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->create([
        'title' => 'Abendritual',
        'trigger_situation' => 'vor dem Schlafengehen',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->fixedSchedule('07:30', [1, 2, 3, 4, 5])->create([
        'title' => 'Morgentraining',
        'position' => 1,
    ]);
    Habit::factory()->for($user)->create([
        'title' => 'Mittagspause',
        'trigger_situation' => 'nach der Vorlesung',
        'position' => 2,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.title', 'Morgentraining')
            ->where('habits.1.title', 'Mittagspause')
            ->where('habits.2.title', 'Abendritual')
        );
});

test('habits anchored to the same hour keep their own order', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-03'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->create([
        'title' => 'Zuerst angelegt',
        'trigger_situation' => 'nach dem Aufstehen',
        'position' => 0,
    ]);
    Habit::factory()->for($user)->create([
        'title' => 'Danach angelegt',
        'trigger_situation' => 'nach dem Aufstehen',
        'position' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.title', 'Zuerst angelegt')
            ->where('habits.1.title', 'Danach angelegt')
        );
});

test('a habit completed today is delivered with its completion time', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();
    $habit->completions()->create([
        'completed_on' => Carbon::today(),
        'completed_at' => Carbon::today()->setTime(7, 30),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.completedAt', '07:30')
        );
});

test('a habit not completed today is delivered as open', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();
    $habit->completions()->create([
        'completed_on' => Carbon::yesterday(),
        'completed_at' => Carbon::yesterday()->setTime(7, 30),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.completedAt', null)
        );
});

test('the consistency rate counts the full 30 day window including today', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();
    $habit->forceFill(['created_at' => Carbon::today()->subDays(60)])->save();

    // Der letzte Tag des Fensters ist die Stelle, an der ein String-Vergleich
    // gegen "Y-m-d" den Datensatz lexikografisch ausschließen würde.
    foreach (range(0, 29) as $daysAgo) {
        $date = Carbon::today()->subDays($daysAgo);
        $habit->completions()->create([
            'completed_on' => $date,
            'completed_at' => $date->copy()->setTime(7, 30),
        ]);
    }

    expect($habit->consistencyRate())->toBe(100);

    // Dreißig vorgesehene Tage, dreißig erledigt. Als Prozentwert wären das
    // 100 %; die Oberfläche nennt beide Zahlen, damit niemand sie für einen
    // Durchschnitt über die Gewohnheiten hält.
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('consistency.done', 30)
            ->where('consistency.scheduled', 30)
        );
});

test('a user without habits gets no consistency rate instead of zero percent', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 0)
            ->where('consistency', null)
        );
});

test('an empty day is told apart from an empty list', function () {
    // Ein Samstag: die Mo–Fr-Gewohnheit steht heute nicht an, es gibt sie aber.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5])->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 0)
            ->where('activeCount', 1)
        );
});

test('a user without habits is reported as empty on both counts', function () {
    $user = User::factory()->create();
    Habit::factory()->for($user)->graduated()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 0)
            ->where('activeCount', 0)
        );
});

test('a habit is hidden on a day it is not scheduled for', function () {
    // Ein Samstag — die Mo–Fr-Gewohnheit steht heute nicht an.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5])->create(['title' => 'Lesen']);
    Habit::factory()->for($user)->create(['title' => 'Trinken']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('habits', 1)
            ->where('habits.0.title', 'Trinken')
            // Das Tagesziel zählt nur, was heute vorgesehen ist — sonst wäre
            // der Tag von vornherein unerfüllbar.
            ->where('todayProgress.total', 1)
        );
});

test('a weekday habit reaches 100 percent without being punished for the weekend', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5])->create();
    $habit->forceFill(['created_at' => Carbon::today()->subDays(60)])->save();

    // Jeden vorgesehenen Tag der letzten 30 erfüllt — und keinen weiteren.
    foreach (range(0, 29) as $daysAgo) {
        $date = Carbon::today()->subDays($daysAgo);

        if (! $habit->isScheduledOn($date)) {
            continue;
        }

        $habit->completions()->create([
            'completed_on' => $date,
            'completed_at' => $date->copy()->setTime(17, 0),
        ]);
    }

    expect($habit->consistencyRate())->toBe(100);

    // Nur die Werktage stehen im Nenner, nicht die dreißig Kalendertage. Sonst
    // bliebe eine lückenlos erfüllte Mo–Fr-Gewohnheit dauerhaft unter 72 %.
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('consistency.done', 21)
            ->where('consistency.scheduled', 21)
        );
});

/**
 * Die Gesamtzahl rechnet wie die Zahl je Gewohnheit.
 *
 * Sie beschnitt ihr Fenster nicht am Anlegedatum, die Gewohnheiten-Seite
 * schon. Wer gestern seine erste Gewohnheit anlegte und erledigte, las hier
 * rund 3 % und dort 100 %. Zwei Zahlen, die beide „Konsistenz" heißen, dürfen
 * nicht verschieden rechnen — und Lally et al. 2010 gibt die Richtung vor:
 * Konsistenz ist der Anteil genutzter Gelegenheiten, und ein Tag vor dem
 * Anlegen war keine.
 */
test('a habit created yesterday is not judged against the month before it', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-05'));

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)
        ->fixedSchedule(days: [1, 2, 3, 4, 5, 6, 7])
        ->create(['created_at' => Carbon::parse('2026-08-04')]);

    // Gestern und heute erfüllt, also beide Gelegenheiten genutzt.
    foreach ([0, 1] as $daysAgo) {
        $date = Carbon::today()->subDays($daysAgo);

        $habit->completions()->create([
            'completed_on' => $date,
            'completed_at' => $date->copy()->setTime(8, 0),
        ]);
    }

    // Zwei Gelegenheiten seit gestern, beide genutzt. Ohne die Grenze am
    // Anlegedatum stünden hier 30 geplante Tage, von denen 28 nie eine
    // Gelegenheit waren.
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('consistency.done', 2)
            ->where('consistency.scheduled', 2)
        );
});

/**
 * Die Zahl nennt Tage, keinen Prozentwert, und das aus einem Grund.
 *
 * „62 %" über alle Gewohnheiten lädt zu einer Fehllesung ein. Wer täglich
 * meditiert und das Wochenend-Radfahren auslässt, läse 79 %, obwohl eine
 * seiner beiden Gewohnheiten bei null steht: Die Zahl ist nach Häufigkeit
 * gewichtet, und niemand liest sie so. „30 von 38" behauptet dagegen gar
 * nicht, ein Durchschnitt zu sein.
 */
test('the overview counts days across habits instead of averaging them', function () {
    // Ein Samstag, damit beide Gewohnheiten heute anstehen.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();

    $daily = Habit::factory()->for($user)
        ->fixedSchedule('21:00', [1, 2, 3, 4, 5, 6, 7])
        ->create(['created_at' => Carbon::today()->subDays(60)]);

    // Am Wochenende, und kein einziges Mal erfüllt.
    Habit::factory()->for($user)
        ->fixedSchedule('17:00', [6, 7])
        ->create(['created_at' => Carbon::today()->subDays(60)]);

    foreach (range(0, 29) as $daysAgo) {
        $date = Carbon::today()->subDays($daysAgo);

        $daily->completions()->create([
            'completed_on' => $date,
            'completed_at' => $date->copy()->setTime(21, 0),
        ]);
    }

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Das Fenster reicht von Freitag, dem 10.07., bis Samstag, dem
            // 08.08.: fünf Samstage und vier Sonntage, also neun Wochenendtage
            // neben den dreißig täglichen. Macht 39 Gelegenheiten, genutzt
            // wurden 30.
            //
            // Als Prozentwert wären das 77 %, obwohl die zweite Gewohnheit bei
            // null steht. Der Durchschnitt der beiden wäre 50 %. Genau deshalb
            // nennt die Oberfläche Tage und keinen Prozentwert.
            ->where('consistency.done', 30)
            ->where('consistency.scheduled', 39)
        );
});

/**
 * Der Zähler gehört in die Hauptabfrage.
 *
 * Er kam schon einmal dort heraus, und niemandem fiel es auf: Die Zahl war
 * weiterhin richtig, nur kostete sie je Gewohnheit eine eigene Zählabfrage.
 * Dieser Test hält fest, dass die Seite mit fünf Gewohnheiten nicht mehr
 * Abfragen braucht als mit einer.
 */
test('the overview does not query more for every additional habit', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $count = function (int $habits): int {
        $user = User::factory()->create();

        Habit::factory()->count($habits)->for($user)
            ->fixedSchedule('09:00', [1, 2, 3, 4, 5, 6, 7])
            ->create(['created_at' => Carbon::today()->subDays(60)]);

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        return $queries;
    };

    expect($count(5))->toBe($count(1));
});

test('the schedule label carries the time and days of a fixed habit', function () {
    // Ein Montag, damit die Gewohnheit in der Tagesliste auftaucht.
    Carbon::setTestNow(Carbon::parse('2026-08-03'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->fixedSchedule('07:30', [1, 2, 3, 4, 5])->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('habits.0.scheduleLabel', '07:30 · Mo–Fr')
            // Dieselbe Auskunft getrennt: Die Uhr steht auf der Übersicht in
            // einer eigenen Spalte, die Wiederholung in der Nebenzeile. Ohne
            // die Trennung lief beides in derselben Kette aus Punkten mit und
            // zwei gleichnamige Gewohnheiten am selben Tag waren nicht
            // auseinanderzuhalten.
            ->where('habits.0.timeLabel', '07:30')
            ->where('habits.0.repeatLabel', 'Mo–Fr')
        );
});

test('a habit anchored to a situation leaves the clock column empty', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-03'));

    $user = User::factory()->create();
    Habit::factory()->for($user)->create(['trigger_situation' => 'nach dem Aufstehen']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Die abgeleitete Stunde gehört nicht in die Spalte: Sie wäre eine
            // Festlegung, die niemand getroffen hat. Der Zeitpunkt steht
            // stattdessen in der Nebenzeile.
            ->where('habits.0.timeLabel', null)
            ->where('habits.0.repeatLabel', 'nach dem Aufstehen')
        );
});

/**
 * Auch die Summe über alle Gewohnheiten bleibt in ihrem Nenner.
 *
 * Der Zähler stand hier als `withCount` in der Hauptabfrage und zählte jeden
 * Haken der letzten dreißig Tage, ohne nach dem Wochentag zu fragen. Der Nenner
 * fragte danach sehr wohl — nach dem heutigen Plan. Eine Planänderung genügte
 * damit, um „20 von 9 Mal erledigt" auf die Übersicht zu schreiben.
 *
 * Die Gewohnheiten-Seite hatte denselben Fehler auf einem anderen Weg. Beide
 * Zahlen kommen jetzt aus {@see Habit::consistencyDone()} und legen dieselbe
 * Bedingung an wie der Nenner.
 */
test('the overview keeps its numerator inside the denominator after a schedule change', function () {
    // Ein Samstag. Die Gewohnheit gibt es seit sechzig Tagen.
    Carbon::setTestNow(Carbon::parse('2026-08-08'));

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->fixedSchedule(days: [1, 2, 3, 4, 5, 6, 7])->create();
    $habit->forceFill(['created_at' => Carbon::today()->subDays(60)])->save();

    // Zwanzig Tage am Stück abgehakt, Wochenenden eingeschlossen.
    foreach (range(0, 19) as $daysAgo) {
        $date = Carbon::today()->subDays($daysAgo);

        $habit->completions()->create([
            'completed_on' => $date,
            'completed_at' => $date->copy()->setTime(17, 0),
        ]);
    }

    // Ab jetzt gilt sie nur noch am Wochenende.
    $habit->forceFill(['scheduled_days' => [6, 7]])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Neun Wochenendtage im Fenster, fünf davon abgehakt.
            ->where('consistency.scheduled', 9)
            ->where('consistency.done', 5)
        );
});

/**
 * Die Konsistenzrate bleibt auch an einem Tag ohne Gewohnheit.
 *
 * Sie lag einmal als Fußnote in der Tageskarte, und die Karte erschien nur,
 * wenn heute etwas anstand. An einem Sonntag mit einer Mo–Fr-Gewohnheit fiel
 * damit beides weg: erst der Tag, dann die Zahl, an der sich der Aufbau
 * überhaupt ablesen lässt.
 */
test('the consistency rate survives a day with nothing due', function () {
    $user = User::factory()->create();
    $sunday = Carbon::today()->next(Carbon::SUNDAY);
    Carbon::setTestNow($sunday->copy()->setTime(9, 0));

    // Mo–Fr: heute ist Sonntag, also steht nichts an.
    $habit = Habit::factory()->for($user)->fixedSchedule('08:00', [1, 2, 3, 4, 5])
        ->withMeasure(20)->create();
    $habit->forceFill(['created_at' => Carbon::today()->subDays(60)])->save();

    $habit->completions()->create([
        'completed_on' => $sunday->copy()->subDays(2),
        'completed_at' => $sunday->copy()->subDays(2)->setTime(8, 0),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('todayProgress.total', 0)
            ->where('consistency.done', 1)
            ->where('consistency.scheduled', fn (int $scheduled): bool => $scheduled > 0)
        );

    Carbon::setTestNow();
});
