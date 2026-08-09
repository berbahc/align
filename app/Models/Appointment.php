<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
