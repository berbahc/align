<?php

namespace App\Policies;

use App\Models\Habit;
use App\Models\User;

class HabitPolicy
{
    /**
     * Gewohnheiten sind rein persönlich.
     *
     * Auch wenn später das Community-Feature dazukommt, wird dort nur ein
     * Signal geteilt („aktiv" / „noch offen") — nie das Abhaken selbst.
     */
    public function complete(User $user, Habit $habit): bool
    {
        return $user->id === $habit->user_id;
    }
}
