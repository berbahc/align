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

class OnboardingController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('onboarding', [
            'directions' => BehaviorType::options(),
            'triggerSuggestions' => Habit::TriggerSuggestions,
            'scheduleTypes' => ScheduleType::options(),
        ]);
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
