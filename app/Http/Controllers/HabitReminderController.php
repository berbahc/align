<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleType;
use App\Models\Habit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class HabitReminderController extends Controller
{
    /**
     * Schaltet die Erinnerung einer einzelnen Gewohnheit um.
     */
    public function update(Request $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('update', $habit);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        if ($validated['enabled'] && ! $habit->canRemind()) {
            throw ValidationException::withMessages([
                'enabled' => 'Erinnerungen gibt es nur für Gewohnheiten mit fester Uhrzeit.',
            ]);
        }

        $habit->update(['reminder_enabled' => $validated['enabled']]);

        return back();
    }

    /**
     * Setzt die Erinnerung für alle Gewohnheiten mit fester Uhrzeit auf einmal.
     *
     * Dynamische Gewohnheiten bleiben unberührt — ohne Zeitpunkt gibt es kein
     * „zehn Minuten vorher". Sie werden übergangen statt abgewiesen: der
     * Sammelschalter meint „alle, für die das gilt", nicht „ausnahmslos alle".
     */
    public function updateAll(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $request->user()
            ->habits()
            ->active()
            ->where('schedule_type', ScheduleType::Fixed->value)
            ->whereNotNull('scheduled_time')
            ->update(['reminder_enabled' => $validated['enabled']]);

        return back();
    }
}
