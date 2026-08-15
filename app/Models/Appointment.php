<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ein gemeinsamer Termin für genau einen Tag.
 *
 * @property int $id
 * @property int $habit_id
 * @property int $requester_id
 * @property int $invitee_id
 * @property Carbon $scheduled_for
 * @property Carbon|null $accepted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['habit_id', 'requester_id', 'invitee_id', 'scheduled_for'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * Wie weit im Voraus man sich verabreden kann, heute eingeschlossen.
     *
     * Drei Tage, wie Screen A1 sie zeigt („heute · morgen · Do."). Weiter
     * vorauszuplanen wäre der Anfang einer Terminfindung — und die gehört
     * laut §9 ausdrücklich nicht in die App, sondern in WhatsApp.
     */
    public const int DayChoices = 3;

    /**
     * Die drei Tage, die Screen A1 zur Wahl stellt.
     *
     * Steht hier und nicht im Controller, weil zwei Wege sie anbieten: die
     * Übersicht und der letzte Schritt beim Anlegen.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function dayChoices(): array
    {
        return collect(range(0, self::DayChoices - 1))
            ->map(function (int $offset): array {
                $day = Carbon::today()->addDays($offset);

                return [
                    'value' => $day->toDateString(),
                    'label' => self::dayLabel($day),
                ];
            })
            ->all();
    }

    /**
     * „heute", „morgen", sonst der Wochentag.
     */
    public static function dayLabel(CarbonInterface $day): string
    {
        if ($day->isToday()) {
            return 'heute';
        }

        if ($day->isTomorrow()) {
            return 'morgen';
        }

        // Die App-Locale ist nicht deutsch, die Oberfläche schon.
        return $day->copy()->locale('de')->isoFormat('dddd');
    }

    /**
     * @return BelongsTo<Habit, $this>
     */
    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    /**
     * @param  Builder<Appointment>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('accepted_at');
    }

    /**
     * @param  Builder<Appointment>  $query
     */
    #[Scope]
    protected function accepted(Builder $query): void
    {
        $query->whereNotNull('accepted_at');
    }

    /**
     * Verabredungen an einem bestimmten Tag.
     *
     * Heißt `onDate` und nicht `on`: `Model::on()` ist bei Eloquent bereits
     * vergeben — es wechselt die Datenbankverbindung. Ein Scope dieses Namens
     * lässt sich nicht einmal laden, PHP verweigert die Klasse.
     *
     * @param  Builder<Appointment>  $query
     */
    #[Scope]
    protected function onDate(Builder $query, Carbon $date): void
    {
        $query->whereDate('scheduled_for', $date);
    }

    /**
     * Alle Verabredungen, an denen jemand beteiligt ist — auf welcher Seite auch immer.
     *
     * @param  Builder<Appointment>  $query
     */
    #[Scope]
    protected function involving(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user) {
            $query->where('requester_id', $user->id)
                ->orWhere('invitee_id', $user->id);
        });
    }

    /**
     * Offene Anfragen an `$user`, fertig für die Oberfläche.
     *
     * Steht hier und nicht im Controller, weil zwei Seiten sie brauchen: die
     * Übersicht (Screen A2) und der Community-Bereich.
     *
     * Vergangenes verfällt still: Eine Anfrage für gestern ist keine Frage
     * mehr, und ein Hinweis darauf wäre ein Vorwurf.
     *
     * @return list<array{id: int, name: string, initial: string, title: string, anchor: string, day: string, blueprint: array{title: string, behaviorType: string, scheduleType: string, triggerSituation: string|null, scheduledTime: string|null, scheduledDays: list<int>|null}}>
     */
    public static function pendingFor(User $user): array
    {
        return self::query()
            ->pending()
            ->where('invitee_id', $user->id)
            ->whereDate('scheduled_for', '>=', Carbon::today())
            ->with(['requester', 'habit'])
            ->get()
            ->map(fn (self $appointment): array => [
                'id' => $appointment->id,
                ...$appointment->companion($user),
                'title' => $appointment->habit->title,
                'anchor' => $appointment->habit->scheduleLabel(),
                'day' => self::dayLabel($appointment->scheduled_for),
                // Wer gefragt wird, sieht hier zum ersten Mal eine Gewohnheit,
                // die er selbst nicht führt. Manchmal ist die Antwort nicht ja
                // oder nein, sondern „das will ich auch" — dafür reist die
                // Vorlage mit.
                'blueprint' => $appointment->habit->blueprint(),
            ])
            ->all();
    }

    /**
     * Alles, woran `$user` beteiligt ist und was noch bevorsteht.
     *
     * Das Fenster ist dasselbe, das die Wahl anbietet: drei Tage, heute
     * eingeschlossen. Weiter zu blicken wäre ein gemeinsamer Kalender
     * (Top-2 46 %, §9).
     *
     * Gibt die Einträge unaufbereitet zurück, weil die beiden Seiten
     * unterschiedlich aussortieren: Die Übersicht lässt weg, was schon in der
     * Habit-Zeile steht, der Community-Bereich hat keine solche Zeile.
     *
     * @return Collection<int, Appointment>
     */
    public static function upcomingFor(User $user, Carbon $today): Collection
    {
        return self::query()
            ->involving($user)
            ->whereDate('scheduled_for', '>=', $today)
            ->whereDate('scheduled_for', '<=', $today->copy()->addDays(self::DayChoices - 1))
            ->with(['requester', 'invitee', 'habit'])
            ->orderBy('scheduled_for')
            ->get();
    }

    /**
     * Wartet diese Verabredung noch auf die Antwort von `$user`?
     *
     * Solche Einträge gehören auf beiden Seiten in die Karte mit „Passt mir"
     * und „Lieber nicht" — und nirgends ein zweites Mal daneben.
     */
    public function awaitsAnswerFrom(User $user): bool
    {
        return $this->accepted_at === null && $this->invitee_id === $user->id;
    }

    /**
     * Ein Eintrag der Liste „Zusammen".
     *
     * @return array{id: int, name: string, initial: string, title: string, anchor: string, day: string, accepted: bool, iAsked: bool}
     */
    public function present(User $user): array
    {
        return [
            'id' => $this->id,
            ...$this->companion($user),
            'title' => $this->habit->title,
            'anchor' => $this->habit->scheduleLabel(),
            'day' => self::dayLabel($this->scheduled_for),
            'accepted' => $this->accepted_at !== null,
            'iAsked' => $this->requester_id === $user->id,
        ];
    }

    /**
     * Die jeweils andere Person aus Sicht von `$user`.
     */
    public function counterpart(User $user): User
    {
        return $this->requester_id === $user->id
            ? $this->invitee
            : $this->requester;
    }

    /**
     * Die andere Person, aufbereitet für die Oberfläche.
     *
     * @return array{name: string, initial: string}
     */
    public function companion(User $user): array
    {
        $person = $this->counterpart($user);

        return [
            'name' => $person->name,
            'initial' => mb_strtoupper(mb_substr($person->name, 0, 1)),
        ];
    }

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'accepted_at' => 'datetime',
        ];
    }
}
