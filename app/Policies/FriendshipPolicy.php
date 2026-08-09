<?php

namespace App\Policies;

use App\Models\Friendship;
use App\Models\User;

class FriendshipPolicy
{
    /**
     * Zusagen darf nur, wer gefragt wurde.
     *
     * Sonst könnte die anfragende Seite ihre eigene Anfrage bestätigen und
     * sich damit in eine fremde Freundesliste schreiben.
     */
    public function accept(User $user, Friendship $friendship): bool
    {
        return $user->id === $friendship->addressee_id
            && $friendship->accepted_at === null;
    }

    /**
     * Auflösen darf jede beteiligte Seite.
     *
     * Derselbe Weg trägt drei Fälle: die Absage auf eine Anfrage, das
     * Zurückziehen der eigenen Anfrage und das spätere Beenden einer
     * bestätigten Freundschaft. Alle drei enden gleich — der Eintrag
     * verschwindet, es bleibt keine Spur (community_feature3.md §9).
     */
    public function delete(User $user, Friendship $friendship): bool
    {
        return $user->id === $friendship->requester_id
            || $user->id === $friendship->addressee_id;
    }
}
