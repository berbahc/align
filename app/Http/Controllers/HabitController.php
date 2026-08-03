<?php

namespace App\Http\Controllers;

use App\Actions\CreateHabit;
use App\Enums\BehaviorType;
use App\Http\Requests\StoreHabitRequest;
use App\Models\Habit;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HabitController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('habits/create', [
            'directions' => BehaviorType::options(),
            'triggerSuggestions' => Habit::TriggerSuggestions,
        ]);
    }

    public function store(StoreHabitRequest $request, CreateHabit $createHabit): RedirectResponse
    {
        $createHabit->handle($request->user(), $request->habitAttributes());

        return to_route('dashboard');
    }
}
