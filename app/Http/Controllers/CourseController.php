<?php

namespace App\Http\Controllers;

use App\Actions\DisplaceHabits;
use App\Actions\RestoreDisplacedHabits;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\Habit;
use App\Support\DayPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CourseController extends Controller
{
    /**
     * Einen Kurs in das laufende Semester eintragen.
     *
     * Ohne Semester gibt es nichts, worin er stünde — die Oberfläche führt
     * deshalb erst durch das Anlegen des Zeitraums.
     */
    public function store(
        StoreCourseRequest $request,
        DisplaceHabits $displace,
    ): RedirectResponse {
        $semester = $request->semester();

        abort_if($semester === null, 404);

        // Erst räumen, dann eintragen: Stünde der Kurs schon, fände die Suche
        // ihn selbst als das, was im Weg liegt.
        $displaced = $displace->handle($request->user(), [$this->span($request)], [$request->integer('weekday')]);

        $semester->courses()->create($request->validated());

        $this->announce($request->string('title')->toString(), $displaced);

        return back()->with('success', 'Der Kurs steht in deinem Plan.');
    }

    /**
     * Ein Kurs zieht um — gibt seine alte Stelle frei und belegt eine neue.
     *
     * Erst räumen, dann umziehen, dann zurückholen. Geräumt wird ohne den
     * Stundenplan als Gegner: Der Kurs steht noch an seiner alten Stelle, und
     * überschnitte die neue sich mit ihr, fände die Suche ihn selbst statt der
     * Gewohnheit darunter. Zwei Kurse übereinander hat der Request ohnehin
     * schon abgewiesen. Zurückgeholt wird erst, wenn er weg ist — sonst läge
     * die alte Stelle noch unter ihm.
     */
    public function update(
        UpdateCourseRequest $request,
        Course $course,
        DisplaceHabits $displace,
        RestoreDisplacedHabits $restore,
    ): RedirectResponse {
        Gate::authorize('update', $course);

        $displaced = $displace->handle(
            $request->user(),
            [$this->span($request)],
            [$request->integer('weekday')],
            withTimetable: false,
        );

        $course->update($request->validated());

        $restore->handle($request->user());

        $this->announce($course->title, $displaced);

        return back()->with('success', 'Der Kurs ist geändert.');
    }

    /**
     * Löschen nimmt über `cascadeOnDelete` die Ausnahmen des Kurses mit — und
     * gibt zurück, was er verdrängt hatte, sofern dort inzwischen nichts
     * anderes liegt.
     */
    public function destroy(Request $request, Course $course, RestoreDisplacedHabits $restore): RedirectResponse
    {
        Gate::authorize('delete', $course);

        $course->delete();

        $restored = $restore->handle($request->user());

        if ($restored !== []) {
            Inertia::flash('habitsRestored', [
                'titles' => array_map(fn (Habit $habit): string => $habit->title, $restored),
            ]);
        }

        return back()->with('success', 'Der Kurs ist aus deinem Plan raus.');
    }

    /**
     * Was der Kurs im Tag belegt — in der Form, die die Suche erwartet.
     *
     * @return array{id: int, title: string, from: int, to: int}
     */
    private function span(StoreCourseRequest $request): array
    {
        return [
            'id' => 0,
            'title' => $request->string('title')->toString(),
            'from' => DayPlan::toMinutes($request->string('starts_at')->toString()),
            'to' => DayPlan::toMinutes($request->string('ends_at')->toString()),
        ];
    }

    /**
     * Nichts verdrängen, ohne es zu sagen.
     *
     * Die Rückmeldung nennt, was seinen Platz verloren hat — als Zeile auf
     * jeder Seite, nicht nur als Band auf einer, die man vielleicht gerade
     * verlässt.
     *
     * @param  list<Habit>  $displaced
     */
    private function announce(string $course, array $displaced): void
    {
        if ($displaced === []) {
            return;
        }

        // Ab wann der Platz weg ist. Bei einem Kurs, dessen Semester erst
        // beginnt, liegt das Wochen in der Zukunft — die Meldung sagte trotzdem
        // „braucht **jetzt** einen neuen Platz" und verwies auf eine Liste, in
        // der bis dahin nichts steht. Wer im September seinen Stundenplan
        // einträgt, suchte dort vergeblich.
        $from = collect($displaced)
            ->map(fn (Habit $habit): ?Carbon => $habit->displaced_at)
            ->filter()
            ->min();

        Inertia::flash('coursePlaced', [
            'title' => $course,
            'displacedFrom' => $from instanceof Carbon && $from->greaterThan(Carbon::today())
                ? $from->isoFormat('D. MMMM')
                : null,
            'displaced' => array_map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'title' => $habit->title,
                'previousTime' => $habit->scheduled_time?->format('H:i') ?? '',
            ], $displaced),
        ]);
    }
}
