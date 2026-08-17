<?php

namespace App\Http\Controllers;

use App\Actions\CreateHabit;
use App\Actions\ReleaseChainedHabits;
use App\Enums\BehaviorType;
use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use App\Http\Requests\StoreHabitRequest;
use App\Http\Requests\UpdateHabitRequest;
use App\Models\Appointment;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HabitController extends Controller
{
    /**
     * Die Gewohnheiten, an die sich etwas anhängen lässt.
     *
     * Nur eigene, laufende, mit Platz im Tag — und beim Bearbeiten nicht die
     * bearbeitete selbst. Ihre belegte Spanne reist mit, damit die Oberfläche
     * gleich sagen kann, wann der Anschluss anfinge.
     *
     * @return list<array{id: int, title: string, anchor: string, startsAt: string|null}>
     */
    private function chainCandidates(User $user, ?Habit $except = null): array
    {
        return array_values($user->habits()
            ->active()
            ->with('chainedTo')
            ->orderBy('position')
            ->get()
            ->reject(fn (Habit $habit): bool => $except !== null && $habit->is($except))
            ->filter(fn (Habit $habit): bool => $habit->schedule_type->isPlanned())
            ->map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'title' => $habit->titleWithMeasure(),
                'anchor' => $habit->scheduleLabel(),
                // Wo der Anschluss läge — null, wenn die Kette an einer
                // Situation hängt und niemand die Uhrzeit kennt.
                'startsAt' => $habit->endsAt()?->format('H:i'),
            ])
            ->all());
    }

    /**
     * Die belegten Fenster des Tages — Grundlage des Überschneidungshinweises.
     *
     * Nur feste Uhrzeiten belegen etwas: Eine Situation hat keinen Zeitpunkt,
     * mit dem sich kollidieren ließe. Ohne Dauer ist das Fenster ein Punkt und
     * stößt nur mit einem Start zur selben Minute zusammen.
     *
     * @return list<array{id: int, title: string, days: list<int>, from: string, to: string}>
     */
    private function busySlots(User $user, ?Habit $except = null): array
    {
        return array_values($user->habits()
            ->active()
            ->get()
            ->reject(fn (Habit $habit): bool => $except !== null && $habit->is($except))
            ->filter(fn (Habit $habit): bool => $habit->startsAt() !== null)
            ->map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'title' => $habit->title,
                'days' => $habit->scheduled_days ?? [1, 2, 3, 4, 5, 6, 7],
                'from' => $habit->startsAt()?->format('H:i') ?? '',
                'to' => ($habit->endsAt() ?? $habit->startsAt())?->format('H:i') ?? '',
            ])
            ->all());
    }

    /**
     * Das Fenster, über das gezählt wird, was sich ergeben hat.
     *
     * Dieselben dreißig Tage wie bei der Konsistenzrate — damit die beiden
     * Zahlen nebeneinander dasselbe meinen, auch wenn sie verschieden rechnen.
     */
    private const int RecentCountDays = 30;

    /**
     * Die Verwaltungsansicht: alle aktiven Gewohnheiten mit ihrer Planung.
     *
     * Anders als die Übersicht zeigt sie auch, was heute nicht ansteht — hier
     * geht es um die Gewohnheit an sich, nicht um den heutigen Tag.
     */
    public function index(Request $request): Response
    {
        $today = Carbon::today();

        $habits = $request->user()
            ->habits()
            ->active()
            ->with('completionDates')
            ->orderBy('position')
            ->get()
            // Zeitlich statt nach Anlegedatum: Was heute ansteht, steht oben,
            // dann morgen, dann der Rest der Woche. Innerhalb eines Tages
            // entscheidet die Tageszeit — dieselbe Achse wie im Kalender.
            // Gewohnheiten ohne gewählten Wochentag haben keinen nächsten
            // Termin und rutschen ans Ende, statt die Reihe anzuführen.
            ->sortBy(fn (Habit $habit): array => [
                $habit->daysUntilNextOccurrence($today) ?? PHP_INT_MAX,
                $habit->dayAnchorHour() ?? PHP_INT_MAX,
                $habit->position,
            ])
            ->values();

        // Die Zahl trägt das Archiv: sie beziffert, was ein endgültiges Löschen
        // kosten würde, und macht aus der Rückfrage mehr als eine Formalie.
        $graduated = $request->user()
            ->habits()
            ->graduated()
            ->withCount('completions')
            ->orderByDesc('graduated_at')
            ->get();

        return Inertia::render('habits/index', [
            'maxActive' => Habit::MaxActivePerUser,
            'habits' => $habits->map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'title' => $habit->title,
                'behaviorType' => $habit->behavior_type->value,
                // Steht hinter dem Titel, nicht darin: „Spazieren gehen · 20 Min".
                'measureLabel' => $habit->measureLabel(),
                'scheduleLabel' => $habit->scheduleLabel(),
                'canRemind' => $habit->canRemind(),
                'reminderEnabled' => $habit->reminder_enabled,
                // Benennt, was die Reihenfolge ohnehin schon behauptet. Ohne
                // diese Zeile sähe die Liste sortiert aus, ohne dass erkennbar
                // wäre, wonach — „17:00 · Mo–Fr" sagt nicht, ob das heute ist.
                'nextOccurrence' => $habit->nextOccurrenceLabel($today),
                // Die Trennlinie der Liste: heute oder später. Serverseitig,
                // weil dieselbe Frage schon die Sortierung entscheidet — sie im
                // Browser ein zweites Mal zu beantworten hieße, zwei Antworten
                // deckungsgleich halten zu müssen.
                'dueToday' => $habit->isScheduledOn($today),
                // Die Serie steht hier für jede Gewohnheit einzeln — anders als
                // auf der Übersicht, die nur die stärkste zeigt. Unterhalb der
                // Mindestlänge bleibt die Zeile weg statt eine „1" zu behaupten.
                'streak' => $this->streakLabel($habit),
                // Was sich ergibt, kennt keine Serie: Zwei Tage ohne Gelegenheit
                // würden den Kulanztag aufbrauchen und die Kette reißen lassen,
                // obwohl nichts versäumt wurde. Gezählt wird stattdessen, was
                // war — ohne Soll, gegen das es sich messen ließe.
                'recentCount' => $habit->schedule_type->isPlanned()
                    ? null
                    : $habit->completionsSince(self::RecentCountDays).'× in '.self::RecentCountDays.' Tagen',
            ])->all(),
            'graduatedHabits' => $graduated->map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'title' => $habit->title,
                'behaviorType' => $habit->behavior_type->value,
                'scheduleLabel' => $habit->scheduleLabel(),
                'graduatedOn' => $habit->graduated_at?->format('d.m.Y') ?? '',
                'completionCount' => (int) $habit->completions_count,
            ])->all(),
        ]);
    }

    /**
     * Die laufende Serie als fertige Zeile — oder nichts.
     */
    private function streakLabel(Habit $habit): ?string
    {
        $streak = $habit->currentStreak();

        return $streak >= Habit::StreakMinimum
            ? $habit->streakLabel($streak)
            : null;
    }

    public function create(Request $request): Response
    {
        return Inertia::render('habits/create', [
            'directions' => BehaviorType::options(),
            'triggerSuggestions' => array_keys(Habit::TriggerSuggestions),
            'scheduleTypes' => ScheduleType::options(),
            'measureUnits' => MeasureUnit::options(),
            'chainCandidates' => $this->chainCandidates($request->user()),
            'busySlots' => $this->busySlots($request->user()),
            // Für den letzten, freiwilligen Schritt: mit wem und wann.
            'friends' => $request->user()->friends()->map(fn (User $friend): array => [
                'id' => $friend->id,
                'name' => $friend->name,
                'initial' => mb_strtoupper(mb_substr($friend->name, 0, 1)),
            ])->all(),
            'appointmentDays' => Appointment::dayChoices(),
            'appointmentsEnabled' => $request->user()->appointments_enabled,
        ]);
    }

    /**
     * Nach dem Anlegen führt der Weg zurück auf die Übersicht — auf der eine
     * Gewohnheit mit fester Uhrzeit heute aber gar nicht stehen muss.
     *
     * Eine Mo–Fr-Gewohnheit, samstags angelegt, wäre dort unsichtbar und der
     * Eindruck wäre, sie sei nicht gespeichert worden. Die Bestätigung nennt
     * deshalb den nächsten Termin und sagt ausdrücklich dazu, wenn er nicht
     * heute liegt.
     */
    public function store(StoreHabitRequest $request, CreateHabit $createHabit): RedirectResponse
    {
        $habit = $createHabit->handle($request->user(), $request->habitAttributes());

        $next = $habit->nextOccurrence();

        Inertia::flash('habitCreated', [
            'id' => $habit->id,
            'title' => $habit->title,
            'anchor' => $habit->scheduleLabel(),
            'when' => $this->nextOccurrenceLabel($habit, $next),
            'scheduledToday' => $next?->isToday() ?? false,
        ]);

        // Die Gewohnheit ist gespeichert. Wer jemanden im Kreis hat, bekommt
        // danach noch die Frage, ob er sie zu zweit angehen will — als
        // freiwilliger letzter Schritt, nicht als Bedingung.
        //
        // Ohne Kreis oder mit abgestellten Verabredungen wäre der Schritt eine
        // leere Seite: Dann führt der Weg wie bisher direkt zur Übersicht.
        $user = $request->user();

        if ($user->appointments_enabled && $user->friends()->isNotEmpty()) {
            return to_route('habits.create');
        }

        return to_route('dashboard');
    }

    /**
     * Das Formular, in dem sich eine Gewohnheit vollständig ändern lässt.
     *
     * Flach statt in fünf Schritten: Der Wizard führt jemanden, der noch nicht
     * weiß, was er will. Wer etwas ändert, weiß es — für ihn wäre die Führung
     * ein Umweg.
     */
    public function edit(Request $request, Habit $habit): Response
    {
        Gate::authorize('update', $habit);

        return Inertia::render('habits/edit', [
            'habit' => [
                'id' => $habit->id,
                'title' => $habit->title,
                'behaviorType' => $habit->behavior_type->value,
                'targetAmount' => $habit->target_amount,
                'targetUnit' => $habit->target_unit?->value,
                'scheduleType' => $habit->schedule_type->value,
                'triggerSituation' => $habit->trigger_situation,
                'scheduledTime' => $habit->scheduled_time?->format('H:i'),
                'scheduledDays' => $habit->scheduled_days,
                'chainedToHabitId' => $habit->chained_to_habit_id,
                'smallestStep' => $habit->smallest_step,
                'motivation' => $habit->motivation,
            ],
            'directions' => BehaviorType::options(),
            'triggerSuggestions' => array_keys(Habit::TriggerSuggestions),
            'scheduleTypes' => ScheduleType::options(),
            'measureUnits' => MeasureUnit::options(),
            // Ohne `$habit` in der Liste: An sich selbst lässt sich nichts
            // anhängen, und das eigene Fenster ist kein Konflikt.
            'chainCandidates' => $this->chainCandidates($request->user(), $habit),
            'busySlots' => $this->busySlots($request->user(), $habit),
        ]);
    }

    /**
     * Die Änderungen übernehmen — ohne den Verlauf anzutasten.
     *
     * `position`, `committed_at` und die abgehakten Tage bleiben, wo sie sind:
     * Sie gehören zum Ablauf, nicht zum Formular. Genau darin liegt der Sinn
     * des Bearbeitens — bisher blieb nur Beenden und Neuanlegen, und das kostete
     * jedes Mal die Serie.
     */
    public function update(UpdateHabitRequest $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('update', $habit);

        $habit->update($request->habitAttributes());

        // Wer von fester Uhrzeit auf eine Situation wechselt, nimmt der
        // Gewohnheit den Zeitpunkt, an dem eine Erinnerung hängen könnte. Bliebe
        // das Flag stehen, zeigte die Liste einen Schalter, der an aussieht und
        // nichts auslöst.
        if (! $habit->canRemind() && $habit->reminder_enabled) {
            $habit->update(['reminder_enabled' => false]);
        }

        Inertia::flash('habitUpdated', [
            'id' => $habit->id,
            'title' => $habit->title,
            'anchor' => $habit->scheduleLabel(),
        ]);

        return to_route('habits.index');
    }

    /**
     * Der nächste Termin als Satzteil: „ab heute", „heute um 17:00",
     * „am Montag um 17:00".
     */
    private function nextOccurrenceLabel(Habit $habit, ?Carbon $next): string
    {
        if (! $habit->schedule_type->isPlanned()) {
            return 'sobald es sich ergibt';
        }

        if (! $habit->schedule_type->hasClockTime()) {
            return 'ab heute';
        }

        if ($next === null) {
            return 'an keinem gewählten Tag';
        }

        $time = $habit->scheduled_time?->format('H:i') ?? '';

        $day = match (true) {
            $next->isToday() => 'heute',
            $next->isTomorrow() => 'morgen',
            // Die App-Locale ist nicht deutsch, die Oberfläche schon.
            default => 'am '.$next->copy()->locale('de')->isoFormat('dddd'),
        };

        return trim($day.' um '.$time);
    }

    /**
     * Löscht eine Gewohnheit samt aller abgehakten Tage.
     *
     * Der eine Weg, auf dem in dieser App Verlauf verloren geht. Er bleibt
     * offen, weil eigene Daten wieder verschwinden können müssen — die
     * Oberfläche bietet ihn aber nur im Archiv an, nach dem Beenden und hinter
     * einer Rückfrage, die die Zahl der betroffenen Tage nennt.
     */
    public function destroy(Habit $habit, ReleaseChainedHabits $release): RedirectResponse
    {
        Gate::authorize('delete', $habit);

        // Was hinter dieser Gewohnheit hing, erbt ihren Anker, bevor sie geht.
        // Ohne diesen Schritt setzte `nullOnDelete` nur die Spalte zurück, und
        // die Nachfolger stünden als „noch ohne Anschluss" da.
        $release->handle($habit);

        $habit->delete();

        return back();
    }
}
