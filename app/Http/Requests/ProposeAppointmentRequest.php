<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class ProposeAppointmentRequest extends FormRequest
{
    /**
     * Die Gewohnheit muss der fragenden Seite gehören.
     *
     * Steht hier und nicht im Controller: `authorize()` läuft **vor** der
     * Validierung. Andersherum bekäme jemand für eine fremde Gewohnheit erst
     * eine Meldung über den eigenen Freundeskreis — eine Auskunft über eine
     * Sache, die ihn nichts angeht.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->habit());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'friend_id' => ['required', 'integer'],
            'scheduled_for' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Die Bedingungen, unter denen eine Verabredung überhaupt entstehen kann.
     *
     * @return list<\Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $me = $this->user();
                $friend = User::query()->find($this->integer('friend_id'));

                // Nur aus dem eigenen Kreis. Ohne bestätigte Freundschaft
                // könnte jede fremde Person Termine schicken.
                if ($friend === null || ! $me->friends()->contains('id', $friend->id)) {
                    $validator->errors()->add('friend_id', 'Diese Person ist nicht in deinem Kreis.');

                    return;
                }

                if (! $friend->appointments_enabled) {
                    $validator->errors()->add('friend_id', 'Diese Person hat Verabredungen ausgeschaltet.');

                    return;
                }

                $date = Carbon::createFromFormat('Y-m-d', $this->string('scheduled_for')->value())->startOfDay();

                // Geprüft wird gegen dieselbe Quelle, aus der die Oberfläche
                // schöpft — nicht gegen eine zweite Rechnung daneben. Gegen
                // die **ganze** Menge und nicht gegen die angebotenen drei:
                // Wo die Auswahl anfängt, hängt vom Weg ab („Nochmal
                // ausmachen?" beginnt am Tag danach), was zulässig ist
                // dagegen nicht.
                $offered = Appointment::possibleDaysFor($this->habit());

                if (! in_array($date->toDateString(), $offered, strict: true)) {
                    $validator->errors()->add('scheduled_for', 'Dieser Tag steht nicht zur Wahl.');

                    return;
                }

                if ($this->habit()->appointments()->onDate($date)->exists()) {
                    $validator->errors()->add('scheduled_for', 'Für diesen Tag steht schon eine Verabredung.');
                }
            },
        ];
    }

    /**
     * Die Gewohnheit aus der Route — sie gehört immer der fragenden Seite.
     */
    public function habit(): Habit
    {
        /** @var Habit $habit */
        $habit = $this->route('habit');

        return $habit;
    }

    public function friend(): User
    {
        return User::query()->findOrFail($this->integer('friend_id'));
    }

    public function scheduledFor(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->string('scheduled_for')->value())->startOfDay();
    }
}
