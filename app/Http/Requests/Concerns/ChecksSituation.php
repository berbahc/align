<?php

namespace App\Http\Requests\Concerns;

use App\Models\Habit;
use Illuminate\Validation\Validator;

/**
 * Eine Situation trägt pro Tag genau eine Gewohnheit.
 *
 * „Nach dem Aufstehen" am selben Montag zweimal zu vergeben hieße, zwei Dinge
 * im selben Moment zu tun. Der Kalender zeigte sie untereinander, als gäbe es
 * eine Reihenfolge — die es nicht gibt.
 *
 * An **verschiedenen** Tagen ist es dagegen keine Doppelbelegung: „Lesen"
 * montags und „Dehnen" dienstags nach dem Aufstehen liegen nirgends
 * übereinander. Die Sperre über die ganze Woche wäre bei drei angebotenen
 * Situationen eine Grenze von drei situativen Gewohnheiten — eine, die aus der
 * Rechnung nicht folgt.
 *
 * Geteilt zwischen Formular und KI-Anpassung, weil beide dieselbe Grenze
 * einhalten müssen: Ein Vorschlag der KI darf so wenig doppelt belegen wie eine
 * Eingabe von Hand.
 *
 * Gegenstück zu {@see Habit::situationChoicesFor()}, aus dem die Oberfläche die
 * belegten Tage sperrt.
 */
trait ChecksSituation
{
    /**
     * Weist eine Situation ab, die an einem dieser Tage schon an einer anderen
     * Gewohnheit hängt.
     *
     * Verglichen wird getrimmt und ohne Rücksicht auf Groß-/Kleinschreibung.
     *
     * @param  list<int>  $days  Die Tage, an denen die Gewohnheit laufen soll.
     */
    protected function validateSituationIsFree(Validator $validator, ?Habit $except = null, array $days = Habit::EveryDay): void
    {
        $situation = $this->string('trigger_situation')->trim()->toString();

        if ($situation === '' || $days === []) {
            return;
        }

        $conflict = $this->user()->habits()
            ->active()
            ->when($except?->exists, fn ($query) => $query->whereKeyNot($except))
            ->get(['id', 'title', 'schedule_type', 'trigger_situation', 'scheduled_days', 'chained_to_habit_id'])
            // Nur wer den Moment wirklich trägt, kann ihn blockieren. Eine
            // gekettete Gewohnheit hängt an ihrem Vorgänger; was in ihrer
            // Spalte steht, liest niemand — es darf also auch niemanden
            // aussperren.
            ->first(fn (Habit $habit): bool => $habit->schedule_type->hasOwnAnchor()
                && $habit->trigger_situation !== null
                && mb_strtolower(trim($habit->trigger_situation)) === mb_strtolower($situation)
                && array_intersect($habit->activeWeekdays(), $days) !== []);

        if ($conflict === null) {
            return;
        }

        /** @var list<int> $overlap */
        $overlap = array_values(array_intersect($conflict->activeWeekdays(), $days));
        sort($overlap);

        // §1.5 — benennt, was gilt, ohne Vorwurf: Der Satz sagt, wem der
        // Moment an welchen Tagen gehört, nicht was die Person falsch gemacht
        // hat. Und er nennt beide Auswege, weil es seit den Wochentagen zwei
        // gibt.
        $validator->errors()->add('trigger_situation', sprintf(
            '„%s" hängt %s schon an diesem Moment. Zwei Gewohnheiten zur selben Zeit sind kein Plan — wähl andere Tage oder einen anderen Auslöser.',
            $conflict->title,
            Habit::weekdayLabel($overlap),
        ));
    }
}
