<?php

namespace App\Enums;

/**
 * Was der anderen Seite abhanden gekommen ist.
 *
 * Die Unterscheidung entscheidet nur über den Satz, nicht über die Behandlung:
 * Beide Notizen sehen gleich aus, beide verschwinden beim Wegklicken, keine
 * von beiden nennt einen Grund (community_feature3.md §5).
 */
enum AppointmentNoticeKind: string
{
    /** Eine offene Anfrage wurde mit „Lieber nicht" beantwortet. */
    case Declined = 'declined';

    /** Eine bereits zugesagte Verabredung wurde aufgelöst. */
    case Cancelled = 'cancelled';
}
