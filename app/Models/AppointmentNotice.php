<?php

namespace App\Models;

use App\Enums\AppointmentNoticeKind;
use Database\Factories\AppointmentNoticeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Eine Absage, die einmal ankommt und dann verschwindet.
 *
 * community_feature3.md §5 verlangt, dass eine Absage beim Fragenden erscheint
 * — „ohne Zähler, ohne Historie". Genau das ist diese Zeile: Sie lebt vom
 * Absagen bis zum Wegklicken und wird dann gelöscht. Was bleibt, ist nichts.
 *
 * Sie trägt bewusst nur Text, keinen Verweis auf die absagende Person: Ohne
 * deren `id` lässt sich aus diesen Zeilen keine Quote bilden.
 *
 * Was sie zusätzlich trägt, ist der Weg, auf dem es allein weitergeht — je nach
 * Seite ein Verweis auf die eigene Gewohnheit oder ein Bauplan zum Übernehmen.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $habit_id
 * @property AppointmentNoticeKind $kind
 * @property string $companion_name
 * @property string $habit_title
 * @property array{title: string, behaviorType: string, scheduleType: string, triggerSituation: string|null, scheduledTime: string|null, scheduledDays: list<int>|null}|null $habit_blueprint
 * @property string $day
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'habit_id', 'kind', 'companion_name', 'habit_title', 'habit_blueprint', 'day'])]
class AppointmentNotice extends Model
{
    /** @use HasFactory<AppointmentNoticeFactory> */
    use HasFactory;

    /**
     * Hinterlässt eine Notiz, wenn durch das Löschen jemand etwas verliert.
     *
     * Muss **vor** dem Löschen laufen — danach sind Namen und Titel fort.
     *
     * Drei Fälle, und nur zwei davon melden sich:
     *
     * 1. `$actor` sagt eine offene Anfrage ab („Lieber nicht"). Die fragende
     *    Seite erfährt sonst nie, ob überhaupt jemand hingesehen hat — §5
     *    verlangt hier ausdrücklich ein „Passt Silas diesmal nicht".
     * 2. `$actor` löst eine zugesagte Verabredung auf. Der schwerere Fall: Die
     *    andere Person hat den Tag um diese Zusage herum geplant und stünde
     *    sonst allein da. Das Konzept kennt ihn nicht — es beschreibt nur die
     *    Absage der offenen Anfrage —, doch hier wiegt das Schweigen schwerer
     *    als bei 1.
     * 3. `$actor` zieht die **eigene** offene Anfrage zurück. Nichts war
     *    zugesagt, niemand hat etwas verplant, niemandem entgeht etwas. Eine
     *    Meldung wäre bloß Lärm — hier bleibt es still.
     */
    public static function afterRemoval(Appointment $appointment, User $actor): void
    {
        $kind = match (true) {
            $appointment->accepted_at !== null => AppointmentNoticeKind::Cancelled,
            $actor->id === $appointment->invitee_id => AppointmentNoticeKind::Declined,
            default => null,
        };

        if ($kind === null) {
            return;
        }

        $recipient = $appointment->counterpart($actor);
        $habit = $appointment->habit;

        // Genau einer der beiden Wege, nie beide: Wem die Gewohnheit gehört,
        // der hat nichts zu übernehmen — sie steht schon in seiner Liste. Wem
        // sie nicht gehört, dem nützt ein Verweis nichts, weil er sie nicht
        // öffnen dürfte; er braucht die Vorlage.
        $isOwnHabit = $habit->user_id === $recipient->id;

        self::query()->create([
            'user_id' => $recipient->id,
            'habit_id' => $isOwnHabit ? $habit->id : null,
            'kind' => $kind,
            'companion_name' => $actor->name,
            'habit_title' => $habit->title,
            'habit_blueprint' => $isOwnHabit ? null : $habit->blueprint(),
            'day' => Appointment::dayLabel($appointment->scheduled_for),
        ]);
    }

    /**
     * Die offenen Notizen einer Person, fertig für die Oberfläche.
     *
     * Steht hier und nicht im Controller, weil zwei Seiten sie brauchen: die
     * Übersicht und der Community-Bereich.
     *
     * @return list<array{id: int, message: string, detail: string, habitId: int|null, blueprint: array{title: string, behaviorType: string, scheduleType: string, triggerSituation: string|null, scheduledTime: string|null, scheduledDays: list<int>|null}|null}>
     */
    public static function forUser(User $user): array
    {
        return array_values(
            self::query()
                ->where('user_id', $user->id)
                ->oldest()
                ->get()
                ->map(fn (self $notice): array => [
                    'id' => $notice->id,
                    'message' => $notice->message(),
                    'detail' => $notice->detail(),
                    // Die eigene Gewohnheit, zu der es weitergeht …
                    'habitId' => $notice->habit_id,
                    // … oder die Vorlage, aus der eine eigene würde.
                    'blueprint' => $notice->habit_blueprint,
                ])
                ->all()
        );
    }

    /**
     * Der Satz selbst.
     *
     * Wortlaut nach §5 („Passt Silas diesmal nicht") — eine Feststellung, keine
     * Meldung. Kein „leider", kein Ausrufezeichen, kein Grund: Sobald eine
     * Absage sich rechtfertigen muss, wird aus der Verabredung eine Schuld.
     *
     * Beim Auflösen steht der Tag im Satz, weil er der eigentliche Verlust ist
     * — wer den Dienstag verplant hatte, muss wissen, welcher Tag frei wird.
     */
    public function message(): string
    {
        return match ($this->kind) {
            AppointmentNoticeKind::Declined => sprintf('Passt %s diesmal nicht.', $this->companion_name),
            AppointmentNoticeKind::Cancelled => sprintf('%s kann %s doch nicht.', $this->companion_name, $this->day),
        };
    }

    /**
     * Die leise Zeile darunter — woran es hing.
     */
    public function detail(): string
    {
        return match ($this->kind) {
            AppointmentNoticeKind::Declined => sprintf('%s · %s', $this->habit_title, $this->day),
            AppointmentNoticeKind::Cancelled => $this->habit_title,
        };
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'kind' => AppointmentNoticeKind::class,
            'habit_blueprint' => 'array',
        ];
    }
}
