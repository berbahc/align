<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShiftHabitDayRequest;
use App\Models\Habit;
use App\Models\HabitDayShift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class HabitDayShiftController extends Controller
{
    /**
     * Eine Gewohnheit für einen einzigen Tag woanders hinlegen.
     *
     * Der Anlass ist die Verabredung: Wer um 7:30 gefragt wird und um 7:30
     * selbst etwas vorhat, hatte bisher nur die Wahl zwischen Absagen und
     * Doppelbuchung. Der dritte Weg ist, den eigenen Tag einmal umzustellen —
     * einmal, nicht für immer. Die Gewohnheit selbst bleibt, wo sie ist.
     *
     * Ersetzt statt anzulegen: Zwei Uhrzeiten für denselben Tag wären zwei
     * Pläne, und die Tabelle lässt sie deshalb gar nicht erst zu.
     */
    public function store(ShiftHabitDayRequest $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('update', $habit);

        /** @var Carbon $date */
        $date = $request->shiftedOn();

        HabitDayShift::query()->updateOrCreate(
            ['habit_id' => $habit->id, 'shifted_on' => $date->toDateString()],
            ['scheduled_time' => $request->string('scheduled_time')->toString()],
        );

        return back()->with('success', sprintf(
            '„%s" liegt an diesem Tag um %s.',
            $habit->title,
            $request->string('scheduled_time')->toString(),
        ));
    }
}
