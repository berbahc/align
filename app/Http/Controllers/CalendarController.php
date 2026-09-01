<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ein Tag als Achse von Ankern — der Ort, an dem der Plan sichtbar liegt.
 *
 * Time-Blocking knüpft an bestehendes Verhalten an: 20 von 25 Befragten planen
 * ohnehin mit Kalender oder Planer, das meistgenutzte Hilfsmittel überhaupt.
 * Übernommen wird aber nur die vertraute Vertikale, nicht das Uhrzeit-Raster —
 * der Situationsanker schlägt in derselben Umfrage die feste Zeit (3,88 zu
 * 3,50), und ein Stundenlineal würde genau dagegen arbeiten.
 *
 * Vergangene Tage sind neutral. Kein Rot, keine Kreuze, keine markierte Lücke:
 * bei einem Schuldwert von ø 3,92 darf ein Rückblick kein Vorwurf sein.
 */
class CalendarController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $today = Carbon::today();
        $date = isset($validated['date'])
            ? Carbon::parse($validated['date'])->startOfDay()
            : $today->copy();

        $habits = $request->user()
            ->habits()
            ->with([
                'completions' => fn (Relation $query) => $query->whereDate('completed_on', $date),
                // Die Kette wird beim Sortieren und Benennen jedes Blocks
                // gefragt — ohne Vorladen wäre das eine Abfrage pro Glied.
                'chainedTo.chainedTo',
            ])
            ->orderBy('position')
            ->get();

        // Für die Anker-Stunden am Tagesrand („nach dem Aufstehen") fragt die
        // Gewohnheit den Schlafplan ihres Nutzers — die Beziehung wird hier
        // gesetzt, damit alle denselben geladenen Nutzer teilen.
        $habits->each(fn (Habit $habit) => $habit->setRelation('user', $request->user()));

        $blocks = $habits
            ->filter(fn (Habit $habit): bool => $this->existedOn($habit, $date))
            ->filter(fn (Habit $habit): bool => $habit->isScheduledOn($date))
            // Der Tag wird von oben nach unten gelesen: Morgen zuerst, Abend
            // zuletzt. Bei gleicher Stunde entscheidet die eigene Reihenfolge
            // aus der Gewohnheitsliste.
            ->sortBy(fn (Habit $habit): array => [$habit->dayAnchorHour() ?? PHP_INT_MAX, $habit->position])
            ->values();

        // Der Rahmen des gezeigten Tages: Die Achse beginnt beim Aufstehen
        // und endet bei der Schlafenszeit — der Tag hat einen Anfang und ein
        // Ende, und beide stehen sichtbar an der Achse.
        $window = $request->user()->sleepWindowFor($date->dayOfWeekIso);

        return Inertia::render('calendar', [
            'date' => $date->toDateString(),
            'heading' => $this->heading($date, $today),
            'isToday' => $date->isSameDay($today),
            // Nachtragen darf nur, was der Wochenstreifen auch zeigt — dieselbe
            // Grenze, die HabitCompletionController serverseitig durchsetzt.
            // Die Zukunft ist ohnehin nicht abhakbar.
            'canComplete' => $this->withinBackdatingWindow($date, $today),
            'previousDate' => $this->previousDate($habits, $date),
            'nextDate' => $date->copy()->addDay()->toDateString(),
            'blocks' => $blocks->map($this->block(...))->all(),
            'wakeTime' => $window['wakeTime'],
            'bedtime' => $window['bedtime'],
        ]);
    }

    /**
     * Eine Gewohnheit als Block — für die Achse wie für den Bereich darunter.
     *
     * @return array{id: int, title: string, anchor: string, anchorHour: int, measureLabel: string|null, timeRange: string|null, behaviorType: string, smallestStep: string|null, motivation: string|null, completed: bool, graduated: bool, chainedToId: int|null}
     */
    private function block(Habit $habit): array
    {
        return [
            'id' => $habit->id,
            'title' => $habit->title,
            'anchor' => $habit->scheduleLabel(),
            // Reist mit, damit ein Vorschlag der KI sich einsortieren kann,
            // bevor er übernommen wurde.
            'anchorHour' => $habit->dayAnchorHour() ?? Habit::UnknownAnchorHour,
            // Der Umfang und, wo er eine Dauer ist, die belegte Spanne.
            // „17:00 – 17:20" sagt zusätzlich, wann der Platz wieder frei
            // ist — die Größe, an der eine angehängte Gewohnheit beginnt.
            'measureLabel' => $habit->measureLabel(),
            'timeRange' => $habit->timeRangeLabel(),
            'behaviorType' => $habit->behavior_type->value,
            'smallestStep' => $habit->smallest_step,
            // Für die Starthilfe, die es jetzt auch im Kalender gibt.
            'motivation' => $habit->motivation,
            'completed' => $habit->completions->isNotEmpty(),
            'graduated' => $habit->graduated_at !== null,
            // Hängt der Block an dem darüber? Dann zieht die Oberfläche einen
            // Steg dazwischen, statt zwei zusammenhängende Blöcke wie zwei
            // unabhängige nebeneinanderzustellen.
            'chainedToId' => $habit->chained_to_habit_id,
        ];
    }

    /**
     * Gab es die Gewohnheit an diesem Tag überhaupt schon — und noch?
     *
     * Beendete Gewohnheiten bleiben in ihrer Vergangenheit stehen: Der Tag, an
     * dem sie lief, hat stattgefunden, und ihn nachträglich zu leeren wäre eine
     * Geschichtsfälschung. Vor dem Anlegen taucht sie dagegen nicht auf, sonst
     * entstünden rückwirkend Lücken, die niemand versäumt hat.
     */
    private function existedOn(Habit $habit, Carbon $date): bool
    {
        if ($habit->created_at?->startOfDay()->greaterThan($date)) {
            return false;
        }

        return $habit->graduated_at === null
            || $habit->graduated_at->startOfDay()->greaterThanOrEqualTo($date);
    }

    private function withinBackdatingWindow(Carbon $date, Carbon $today): bool
    {
        if ($date->greaterThan($today)) {
            return false;
        }

        return $date->greaterThanOrEqualTo(
            $today->copy()->subDays(Habit::WeekOverviewDays - 1),
        );
    }

    /**
     * Der Tag davor — aber nicht weiter zurück als bis zur ersten Gewohnheit.
     *
     * Davor gäbe es nichts zu sehen, und ein Pfeil, der in leere Tage führt,
     * verspricht etwas, das er nicht hält.
     *
     * @param  Collection<int, Habit>  $habits
     */
    private function previousDate(Collection $habits, Carbon $date): ?string
    {
        $first = $habits->min('created_at');

        if ($first === null) {
            return null;
        }

        $previous = $date->copy()->subDay();

        return $previous->greaterThanOrEqualTo(Carbon::parse($first)->startOfDay())
            ? $previous->toDateString()
            : null;
    }

    /**
     * Die Datumszeile im Kopf: „Heute · Dienstag, 21. Juli".
     *
     * Die App-Locale ist nicht deutsch, die Oberfläche schon — dasselbe Muster
     * wie im DashboardController.
     */
    private function heading(Carbon $date, Carbon $today): string
    {
        $localised = $date->copy();
        $localised->locale('de');

        $prefix = match (true) {
            $date->isSameDay($today) => 'Heute · ',
            $date->isSameDay($today->copy()->subDay()) => 'Gestern · ',
            $date->isSameDay($today->copy()->addDay()) => 'Morgen · ',
            default => '',
        };

        return $prefix.$localised->isoFormat('dddd, D. MMMM');
    }
}
