<?php

namespace App\Http\Controllers;

use App\Actions\DisplaceHabits;
use App\Http\Requests\StoreCourseExceptionRequest;
use App\Models\Course;
use App\Models\Habit;
use App\Support\DayPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CourseExceptionController extends Controller
{
    /**
     * Ein Kurs fällt an einem Tag aus oder liegt dort woanders.
     *
     * `updateOrCreate` auf `[course_id, on_date]`: Zwei Ausnahmen für denselben
     * Tag wären zwei Antworten auf eine Frage. Wer erst absagt und dann einen
     * Ersatztermin einträgt, ersetzt damit die Absage — genau wie der
     * eindeutige Schlüssel es verlangt.
     */
    public function store(StoreCourseExceptionRequest $request, Course $course, DisplaceHabits $displace): RedirectResponse
    {
        Gate::authorize('update', $course);

        // Das Datum als Carbon und nicht als Zeichenkette: Der `date`-Cast legt
        // „2026-09-07 00:00:00" ab, und ein Vergleich gegen „2026-09-07" fände
        // die Zeile nicht — `updateOrCreate` legte dann eine zweite an und
        // liefe in den eindeutigen Schlüssel.
        $course->exceptions()->updateOrCreate(
            ['on_date' => $request->onDate()],
            [
                'starts_at' => $request->isCancellation() ? null : $request->string('starts_at')->toString(),
                'ends_at' => $request->isCancellation() ? null : $request->string('ends_at')->toString(),
            ],
        );

        // Ein Ersatztermin belegt Zeit wie jeder Kurs — nur an diesem einen
        // Tag. Was dort liegt, weicht, sonst zeichnete der Tag zwei Sachen
        // übereinander. Ohne den Stundenplan als Gegner: Der verlegte Kurs
        // liegt inzwischen selbst darin und kollidierte mit sich.
        $displaced = $request->isCancellation() ? [] : $displace->handleOn(
            $request->user(),
            [[
                'id' => -$course->id,
                'title' => $course->title,
                'from' => DayPlan::toMinutes($request->string('starts_at')->toString()),
                'to' => DayPlan::toMinutes($request->string('ends_at')->toString()),
            ]],
            $request->onDate(),
            withTimetable: false,
        );

        if ($displaced !== []) {
            Inertia::flash('coursePlaced', [
                'title' => $course->title,
                'displaced' => array_map(fn (Habit $habit): array => [
                    'id' => $habit->id,
                    'title' => $habit->title,
                    'previousTime' => $habit->scheduled_time?->format('H:i') ?? '',
                ], $displaced),
            ]);
        }

        return back()->with('success', $request->isCancellation()
            ? 'Der Termin ist als Ausfall vermerkt.'
            : 'Der Ersatztermin steht.');
    }

    /**
     * Die Ausnahme zurücknehmen — der Kurs läuft wieder wie jede Woche.
     */
    public function destroy(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $validated = $request->validate([
            'on_date' => ['required', 'date_format:Y-m-d'],
        ]);

        // `whereDate` und nicht `where`, aus demselben Grund wie beim Setzen:
        // In der Spalte steht ein Zeitstempel, gemeint ist ein Tag.
        $course->exceptions()->whereDate('on_date', $validated['on_date'])->delete();

        return back()->with('success', 'Der Termin läuft wieder wie geplant.');
    }
}
