<?php

namespace App\Support;

use App\Models\Habit;
use App\Models\SleepSchedule;
use Illuminate\Support\Collection;

/**
 * Der Tag als Folge belegter und freier Fenster.
 *
 * Time-Blocking heißt: Jede Gewohnheit hat eine Spanne, und zwei Spannen
 * überschneiden sich nicht. Bis hierher rechnete das niemand aus — die KI
 * durfte beliebige Situationen erfinden („nachdem ich die Laufschuhe
 * ausgezogen habe"), und ob dort überhaupt Zeit war, fragte sie nicht.
 *
 * Diese Klasse trennt die harte Rechnung von der weichen Einschätzung: Sie
 * sagt, **wo** im Tag Platz ist; die KI wählt daraus und begründet. So kann
 * kein Vorschlag mehr kommen, der nicht in den Tag passt.
 *
 * Alle Zeiten sind Minuten seit Mitternacht. Der Rahmen kommt aus dem
 * Schlafplan: Was vor dem Aufstehen oder nach der Schlafenszeit läge, ist
 * kein freies Fenster, sondern Nacht.
 */
class DayPlan
{
    /**
     * Wie viel Luft zwischen zwei Gewohnheiten bleiben soll.
     *
     * Ein Tag, in dem jede Minute verplant ist, ist kein Plan, sondern eine
     * Taktung — und die erste Verspätung bringt alles Weitere zum Kippen.
     */
    public const int BreatherMinutes = 15;

    /**
     * Wie lange eine Gewohnheit ohne eigene Dauer belegt.
     *
     * Nur alte Zeilen aus der Zeit der freien Eingabe haben keine; der Katalog
     * vergibt immer eine.
     */
    private const int AssumedMinutes = 15;

    /**
     * @param  Collection<int, Habit>  $habits  Die Gewohnheiten, die an diesem Tag anstehen
     * @param  array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}  $window  Der Rahmen des Tages
     */
    public function __construct(
        private readonly Collection $habits,
        private readonly array $window,
    ) {}

    /** „07:30" → 450. */
    public static function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hours) * 60 + (int) $minutes;
    }

    /** 450 → „07:30". Über den Tagesrand hinaus wird umgebrochen. */
    public static function toTime(int $minutes): string
    {
        $clamped = (($minutes % 1440) + 1440) % 1440;

        return sprintf('%02d:%02d', intdiv($clamped, 60), $clamped % 60);
    }

    /**
     * Der wache Teil des Tages als eine Spanne.
     *
     * Eine Schlafenszeit nach Mitternacht reicht über den Tagesrand; sie wird
     * dann als Minute jenseits von 1440 gezählt, damit die Rechnung eine
     * durchgehende Achse behält statt zweier Stücke.
     *
     * @return array{from: int, to: int}
     */
    public function frame(): array
    {
        $wake = self::toMinutes($this->window['wakeTime']);
        $bed = self::toMinutes($this->window['bedtime']);

        return ['from' => $wake, 'to' => $bed > $wake ? $bed : $bed + 1440];
    }

    /**
     * Was im Tag schon belegt ist, in Reihenfolge.
     *
     * Jede Gewohnheit bekommt eine Stelle: die feste Uhrzeit, den Anschluss an
     * ihren Vorgänger oder — bei einer Situation — die Stunde, auf die sie
     * ohnehin sortiert wird. Die Situation ist damit keine exakte Uhrzeit,
     * aber eine ehrliche Näherung: Sie ist die Stelle, an der die Gewohnheit
     * im Kalender steht.
     *
     * @return list<array{id: int, title: string, from: int, to: int}>
     */
    public function occupied(?Habit $except = null): array
    {
        $blocks = $this->habits
            ->reject(fn (Habit $habit): bool => $except !== null && $habit->is($except))
            ->map(function (Habit $habit): ?array {
                $start = $this->startOf($habit);

                if ($start === null) {
                    return null;
                }

                return [
                    'id' => $habit->id,
                    'title' => $habit->title,
                    'from' => $start,
                    'to' => $start + ($habit->durationMinutes() ?? self::AssumedMinutes),
                ];
            })
            ->filter()
            ->sortBy('from')
            ->values()
            ->all();

        /** @var list<array{id: int, title: string, from: int, to: int}> $blocks */
        return $blocks;
    }

    /**
     * Wo im Tag noch Platz für `$minutes` ist.
     *
     * Gerechnet wird gegen den Rahmen und die belegten Fenster, mit einer
     * Atempause zwischen zwei Gewohnheiten. Ein Fenster erscheint nur, wenn
     * die Dauer wirklich hineinpasst — ein Vorschlag, der nicht passt, wäre
     * genau die Sorte Planung, die die App vermeiden soll.
     *
     * @return list<array{from: int, to: int}>
     */
    public function freeWindows(int $minutes, ?Habit $except = null): array
    {
        $frame = $this->frame();
        $cursor = $frame['from'];
        $windows = [];

        foreach ($this->occupied($except) as $block) {
            $gap = $block['from'] - self::BreatherMinutes - $cursor;

            if ($gap >= $minutes) {
                $windows[] = ['from' => $cursor, 'to' => $block['from'] - self::BreatherMinutes];
            }

            $cursor = max($cursor, $block['to'] + self::BreatherMinutes);
        }

        if ($minutes <= $frame['to'] - $cursor) {
            $windows[] = ['from' => $cursor, 'to' => $frame['to']];
        }

        return $windows;
    }

    /**
     * Passt die Dauer überhaupt noch irgendwo in den Tag?
     */
    public function hasRoomFor(int $minutes, ?Habit $except = null): bool
    {
        return $this->freeWindows($minutes, $except) !== [];
    }

    /**
     * Die freien Fenster als lesbare Zeilen — für den Prompt der KI.
     *
     * @return list<string>
     */
    public function freeWindowLabels(int $minutes, ?Habit $except = null): array
    {
        return array_map(
            fn (array $window): string => self::toTime($window['from']).' bis '.self::toTime($window['to']),
            $this->freeWindows($minutes, $except),
        );
    }

    /**
     * Wo eine Gewohnheit im Tag beginnt — als Minute.
     *
     * Feste Uhrzeiten und Ketten bringen einen echten Zeitpunkt mit. Eine
     * Situation hat keinen; für sie gilt die Stunde, auf die der Kalender sie
     * ohnehin sortiert. Ohne jede Stelle im Tag gibt es nichts zu belegen.
     */
    private function startOf(Habit $habit): ?int
    {
        $exact = $habit->startsAt();

        if ($exact !== null) {
            return self::toMinutes($exact->format('H:i'));
        }

        $hour = $habit->dayAnchorHour();

        return $hour === null ? null : $hour * 60;
    }

    /**
     * Baut den Plan für einen Nutzer und einen Wochentag.
     *
     * @param  Collection<int, Habit>  $habits
     * @param  array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>  $windows
     */
    public static function for(Collection $habits, int $weekday, array $windows): self
    {
        return new self(
            $habits,
            $windows[$weekday] ?? [
                'weekday' => $weekday,
                'wakeTime' => SleepSchedule::DefaultWakeTime,
                'bedtime' => SleepSchedule::DefaultBedtime,
                'alarmEnabled' => false,
            ],
        );
    }
}
