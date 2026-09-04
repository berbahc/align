<?php

namespace App\Http\Controllers;

use App\Actions\DisplaceHabits;
use App\Actions\RestoreDisplacedHabits;
use App\Http\Requests\StoreSemesterRequest;
use App\Models\Habit;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SemesterController extends Controller
{
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
    public function update(StoreSemesterRequest $request, DisplaceHabits $displace): RedirectResponse
    {
        $semester = $request->user()->currentSemester();

        abort_if($semester === null, 404);

        $semester->update($request->validated());

        // Ein verschobener Zeitraum lässt Kurse gelten, die vorher nicht galten
        // — und die können auf Gewohnheiten liegen, die es damals noch nicht
        // gab. Auch hier gewinnt der Kurs, und die Gewohnheit wird geparkt.
        $displaced = $this->displaceUnderCourses($request->user(), $semester, $displace);

        if ($displaced !== []) {
            Inertia::flash('coursePlaced', [
                'title' => $semester->title,
                'displaced' => array_map(fn (Habit $habit): array => [
                    'id' => $habit->id,
                    'title' => $habit->title,
                    'previousTime' => $habit->scheduled_time?->format('H:i') ?? '',
                ], $displaced),
            ]);
        }

        return back()->with('success', 'Dein Semester ist gespeichert.');
    }

    /**
     * Den Plan löschen — samt Kursen und deren Ausnahmen.
     */
    public function destroy(Request $request, RestoreDisplacedHabits $restore): RedirectResponse
    {
        $semester = $request->user()->currentSemester();

        abort_if($semester === null, 404);

        $semester->delete();

        // Mit dem Plan verschwinden seine Kurse — was sie verdrängt hatten,
        // darf zurück, wo es frei ist.
        $restore->handle($request->user());

        return back()->with('success', 'Dein Semesterplan ist gelöscht.');
    }

    /**
     * Räumt unter allen Kursen des Semesters, was dort noch liegt.
     *
     * Ohne den Stundenplan als Gegner: Er ist hier selbst der Prüfling, und
     * ein Kurs kollidierte sonst mit sich.
     *
     * @return list<Habit>
     */
    private function displaceUnderCourses(User $user, Semester $semester, DisplaceHabits $displace): array
    {
        $displaced = [];

        foreach ($semester->courses as $course) {
            $displaced = [
                ...$displaced,
                ...$displace->handle($user, [[
                    'id' => -$course->id,
                    'title' => $course->title,
                    'from' => $course->startMinute(),
                    'to' => $course->endMinute(),
                ]], [$course->weekday], withTimetable: false),
            ];
        }

        return $displaced;
    }
}
