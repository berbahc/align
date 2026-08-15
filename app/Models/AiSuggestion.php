<?php

namespace App\Models;

use App\Enums\SuggestionKind;
use Database\Factories\AiSuggestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ein Vorschlag, den die KI gemacht hat — und was daraus wurde.
 *
 * Eine Zeile je Vorschlag, nicht je Anfrage: Wer drei Zeitpunkte angeboten
 * bekommt, hat drei Entscheidungen vor sich, und höchstens eine davon fällt
 * positiv aus.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $habit_id
 * @property SuggestionKind $kind
 * @property string $label
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $accepted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['habit_id', 'kind', 'label', 'payload', 'accepted_at'])]
class AiSuggestion extends Model
{
    /** @use HasFactory<AiSuggestionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Habit, $this>
     */
    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    /**
     * Angeboten, aber nicht übernommen — der Teil des Gedächtnisses, aus dem
     * die KI lernt, sich nicht zu wiederholen.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function notTaken(Builder $query): void
    {
        $query->whereNull('accepted_at');
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function taken(Builder $query): void
    {
        $query->whereNotNull('accepted_at');
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function ofKind(Builder $query, SuggestionKind $kind): void
    {
        $query->where('kind', $kind);
    }

    /**
     * Trägt ein, dass dieser Vorschlag übernommen wurde.
     *
     * Ohne Wirkung, wenn er schon eingetragen war: Ein doppelt abgeschickter
     * Request darf den Zeitpunkt nicht nach hinten schieben.
     */
    public function markAccepted(): void
    {
        if ($this->accepted_at !== null) {
            return;
        }

        $this->forceFill(['accepted_at' => now()])->save();
    }

    protected function casts(): array
    {
        return [
            'kind' => SuggestionKind::class,
            'payload' => 'array',
            'accepted_at' => 'datetime',
        ];
    }
}
