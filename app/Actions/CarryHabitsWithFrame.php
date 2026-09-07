<?php

namespace App\Actions;

use App\Enums\ScheduleType;
use App\Enums\ShiftOrigin;
use App\Models\Habit;
use App\Models\HabitDayShift;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\SlotConflict;
use Illuminate\Support\Carbon;

/**
 * Wandert der Rahmen, wandern die Gewohnheiten mit.
 *
 * Situative Gewohnheiten hängen längst am Schlafplan: „nach dem Aufstehen"
 * sitzt zur echten Aufstehzeit ({@see Habit::sleepBoundStartMinute()}), und
 * wenn dort etwas liegt, weichen sie aus. Feste Uhrzeiten taten das nicht.
 * Sie wurden beim Anlegen einmal gegen den Rahmen geprüft und danach nie
 * wieder — wer seine Aufstehzeit von 07:00 auf 10:00 zog, ließ sein Frühstück
 * um 08:00 in der Nacht zurück.
 *
 * Die Antwort ist keine neue Art von Anker, sondern eine Verschiebung: Fällt
 * eine Uhrzeit durch den neuen Rahmen heraus, rückt sie um dieselbe Differenz
 * nach, um die sich die Kante bewegt hat. Der Abstand zum Aufstehen bleibt,
 * was er war — Frühstück war eine Stunde nach dem Wecker und bleibt es.
 *
 * **Der neue Rahmen muss beim Aufruf schon gespeichert sein.** Die
 * Kollisionsprüfung geht über {@see SlotConflict}, und der liest den Tag aus
 * der Datenbank — samt der situativen Gewohnheiten, die dem neuen Rahmen
 * bereits gefolgt sind. Gegen den alten Stand gerechnet übersähe sie genau
 * die Blöcke, die sich gerade mitbewegt haben. Die Vorschau löst das, indem
 * sie speichert, rechnet und zurückrollt — so kann sie gar nicht erst etwas
 * anderes zeigen als das, was danach passiert.
 *
 * **Jeder Zug wird sofort geschrieben.** Nicht aus Bequemlichkeit, sondern
 * weil zwei Züge sonst auf demselben Platz landen: Wer erst alles ausrechnet
 * und danach schreibt, prüft den zweiten Zug gegen einen Tag, in dem der erste
 * noch an seiner alten Stelle steht. Genau das ist passiert — Tagebuch und
 * Frühstück wanderten beide auf 13:15. Geschrieben wird darum in derselben
 * Schleife, in der gerechnet wird, und die Vorschau rollt hinterher zurück.
 */
class CarryHabitsWithFrame
{
    /**
     * Wie oft die Platzsuche höchstens weiterspringt.
     *
     * Jeder Sprung geht hinter den Block, der gerade im Weg lag — mehr
     * Versuche als Blöcke im Tag kann es also nicht brauchen. Die Zahl ist
     * eine Notbremse gegen Daten, die nie durch ein Formular gegangen sind,
     * keine Regel.
     */
    private const int MaxProbes = 24;

    /**
     * Was mitzieht und was nicht mitkann — für den dauerhaften Plan.
     *
     * @param  array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>  $before  Der Rahmen, wie er war
     * @return array{moves: list<array{habit: Habit, from: int, to: int}>, blocked: list<array{habit: Habit, reason: string}>}
     */
    public function forWeek(User $user, array $before): array
    {
        return $this->carry(
            $user,
            $before,
            $user->sleepWindows(),
            fn (Habit $habit): array => $habit->activeWeekdays(),
            fn (Habit $habit, array $spans): ?array => SlotConflict::find(
                $user,
                $spans,
                $habit->activeWeekdays(),
                array_column($habit->spansFrom(0), 'id'),
            ),
            function (Habit $habit, int $start): void {
                // Eine Uhrzeit für die ganze Woche: Diese Rechnung sucht einen
                // Platz, an dem die Gewohnheit an **allen** ihren Tagen im
                // Rahmen liegt, und findet deshalb genau einen. Hatte sie
                // vorher verschiedene Zeiten je Tag, fallen sie hier zusammen —
                // sichtbar, denn der Rahmenwechsel meldet, was er verschoben
                // hat. Sie stehen zu lassen hieße, den gefundenen Platz gleich
                // wieder zu überschreiben.
                $habit->update([
                    'scheduled_times' => null,
                    'scheduled_time' => DayPlan::toTime($start),
                ]);

                // Eine Uhrzeit im Rahmen ist wieder ein Platz — dieselbe
                // Ansage wie bei jedem anderen Weg, der eine setzt.
                $habit->takeAPlace();
            },
        );
    }

    /**
     * Dieselbe Rechnung für einen einzigen Tag.
     *
     * Der Unterschied ist die Zahl der Tage, an denen das Ergebnis stimmen
     * muss: Eine dauerhafte Uhrzeit gilt an allen Tagen der Gewohnheit, eine
     * Tages-Ausnahme nur an diesem einen. Deshalb hier nur sein Wochentag —
     * und die Prüfung am Datum statt am Wochentag, damit ausgefallene
     * Vorlesungen und bestehende Umzüge mitzählen.
     *
     * @param  array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>  $before
     * @return array{moves: list<array{habit: Habit, from: int, to: int}>, blocked: list<array{habit: Habit, reason: string}>}
     */
    public function forDay(User $user, Carbon $date, array $before): array
    {
        return $this->carry(
            $user,
            $before,
            $user->sleepWindowsOn($date),
            fn (Habit $habit): array => [$date->dayOfWeekIso],
            fn (Habit $habit, array $spans): ?array => SlotConflict::findOn(
                $user,
                $spans,
                $date,
                array_column($habit->spansFrom(0), 'id'),
            ),
            // Das Datum als Carbon und nicht als Zeichenkette: Der `date`-Cast
            // legt „2026-09-05 00:00:00" ab, und ein Vergleich gegen
            // „2026-09-05" fände die eigene Zeile nicht — `updateOrCreate`
            // legte dann eine zweite an und liefe in den eindeutigen Schlüssel.
            function (Habit $habit, int $start) use ($date): void {
                HabitDayShift::query()->updateOrCreate(
                    ['habit_id' => $habit->id, 'shifted_on' => $date],
                    [
                        'scheduled_time' => DayPlan::toTime($start),
                        'origin' => ShiftOrigin::Frame,
                    ],
                );
            },
            $date,
        );
    }

    /**
     * Die gemeinsame Rechnung beider Einstiege.
     *
     * @param  array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>  $before
     * @param  array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>  $after
     * @param  callable(Habit): list<int>  $daysOf  An welchen Wochentagen das Ergebnis stimmen muss
     * @param  callable(Habit, list<array{id: int, title: string, from: int, to: int}>): (array{block: array{id: int, title: string, from: int, to: int}, date: Carbon}|null)  $conflictAt
     * @param  callable(Habit, int): void  $commit  Schreibt den Zug, damit der nächste ihn sieht
     * @return array{moves: list<array{habit: Habit, from: int, to: int}>, blocked: list<array{habit: Habit, reason: string}>}
     */
    private function carry(
        User $user,
        array $before,
        array $after,
        callable $daysOf,
        callable $conflictAt,
        callable $commit,
        ?Carbon $date = null,
    ): array {
        $moves = [];
        $blocked = [];

        foreach ($this->candidates($user, $date) as $habit) {
            $days = $daysOf($habit);
            $start = $this->startOf($habit, $date);

            if ($days === [] || $start === null) {
                continue;
            }

            $target = $this->targetFor($habit, $start, $days, $before, $after);

            // Sie liegt im neuen Rahmen — dann bleibt sie, wo sie ist. Alles
            // zu verschieben, nur weil sich eine Kante bewegt hat, wäre eine
            // Umplanung und keine Nachführung.
            if ($target === null) {
                continue;
            }

            $room = $this->roomFor($habit, $days, $after);

            if ($room === null) {
                $blocked[] = [
                    'habit' => $habit,
                    'reason' => 'Sie passt an keinem ihrer Tage mehr in den neuen Rahmen — dafür ist sie zu lang.',
                ];

                continue;
            }

            $free = $this->firstFreeFrom($habit, $target, $room, $conflictAt);

            if ($free === null) {
                $blocked[] = [
                    'habit' => $habit,
                    'reason' => $this->whyNot($habit, $room, $conflictAt),
                ];

                continue;
            }

            // Erst schreiben, dann weiter: Der nächste Zug wird gegen einen
            // Tag geprüft, in dem dieser hier schon liegt.
            $commit($habit, $free);

            $moves[] = ['habit' => $habit, 'from' => $start, 'to' => $free];
        }

        return ['moves' => $moves, 'blocked' => $blocked];
    }

    /**
     * Wer überhaupt mitziehen kann.
     *
     * Nur Gewohnheiten mit eigener Uhrzeit. Gekettete hängen über
     * {@see Habit::spansFrom()} an ihrem Anker und rücken mit ihm; situative
     * folgen dem Rahmen ohnehin von selbst. Beide hier noch einmal anzufassen
     * hieße, sie zweimal zu verschieben.
     *
     * Nach der alten Startzeit sortiert: Die früheste Gewohnheit soll den
     * frühesten Platz behalten, sonst kehrt sich die Reihenfolge des Tages um,
     * nur weil zwei um denselben Platz konkurrieren.
     *
     * @return list<Habit>
     */
    private function candidates(User $user, ?Carbon $date): array
    {
        $habits = $user->habits()
            ->active()
            ->where('schedule_type', ScheduleType::Fixed)
            ->whereNotNull('scheduled_time')
            ->with([
                'chainedHabits',
                'dayShifts' => fn ($query) => $date === null
                    ? $query->whereRaw('1 = 0')
                    : $query->whereDate('shifted_on', $date),
            ])
            ->get();

        $habits->each(fn (Habit $habit) => $habit->setRelation('user', $user));

        /** @var list<Habit> $sorted */
        $sorted = $habits
            ->filter(fn (Habit $habit): bool => $date === null || $habit->isScheduledOn($date))
            ->sortBy(fn (Habit $habit): int => $this->startOf($habit, $date) ?? 0)
            ->values()
            ->all();

        return $sorted;
    }

    /**
     * Wo sie heute liegt — die Tages-Ausnahme geht der dauerhaften Zeit vor.
     */
    private function startOf(Habit $habit, ?Carbon $date): ?int
    {
        $shifted = $date === null ? null : $habit->shiftedTimeOn($date);

        if ($shifted !== null) {
            return DayPlan::toMinutes($shifted);
        }

        return $habit->scheduled_time === null
            ? null
            : DayPlan::toMinutes($habit->scheduled_time->format('H:i'));
    }

    /**
     * Wohin sie soll — oder `null`, wenn sie bleiben kann.
     *
     * Geprüft wird jeder ihrer Tage einzeln, denn der Rahmen ist je Wochentag
     * einstellbar. Fällt sie an einem heraus, ist die Verschiebung die der
     * Kante, die sie hinausgedrängt hat: Steht sie vor dem Aufstehen, gilt die
     * Differenz der Aufstehzeit; ragt sie über die Schlafenszeit, die der
     * Schlafenszeit.
     *
     * Der weiteste dieser Züge gewinnt. Wer an zwei Tagen herausfällt, muss an
     * beiden wieder hineinpassen, und die kleinere Verschiebung ließe ihn an
     * einem davon draußen.
     *
     * @param  list<int>  $days
     * @param  array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>  $before
     * @param  array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>  $after
     */
    private function targetFor(Habit $habit, int $start, array $days, array $before, array $after): ?int
    {
        $needed = $this->lengthOf($habit);
        $shift = null;

        foreach ($days as $weekday) {
            $was = $this->frameOf($before, $weekday);
            $is = $this->frameOf($after, $weekday);

            if ($start >= $is['from'] && $start + $needed <= $is['to']) {
                continue;
            }

            $ahead = $start < $is['from'];

            $delta = $ahead
                ? $is['from'] - $was['from']
                : $is['to'] - $was['to'];

            // Eine Kante, die sich gar nicht bewegt hat, kann niemanden
            // hinausgedrängt haben — dann lag die Gewohnheit schon vorher
            // draußen, und der Weg zurück ist der an die Kante selbst.
            if ($delta === 0) {
                $delta = $ahead
                    ? $is['from'] - $start
                    : $is['to'] - $needed - $start;
            }

            $shift = $shift === null || abs($delta) > abs($shift) ? $delta : $shift;
        }

        return $shift === null ? null : $start + $shift;
    }

    /**
     * Die Spanne, in der ihre neue Uhrzeit liegen darf.
     *
     * Eine dauerhafte Uhrzeit gilt an **allen** Tagen der Gewohnheit: Wer
     * Frühstück von Montag bis Freitag hat und nur den Montag verschiebt, darf
     * die neue Zeit nicht so wählen, dass sie am Dienstag in der Nacht landet.
     * Deshalb der Schnitt über alle ihre Tage. Ist er leer, gibt es keine
     * Uhrzeit, die überall passt.
     *
     * @param  list<int>  $days
     * @param  array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>  $after
     * @return array{from: int, to: int}|null
     */
    private function roomFor(Habit $habit, array $days, array $after): ?array
    {
        $needed = $this->lengthOf($habit);
        $starts = [];
        $ends = [];

        foreach ($days as $weekday) {
            $frame = $this->frameOf($after, $weekday);

            $starts[] = $frame['from'];
            $ends[] = $frame['to'] - $needed;
        }

        if ($starts === []) {
            return null;
        }

        // Der späteste Anfang und das früheste Ende: nur dazwischen liegt eine
        // Uhrzeit, die an jedem ihrer Tage im Rahmen ist.
        $from = max($starts);
        $to = min($ends);

        return $from > $to ? null : ['from' => $from, 'to' => $to];
    }

    /**
     * Der erste Platz ab dem Ziel, an dem nichts im Weg liegt.
     *
     * Gesprungen statt geschritten: Liegt etwas im Weg, geht es hinter dessen
     * Ende weiter — jeder Versuch schließt also einen Block ab, statt sich in
     * Viertelstundenschritten durch ihn hindurchzutasten. Dieselbe Bauart wie
     * {@see DayPlan} sie intern für Situationen benutzt, nur dass die Blöcke
     * hier von {@see SlotConflict} kommen und damit die Luft und den
     * Stundenplan schon mitbringen.
     *
     * @param  array{from: int, to: int}  $room
     * @param  callable(Habit, list<array{id: int, title: string, from: int, to: int}>): (array{block: array{id: int, title: string, from: int, to: int}, date: Carbon}|null)  $conflictAt
     */
    private function firstFreeFrom(Habit $habit, int $target, array $room, callable $conflictAt): ?int
    {
        $step = DayPlan::BreatherMinutes;
        $cursor = $this->onGrid(max($target, $room['from']), $step);

        for ($probe = 0; $probe < self::MaxProbes; $probe++) {
            if ($cursor > $room['to']) {
                return null;
            }

            $conflict = $conflictAt($habit, $habit->spansFrom($cursor));

            if ($conflict === null) {
                return $cursor;
            }

            $next = $this->onGrid($conflict['block']['to'] + $step, $step);

            // Kommt die Suche nicht voran, ist der Block hinter dem Zeiger
            // gemeldet worden — dann ist das nächste Raster der einzige Weg
            // nach vorn, sonst liefe die Schleife auf der Stelle.
            $cursor = $next > $cursor ? $next : $cursor + $step;
        }

        return null;
    }

    /** Auf den nächsten Viertelstundenpunkt, nach oben. */
    private function onGrid(int $minute, int $step): int
    {
        return (int) (ceil($minute / $step) * $step);
    }

    /**
     * Warum es nicht ging — mit dem Satz, den die App überall benutzt.
     *
     * @param  array{from: int, to: int}  $room
     * @param  callable(Habit, list<array{id: int, title: string, from: int, to: int}>): (array{block: array{id: int, title: string, from: int, to: int}, date: Carbon}|null)  $conflictAt
     */
    private function whyNot(Habit $habit, array $room, callable $conflictAt): string
    {
        $conflict = $conflictAt($habit, $habit->spansFrom($room['from']));

        return $conflict === null
            ? 'In ihrem neuen Rahmen ist kein freier Platz mehr für sie.'
            : SlotConflict::message(
                $conflict['block'],
                $conflict['date'],
                'Verschiebe die zuerst, dann zieht diese hier mit.',
            );
    }

    /**
     * Wie viel Zeit sie braucht — samt allem, was an ihr hängt.
     *
     * Die Kette zählt mit, weil sie mitrückt: Eine Gewohnheit, die gerade so
     * in den Abend passt, aber zwei Nachfolger trägt, passt eben nicht.
     */
    private function lengthOf(Habit $habit): int
    {
        $ends = array_column($habit->spansFrom(0), 'to');

        return $ends === [] ? DayPlan::AssumedMinutes : max($ends);
    }

    /**
     * Der wache Teil eines Wochentags als Spanne.
     *
     * Dieselbe Rechnung wie {@see DayPlan::frame()}, damit eine Schlafenszeit
     * nach Mitternacht auch hier jenseits des Tagesrands weiterzählt.
     *
     * @param  array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>  $windows
     * @return array{from: int, to: int}
     */
    private function frameOf(array $windows, int $weekday): array
    {
        $window = $windows[$weekday];
        $wake = DayPlan::toMinutes($window['wakeTime']);
        $bed = DayPlan::toMinutes($window['bedtime']);

        return ['from' => $wake, 'to' => $bed > $wake ? $bed : $bed + 1440];
    }
}
