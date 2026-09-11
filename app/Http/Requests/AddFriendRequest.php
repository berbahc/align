<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class AddFriendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Ein Feld für beide Formen.
     *
     * Enthält die Eingabe ein „@", ist sie eine Adresse, sonst ein Handle.
     * Niemand muss vorher wissen, was erwartet wird — und wer den Handle nicht
     * kennt, kommt mit der Adresse weiter.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'handle' => Str::lower(trim((string) $this->string('handle'))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'handle' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Wird die Eingabe als E-Mail-Adresse gelesen?
     */
    public function looksLikeEmail(): bool
    {
        return str_contains((string) $this->string('handle'), '@');
    }

    /**
     * Die vier Gründe, aus denen eine Anfrage nicht zustande kommt.
     *
     * Bewusst als `after`-Prüfung: Die Meldungen sollen benennen, was ist, ohne
     * jemanden zu belehren — dieselbe Haltung, die designsprache.md §8 für die
     * ganze Oberfläche festlegt.
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
                $handle = (string) $this->string('handle');
                $isEmail = $this->looksLikeEmail();

                if ($handle === Str::lower($me->email) || $handle === $me->username) {
                    $validator->errors()->add('handle', 'Das bist du selbst.');

                    return;
                }

                $other = $this->find($handle);

                if ($other === null) {
                    $validator->errors()->add('handle', $isEmail
                        // Dass diese Meldung bestätigt, ob eine Adresse ein
                        // Konto hat, ist der Preis des Adressen-Wegs. Beim
                        // Handle entfällt er: Eine öffentliche Kennung darf
                        // als existent erkennbar sein.
                        ? 'Unter dieser Adresse ist niemand bei Align.'
                        : 'Diesen Namen gibt es hier nicht.');

                    return;
                }

                if (! $other->appointments_enabled) {
                    $validator->errors()->add('handle', 'Diese Person hat Verabredungen ausgeschaltet.');

                    return;
                }

                // Eine offene Anfrage der Gegenseite ist kein Hindernis,
                // sondern der kürzeste Weg zur Zusage — der Controller nimmt
                // sie an, statt eine zweite in Gegenrichtung anzulegen.
                if ($me->hasFriendshipWith($other) && $me->pendingRequestFrom($other) === null) {
                    $validator->errors()->add('handle', 'Ihr seid schon verbunden, oder eine Anfrage ist noch offen.');
                }
            },
        ];
    }

    /**
     * Die Person hinter der Eingabe — erst nach bestandener Prüfung aufrufen.
     */
    public function addressee(): User
    {
        $other = $this->find((string) $this->string('handle'));

        abort_if($other === null, 404);

        return $other;
    }

    /**
     * Immer exakt, nie ein Teiltreffer.
     *
     * Eine Suche, die auch Bruchstücke findet, machte aus dem Handle ein
     * durchblätterbares Personenverzeichnis. Die Umfrage trägt den engen Kreis
     * (21/25 nur enge Freunde), nicht das Entdecken Fremder.
     */
    private function find(string $handle): ?User
    {
        $column = str_contains($handle, '@') ? 'email' : 'username';

        return User::query()->whereRaw("lower({$column}) = ?", [$handle])->first();
    }
}
