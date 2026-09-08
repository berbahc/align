<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Was ausgemacht ist, rückt nicht.
 *
 * Die Uhrzeit einer Verabredung steht fest, sobald gefragt wurde
 * ({@see Appointment::startMinute()}) — genau damit beide Seiten dieselbe
 * lesen. Wer danach seine Gewohnheit für diesen Tag woanders hinlegt, bricht
 * das auf: Sein Block wandert, die Verabredung bleibt, und die andere Person
 * erfährt nichts davon. Zwei Kalender, zwei Uhrzeiten, beide überzeugt.
 *
 * Deshalb dieselbe Antwort wie beim Kurs: Es geht nicht. Ein Kurs kommt von
 * der Uni, eine Zusage gehört zu zweit — beide sind keine Sache, die man
 * allein verschiebt. Der Ausweg ist nicht ein anderer Platz, sondern die
 * Absage, und die trifft man bewusst.
 *
 * Der Riegel gilt für beide Seiten. Auf der fragenden hängt die Verabredung an
 * der eigenen Gewohnheit; auf der gefragten an der, die sie an diesem Tag
 * ersetzt ({@see Appointment::replaces()}). In beiden Fällen ist es die Zeile,
 * die im Kalender der anderen Person steht.
 */
final class PromiseLock
{
    /**
     * Die Zusage, die diese Gewohnheit an einem dieser Tage festhält.
     *
     * @param  list<Carbon>  $dates
     * @return array{appointment: Appointment, date: Carbon}|null
     */
    public static function on(User $user, Habit $habit, array $dates): ?array
    {
        if ($dates === []) {
            return null;
        }

        $days = array_map(fn (Carbon $date): Carbon => $date->copy()->startOfDay(), $dates);

        $appointments = Appointment::query()
            ->accepted()
            ->involving($user)
            ->whereIn('scheduled_for', $days)
            ->with(['habit', 'requester', 'invitee'])
            ->get();

        if ($appointments->isEmpty()) {
            return null;
        }

        // Der eigene Plan einmal, für die Frage nach der ersetzten Sache.
        $habits = $user->habits()->active()->get();
        $habits->each(fn (Habit $own) => $own->setRelation('user', $user));

        foreach ($appointments as $appointment) {
            $pinned = $appointment->requester_id === $user->id
                ? $appointment->habit_id
                : $appointment->replaces($user, $habits)?->id;

            if ($pinned === $habit->id) {
                return [
                    'appointment' => $appointment,
                    'date' => Carbon::parse($appointment->scheduled_for)->startOfDay(),
                ];
            }
        }

        return null;
    }

    /**
     * Warum es nicht geht, und was stattdessen zu tun wäre.
     *
     * Mit Tag, Uhrzeit und Namen: „Geht nicht" ohne den Grund führte dazu,
     * dass man die Verabredung sucht, statt sie zu sehen.
     */
    public static function message(Appointment $appointment, Carbon $date, User $user): string
    {
        return sprintf(
            '%s ist „%s" um %s mit %s ausgemacht. Solange die Verabredung steht, bleibt die Zeit, wie sie ist — sag sie ab, wenn du den Tag ändern willst.',
            ucfirst(Appointment::dayLabel($date)),
            $appointment->habit->title,
            DayPlan::toTime($appointment->startMinute()),
            $appointment->counterpart($user)->name,
        );
    }
}
