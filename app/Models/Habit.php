<?php

namespace App\Models;

use App\Enums\BehaviorType;
use Database\Factories\HabitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $trigger_situation
 * @property BehaviorType $behavior_type
 * @property int|null $focus_minutes
 * @property int $position
 * @property Carbon|null $committed_at
 * @property Carbon|null $graduated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'trigger_situation', 'behavior_type', 'focus_minutes', 'position', 'committed_at'])]
class Habit extends Model
{
    /** @use HasFactory<HabitFactory> */
    use HasFactory;

    /**
     * progress-tracking.md: höchstens 5 gleichzeitig aktive Gewohnheiten,
     * damit die Liste schmal und der Fokus erhalten bleibt.
     */
    public const int MaxActivePerUser = 5;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<HabitCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(HabitCompletion::class);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('graduated_at');
    }

    /**
     * Anteil der Tage mit Erfüllung im Rückblickfenster, in Prozent.
     *
     * progress-tracking.md: bewusst eine Konsistenzrate statt eines Streaks.
     * Ein Streak bricht bei einem einzigen Fehltag komplett zusammen, obwohl
     * einzelne Aussetzer laut Lally et al. keine messbaren Langzeitkosten haben.
     * Vor dem ersten Tag existiert kein Fenster — dann gibt es keine Rate.
     */
    public function consistencyRate(int $days = 30, ?Carbon $until = null): ?int
    {
        $until ??= Carbon::today();
        $start = $until->copy()->subDays($days - 1)->max($this->created_at->copy()->startOfDay());

        $window = $start->diffInDays($until) + 1;

        if ($window < 1) {
            return null;
        }

        // Carbon-Instanzen statt Datums-Strings: der `date`-Cast legt die Spalte
        // als "Y-m-d H:i:s" ab, ein String-Vergleich gegen "Y-m-d" würde den
        // letzten Tag lexikografisch aus dem Fenster schneiden.
        $completed = $this->completions()
            ->whereBetween('completed_on', [$start->copy()->startOfDay(), $until->copy()->endOfDay()])
            ->count();

        return (int) round($completed / $window * 100);
    }

    protected function casts(): array
    {
        return [
            'behavior_type' => BehaviorType::class,
            'committed_at' => 'datetime',
            'graduated_at' => 'datetime',
        ];
    }
}
