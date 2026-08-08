<?php

namespace App\Http\Controllers;

use App\Actions\CreateHabit;
use App\Enums\BehaviorType;
use App\Enums\ScheduleType;
use App\Http\Requests\StoreHabitRequest;
use App\Models\Habit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HabitController extends Controller
{
    /**
     * Die Verwaltungsansicht: alle aktiven Gewohnheiten mit ihrer Planung.
     *
     * Anders als die Übersicht zeigt sie auch, was heute nicht ansteht — hier
     * geht es um die Gewohnheit an sich, nicht um den heutigen Tag.
     */
    public function index(Request $request): Response
    {
        $habits = $request->user()
            ->habits()
            ->active()
            ->orderBy('position')
            ->get();

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
                'scheduleLabel' => $habit->scheduleLabel(),
                'canRemind' => $habit->canRemind(),
                'reminderEnabled' => $habit->reminder_enabled,
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

    public function create(): Response
    {
        return Inertia::render('habits/create', [
            'directions' => BehaviorType::options(),
            'triggerSuggestions' => Habit::TriggerSuggestions,
            'scheduleTypes' => ScheduleType::options(),
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
            'title' => $habit->title,
            'when' => $this->nextOccurrenceLabel($habit, $next),
            'scheduledToday' => $next?->isToday() ?? false,
        ]);

        return to_route('dashboard');
    }

    /**
     * Der nächste Termin als Satzteil: „ab heute", „heute um 17:00",
     * „am Montag um 17:00".
     */
    private function nextOccurrenceLabel(Habit $habit, ?Carbon $next): string
    {
        if ($habit->schedule_type !== ScheduleType::Fixed) {
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
    public function destroy(Habit $habit): RedirectResponse
    {
        Gate::authorize('delete', $habit);

        $habit->delete();

        return back();
    }
}
