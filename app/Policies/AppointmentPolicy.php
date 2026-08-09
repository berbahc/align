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
