<?php

namespace App\Http\Controllers;

use App\Actions\ReleaseChainedHabits;
use App\Models\Habit;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\SlotConflict;
use App\Support\Timetable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class HabitGraduationController extends Controller
{
    /**
     * Beendet eine Gewohnheit: sie verlässt die Tagesliste und gibt ihren Platz frei.
     *
     * Bewusst kein Löschen. Dieselbe Haltung, aus der die Serie ihren
     * Kulanztag bekommt: Ein einzelner Fehltag darf nicht alles zunichtemachen
     * — eine Gewohnheit aufzugeben und dabei jeden abgehakten Tag zu verlieren,
     * wäre dieselbe Bestrafung in größer. Die Erfüllungen bleiben,
     * `reminder_enabled` auch: beim Wiederaufnehmen steht die Gewohnheit exakt
     * so da wie vorher. Dass beendete Gewohnheiten nicht mehr erinnern,
     * erledigt der `active()`-Filter in HandleInertiaRequests von selbst.
     */
    public function store(Habit $habit, ReleaseChainedHabits $release): RedirectResponse
    {
        Gate::authorize('graduate', $habit);

        // Direkt gesetzt statt über `update()`: `graduated_at` steht bewusst
        // nicht in der Fillable-Liste — der Zustand gehört dem Ablauf, nicht
        // dem Formular.
        if ($habit->graduated_at === null) {
            // Erst die Nachfolger versorgen, dann beenden: Was hinter dieser
            // Gewohnheit hing, soll nicht mit ihr aus dem Tag verschwinden,
            // sondern ihren Platz übernehmen.
            $release->handle($habit);

            $habit->graduated_at = now();
            $habit->save();
        }

        return back();
    }

    /**
     * Nimmt eine beendete Gewohnheit wieder auf.
     *
     * Der Platz muss frei sein — sonst stünden über den Umweg des Archivs mehr
     * als die fünf Gewohnheiten in der Liste, die StoreHabitRequest beim
     * Anlegen verhindert.
     */
    public function destroy(Request $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('graduate', $habit);

        if ($request->user()->habits()->active()->count() >= Habit::MaxActivePerUser) {
            throw ValidationException::withMessages([
                'habit' => sprintf(
                    'Du hast bereits %d aktive Gewohnheiten. Beende zuerst eine andere.',
                    Habit::MaxActivePerUser,
                ),
            ]);
        }

        // Der Platz von damals kann inzwischen vergeben sein — ein Kurs ist
        // dazugekommen oder eine andere Gewohnheit ist dorthin gerückt. Sie
        // stillschweigend wieder aufzunehmen hieße, zwei Dinge auf eine Minute
        // zu legen; deshalb sagt die App, was zuerst zu tun ist.
        $this->guardOldSlot($request->user(), $habit);

        $habit->graduated_at = null;
        $habit->save();

        return back();
    }

    /**
     * Weist ab, was an seiner alten Stelle nicht mehr hinpasst.
     *
     * Nur für feste Uhrzeiten: Eine Situation und eine Kette haben keinen
     * Zeitpunkt, den man prüfen könnte — sie finden ihre Stelle ohnehin neu.
     */
    private function guardOldSlot(User $user, Habit $habit): void
    {
        if (! $habit->schedule_type->hasClockTime() || $habit->scheduled_time === null) {
            return;
        }

        // Eine geparkte Gewohnheit belegt nichts — es gibt keinen Platz, den
        // man ihr verwehren könnte. Sie kommt geparkt zurück. Geprüft wird der
        // Vermerk für heute: Einer, der erst zum Semesterbeginn gilt, hält den
        // alten Platz bis dahin noch besetzt.
        if ($habit->isDisplaced()) {
            return;
        }

        $conflict = SlotConflict::find(
            $user,
            $habit->spansFrom(DayPlan::toMinutes($habit->scheduled_time->format('H:i'))),
            $habit->activeWeekdays(),
            [$habit->id],
        );

        if ($conflict === null) {
            return;
        }

        throw ValidationException::withMessages([
            'habit' => SlotConflict::message(
                $conflict['block'],
                $conflict['date'],
                Timetable::isCourseBlock($conflict['block'])
                    ? 'Gib der Gewohnheit erst eine andere Zeit, dann lässt sie sich wieder aufnehmen — der Kurs rückt nicht.'
                    : 'Verschiebe eine der beiden, dann lässt sich diese wieder aufnehmen.',
            ),
        ]);
    }
}
