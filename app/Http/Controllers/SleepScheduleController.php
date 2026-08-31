<?php

namespace App\Http\Controllers;

use App\Models\SleepSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    /**
     * Alle sieben Tage in einem Zug speichern.
     *
     * Ein Speichern pro Tag würde sieben Bestätigungen bedeuten — der Plan
     * ist aber eine Woche, keine sieben Entscheidungen. Upsert statt
     * Insert, weil jeder Wochentag höchstens eine Zeile hat.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'array', 'size:7'],
            'days.*.weekday' => ['required', 'integer', 'between:1,7', 'distinct'],
            'days.*.wake_time' => ['required', 'date_format:H:i'],
            'days.*.bedtime' => ['required', 'date_format:H:i', 'different:days.*.wake_time'],
            'days.*.alarm_enabled' => ['required', 'boolean'],
            'bedtime_reminder_enabled' => ['required', 'boolean'],
        ], [
            'days.*.bedtime.different' => 'Aufsteh- und Schlafenszeit können nicht dieselbe sein.',
        ]);

        foreach ($validated['days'] as $day) {
            $request->user()->sleepSchedules()->updateOrCreate(
                ['weekday' => $day['weekday']],
                [
                    'wake_time' => $day['wake_time'],
                    'bedtime' => $day['bedtime'],
                    'alarm_enabled' => $day['alarm_enabled'],
                ],
            );
        }

        // `forceFill`, weil das Feld bewusst nicht massen-zuweisbar ist — es
        // gehört zu keinem Registrierungs- oder Profilformular.
        $request->user()->forceFill([
            'bedtime_reminder_enabled' => $validated['bedtime_reminder_enabled'],
        ])->save();

        // Die Inertia-Middleware hat die Beziehung vor dem Controller schon
        // geladen (für die geteilten Schlaf-Props) — ohne dieses Zurücksetzen
        // läse jeder spätere Zugriff im selben Request den alten Stand.
        $request->user()->unsetRelation('sleepSchedules');

        return back()->with('success', 'Dein Schlafplan ist gespeichert.');
    }
}
