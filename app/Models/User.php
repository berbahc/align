<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $onboarded_at
 * @property bool $appointments_enabled
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Verabredungen sind an, solange niemand sie abstellt.
     *
     * Der Wert steht auch in der Migration als Spalten-Default. Hier steht er
     * ein zweites Mal, damit ein frisch erzeugtes Modell ihn schon vor dem
     * ersten Lesen aus der Datenbank trägt — sonst wäre die Eigenschaft im
     * Speicher `null` und eine Prüfung darauf stillschweigend falsch.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'appointments_enabled' => true,
    ];

    /**
     * @return HasMany<Habit, $this>
     */
    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class);
    }

    /**
     * Das Gedächtnis der KI zu dieser Person.
     *
     * Was sie vorgeschlagen hat und was davon übernommen wurde — die
     * Grundlage dafür, sich nicht zu wiederholen. Hängt am Konto und
     * verschwindet mit ihm (Cascade in der Migration).
     *
     * @return HasMany<AiSuggestion, $this>
     */
    public function aiSuggestions(): HasMany
    {
        return $this->hasMany(AiSuggestion::class);
    }

    /**
     * Der Handle wird immer klein gespeichert.
     *
     * Damit ist „Berkay" und „berkay" dieselbe Person — unabhängig davon, über
     * welchen Weg der Wert hereinkommt (Formular, Factory, Seeder). Ohne das
     * würde die Eindeutigkeitsprüfung an SQLites zeichengenauem Vergleich
     * vorbeilaufen und erst der Index in der Datenbank Alarm schlagen.
     *
     * @return Attribute<string, string>
     */
    protected function username(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::lower(trim($value)),
        );
    }

    /**
     * Anfragen, die diese Person gestellt hat — offen wie bestätigt.
     *
     * @return HasMany<Friendship, $this>
     */
    public function sentFriendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'requester_id');
    }

    /**
     * Anfragen, die an diese Person gingen.
     *
     * @return HasMany<Friendship, $this>
     */
    public function receivedFriendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'addressee_id');
    }

    /**
     * Die bestätigten Freundinnen und Freunde, alphabetisch.
     *
     * Bewusst keine Eloquent-Beziehung: Eine Freundschaft ist symmetrisch, die
     * Tabelle speichert sie aber gerichtet. Beide Richtungen zusammenzuführen
     * geht als Sammlung ehrlicher als über eine Beziehung, die immer nur eine
     * Hälfte sieht.
     *
     * @return Collection<int, User>
     */
    public function friends(): Collection
    {
        $accepted = Friendship::query()
            ->accepted()
            ->involving($this)
            ->with(['requester', 'addressee'])
            ->get();

        return $accepted
            ->map(fn (Friendship $friendship): User => $friendship->counterpart($this))
            ->sortBy('name')
            ->values();
    }

    /**
     * Besteht zwischen beiden schon eine Freundschaft oder eine offene Anfrage?
     */
    public function hasFriendshipWith(User $other): bool
    {
        return Friendship::query()
            ->involving($this)
            ->involving($other)
            ->exists();
    }

    /**
     * Die offene Anfrage, die `$other` an diese Person gerichtet hat.
     *
     * Zwei Menschen, die gleichzeitig aneinander denken, sollen nicht in einem
     * Zustand landen, in dem beide auf den jeweils anderen warten: Wer eine
     * offene Anfrage vorfindet, nimmt sie an, statt eine zweite zu stellen.
     */
    public function pendingRequestFrom(User $other): ?Friendship
    {
        return Friendship::query()
            ->pending()
            ->where('requester_id', $other->id)
            ->where('addressee_id', $this->id)
            ->first();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'appointments_enabled' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
