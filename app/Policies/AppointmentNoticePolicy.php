<?php

namespace App\Policies;

use App\Models\AppointmentNotice;
use App\Models\User;

class AppointmentNoticePolicy
{
    /**
     * Wegklicken darf nur, wem die Notiz gilt.
     */
    public function delete(User $user, AppointmentNotice $notice): bool
    {
        return $user->id === $notice->user_id;
    }
}
