<?php

namespace App\Models;

use Database\Factories\FriendshipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Eine Freundschaft zwischen zwei Personen — offen oder bestätigt.
 *
 * Die Richtung (wer gefragt hat) bleibt erhalten, damit die offene Anfrage
 * beide Seiten unterschiedlich erreicht. Sobald `accepted_at` gesetzt ist,
 * spielt sie keine Rolle mehr: Eine bestätigte Freundschaft ist symmetrisch.
 *
 * @property int $id
 * @property int $requester_id
 * @property int $addressee_id
 * @property Carbon|null $accepted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['requester_id', 'addressee_id'])]
class Friendship extends Model
{
    /** @use HasFactory<FriendshipFactory> */
    use HasFactory;

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
    public function addressee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }

    /**
     * Noch nicht beantwortete Anfragen.
     *
     * @param  Builder<Friendship>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('accepted_at');
    }

    /**
     * @param  Builder<Friendship>  $query
     */
    #[Scope]
    protected function accepted(Builder $query): void
    {
        $query->whereNotNull('accepted_at');
    }

    /**
     * Alle Freundschaften, an denen jemand beteiligt ist — egal auf welcher Seite.
     *
     * @param  Builder<Friendship>  $query
     */
    #[Scope]
    protected function involving(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user) {
            $query->where('requester_id', $user->id)
                ->orWhere('addressee_id', $user->id);
        });
    }

    /**
     * Die jeweils andere Person aus Sicht von `$user`.
     */
    public function counterpart(User $user): User
    {
        return $this->requester_id === $user->id
            ? $this->addressee
            : $this->requester;
    }

    /**
     * Offene Anfragen an `$user`, fertig für die Oberfläche.
     *
     * Steht hier und nicht im Controller, weil zwei Seiten sie brauchen: die
     * Übersicht (Mockup A2) und der Community-Bereich.
     *
     * @return list<array{id: int, name: string, initial: string}>
     */
    public static function pendingFor(User $user): array
    {
        return self::query()
            ->pending()
            ->where('addressee_id', $user->id)
            ->with('requester')
            ->get()
            ->map(fn (self $friendship): array => $friendship->present($friendship->requester))
            ->all();
    }

    /**
     * Eine Person für die Oberfläche.
     *
     * Die ausgelieferte `id` ist die Freundschaft, nie die Person — Annehmen,
     * Absagen und Entfernen sprechen alle denselben Eintrag an.
     *
     * @return array{id: int, name: string, initial: string}
     */
    public function present(User $person): array
    {
        return [
            'id' => $this->id,
            'name' => $person->name,
            'initial' => mb_strtoupper(mb_substr($person->name, 0, 1)),
        ];
    }

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }
}
