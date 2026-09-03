<?php

namespace App\Http\Controllers;

use App\Enums\CourseKind;
use App\Http\Requests\StoreSemesterRequest;
use App\Models\Course;
use App\Models\CourseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SemesterController extends Controller
{
    /**
     * Der Stundenplan einer Woche.
     *
     * Ein Semester je Person, sichtbar auch nach seinem Ende: Wer im März auf
     * die Seite kommt, soll seinen alten Plan als Vorlage vorfinden und nicht
     * ein leeres Formular. Ob die Kurse gerade Zeit belegen, entscheidet
     * allein der Zeitraum — nicht, ob sie hier stehen.
     */
    public function show(Request $request): Response
    {
        $semester = $request->user()->currentSemester();

        $courses = $semester === null
            ? collect()
            : $semester->courses()->with('exceptions')->orderBy('weekday')->orderBy('starts_at')->get();

        return Inertia::render('semester', [
            'semester' => $semester === null ? null : [
                'title' => $semester->title,
                'startsOn' => $semester->starts_on->toDateString(),
                'endsOn' => $semester->ends_on->toDateString(),
                'rangeLabel' => $semester->rangeLabel(),
                // Ein abgelaufenes Semester bleibt bearbeitbar, sagt aber, dass
                // es nichts mehr blockiert — sonst wundert sich jemand, warum
                // seine Vorlesungen im Kalender fehlen.
                'isCurrent' => $semester->covers(Carbon::today()),
            ],
            'courses' => $courses->map(fn (Course $course): array => $this->course($course))->all(),
            'kinds' => CourseKind::options(),
            'maxCourses' => Course::MaxPerSemester,
        ]);
    }

    public function store(StoreSemesterRequest $request): RedirectResponse
    {
        $request->user()->semesters()->create($request->validated());

        return back()->with('success', 'Dein Semester steht.');
    }

    /**
     * Titel oder Zeitraum ändern.
     *
     * Ohne Kennung in der Strecke, weil es je Person genau eins gibt, um das
     * es geht — dasselbe Muster wie beim Schlafplan.
     */
    public function update(StoreSemesterRequest $request): RedirectResponse
    {
        $semester = $request->user()->currentSemester();

        abort_if($semester === null, 404);

        $semester->update($request->validated());

        return back()->with('success', 'Dein Semester ist gespeichert.');
    }

    /**
     * Den Plan löschen — samt Kursen und deren Ausnahmen.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $semester = $request->user()->currentSemester();

        abort_if($semester === null, 404);

        $semester->delete();

        return back()->with('success', 'Dein Semesterplan ist gelöscht.');
    }

    /**
     * Ein Kurs als Zeile im Plan.
     *
     * @return array{id: int, title: string, kind: string, kindLabel: string, weekday: int, startsAt: string, endsAt: string, timeRange: string, location: string|null, exceptions: list<array{onDate: string, dateLabel: string, cancelled: bool, timeRange: string|null}>}
     */
    private function course(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'kind' => $course->kind->value,
            'kindLabel' => $course->kind->label(),
            'weekday' => $course->weekday,
            'startsAt' => $course->starts_at->format('H:i'),
            'endsAt' => $course->ends_at->format('H:i'),
            'timeRange' => $course->timeRangeLabel(),
            'location' => $course->location,
            'exceptions' => array_values($course->exceptions
                ->sortBy(fn (CourseException $exception): string => $exception->on_date->toDateString())
                ->map(fn (CourseException $exception): array => [
                    'onDate' => $exception->on_date->toDateString(),
                    'dateLabel' => $exception->dateLabel(),
                    'cancelled' => $exception->isCancellation(),
                    'timeRange' => $exception->isCancellation() ? null : sprintf(
                        '%s – %s',
                        $exception->starts_at?->format('H:i'),
                        $exception->ends_at?->format('H:i'),
                    ),
                ])
                ->all()),
        ];
    }
}
