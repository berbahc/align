<?php

namespace App\Http\Controllers;

use App\Actions\CreateHabit;
use App\Enums\BehaviorType;
use App\Enums\ScheduleType;
use App\Http\Requests\StoreHabitRequest;
use App\Models\Habit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return Inertia::render('habits/index', [
            'habits' => $habits->map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'title' => $habit->title,
                'behaviorType' => $habit->behavior_type->value,
                'scheduleLabel' => $habit->scheduleLabel(),
                'canRemind' => $habit->canRemind(),
                'reminderEnabled' => $habit->reminder_enabled,
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

    public function store(StoreHabitRequest $request, CreateHabit $createHabit): RedirectResponse
    {
        $createHabit->handle($request->user(), $request->habitAttributes());

        return to_route('dashboard');
    }
}
