<?php

namespace App\Support;

use App\Models\Habit;
use App\Models\SleepSchedule;
use Illuminate\Support\Carbon;
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
     * vergibt immer eine. Öffentlich, weil das Verschieben dieselbe Annahme
     * braucht — zwei verschiedene Annahmen wären zwei verschiedene Tage.
     */
    public const int AssumedMinutes = 15;

    /**
     * @param  Collection<int, Habit>  $habits  Die Gewohnheiten, die an diesem Tag anstehen
     * @param  array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}  $window  Der Rahmen des Tages
     * @param  Carbon|null  $date  Der konkrete Tag — nur mit ihm gelten Tagesverschiebungen
     * @param  list<array{id: int, title: string, from: int, to: int}>  $extraBlocks  Was den Tag sonst noch belegt
     */
    public function __construct(
        private readonly Collection $habits,
        private readonly array $window,
        private readonly ?Carbon $date = null,
        private readonly array $extraBlocks = [],
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
     * Was nicht aus einer Gewohnheit kommt — etwa eine Verabredung, die an
     * diesem Tag Platz braucht — reicht der Aufrufer als Fremdblock herein.
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
            ->concat($this->extraBlocks)
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
     * Was einer Spanne im Weg liegt — oder nichts.
     *
     * Anders als {@see freeWindows()} ohne die 15 Minuten Atempause: Zwei
     * Blöcke direkt hintereinander sind eine Planung, keine Doppelbuchung. Die
     * Atempause ist ein Rat für einen Vorschlag, keine Grenze für eine
     * Entscheidung, die jemand selbst trifft.
     *
     * @return array{id: int, title: string, from: int, to: int}|null
     */
    public function collisionWith(int $from, int $to, ?Habit $except = null): ?array
    {
        foreach ($this->occupied($except) as $block) {
            if ($from < $block['to'] && $to > $block['from']) {
                return $block;
            }
        }

        return null;
    }

    /**
     * Passt die Dauer überhaupt noch irgendwo in den Tag?
     */
    public function hasRoomFor(int $minutes, ?Habit $except = null): bool
    {
        return $this->freeWindows($minutes, $except) !== [];
    }

    /**
     * Die Fenster, die an **allen** Tagen frei sind.
     *
     * Ein Wochentag, zwei Daten: der nächste Termin und der erste im Semester,
     * wenn das noch vor uns liegt. Was nächste Woche frei ist, aber am ersten
     * Vorlesungsmontag unter einem Kurs liegt, ist kein Fenster — der
     * Vorschlag fiele sonst beim Übernehmen an genau dem Kurs durch, den er
     * nicht sah. Dieselbe Datumswahl wie {@see SlotConflict::datesFor()}.
     *
     * @param  list<self>  $plans
     * @return list<array{from: int, to: int}>
     */
    public static function commonFreeWindows(array $plans, int $minutes, ?Habit $except = null): array
    {
        $common = null;

        foreach ($plans as $plan) {
            $windows = $plan->freeWindows($minutes, $except);

            if ($common === null) {
                $common = $windows;

                continue;
            }

            $next = [];

            foreach ($common as $window) {
                foreach ($windows as $other) {
                    $from = max($window['from'], $other['from']);
                    $to = min($window['to'], $other['to']);

                    if ($to - $from >= $minutes) {
                        $next[] = ['from' => $from, 'to' => $to];
                    }
                }
            }

            $common = $next;
        }

        return $common ?? [];
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
     * Die Rechnung steht am Modell ({@see Habit::dayStartMinute()}), weil das
     * Stundenraster im Kalender dieselbe braucht: Ein Block, der woanders
     * gezeichnet wird, als der Server ihn belegt, wäre ein sichtbarer
     * Widerspruch.
     */
    private function startOf(Habit $habit): ?int
    {
        return $habit->dayStartMinute($this->date);
    }

    /**
     * Baut den Plan für einen Nutzer und einen Wochentag.
     *
     * @param  Collection<int, Habit>  $habits
     * @param  array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>  $windows
     * @param  list<array{id: int, title: string, from: int, to: int}>  $extraBlocks
     */
    public static function for(Collection $habits, int $weekday, array $windows, ?Carbon $date = null, array $extraBlocks = []): self
    {
        return new self(
            $habits,
            $windows[$weekday] ?? [
                'weekday' => $weekday,
                'wakeTime' => SleepSchedule::DefaultWakeTime,
                'bedtime' => SleepSchedule::DefaultBedtime,
                'alarmEnabled' => false,
            ],
            $date,
            $extraBlocks,
        );
    }

    /**
     * Derselbe Plan für ein Datum — Wochentag und Verschiebungen inbegriffen.
     *
     * @param  Collection<int, Habit>  $habits
     * @param  array<int, array{weekday: int, wakeTime: string, bedtime: string, alarmEnabled: bool}>  $windows
     * @param  list<array{id: int, title: string, from: int, to: int}>  $extraBlocks
     */
    public static function forDate(Collection $habits, Carbon $date, array $windows, array $extraBlocks = []): self
    {
        return self::for($habits, $date->dayOfWeekIso, $windows, $date, $extraBlocks);
    }
}
