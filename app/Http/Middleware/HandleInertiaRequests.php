<?php

namespace App\Http\Middleware;

use App\Enums\ScheduleType;
use App\Models\Habit;
use App\Models\SleepSchedule;
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

        // Über das Datum und nicht über den Wochentag: Wer heute später
        // aufgestanden ist, soll den Wecker und den Schlafenszeit-Hinweis auf
        // seinen echten Tag bezogen bekommen, nicht auf den geplanten.
        $today = Carbon::today();

        return [
            'yesterday' => $user->sleepWindowOn($today->copy()->subDay()),
            'today' => $user->sleepWindowOn($today),
            'tomorrow' => $user->sleepWindowOn($today->copy()->addDay()),
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
            // Diese Abfrage liest die Uhrzeit roh, nicht über `startsAt()` —
            // eine geparkte Gewohnheit klingelte sonst weiter zur alten Zeit,
            // obwohl dort jetzt eine Vorlesung läuft.
            ->placed()
            // Was heute ausnahmsweise woanders liegt: Eine Erinnerung zur
            // alten Uhrzeit wäre ein Wecker für einen Block, der dort nicht
            // mehr steht — und käme ausgerechnet an dem Tag, an dem jemand
            // für eine Verabredung Platz gemacht hat.
            ->with(['dayShifts' => fn (Relation $query) => $query->whereDate('shifted_on', $today)])
            ->withExists(['completions as completed_today' => fn (Builder $query) => $query
                ->whereDate('completed_on', $today),
            ])
            ->get()
            ->map(fn (Habit $habit): array => [
                'id' => $habit->id,
                'title' => $habit->title,
                'scheduledTime' => $habit->shiftedTimeOn($today)
                    ?? $habit->scheduled_time?->format('H:i')
                    ?? '',
                'scheduledDays' => $habit->scheduled_days ?? [],
                'completedToday' => (bool) $habit->completed_today,
            ])
            ->all();
    }
}
