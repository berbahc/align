<?php

namespace App\Http\Controllers;

use App\Actions\CreateHabit;
use App\Enums\HabitCategory;
use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use App\Http\Requests\StoreHabitRequest;
use App\Models\Habit;
use App\Models\SleepSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Zwei Stufen: erst der Rahmen, dann die erste Gewohnheit.
     *
     * Der Schlafplan kommt zuerst, weil er die Frage beantwortet, in der
     * alles Weitere stattfindet — eine Gewohnheit um 6:30 ist ein anderes
     * Versprechen, wenn der Tag um 7:00 beginnt. Im Onboarding reicht ein
     * Paar für alle Tage; je Wochentag verfeinern lässt es sich später
     * unter „Schlaf".
     */
    public function show(Request $request): Response
    {
        return Inertia::render('onboarding', [
            'hasSleepSchedule' => $request->user()->sleepSchedules()->exists(),
            'defaultWakeTime' => SleepSchedule::DefaultWakeTime,
            'defaultBedtime' => SleepSchedule::DefaultBedtime,
            'categories' => HabitCategory::options(Habit::takenTemplatesFor($request->user())),
            'triggerSuggestions' => Habit::situationChoicesFor($request->user()),
            'scheduleTypes' => ScheduleType::options(),
            'durationLimits' => MeasureUnit::minutesLimits(),
            'sleepWindows' => array_values($request->user()->sleepWindows()),
        ]);
    }

    /**
     * Der Rahmen aus dem Onboarding: ein Paar, sieben Tage.
     *
     * Wer hier 07:00/23:00 bestätigt, hat denselben Plan wie die
     * Voreinstellung — gespeichert wird er trotzdem: Ab jetzt ist es eine
     * Entscheidung, keine Annahme mehr.
     */
    public function storeSleep(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'wake_time' => ['required', 'date_format:H:i'],
            'bedtime' => ['required', 'date_format:H:i', 'different:wake_time'],
        ], [
            'bedtime.different' => 'Aufsteh- und Schlafenszeit können nicht dieselbe sein.',
        ]);

        foreach (range(1, 7) as $weekday) {
            $request->user()->sleepSchedules()->updateOrCreate(
                ['weekday' => $weekday],
                [
                    'wake_time' => $validated['wake_time'],
                    'bedtime' => $validated['bedtime'],
                ],
            );
        }

        // Die Inertia-Middleware hat die Beziehung vor dem Controller schon
        // geladen (für die geteilten Schlaf-Props) — ohne dieses Zurücksetzen
        // läse jeder spätere Zugriff im selben Request den alten Stand.
        $request->user()->unsetRelation('sleepSchedules');

        return to_route('onboarding.show');
    }

    public function store(StoreHabitRequest $request, CreateHabit $createHabit): RedirectResponse
    {
        $createHabit->handle($request->user(), $request->habitAttributes());

        $request->user()->forceFill(['onboarded_at' => now()])->save();

        return to_route('dashboard');
    }

    /**
     * Überspringen ist gleichwertig zum Abschließen.
     *
     * Die App fordert nichts ein — wer jetzt keine Gewohnheit anlegen möchte,
     * landet auf dem leeren Dashboard und wird nicht erneut hierher geleitet.
     */
    public function skip(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['onboarded_at' => now()])->save();

        return to_route('dashboard');
    }
}
