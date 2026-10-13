<?php

namespace App\Console\Commands;

use App\Enums\CourseKind;
use App\Enums\HabitTemplate;
use App\Enums\MeasureUnit;
use App\Enums\ScheduleType;
use App\Models\Appointment;
use App\Models\AppointmentNotice;
use App\Models\Friendship;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Bringt ein Konto in den Ausgangszustand der Live-Demo.
 *
 * Gezeigt wird kein leeres Konto, sondern eines mitten im Semester: vier
 * Gewohnheiten mit Verlauf, ein Stundenplan, ein Tagesrahmen. Von dort aus
 * lässt sich jede Funktion einmal vorführen, ohne erst alles anzulegen.
 *
 * Vier, nicht fünf: Die fünfte legt man live an, und danach greift die Grenze
 * sichtbar. Eine Kette gibt es bewusst noch nicht — sie entsteht vor dem
 * Publikum („Aufräumen" nach „Essen vorkochen"), damit man sie mitrücken sieht.
 *
 * Der Stundenplan hat freitags früh noch Luft. Dort kommt live ein Tutorium
 * auf die Zeit von „Joggen gehen" — erst dann parkt die App die Gewohnheit und
 * bietet einen neuen Platz an. Vorher eingetragen gäbe es nichts zu sehen.
 *
 * Der Verlauf hängt am heutigen Tag. Der Befehl gehört deshalb an den Morgen
 * der Vorführung und nach jede Probe, nicht einmal Tage vorher.
 *
 * Er räumt nur das genannte Konto (und das des Partners) ab. Alle anderen
 * Daten bleiben, wie sie sind.
 */
class PrepareDemo extends Command
{
    protected $signature = 'demo:vorbereiten
        {email=silas.hoefle@gmail.com : Das Konto, das vorgeführt wird}
        {--partner-name= : Name des Kontos, mit dem die Verabredung gezeigt wird}
        {--partner-email= : E-Mail dieses Kontos; fehlt sie, gibt es keinen Partner}
        {--force : Ohne Rückfrage ausführen}';

    protected $description = 'Setzt ein Konto auf den Ausgangszustand der Live-Demo zurück';

    /**
     * Das Passwort eines neu angelegten Partnerkontos. Es existiert nur lokal.
     */
    private const string PartnerPassword = 'align-demo';

    /**
     * Die vier Gewohnheiten, mit denen das Konto in die Demo geht.
     *
     * `rate` ist der Anteil erledigter Tage, `streak` die Zahl der letzten
     * fälligen Tage, die auf jeden Fall erledigt sind, `missed` Tage, die auf
     * jeden Fall offen bleiben (in Tagen vor heute).
     *
     * @var list<array{template: HabitTemplate, schedule: ScheduleType, trigger: string|null, time: string|null, days: list<int>|null, rate: float, streak: int, missed: list<int>, today: bool, done_at: string, motivation: string, step: string}>
     */
    private const array Habits = [
        [
            'template' => HabitTemplate::Joggen,
            'schedule' => ScheduleType::Fixed,
            'trigger' => null,
            'time' => '07:30',
            'days' => [1, 3, 5],
            'rate' => 0.8,
            'streak' => 6,
            'missed' => [],
            'today' => true,
            'done_at' => '08:05',
            'motivation' => 'Damit ich mit freiem Kopf in den Uni-Tag starte.',
            'step' => 'Laufschuhe abends neben die Tür stellen.',
        ],
        [
            'template' => HabitTemplate::VorlesungNachbereiten,
            'schedule' => ScheduleType::Dynamic,
            'trigger' => Habit::AfterLecture,
            'time' => null,
            'days' => null,
            'rate' => 0.7,
            'streak' => 0,
            'missed' => [],
            'today' => false,
            'done_at' => '16:00',
            'motivation' => 'Damit ich vor der Klausur nicht alles neu lernen muss.',
            'step' => 'Die Folien von heute öffnen.',
        ],
        [
            'template' => HabitTemplate::EssenVorkochen,
            'schedule' => ScheduleType::Fixed,
            'trigger' => null,
            'time' => '17:00',
            'days' => [1, 2, 3, 4, 5],
            'rate' => 0.55,
            'streak' => 0,
            'missed' => [],
            'today' => false,
            'done_at' => '17:45',
            'motivation' => 'Damit ich mittags nicht jeden Tag in die Mensa muss.',
            'step' => 'Ein Rezept für morgen aussuchen.',
        ],
        [
            'template' => HabitTemplate::Meditieren,
            'schedule' => ScheduleType::Dynamic,
            'trigger' => 'vor dem Schlafengehen',
            'time' => null,
            'days' => null,
            'rate' => 0.35,
            'streak' => 0,
            'missed' => [1],
            'today' => false,
            'done_at' => '22:45',
            'motivation' => 'Damit ich abends besser abschalten kann.',
            'step' => 'Handy auf lautlos stellen und hinsetzen.',
        ],
    ];

    /**
     * Ein Kurs pro Wochentag. Mittwochs liegt „Statistik" am frühen
     * Nachmittag, damit „Vorlesung nachbereiten" um 16 Uhr gerade ansteht.
     *
     * @var list<array{title: string, kind: CourseKind, weekday: int, from: string, to: string, location: string}>
     */
    private const array Courses = [
        ['title' => 'Analysis I', 'kind' => CourseKind::Vorlesung, 'weekday' => 1, 'from' => '10:00', 'to' => '11:30', 'location' => 'H2'],
        ['title' => 'Programmieren', 'kind' => CourseKind::Vorlesung, 'weekday' => 2, 'from' => '08:30', 'to' => '10:00', 'location' => 'H1'],
        ['title' => 'Statistik', 'kind' => CourseKind::Vorlesung, 'weekday' => 3, 'from' => '13:45', 'to' => '15:15', 'location' => 'H4'],
        ['title' => 'Englisch', 'kind' => CourseKind::Seminar, 'weekday' => 4, 'from' => '10:00', 'to' => '11:30', 'location' => 'S3'],
        ['title' => 'Analysis I', 'kind' => CourseKind::Uebung, 'weekday' => 5, 'from' => '12:00', 'to' => '13:30', 'location' => 'S12'],
    ];

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Der Demo-Zustand ist nur lokal gedacht.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error(sprintf('Kein Konto mit der E-Mail %s.', $this->argument('email')));

            return self::FAILURE;
        }

        $partner = $this->partner();

        if (! $this->option('force') && ! $this->confirm(sprintf(
            'Alle Gewohnheiten, Semester, Kontakte und Verabredungen von %s werden gelöscht und neu angelegt. Weiter?',
            $user->name,
        ), true)) {
            return self::FAILURE;
        }

        DB::transaction(function () use ($user, $partner): void {
            $this->clear($user);

            if ($partner !== null) {
                $this->clear($partner);
            }

            $user->forceFill([
                'onboarded_at' => $user->onboarded_at ?? now(),
                'intro_seen_at' => $user->intro_seen_at ?? now(),
            ])->save();

            $this->seedSleepSchedule($user);
            $this->seedSemesters($user);
            $this->seedHabits($user);

            if ($partner !== null) {
                Friendship::query()->create([
                    'requester_id' => $user->id,
                    'addressee_id' => $partner->id,
                ])->forceFill(['accepted_at' => now()])->save();
            }
        });

        $this->info(sprintf('%s ist bereit für die Demo.', $user->name));

        if ($partner === null) {
            $this->warn('Ohne Partner: Für die Community-Station --partner-name und --partner-email angeben.');
        } else {
            $this->info(sprintf('Partner: %s (%s), Passwort „%s“.', $partner->name, $partner->email, self::PartnerPassword));
        }

        return self::SUCCESS;
    }

    /**
     * Das Konto, das auf der anderen Seite der Verabredung antwortet.
     *
     * Gibt es die E-Mail schon, wird dieses Konto genommen und nur abgeräumt;
     * sonst entsteht ein neues, eingerichtetes Konto ohne eigene Gewohnheiten.
     */
    private function partner(): ?User
    {
        $email = $this->option('partner-email');

        if (! is_string($email) || $email === '') {
            return null;
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            return $existing;
        }

        $name = $this->option('partner-name');
        $name = is_string($name) && $name !== '' ? $name : strstr($email, '@', true);

        $partner = User::query()->create([
            'name' => $name,
            'username' => $this->freeUsername((string) $name),
            'email' => $email,
            'password' => Hash::make(self::PartnerPassword),
        ]);

        $partner->forceFill([
            'email_verified_at' => now(),
            'onboarded_at' => now(),
            'intro_seen_at' => now(),
        ])->save();

        return $partner;
    }

    private function freeUsername(string $name): string
    {
        $base = substr(preg_replace('/[^a-z0-9]/', '', strtolower($name)) ?: 'demo', 0, 24);
        $candidate = $base;

        for ($n = 2; User::query()->where('username', $candidate)->exists(); $n++) {
            $candidate = $base.$n;
        }

        return $candidate;
    }

    /**
     * Alles, was das Konto in der App angelegt hat. Das Konto selbst bleibt.
     *
     * Erledigungen, Verschiebungen und Verabredungen zu eigenen Gewohnheiten
     * gehen über die Fremdschlüssel mit.
     */
    private function clear(User $user): void
    {
        Appointment::query()
            ->where('requester_id', $user->id)
            ->orWhere('invitee_id', $user->id)
            ->delete();

        AppointmentNotice::query()->where('user_id', $user->id)->delete();

        Friendship::query()
            ->where('requester_id', $user->id)
            ->orWhere('addressee_id', $user->id)
            ->delete();

        $user->aiSuggestions()->delete();
        $user->habits()->delete();
        $user->semesters()->delete();
        $user->sleepSchedules()->delete();
        $user->sleepDayOverrides()->delete();
    }

    /**
     * Unter der Woche früh raus mit Wecker, am Wochenende später.
     */
    private function seedSleepSchedule(User $user): void
    {
        foreach (range(1, 7) as $weekday) {
            $weekend = $weekday >= 6;

            $user->sleepSchedules()->create([
                'weekday' => $weekday,
                'wake_time' => $weekend ? '09:00' : '07:00',
                'bedtime' => $weekend ? '23:30' : '23:00',
                'alarm_enabled' => ! $weekend,
            ]);
        }
    }

    /**
     * Das laufende Wintersemester mit Stundenplan.
     */
    private function seedSemesters(User $user): void
    {
        $winter = $user->semesters()->create([
            'title' => 'Wintersemester 2026/27',
            'starts_on' => '2026-10-01',
            'ends_on' => '2027-02-06',
        ]);

        foreach (self::Courses as $course) {
            $winter->courses()->create([
                'title' => $course['title'],
                'kind' => $course['kind'],
                'weekday' => $course['weekday'],
                'starts_at' => $course['from'],
                'ends_at' => $course['to'],
                'location' => $course['location'],
            ]);
        }
    }

    private function seedHabits(User $user): void
    {
        $today = Carbon::today();

        foreach (self::Habits as $position => $demo) {
            $template = $demo['template'];

            $habit = $user->habits()->create([
                'title' => $template->title(),
                'template_key' => $template->value,
                'behavior_type' => $template->behaviorType(),
                'schedule_type' => $demo['schedule'],
                'trigger_situation' => $demo['trigger'],
                'scheduled_time' => $demo['time'],
                'scheduled_days' => $demo['days'],
                'target_amount' => $template->defaultMinutes(),
                'target_unit' => MeasureUnit::Minutes,
                'motivation' => $demo['motivation'],
                'smallest_step' => $demo['step'],
                'position' => $position,
                'committed_at' => $today->copy()->subDays(45),
            ]);

            // Rückwirkend, damit die Konsistenzrate ein volles 30-Tage-Fenster hat.
            $habit->forceFill(['created_at' => $today->copy()->subDays(45)])->save();
            $habit->setRelation('user', $user);

            $this->seedCompletions($habit, $demo, $today);
        }
    }

    /**
     * Erledigungen an fälligen Tagen, gleichmäßig verteilt statt zufällig.
     *
     * Deterministisch, damit jede Probe dieselbe Oberfläche zeigt. Gezählt
     * wird nur, was wirklich anstand — eine Erledigung an einem Tag ohne
     * Vorlesung zu „Vorlesung nachbereiten" gäbe es in der App nicht.
     *
     * @param  array{rate: float, streak: int, missed: list<int>, today: bool, done_at: string}  $demo
     */
    private function seedCompletions(Habit $habit, array $demo, Carbon $today): void
    {
        $credit = 0.0;
        $dueSoFar = 0;

        for ($daysAgo = 0; $daysAgo < 30; $daysAgo++) {
            $date = $today->copy()->subDays($daysAgo);

            if (! $habit->isDueOn($date)) {
                continue;
            }

            if ($daysAgo === 0) {
                $done = $demo['today'];
            } elseif (in_array($daysAgo, $demo['missed'], strict: true)) {
                $done = false;
            } elseif ($dueSoFar <= $demo['streak']) {
                $done = true;
            } else {
                $credit += $demo['rate'];
                $done = $credit >= 1.0;
                $credit -= $done ? 1.0 : 0.0;
            }

            $dueSoFar++;

            if (! $done) {
                continue;
            }

            [$hour, $minute] = array_map('intval', explode(':', $demo['done_at']));

            $habit->completions()->create([
                'completed_on' => $date,
                'completed_at' => $date->copy()->setTime($hour, $minute),
            ]);
        }
    }
}
