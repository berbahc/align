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

    /**
     * Einstellungen einer Gewohnheit ändern — derzeit die Erinnerung.
     */
    public function update(User $user, Habit $habit): bool
    {
        return $user->id === $habit->user_id;
    }

    /**
     * Beenden und Wiederaufnehmen.
     *
     * Beides bewegt nur `graduated_at` und lässt die Erfüllungen unberührt —
     * der Weg zurück ist deshalb immer offen.
     */
    public function graduate(User $user, Habit $habit): bool
    {
        return $user->id === $habit->user_id;
    }

    /**
     * Endgültiges Löschen — nimmt über `cascadeOnDelete` alle abgehakten Tage
     * mit. Die Oberfläche bietet es nur im Archiv an, hinter einer Rückfrage.
     */
    public function delete(User $user, Habit $habit): bool
    {
        return $user->id === $habit->user_id;
    }
}
