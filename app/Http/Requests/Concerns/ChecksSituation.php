<?php

namespace App\Http\Requests\Concerns;

use App\Models\Habit;
use Illuminate\Validation\Validator;

/**
 * Eine Situation trägt genau eine Gewohnheit.
 *
 * „Nach dem Aufstehen" zweimal zu vergeben hieße, zwei Dinge im selben Moment
 * zu tun. Der Kalender zeigte sie untereinander, als gäbe es eine Reihenfolge
 * — die es nicht gibt. Geteilt zwischen Formular und KI-Anpassung, weil beide
 * dieselbe Grenze einhalten müssen: Ein Vorschlag der KI darf so wenig
 * doppelt belegen wie eine Eingabe von Hand.
 *
 * Gegenstück zu {@see Habit::situationChoicesFor()}, aus dem die Oberfläche
 * die belegten Einträge sperrt.
 */
trait ChecksSituation
{
    /**
     * Weist eine Situation ab, die schon an einer anderen Gewohnheit hängt.
     *
     * Verglichen wird getrimmt und ohne Rücksicht auf Groß-/Kleinschreibung:
     * Die Regel gilt auch für den Freitext, sonst ließe sie sich über „Eigene
     * Situation" mit einem großen Anfangsbuchstaben umgehen.
     */
    protected function validateSituationIsFree(Validator $validator, ?Habit $except = null): void
    {
        $situation = $this->string('trigger_situation')->trim()->toString();

        if ($situation === '') {
            return;
        }

        $conflict = $this->user()->habits()
            ->active()
            ->when($except?->exists, fn ($query) => $query->whereKeyNot($except))
            ->get(['id', 'title', 'schedule_type', 'trigger_situation'])
            // Nur wer den Moment wirklich trägt, kann ihn blockieren. Eine
            // gekettete Gewohnheit hängt an ihrem Vorgänger; was in ihrer
            // Spalte steht, liest niemand — es darf also auch niemanden
            // aussperren.
            ->first(fn (Habit $habit): bool => $habit->schedule_type->hasOwnAnchor()
                && $habit->trigger_situation !== null
                && mb_strtolower(trim($habit->trigger_situation)) === mb_strtolower($situation));

        if ($conflict === null) {
            return;
        }

        // §1.5 — benennt, was gilt, ohne Vorwurf: Der Satz sagt, wem der
        // Moment gehört, nicht was die Person falsch gemacht hat.
        $validator->errors()->add('trigger_situation', sprintf(
            '„%s" hängt schon an diesem Moment. Zwei Gewohnheiten zur selben Zeit sind kein Plan — wähle einen anderen Auslöser.',
            $conflict->title,
        ));
    }
}
