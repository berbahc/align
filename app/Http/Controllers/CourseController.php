<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    /**
     * Einen Kurs in das laufende Semester eintragen.
     *
     * Ohne Semester gibt es nichts, worin er stünde — die Oberfläche führt
     * deshalb erst durch das Anlegen des Zeitraums.
     */
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $semester = $request->semester();

        abort_if($semester === null, 404);

        $semester->courses()->create($request->validated());

        return back()->with('success', 'Der Kurs steht in deinem Plan.');
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $course->update($request->validated());

        return back()->with('success', 'Der Kurs ist geändert.');
    }

    /**
     * Löschen nimmt über `cascadeOnDelete` die Ausnahmen des Kurses mit.
     */
    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('delete', $course);

        $course->delete();

        return back()->with('success', 'Der Kurs ist aus deinem Plan raus.');
    }
}
