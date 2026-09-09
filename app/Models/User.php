<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\DayPlan;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $onboarded_at
 * @property Carbon|null $intro_seen_at
 * @property bool $appointments_enabled
 * @property bool $bedtime_reminder_enabled
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Verabredungen sind an, solange niemand sie abstellt.
     *
     * Der Wert steht auch in der Migration als Spalten-Default. Hier steht er
     * ein zweites Mal, damit ein frisch erzeugtes Modell ihn schon vor dem
     * ersten Lesen aus der Datenbank trägt — sonst wäre die Eigenschaft im
     * Speicher `null` und eine Prüfung darauf stillschweigend falsch.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'appointments_enabled' => true,
        'bedtime_reminder_enabled' => true,
    ];

    /**
     * @return HasMany<Habit, $this>
     */
    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class);
    }

    /**
     * Der Schlafrhythmus — höchstens eine Zeile je Wochentag.
     *
     * @return HasMany<SleepSchedule, $this>
     */
    public function sleepSchedules(): HasMany
    {
        return $this->hasMany(SleepSchedule::class);
    }

    /**
     * Die Tage, an denen der Rahmen ausnahmsweise ein anderer war.
     *
     * @return HasMany<SleepDayOverride, $this>
     */
    public function sleepDayOverrides(): HasMany
    {
        return $this->hasMany(SleepDayOverride::class);
    }

    /**
     * @return HasMany<Semester, $this>
     */
    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }

    /**
     * Das Semester, um das es gerade geht.
     *
     * Das laufende, wenn heute in einem liegt; sonst das zuletzt begonnene.
     * So bleibt der Plan in der vorlesungsfreien Zeit sichtbar und bearbeitbar,
     * ohne dass seine Kurse dann noch Zeit belegen — darüber entscheidet
     * {@see Semester::covers()}, nicht diese Auswahl.
     */
    public function currentSemester(): ?Semester
    {
        $today = Carbon::today()->toDateString();

        return $this->semesters()
            ->orderByRaw('(starts_on <= ? and ends_on >= ?) desc', [$today, $today])
            ->orderByDesc('starts_on')
            ->first();
    }

    /**
     * Der Rahmen aller sieben Wochentage, Lücken mit der Voreinstellung gefüllt.
     *
     * Der Rahmen existiert immer — auch wer nie etwas eingestellt hat, hat
     * einen Tag mit Anfang und Ende. Gespeichert ist nur die Abweichung;
     * hier wird beides zu einer vollständigen Woche zusammengelegt, damit
     * kein Aufrufer die Voreinstellung selbst kennen muss.
     *
     * @return array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>
     */
    public function sleepWindows(): array
    {
        $custom = $this->sleepSchedules->keyBy('weekday');

        $windows = [];

        foreach (range(1, 7) as $weekday) {
            $schedule = $custom->get($weekday);

            $windows[$weekday] = $schedule instanceof SleepSchedule
                ? [
                    'weekday' => $weekday,
                    'wakeTime' => $schedule->wake_time->format('H:i'),
                    'bedtime' => $schedule->bedtime->format('H:i'),
                    'alarmEnabled' => $schedule->alarm_enabled,
                ]
                : [
                    'weekday' => $weekday,
                    'wakeTime' => SleepSchedule::DefaultWakeTime,
                    'bedtime' => SleepSchedule::DefaultBedtime,
                    // Ein Wecker, den niemand gestellt hat, klingelt nicht.
                    'alarmEnabled' => false,
                ];
        }

        return $windows;
    }

    /**
     * Der Rahmen eines einzelnen Wochentags (ISO, 1 = Montag).
     *
     * @return array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}
     */
    public function sleepWindowFor(int $weekday): array
    {
        return $this->sleepWindows()[$weekday];
    }

    /**
     * Der Rahmen eines konkreten Tages — mit seiner Ausnahme, falls es eine gibt.
     *
     * Der Unterschied zu {@see sleepWindowFor()} ist der Unterschied zwischen
     * „montags" und „am 7. September": Der Wochenplan sagt, wie ein Montag
     * üblicherweise anfängt; die Ausnahme sagt, wie dieser eine angefangen
     * hat. Überall dort, wo ein Datum vorliegt, gilt diese Antwort — sonst
     * zeichnete der Kalender einen Rahmen, in dem der Nutzer noch schlief.
     *
     * Weil hier nur die **geladene** Beziehung gelesen wird, kostet die Frage
     * nichts, auch wenn sie je Gewohnheit gestellt wird
     * ({@see Habit::sleepBoundStartMinute()}).
     *
     * @return array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}
     */
    public function sleepWindowOn(Carbon $date): array
    {
        $window = $this->sleepWindowFor($date->dayOfWeekIso);

        $override = $this->sleepDayOverrides
            ->first(fn (SleepDayOverride $day): bool => $day->on_date->isSameDay($date));

        if (! $override instanceof SleepDayOverride) {
            return $window;
        }

        // Feld für Feld: Wer nur später aufsteht, behält seine Schlafenszeit
        // aus dem Wochenplan — und behält sie auch dann, wenn er den Plan
        // später ändert.
        return [
            ...$window,
            'wakeTime' => $override->wake_time?->format('H:i') ?? $window['wakeTime'],
            'bedtime' => $override->bedtime?->format('H:i') ?? $window['bedtime'],
        ];
    }

    /**
     * Die ganze Woche, aber mit der Ausnahme dieses einen Datums darin.
     *
     * Dieselbe wochentagsindizierte Form wie {@see sleepWindows()}, damit
     * {@see DayPlan::for()} unverändert damit rechnen kann. Nur
     * der Eintrag des betroffenen Wochentags ist ersetzt — die übrigen sechs
     * kommen in dieser Rechnung ohnehin nie vor.
     *
     * @return array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>
     */
    public function sleepWindowsOn(?Carbon $date): array
    {
        $windows = $this->sleepWindows();

        if ($date === null) {
            return $windows;
        }

        $windows[$date->dayOfWeekIso] = $this->sleepWindowOn($date);

        return $windows;
    }

    /**
     * Das Gedächtnis der KI zu dieser Person.
     *
     * Was sie vorgeschlagen hat und was davon übernommen wurde — die
     * Grundlage dafür, sich nicht zu wiederholen. Hängt am Konto und
     * verschwindet mit ihm (Cascade in der Migration).
     *
     * @return HasMany<AiSuggestion, $this>
     */
    public function aiSuggestions(): HasMany
    {
        return $this->hasMany(AiSuggestion::class);
    }

    /**
     * Der Handle wird immer klein gespeichert.
     *
     * Damit ist „Berkay" und „berkay" dieselbe Person — unabhängig davon, über
     * welchen Weg der Wert hereinkommt (Formular, Factory, Seeder). Ohne das
     * würde die Eindeutigkeitsprüfung an SQLites zeichengenauem Vergleich
     * vorbeilaufen und erst der Index in der Datenbank Alarm schlagen.
     *
     * @return Attribute<string, string>
     */
    protected function username(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::lower(trim($value)),
        );
    }

    /**
     * Die bestätigten Freundinnen und Freunde, alphabetisch.
     *
     * Bewusst keine Eloquent-Beziehung: Eine Freundschaft ist symmetrisch, die
     * Tabelle speichert sie aber gerichtet. Beide Richtungen zusammenzuführen
     * geht als Sammlung ehrlicher als über eine Beziehung, die immer nur eine
     * Hälfte sieht.
     *
     * @return Collection<int, User>
     */
    public function friends(): Collection
    {
        $accepted = Friendship::query()
            ->accepted()
            ->involving($this)
            ->with(['requester', 'addressee'])
            ->get();

        return $accepted
            ->map(fn (Friendship $friendship): User => $friendship->counterpart($this))
            ->sortBy('name')
            ->values();
    }

    /**
     * Besteht zwischen beiden schon eine Freundschaft oder eine offene Anfrage?
     */
    public function hasFriendshipWith(User $other): bool
    {
        return Friendship::query()
            ->involving($this)
            ->involving($other)
            ->exists();
    }

    /**
     * Die offene Anfrage, die `$other` an diese Person gerichtet hat.
     *
     * Zwei Menschen, die gleichzeitig aneinander denken, sollen nicht in einem
     * Zustand landen, in dem beide auf den jeweils anderen warten: Wer eine
     * offene Anfrage vorfindet, nimmt sie an, statt eine zweite zu stellen.
     */
    public function pendingRequestFrom(User $other): ?Friendship
    {
        return Friendship::query()
            ->pending()
            ->where('requester_id', $other->id)
            ->where('addressee_id', $this->id)
            ->first();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'intro_seen_at' => 'datetime',
            'appointments_enabled' => 'boolean',
            'bedtime_reminder_enabled' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
