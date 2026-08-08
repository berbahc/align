<?php

namespace App\Http\Middleware;

use App\Enums\ScheduleType;
use App\Models\Habit;
use Illuminate\Database\Eloquent\Builder;
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
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
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
            ->get()
            ->map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'title' => $habit->title,
                'scheduledTime' => $habit->scheduled_time?->format('H:i') ?? '',
                'scheduledDays' => $habit->scheduled_days ?? [],
                'completedToday' => (bool) $habit->completed_today,
            ])
            ->all();
    }
}
