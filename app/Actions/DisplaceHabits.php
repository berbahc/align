<?php

namespace App\Actions;

use App\Models\Habit;
use App\Models\User;
use App\Support\SlotConflict;
use App\Support\Timetable;
use Carbon\CarbonInterface;

/**
 * Räumt Gewohnheiten aus einer Spanne, die ein Kurs jetzt belegt.
 *
 * Bis hierher war es umgekehrt: Der Kurs wurde abgewiesen, solange dort eine
 * Gewohnheit lag. Beim Semesterwechsel ist das die falsche Richtung — wer
 * seinen Stundenplan einträgt, kann nicht erst jede Gewohnheit von Hand
 * wegräumen, und wer es versucht, verliert sie.
 *
 * Der Kurs ist die Tatsache, die Gewohnheit das Bewegliche. Sie wird deshalb
 * geparkt, nicht gelöscht: Ihre Uhrzeit bleibt als Erinnerung, ihr Platz im
 * Tag ist weg. So liegt weiterhin nichts übereinander, und trotzdem geht
 * nichts verloren.
 */
class DisplaceHabits
{
    /**
     * Alles, was in diese Spannen ragt, verliert seinen Platz.
     *
     * Über {@see SlotConflict::find()} in Runden, nicht über eine eigene
     * Suche: Die gibt bewusst nur den ersten Konflikt zurück, aber jede Runde
     * parkt einen, und die nächste findet den nächsten. So erbt die Suche
     * alles, was sie ohnehin kann — Tagesausnahmen, den Lauf durch die Kette,
     * den Schlafrahmen je Wochentag. Begrenzt durch die Fünfergrenze.
     *
     * Verdrängt wird der Anker; was an ihm hängt, wird darüber von selbst
     * platzlos und braucht keinen eigenen Vermerk.
     *
     * @param  list<array{id: int, title: string, from: int, to: int}>  $spans  Was der Kurs belegt
     * @param  list<int>  $days  An welchen ISO-Wochentagen
     * @param  bool  $withTimetable  Nein, wenn der Stundenplan selbst der Prüfling ist
     * @return list<Habit> Was dafür seinen Platz verloren hat
     */
    public function handle(User $user, array $spans, array $days, bool $withTimetable = true): array
    {
        $displaced = [];
        $from = null;
        // Was schon geparkt ist, zählt in der nächsten Runde nicht mehr mit.
        // Nötig, weil ein Vermerk erst ab Semesterbeginn gilt: Bis dahin
        // belegt die Gewohnheit ihren alten Platz weiter — und die Suche
        // fände sie sonst jede Runde aufs Neue, statt zur nächsten zu kommen.
        $ignore = [];

        for ($round = 0; $round < Habit::MaxActivePerUser; $round++) {
            $conflict = SlotConflict::find($user, $spans, $days, $ignore, $withTimetable, spanIsCourse: true);

            if ($conflict === null) {
                break;
            }

            // Ein Kurs im Weg bleibt eine Abweisung — er rückt nicht, und zwei
            // Kurse übereinander weist der Request ohnehin schon ab. Die 0 ist
            // die Verabredung und ebenfalls nichts, was sich parken ließe.
            if (Timetable::isCourseBlock($conflict['block']) || $conflict['block']['id'] < 1) {
                break;
            }

            $habit = $user->habits()->active()->find($conflict['block']['id']);

            if ($habit === null) {
                break;
            }

            // Der Block gehört vielleicht einer gekoppelten Gewohnheit; geparkt
            // wird ihr Anker, weil nur der eine Stelle im Tag hat.
            $anchor = $habit->anchorHabit() ?? $habit;

            $anchor->forceFill(['displaced_at' => $from ??= self::effectiveFrom($user)])->save();
            $displaced[] = $anchor;
            // Samt Kette — die Nachfolger hängen am Anker und fielen sonst
            // ebenfalls jede Runde aufs Neue auf.
            $ignore = [...$ignore, ...array_column($anchor->spansFrom(0), 'id')];
        }

        return $displaced;
    }

    /**
     * Ab wann der Platz weg ist.
     *
     * Sofort, wenn das Semester schon läuft — sonst erst an seinem ersten Tag.
     * Ein Kurs im Oktober nimmt im September noch nichts weg: Bis dahin läuft
     * die Gewohnheit weiter, wo sie lief, und erst mit dem Semester steht die
     * Frage nach einem neuen Platz an.
     */
    private static function effectiveFrom(User $user): CarbonInterface
    {
        $semester = $user->currentSemester();
        $now = now();

        if ($semester === null || $semester->starts_on->lte($now)) {
            return $now;
        }

        return $semester->starts_on->startOfDay();
    }
}
