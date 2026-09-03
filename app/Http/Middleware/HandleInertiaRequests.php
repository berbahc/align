<?php

namespace App\Http\Middleware;

use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\SleepSchedule;
use App\Support\DayPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'habitReminders' => $this->habitReminders($request),
            'sleep' => $this->sleep($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Der Schlafrahmen der umliegenden Tage — für Hinweis und Wecker.
     *
     * Geteilt statt seitengebunden, aus demselben Grund wie die
     * Gewohnheits-Erinnerungen: Die Schlafenszeit hängt an der Uhr, nicht an
     * der Seite. Drei Tage, nicht einer: Eine Schlafenszeit nach Mitternacht
     * gehört zum gestrigen Wochentag, und der Wecker von morgen kann
     * klingeln, während der Tab noch offen ist.
     *
     * @return array{yesterday: array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}, today: array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}, tomorrow: array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}, reminderEnabled: bool, leadMinutes: int}|null
     */
    private function sleep(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $windows = $user->sleepWindows();
        $today = Carbon::today()->dayOfWeekIso;

        $weekday = fn (int $offset): int => (($today - 1 + $offset + 7) % 7) + 1;

        return [
            'yesterday' => $windows[$weekday(-1)],
            'today' => $windows[$weekday(0)],
            'tomorrow' => $windows[$weekday(1)],
            'reminderEnabled' => $user->bedtime_reminder_enabled,
            'leadMinutes' => SleepSchedule::BedtimeReminderLeadMinutes,
        ];
    }

    /**
     * Die Gewohnheiten, die heute noch eine Erinnerung auslösen können.
     *
     * Geteilt statt seitengebunden, damit die Erinnerung überall greift — sie
     * hängt an der Uhrzeit, nicht daran, wo man gerade ist. Höchstens fünf
     * aktive Gewohnheiten pro Nutzer, die Abfrage bleibt also klein.
     *
     * @return list<array{id: int, title: string, scheduledTime: string, scheduledDays: list<int>, completedToday: bool}>
     */
    private function habitReminders(Request $request): array
    {
        $user = $request->user();

        if ($user === null) {
            return [];
        }

        $today = Carbon::today();

        return $user->habits()
            ->active()
            ->where('reminder_enabled', true)
            ->where('schedule_type', ScheduleType::Fixed->value)
            ->whereNotNull('scheduled_time')
            ->withExists(['completions as completed_today' => fn (Builder $query) => $query
                ->whereDate('completed_on', $today),
            ])
            // Der Wecker muss wissen, ob die Gewohnheit heute woanders liegt.
            // Ohne diese Zeile meldete er sich um 17:00, während der Kalender
            // 14:00 zeigt — genau der Widerspruch, gegen den die App gebaut ist.
            ->with(['dayShifts' => fn (Relation $query) => $query->whereDate('shifted_on', $today)])
            ->get()
            ->map(function (Habit $habit) use ($today): array {
                $minute = $habit->placementOn($today)['minute'];

                return [
                    'id' => $habit->id,
                    'title' => $habit->title,
                    'scheduledTime' => $minute === null ? '' : DayPlan::toTime($minute),
                    'scheduledDays' => $habit->scheduled_days ?? [],
                    'completedToday' => (bool) $habit->completed_today,
                ];
            })
            ->all();
    }
}
