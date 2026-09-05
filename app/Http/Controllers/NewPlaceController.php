<?php

namespace App\Http\Controllers;

use App\Actions\RememberSuggestions;
use App\Ai\Agents\SuggestNewPlaces;
use App\Ai\UserContext;
use App\Enums\ScheduleType;
use App\Enums\SuggestionKind;
use App\Models\AiSuggestion;
use App\Models\Habit;
use App\Models\SleepSchedule;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\SlotConflict;
use App\Support\Timetable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

/**
 * Neue Plätze für das, was der Stundenplan verdrängt hat.
 *
 * Zwei Schritte, wie bei jeder KI-Funktion in dieser App: Erst der Vorschlag
 * (kostet einen Aufruf, wird gedrosselt), dann das Übernehmen (kostet nichts,
 * prüft aber jeden Platz noch einmal — zwischen Vorschlag und Annahme liegt
 * eine Entscheidung, und in der Zeit kann sich der Tag geändert haben).
 */
class NewPlaceController extends Controller
{
    /**
     * Wie lange ein Tagesfenster nach dem Aufstehen noch gilt, wenn es
     * eigentlich davor läge — wer um elf aufsteht, frühstückt trotzdem.
     */
    private const int BandFallbackMinutes = 180;

    public function suggestions(Request $request, RememberSuggestions $remember): JsonResponse
    {
        $user = $request->user();
        $parked = $this->parked($user);

        if ($parked->isEmpty()) {
            return response()->json([
                'message' => 'Gerade steht keine Gewohnheit ohne Platz da.',
            ], 422);
        }

        [$askable, $unplaced] = $this->prepare($user, $parked);

        // Alles ohne freies Fenster bleibt beim Menschen — das Modell nach
        // etwas zu fragen, das es nicht geben kann, erzeugt nur einen
        // Vorschlag, den die Nachprüfung wegwirft.
        if ($askable === []) {
            return response()->json([
                'reason' => 'Für keine davon ist im neuen Stundenplan ein Fenster frei, das zu ihr passt.',
                'places' => [],
                'unplaced' => $unplaced,
            ]);
        }

        try {
            $places = (new SuggestNewPlaces(
                habits: $askable,
                sleepWindows: $user->sleepWindows(),
                context: UserContext::for($user, SuggestionKind::Anchor),
            ))->places();
        } catch (Throwable $exception) {
            Log::warning('Vorschlag für neue Plätze fehlgeschlagen.', ['exception' => $exception]);

            return response()->json([
                'message' => 'Die Vorschläge lassen sich gerade nicht laden.',
            ], 503);
        }

        $byId = $parked->keyBy('id');
        $placedIds = array_column($places, 'id');
        $timetable = Timetable::for($user);

        // Was das Modell weggelassen hat, steht neben dem, was nie gefragt
        // wurde: Für die Person ist es dasselbe — sie legt es selbst hin.
        foreach ($askable as $habit) {
            if (! in_array($habit['id'], $placedIds, strict: true)) {
                $unplaced[] = [
                    'id' => $habit['id'],
                    'title' => $habit['title'],
                    'previousLabel' => $byId->get($habit['id'])?->scheduleLabel() ?? '',
                    'message' => 'Hier hat die KI keinen Platz gefunden, der zur Gewohnheit passt.',
                ];
            }
        }

        return response()->json([
            'reason' => sprintf(
                count($places) === 1
                    ? 'Eine von %d passt wieder in deinen Tag.'
                    : '%d von %d passen wieder in deinen Tag.',
                ...(count($places) === 1 ? [$parked->count()] : [count($places), $parked->count()]),
            ),
            'places' => array_map(function (array $place) use ($byId, $user, $remember, $timetable): array {
                /** @var Habit $habit */
                $habit = $byId->get($place['id']);

                $alternative = ['time' => $place['time'], 'days' => $place['days'], 'reason' => $place['reason']];
                /** @var AiSuggestion $remembered */
                $remembered = $remember->anchors($user, $habit, [$alternative])->first();

                return [
                    'id' => $habit->id,
                    'title' => $habit->title,
                    'suggestionId' => $remembered->id,
                    'previousLabel' => $habit->scheduleLabel(),
                    'time' => $place['time'],
                    'days' => $place['days'],
                    'label' => Habit::anchorLabel(time: $place['time'], days: $place['days']),
                    'timeRange' => $place['time'].' – '.DayPlan::toTime(DayPlan::toMinutes($place['time']) + $place['minutes']),
                    'reason' => $place['reason'],
                    'previewDate' => $this->previewDate($place['days'], $timetable),
                ];
            }, $places),
            'unplaced' => $unplaced,
        ]);
    }

    /**
     * Die angenommenen Plätze übernehmen.
     *
     * Jeder Platz wird noch einmal geprüft — gegen den Tag, wie er jetzt
     * liegt, und die Plätze gegeneinander. Ein Vorschlag ist ein Vorschlag,
     * keine Vollmacht. Was durchkommt, bekommt seine Zeit, verliert den
     * Vermerk und markiert den Vorschlag als übernommen.
     *
     * Kein Rückweg wie bei der Einzelanpassung: Die alten Zeiten waren
     * vergeben, dorthin führt nichts zurück. Was jetzt steht, lässt sich wie
     * jede Gewohnheit anpassen.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'places' => ['required', 'array', 'min:1'],
            'places.*.id' => ['required', 'integer'],
            'places.*.time' => ['required', 'date_format:H:i'],
            'places.*.days' => ['required', 'array', 'min:1', 'max:7'],
            // Kein `distinct`: Die Regel vergliche über alle Plätze hinweg,
            // und zwei Gewohnheiten dürfen denselben Wochentag haben. Doppelte
            // Tage innerhalb eines Platzes fallen unten ohnehin zusammen.
            'places.*.days.*' => ['integer', 'between:1,7'],
            'places.*.suggestion_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $parked = $this->parked($user)->keyBy('id');

        /** @var list<array{habit: Habit, start: int, days: list<int>, suggestionId: int|null}> $rows */
        $rows = [];

        foreach ($validated['places'] as $index => $row) {
            $habit = $parked->get((int) $row['id']);

            if ($habit === null) {
                throw ValidationException::withMessages([
                    "places.$index.id" => 'Diese Gewohnheit wartet nicht mehr auf einen Platz.',
                ]);
            }

            /** @var list<int> $days */
            $days = array_values(array_unique(array_map(intval(...), $row['days'])));
            sort($days);

            $rows[] = [
                'habit' => $habit,
                'start' => DayPlan::toMinutes($row['time']),
                'days' => $days,
                'suggestionId' => isset($row['suggestion_id']) ? (int) $row['suggestion_id'] : null,
            ];
        }

        $this->guard($user, $rows);

        foreach ($rows as $row) {
            $row['habit']->update([
                'schedule_type' => ScheduleType::Fixed,
                'scheduled_time' => DayPlan::toTime($row['start']),
                'scheduled_days' => $row['days'],
                'trigger_situation' => null,
                'chained_to_habit_id' => null,
            ]);
            $row['habit']->takeAPlace();

            if ($row['suggestionId'] !== null) {
                $user->aiSuggestions()
                    ->where('habit_id', $row['habit']->id)
                    ->find($row['suggestionId'])
                    ?->markAccepted();
            }
        }

        Inertia::flash('placesApplied', [
            'titles' => array_map(fn (array $row): string => $row['habit']->title, $rows),
            'remaining' => $this->parked($user)->count(),
        ]);

        return back();
    }

    /**
     * Der Tag, an dem man den Vorschlag ansieht.
     *
     * Der erste seiner Wochentage, an dem der Stundenplan gilt — sonst der
     * nächste. Wer den Vorschlag gestrichelt im Raster sieht, soll die Kurse
     * daneben sehen, um die es geht; ein Montag im September ohne Mathe
     * zeigte einen Tag, den es so nicht mehr geben wird.
     *
     * @param  list<int>  $days
     */
    private function previewDate(array $days, Timetable $timetable): string
    {
        $dates = array_map(
            fn (int $day): Carbon => $timetable->firstDateOf($day) ?? SlotConflict::nextWeekday($day),
            $days,
        );

        usort($dates, fn (Carbon $a, Carbon $b): int => $a->getTimestamp() <=> $b->getTimestamp());

        return $dates[0]->toDateString();
    }

    /**
     * @return Collection<int, Habit>
     */
    private function parked(User $user): Collection
    {
        // Auch die ohne Uhrzeit: Eine Gewohnheit an einer Situation belegte
        // im Tag die Stunde ihres Ankers und wurde darüber verdrängt. Sie hier
        // wegzufiltern hieße, sie ohne Vorschlag hängen zu lassen.
        $habits = $user->habits()
            ->active()
            ->displaced()
            ->orderBy('position')
            ->get();

        $habits->each(fn (Habit $habit) => $habit->setRelation('user', $user));

        return $habits;
    }

    /**
     * Was das Modell je Gewohnheit bekommt — und was gar nicht erst hingeht.
     *
     * Die freien Fenster je Wochentag, beschnitten auf Schlafrahmen und
     * Tagesfenster, sortiert nach Abstand zur alten Zeit. Eine Gewohnheit ohne
     * ein einziges Fenster geht nicht zum Modell, sondern zur Person.
     *
     * @param  Collection<int, Habit>  $parked
     * @return array{0: list<array{id: int, title: string, minutes: int, previousTime: string, previousDays: list<int>, band: array{from: int, to: int}|null, bandIsHard: bool, windows: array<int, list<array{from: int, to: int}>>}>, 1: list<array{id: int, title: string, previousLabel: string, message: string}>}
     */
    private function prepare(User $user, Collection $parked): array
    {
        $timetable = Timetable::for($user);
        $sleep = $user->sleepWindows();

        // Der Tag einmal je Wochentag — ohne die geparkten, mit allem, was
        // noch steht. Ausdrücklich ohne: Ein Vermerk, der erst ab
        // Semesterbeginn gilt, belegt nächste Woche noch seinen alten Platz,
        // und der darf dem eigenen Vorschlag nicht im Weg liegen.
        $others = $user->habits()
            ->active()
            ->whereNotIn('id', $parked->pluck('id'))
            ->with('chainedTo.chainedTo')
            ->get();
        $others->each(fn (Habit $habit) => $habit->setRelation('user', $user));

        // Je Wochentag dieselben Daten wie die Kollisionsprüfung: der nächste
        // Termin — und der erste im Semester, wenn das noch vor uns liegt.
        // Sonst rechnete der Vorschlag gegen einen September ohne Kurse und
        // fiele beim Übernehmen an genau dem Kurs durch, den er nicht sah.
        /** @var array<int, list<DayPlan>> $plans */
        $plans = [];

        foreach (range(1, 7) as $weekday) {
            foreach (SlotConflict::datesFor([$weekday], $timetable) as $date) {
                $plans[$weekday][] = DayPlan::forDate(
                    $others->filter(fn (Habit $habit): bool => $habit->isScheduledOn($date))->values(),
                    $date,
                    $sleep,
                    $timetable->blocksOn($date),
                );
            }
        }

        $askable = [];
        $unplaced = [];

        foreach ($parked as $habit) {
            $minutes = $habit->durationMinutes() ?? DayPlan::AssumedMinutes;
            // Wo sie lag: die Uhrzeit, oder — ohne eine — die Stunde ihres
            // Ankers. Das ist der Punkt, um den die Vorschläge kreisen.
            $previousStart = $this->previousStart($habit);

            if ($previousStart === null) {
                continue;
            }

            $days = $habit->scheduled_days ?? [1, 2, 3, 4, 5, 6, 7];

            $band = $habit->dayBand();
            $bandIsHard = true;
            $windows = [];

            foreach ($days as $weekday) {
                $frame = $plans[$weekday][0]->frame();
                [$clip, $hard] = $this->clip($band, $frame);
                $bandIsHard = $bandIsHard && $hard;

                // Frei ist nur, was an jedem der Daten frei ist.
                $free = array_values(array_filter(array_map(
                    fn (array $window): ?array => $this->intersect($window, $clip, $minutes),
                    DayPlan::commonFreeWindows($plans[$weekday], $minutes),
                )));

                usort($free, fn (array $a, array $b): int => abs($a['from'] - $previousStart) <=> abs($b['from'] - $previousStart));

                $windows[$weekday] = $free;
            }

            if (array_filter($windows) === []) {
                $unplaced[] = [
                    'id' => $habit->id,
                    'title' => $habit->title,
                    'previousLabel' => $habit->scheduleLabel(),
                    'message' => sprintf(
                        'An keinem ihrer Tage ist ein Fenster von %d Minuten frei, das zu ihr passt.',
                        $minutes,
                    ),
                ];

                continue;
            }

            $askable[] = [
                'id' => $habit->id,
                'title' => $habit->title,
                'minutes' => $minutes,
                'previousTime' => DayPlan::toTime($previousStart),
                'previousDays' => $days,
                'band' => $band,
                'bandIsHard' => $bandIsHard,
                'windows' => $windows,
            ];
        }

        return [$askable, $unplaced];
    }

    /**
     * Wo die Gewohnheit lag, bevor der Stundenplan kam — in Minuten.
     *
     * Eine feste Uhrzeit sagt es selbst; eine Gewohnheit an einer Situation
     * belegte die volle Stunde ihres Ankers, und genau die ist ihr
     * Ausgangspunkt. Null heißt: Sie hatte gar keine Stelle, und dann gibt es
     * auch keine, zu der ein Vorschlag nah liegen könnte.
     */
    private function previousStart(Habit $habit): ?int
    {
        if ($habit->scheduled_time !== null) {
            return DayPlan::toMinutes($habit->scheduled_time->format('H:i'));
        }

        $hour = $habit->plannedAnchorHour();

        return $hour === null ? null : $hour * 60;
    }

    /**
     * Das Tagesfenster mit dem Wachrahmen verschneiden.
     *
     * Die Fenster stehen in Uhrzeiten, der Tag einer Person nicht. Läge das
     * ganze Fenster vor dem Aufstehen, gälte es nicht mehr als Grenze —
     * dann rutscht es nach hinten, an den Anfang des Tages, und wird zum
     * Hinweis. Ein Fenster, das jemandem seinen eigenen Tag verböte, wäre
     * keine Sorgfalt, sondern ein Fehler. Ein Abend endet mit dem Tag, auch
     * wenn der nach Mitternacht endet.
     *
     * @param  array{from: int, to: int}|null  $band
     * @param  array{from: int, to: int}  $frame
     * @return array{0: array{from: int, to: int}, 1: bool} Der Ausschnitt, und ob er noch eine Grenze ist
     */
    private function clip(?array $band, array $frame): array
    {
        if ($band === null) {
            return [$frame, true];
        }

        if ($band['to'] >= 1440) {
            $band['to'] = max(1440, $frame['to']);
        }

        if ($band['to'] <= $frame['from']) {
            return [['from' => $frame['from'], 'to' => $frame['from'] + self::BandFallbackMinutes], false];
        }

        return [[
            'from' => max($band['from'], $frame['from']),
            'to' => min($band['to'], $frame['to']),
        ], true];
    }

    /**
     * @param  array{from: int, to: int}  $window
     * @param  array{from: int, to: int}  $clip
     * @return array{from: int, to: int}|null
     */
    private function intersect(array $window, array $clip, int $minutes): ?array
    {
        $from = max($window['from'], $clip['from']);
        $to = min($window['to'], $clip['to']);

        return $to - $from >= $minutes ? ['from' => $from, 'to' => $to] : null;
    }

    /**
     * Weist ab, was beim Übernehmen nicht mehr passt.
     *
     * Gegen den Tag über {@see SlotConflict} — samt der Kette, die mitrückt —
     * und die Plätze gegeneinander, weil {@see SlotConflict} die bewegten
     * bewusst nicht zählt.
     *
     * @param  list<array{habit: Habit, start: int, days: list<int>, suggestionId: int|null}>  $rows
     */
    private function guard(User $user, array $rows): void
    {
        $sleep = $user->sleepWindows();

        foreach ($rows as $index => $row) {
            $spans = $row['habit']->spansFrom($row['start']);
            $time = DayPlan::toTime($row['start']);

            foreach ($row['days'] as $day) {
                $window = $sleep[$day] ?? null;

                if ($window !== null && ! SleepSchedule::containsTime($window['wakeTime'], $window['bedtime'], $time)) {
                    throw ValidationException::withMessages([
                        "places.$index.time" => sprintf(
                            '„%s" läge um %s außerhalb deines Tages.',
                            $row['habit']->title,
                            $time,
                        ),
                    ]);
                }
            }

            $conflict = SlotConflict::find($user, $spans, $row['days'], array_column($spans, 'id'));

            if ($conflict !== null) {
                throw ValidationException::withMessages([
                    "places.$index.time" => SlotConflict::message(
                        $conflict['block'],
                        $conflict['date'],
                        'Der Platz ist seit dem Vorschlag vergeben — bitte neu vorschlagen lassen.',
                    ),
                ]);
            }

            foreach (array_slice($rows, 0, $index) as $other) {
                if (array_intersect($row['days'], $other['days']) === []) {
                    continue;
                }

                $end = $row['start'] + ($row['habit']->durationMinutes() ?? DayPlan::AssumedMinutes);
                $otherEnd = $other['start'] + ($other['habit']->durationMinutes() ?? DayPlan::AssumedMinutes);

                if ($row['start'] < $otherEnd && $end > $other['start']) {
                    throw ValidationException::withMessages([
                        "places.$index.time" => sprintf(
                            '„%s" und „%s" lägen übereinander.',
                            $row['habit']->title,
                            $other['habit']->title,
                        ),
                    ]);
                }
            }
        }
    }
}
