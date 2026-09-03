<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseExceptionRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
    public function store(StoreCourseExceptionRequest $request, Course $course): RedirectResponse
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
