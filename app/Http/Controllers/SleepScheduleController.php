<?php

namespace App\Http\Controllers;

use App\Actions\CarryHabitsWithFrame;
use App\Models\Habit;
use App\Models\SleepSchedule;
use App\Models\User;
use App\Support\DayPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SleepScheduleController extends Controller
{
    /**
     * Der Schlafplan: Aufsteh- und Schlafenszeit für jeden Wochentag.
     *
     * Immer alle sieben Tage, auch wenn nie etwas eingestellt wurde — der
     * Rahmen existiert von Anfang an, gespeichert wird nur die Abweichung
     * von der Voreinstellung.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('sleep', [
            'windows' => array_values($request->user()->sleepWindows()),
            'bedtimeReminderEnabled' => $request->user()->bedtime_reminder_enabled,
            'reminderLeadMinutes' => SleepSchedule::BedtimeReminderLeadMinutes,
            // Mit welchem Tag der Editor aufgeht. Der Kalender schickt ihn mit:
            // Wer im Tag auf die Aufsteh-Marke tippt, meint diesen Tag und
            // nicht den Montag. Ohne Angabe bleibt es beim Montag — der Anfang
            // der Woche ist der Anfang des Plans.
            'selectedWeekday' => $this->requestedWeekday($request),
        ]);
    }

    /**
     * Der Wochentag aus der Adresse — oder nichts.
     *
     * Geprüft und nicht durchgereicht: `?weekday=99` würde sonst einen Editor
     * ohne Tag ergeben. Was nicht zwischen 1 und 7 liegt, gilt als nicht
     * gefragt.
     */
    private function requestedWeekday(Request $request): ?int
    {
        $weekday = $request->integer('weekday');

        return $weekday >= 1 && $weekday <= 7 ? $weekday : null;
    }

    /**
     * Was der neue Rahmen mit den Gewohnheiten machen würde.
     *
     * Erst zeigen, dann übernehmen — dasselbe Muster wie beim Ordnen eines
     * Tages ({@see DayOrderController::suggestions()}). Der Schlafplan ist der
     * Rahmen von allem; ihn zu verschieben und die Gewohnheiten stillschweigend
     * mitzunehmen hieße, den Tag hinter dem Rücken des Nutzers umzuräumen.
     *
     * Gerechnet wird, indem wirklich gespeichert und danach zurückgerollt
     * wird. Der Umweg ist Absicht: {@see CarryHabitsWithFrame} liest den Tag
     * aus der Datenbank, samt der situativen Gewohnheiten, die dem neuen
     * Rahmen schon gefolgt sind. Gegen den alten Stand gerechnet zeigte die
     * Vorschau etwas anderes, als hinterher passierte — und eine Vorschau, der
     * man nicht trauen kann, ist schlimmer als keine.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $this->validatePlan($request);
        $user = $request->user();

        $before = $user->sleepWindows();

        DB::beginTransaction();

        try {
            $this->storePlan($user, $validated['days']);

            $carried = (new CarryHabitsWithFrame)->forWeek($user, $before);
        } finally {
            // Nur gefragt, nicht getan: Der Rollback nimmt die sieben Zeilen
            // wieder zurück. Gespeichert wird erst in `update()`.
            DB::rollBack();
            $user->unsetRelation('sleepSchedules');
        }

        return response()->json([
            'moves' => array_map(fn (array $move): array => [
                'habitId' => $move['habit']->id,
                'title' => $move['habit']->title,
                'from' => DayPlan::toTime($move['from']),
                'to' => DayPlan::toTime($move['to']),
            ], $carried['moves']),
            'blocked' => array_map(fn (array $entry): array => [
                'habitId' => $entry['habit']->id,
                'title' => $entry['habit']->title,
                'reason' => $entry['reason'],
            ], $carried['blocked']),
        ]);
    }

    /**
     * Alle sieben Tage in einem Zug speichern.
     *
     * Ein Speichern pro Tag würde sieben Bestätigungen bedeuten — der Plan
     * ist aber eine Woche, keine sieben Entscheidungen. Upsert statt
     * Insert, weil jeder Wochentag höchstens eine Zeile hat.
     *
     * `carry_habits` sagt, ob die Gewohnheiten mitziehen sollen, die durch den
     * neuen Rahmen fallen. Der Plan wird in jedem Fall gespeichert: Er
     * beschreibt, wann jemand schläft, und das weist die App nicht ab. Was
     * draußen bleibt, bleibt sichtbar — das Raster zeichnet es im Nachtband.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $this->validatePlan($request);
        $user = $request->user();
        $carry = $request->boolean('carry_habits');

        $moved = DB::transaction(function () use ($user, $validated, $carry): array {
            $before = $user->sleepWindows();

            $this->storePlan($user, $validated['days']);

            if ($carry === false) {
                return [];
            }

            // Die Aktion schreibt die Züge selbst — jeden sofort, damit der
            // nächste ihn sieht. Sonst landeten zwei auf demselben Platz.
            return (new CarryHabitsWithFrame)->forWeek($user, $before)['moves'];
        });

        // `forceFill`, weil das Feld bewusst nicht massen-zuweisbar ist — es
        // gehört zu keinem Registrierungs- oder Profilformular.
        $user->forceFill([
            'bedtime_reminder_enabled' => $validated['bedtime_reminder_enabled'],
        ])->save();

        return back()->with('success', $this->savedMessage($moved));
    }

    /**
     * Die sieben Zeilen schreiben — ohne Rücksicht darauf, was daran hängt.
     *
     * Die Beziehung wird danach zurückgesetzt: Die Inertia-Middleware hat sie
     * vor dem Controller schon geladen (für die geteilten Schlaf-Props), und
     * ohne dieses Zurücksetzen läse jeder spätere Zugriff im selben Request
     * den alten Stand — auch {@see CarryHabitsWithFrame}, das den neuen
     * braucht.
     *
     * @param  list<array{weekday: int, wake_time: string, bedtime: string, alarm_enabled: bool}>  $days
     */
    private function storePlan(User $user, array $days): void
    {
        foreach ($days as $day) {
            $user->sleepSchedules()->updateOrCreate(
                ['weekday' => $day['weekday']],
                [
                    'wake_time' => $day['wake_time'],
                    'bedtime' => $day['bedtime'],
                    'alarm_enabled' => $day['alarm_enabled'],
                ],
            );
        }

        $user->unsetRelation('sleepSchedules');
    }

    /**
     * Der Satz danach — er nennt, was sich mitbewegt hat.
     *
     * Bis zu zwei Gewohnheiten beim Namen, danach eine Zahl: Eine Meldung, die
     * fünf Titel aufzählt, liest niemand mehr, und im Kalender steht ohnehin,
     * wo jetzt was liegt.
     *
     * @param  list<array{habit: Habit, from: int, to: int}>  $moved
     */
    private function savedMessage(array $moved): string
    {
        if ($moved === []) {
            return 'Dein Schlafplan ist gespeichert.';
        }

        $named = array_slice($moved, 0, 2);

        $titles = implode(' und ', array_map(
            fn (array $move): string => sprintf(
                '„%s" auf %s',
                $move['habit']->title,
                DayPlan::toTime($move['to']),
            ),
            $named,
        ));

        $rest = count($moved) - count($named);

        return $rest > 0
            ? sprintf('Dein Schlafplan ist gespeichert. %s und %d weitere sind mitgezogen.', $titles, $rest)
            : sprintf('Dein Schlafplan ist gespeichert. %s ist mitgezogen.', $titles);
    }

    /**
     * Die Regeln, die Vorschau und Speichern teilen.
     *
     * Zwei Listen wären zwei Pläne: Die Vorschau muss genau das prüfen, was
     * hinterher gespeichert wird, sonst zeigte sie eine Rechnung für Zeiten,
     * die gar nicht durchkommen.
     *
     * @return array{days: list<array{weekday: int, wake_time: string, bedtime: string, alarm_enabled: bool}>, bedtime_reminder_enabled: bool}
     */
    private function validatePlan(Request $request): array
    {
        /** @var array{days: list<array{weekday: int, wake_time: string, bedtime: string, alarm_enabled: bool}>, bedtime_reminder_enabled: bool} $validated */
        $validated = $request->validate([
            'days' => ['required', 'array', 'size:7'],
            'days.*.weekday' => ['required', 'integer', 'between:1,7', 'distinct'],
            'days.*.wake_time' => ['required', 'date_format:H:i'],
            'days.*.bedtime' => ['required', 'date_format:H:i', 'different:days.*.wake_time'],
            'days.*.alarm_enabled' => ['required', 'boolean'],
            'bedtime_reminder_enabled' => ['required', 'boolean'],
            // Ohne Angabe bleibt alles liegen, wo es liegt: Das Mitziehen ist
            // eine Zusage, keine Voreinstellung.
            'carry_habits' => ['nullable', 'boolean'],
        ], [
            'days.*.bedtime.different' => 'Aufsteh- und Schlafenszeit können nicht dieselbe sein.',
        ]);

        return $validated;
    }
}
