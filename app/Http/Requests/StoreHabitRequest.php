<?php

namespace App\Http\Requests;

use App\Models\Habit;
use Illuminate\Validation\Validator;

/**
 * Eine neue Gewohnheit anlegen.
 *
 * Die Felder selbst stehen in {@see HabitFormRequest} — hier kommt nur dazu,
 * was ausschließlich beim Anlegen gilt: die Grenze von fünf aktiven
 * Gewohnheiten. Beim Bearbeiten darf sie nicht greifen, sonst blockierte sich
 * die fünfte Gewohnheit selbst.
 */
class StoreHabitRequest extends HabitFormRequest
{
    /**
     * progress-tracking.md begrenzt auf 5 gleichzeitig aktive Gewohnheiten.
     * Die Grenze gehört in die Validierung, nicht nur ins Frontend — sonst
     * lässt sie sich mit einem direkten Request umgehen.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                $active = $this->user()->habits()->active()->count();

                if ($active >= Habit::MaxActivePerUser) {
                    $validator->errors()->add('title', sprintf(
                        'Du verfolgst bereits %d Gewohnheiten. Markiere zuerst eine als gefestigt, um Platz zu schaffen.',
                        Habit::MaxActivePerUser,
                    ));
                }
            },
        ];
    }
}
