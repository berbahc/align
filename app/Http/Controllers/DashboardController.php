<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Friendship;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $today = Carbon::today();

        // Die Oberfläche ist durchgängig deutsch; die App-Locale ist es nicht,
        // deshalb wird sie hier gezielt für die Datumsausgabe gesetzt.
        $localisedToday = $today->copy();
        $localisedToday->locale('de');

        $habits = $request->user()
            ->habits()
            ->active()
            ->with(['completions' => fn (Relation $query) => $query->whereDate('completed_on', $today)])
            // Screen A3: die zugesagte Verabredung sitzt in der Habit-Zeile,
            // als Doppel-Zeichen an der Stelle der Icon-Kachel.
            ->with(['appointments' => fn (Relation $query) => $query->accepted()->onDate($today)->with('invitee')])
            ->withCount(['completions as completions_last_30_days' => fn (Builder $query) => $query
                ->where('completed_on', '>=', $today->copy()->subDays(29)->startOfDay()),
            ])
            ->orderBy('position')
            ->get();

        // Die Tagesliste zeigt nur, was heute ansteht. Eine Mo–Fr-Gewohnheit
        // ist am Samstag nicht offen, sondern nicht vorgesehen — sie dennoch
        // als unerledigt zu zeigen wäre eine Forderung, die niemand erhoben hat.
        $todaysHabits = $habits->filter(
            fn (Habit $habit): bool => $habit->isScheduledOn($today),
        )->values();

        return Inertia::render('dashboard', [
            'greeting' => $this->greeting($today),
            'today' => $localisedToday->isoFormat('dddd, D. MMMM'),
            // Mockup A2 setzt die offene Anfrage über die Gewohnheiten. Es ist
            // der einzige Weg, auf dem jemand von ihr erfährt — es gibt keine
            // Mail und kein Nachfassen (community_feature3.md §5).
            'friendRequests' => Friendship::pendingFor($request->user()),
            // Screen A2 für die Verabredung: dieselbe Stelle, anderer Inhalt.
            'appointmentRequests' => $this->appointmentRequests($request->user()),
            // Alles Verabredete, was noch bevorsteht — für beide Seiten.
            'upcomingAppointments' => $this->upcomingAppointments($request->user(), $today),
            'friends' => $request->user()->friends()->map(fn (User $friend): array => [
                'id' => $friend->id,
                'name' => $friend->name,
                'initial' => mb_strtoupper(mb_substr($friend->name, 0, 1)),
            ])->all(),
            'appointmentDays' => Appointment::dayChoices(),
            'appointmentsEnabled' => $request->user()->appointments_enabled,
            'todayProgress' => $this->todayProgress($todaysHabits),
            'consistency' => $this->consistencyRate($habits, $today),
            // Eine leere Tagesliste heißt nicht, dass es keine Gewohnheiten
            // gibt — eine Mo–Fr-Gewohnheit ist am Samstag schlicht nicht
            // vorgesehen. Ohne diese Zahl könnte die Oberfläche die beiden
            // Fälle nicht auseinanderhalten und würde am Wochenende zum
            // Anlegen auffordern, obwohl längst fünf Gewohnheiten laufen.
            'activeCount' => $habits->count(),
            'maxActive' => Habit::MaxActivePerUser,
            'habits' => $todaysHabits->map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'title' => $habit->title,
                'scheduleLabel' => $habit->scheduleLabel(),
                'behaviorType' => $habit->behavior_type->value,
                'focusMinutes' => $habit->focus_minutes,
                'smallestStep' => $habit->smallest_step,
                // Der Warum-Satz wird seit dem Anlegen gespeichert und stand
                // bislang nirgends. Er erscheint jetzt im Starthilfe-Sheet —
                // dort, wo er trägt, und nicht dauerhaft in der Liste, wo er
                // abstumpfen würde.
                'motivation' => $habit->motivation,
                'completedAt' => $habit->completions->first()?->completed_at->format('H:i'),
                // §6: Zwei Häkchen? Nein — eines. Der Fortschritt der anderen
                // Person steht hier bewusst nicht, sonst wäre die Verabredung
                // durch die Hintertür doch ein Dauerstatus.
                'companion' => $habit->appointments->first()?->companion($request->user()),
                'appointmentId' => $habit->appointments->first()?->id,
            ])->all(),
        ]);
    }

    /**
     * Offene Verabredungs-Anfragen an diese Person.
     *
     * @return list<array{id: int, name: string, initial: string, title: string, anchor: string, day: string}>
     */
    private function appointmentRequests(User $user): array
    {
        return Appointment::query()
            ->pending()
            ->where('invitee_id', $user->id)
            // Vergangenes verfällt still: Eine Anfrage für gestern ist keine
            // Frage mehr, und ein Hinweis darauf wäre ein Vorwurf.
            ->whereDate('scheduled_for', '>=', Carbon::today())
            ->with(['requester', 'habit'])
            ->get()
            ->map(fn (Appointment $appointment): array => [
                'id' => $appointment->id,
                ...$appointment->companion($user),
                'title' => $appointment->habit->title,
                'anchor' => $appointment->habit->scheduleLabel(),
                'day' => Appointment::dayLabel($appointment->scheduled_for),
            ])
            ->all();
    }

    /**
     * Was mit jemandem ansteht — zugesagt oder von mir gefragt.
     *
     * Eine Verabredung, die erst morgen gilt, war bis eben unsichtbar: Sie
     * erschien am Tag selbst und davor nirgends. Wer für morgen zusagte, sah
     * danach nichts mehr und musste annehmen, es sei schiefgegangen.
     *
     * Das ist **kein** gemeinsamer Kalender (Top-2 46 %, abgelehnt): Es steht
     * hier nur, was ohnehin schon vereinbart ist, höchstens drei Tage weit,
     * und nach dem Tag verschwindet es spurlos (§7).
     *
     * Zwei Fälle fehlen bewusst, weil sie anderswo schon stehen: offene
     * Anfragen an mich (die Karte mit den Knöpfen darüber) und was heute an
     * meiner eigenen Gewohnheit hängt (das Doppel-Zeichen in der Habit-Zeile).
     *
     * @return list<array{id: int, name: string, initial: string, title: string, anchor: string, day: string, accepted: bool, iAsked: bool}>
     */
    private function upcomingAppointments(User $user, Carbon $today): array
    {
        return Appointment::query()
            ->involving($user)
            ->whereDate('scheduled_for', '>=', $today)
            ->whereDate('scheduled_for', '<=', $today->copy()->addDays(Appointment::DayChoices - 1))
            ->with(['requester', 'invitee', 'habit'])
            ->orderBy('scheduled_for')
            ->get()
            ->reject(fn (Appointment $appointment): bool => (
                $appointment->accepted_at === null && $appointment->invitee_id === $user->id
            ) || (
                $appointment->accepted_at !== null
                && $appointment->requester_id === $user->id
                && $appointment->scheduled_for->isToday()
            ))
            ->map(fn (Appointment $appointment): array => [
                'id' => $appointment->id,
                ...$appointment->companion($user),
                'title' => $appointment->habit->title,
                'anchor' => $appointment->habit->scheduleLabel(),
                'day' => Appointment::dayLabel($appointment->scheduled_for),
                'accepted' => $appointment->accepted_at !== null,
                'iAsked' => $appointment->requester_id === $user->id,
            ])
            ->values()
            ->all();
    }

    private function greeting(Carbon $now): string
    {
        return match (true) {
            $now->hour < 11 => 'Guten Morgen',
            $now->hour < 18 => 'Schönen Tag',
            default => 'Guten Abend',
        };
    }

    /**
     * Fortschritt des heutigen Tages.
     *
     * Das Tagesziel ist schlicht die Anzahl der aktiven Gewohnheiten — es gibt
     * keine separate Zielgröße, die man verfehlen könnte. Formuliert wird
     * immer, was erledigt ist, nie was fehlt (Designsprache §1.5).
     *
     * @param  Collection<int, Habit>  $habits
     * @return array{completed: int, total: int, percentage: int}
     */
    private function todayProgress(Collection $habits): array
    {
        $total = $habits->count();
        $completed = $habits->filter(
            fn (Habit $habit): bool => $habit->completions->isNotEmpty(),
        )->count();

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total === 0 ? 0 : (int) round($completed / $total * 100),
        ];
    }

    /**
     * Gemeinsame Konsistenzrate über alle aktiven Gewohnheiten der letzten 30 Tage.
     *
     * progress-tracking.md schreibt bewusst eine Konsistenzrate statt eines
     * Streaks vor: ein Streak bricht bei einem einzigen Fehltag zusammen,
     * obwohl einzelne Aussetzer laut Lally et al. (2010) keine messbaren
     * Langzeitkosten haben. Ohne aktive Gewohnheiten gibt es keine Rate —
     * dann zeigt die Oberfläche den leeren Zustand statt „0 %".
     *
     * Die Zahl der möglichen Tage wird pro Gewohnheit ermittelt, nicht pauschal
     * mit 30 multipliziert: eine Mo–Fr-Gewohnheit hat in dreißig Tagen rund
     * zweiundzwanzig vorgesehene Tage. Über alle Tage zu rechnen würde sie
     * dauerhaft unter 72 % halten, obwohl sie lückenlos erfüllt wurde.
     *
     * @param  Collection<int, Habit>  $habits
     */
    private function consistencyRate(Collection $habits, Carbon $today): ?int
    {
        if ($habits->isEmpty()) {
            return null;
        }

        $start = $today->copy()->subDays(29);

        $possible = $habits->sum(
            fn (Habit $habit): int => $habit->scheduledDaysBetween($start, $today),
        );

        if ($possible < 1) {
            return null;
        }

        $completed = $habits->sum('completions_last_30_days');

        return (int) round($completed / $possible * 100);
    }
}
