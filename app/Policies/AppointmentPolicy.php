<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Zusagen darf nur, wer gefragt wurde.
     */
    public function accept(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->invitee_id
            && $appointment->accepted_at === null;
    }

    /**
     * Abhaken darf nur die gefragte Seite — an ihrer Verabredung.
     *
     * Die fragende hakt ihre eigene Gewohnheit ab wie immer; hier wäre das ein
     * zweiter Haken für dieselbe Sache. Die Bedingungen stehen im Modell,
     * damit die Oberfläche denselben Satz fragen kann, den der Server prüft.
     */
    public function complete(User $user, Appointment $appointment): bool
    {
        return $appointment->isCompletableBy($user);
    }

    /**
     * Absagen, zurückziehen oder eine zugesagte Verabredung auflösen.
     *
     * Alle drei enden gleich — der Eintrag verschwindet. Es bleibt keine Notiz
     * darüber, dass jemand abgesagt hat: Eine Absage-Statistik wäre bei
     * Schuldgefühl ø 3,92 die schärfste denkbare Bestrafung
     * (community_feature3.md §9).
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->requester_id
            || $user->id === $appointment->invitee_id;
    }
}
