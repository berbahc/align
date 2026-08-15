<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Dasselbe Anlegen, nur aus einer fremden Vorlage heraus.
 *
 * Erbt bewusst alles: Eine übernommene Gewohnheit ist keine zweite Art von
 * Gewohnheit, sie entsteht nur an anderer Stelle. Damit gelten hier dieselbe
 * Validierung und dieselbe Grenze von fünf aktiven Gewohnheiten — sie ließe
 * sich sonst über diesen Weg umgehen.
 */
class AdoptHabitRequest extends StoreHabitRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            // Die Absage-Notiz, aus der heraus übernommen wird. Fehlt, wenn
            // der Weg aus der Anfrage-Karte kommt — dort gibt es keine Notiz.
            'notice_id' => ['nullable', 'integer'],
        ];
    }
}
