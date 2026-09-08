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
     * Wo der Kalendertag endet — jenseits davon gibt es keine Uhrzeit mehr.
     *
     * Hier und nicht bei einem Aufrufer: Der Rahmen reicht über Mitternacht
     * hinaus, wenn jemand später ins Bett geht ({@see frame()}), und jede
     * Stelle, die eine Uhrzeit daraus macht, braucht dieselbe Deckelung.
     */
    public const int MinutesPerDay = 1440;

    /**
     * Die Luft innerhalb einer Kette — kürzer als zwischen zwei Blöcken.
     *
     * Wer „danach" plant, ist schon dabei: Er muss nirgends hinkommen und
     * nichts umschalten, nur weiterlaufen. Die Viertelstunde zwischen zwei
     * unabhängigen Blöcken ist Weg und Wechsel; hier reicht das Atemholen.
     */
    public const int ChainBreatherMinutes = 5;

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

    /**
     * Einmal gerechnet, mehrfach gefragt — der Tag ändert sich zwischendurch nicht.
     *
     * @var array<int, int>|null
     */
    private ?array $placements = null;

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
        $places = $this->placements();

        $blocks = $this->habits
            ->reject(fn (Habit $habit): bool => $except !== null && $habit->is($except))
            ->map(function (Habit $habit) use ($places): ?array {
                $start = $places[$habit->id] ?? null;

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
     * Mit derselben Viertelstunde Luft wie {@see freeWindows()}: Was die KI
     * nie vorschlüge, geht auch von Hand nicht — sonst hätte der Tag zwei
     * Maßstäbe, und ein Vorschlag sähe strenger aus als die eigene Hand. Zwei
     * Blöcke direkt hintereinander sind darum keine Planung, sondern zu eng.
     *
     * Die eine Ausnahme sind Kurse untereinander: Die Uni legt sie Rücken an
     * Rücken, und daran ist nichts zu prüfen.
     *
     * @param  bool  $spanIsCourse  Ist die Spanne selbst ein Kurs? Dann braucht sie zu anderen Kursen keine Luft
     * @return array{id: int, title: string, from: int, to: int}|null
     */
    public function collisionWith(int $from, int $to, ?Habit $except = null, bool $spanIsCourse = false): ?array
    {
        foreach ($this->occupied($except) as $block) {
            $air = $spanIsCourse && Timetable::isCourseBlock($block) ? 0 : self::BreatherMinutes;

            if ($from < $block['to'] + $air && $to + $air > $block['from']) {
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
        return self::windowLabels($this->freeWindows($minutes, $except));
    }

    /**
     * Fenster als Zeilen für den Prompt — „07:00 bis 09:45".
     *
     * @param  list<array{from: int, to: int}>  $windows
     * @return list<string>
     */
    public static function windowLabels(array $windows): array
    {
        return array_map(
            fn (array $window): string => self::toTime($window['from']).' bis '.self::toTime($window['to']),
            $windows,
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
    public function startOf(Habit $habit): ?int
    {
        return $this->placements()[$habit->id] ?? null;
    }

    /**
     * Wo an diesem Tag jede Gewohnheit liegt — die eine Wahrheit des Tages.
     *
     * Zwei Durchgänge, weil nicht alles gleich fest ist. Eine Uhrzeit steht;
     * eine Situation nicht. „Nach dem Frühstück" heißt irgendwann am
     * Vormittag, und wenn dort schon etwas liegt, rutscht die Gewohnheit
     * innerhalb dessen weiter, was die Situation ohnehin bedeutet
     * ({@see Habit::situationWindow()}). Der Nutzer sieht die Spanne nie — er
     * hat eine Situation gewählt, keine Uhrzeit, und genau deshalb darf sie
     * nachgeben, statt die App etwas abweisen zu lassen.
     *
     * Was im Fenster keinen Platz findet, bleibt an seinem Anfang liegen. Eine
     * Gewohnheit verschwinden zu lassen, weil der Vormittag voll ist, wäre
     * schlimmer als ein sichtbares Gedränge.
     *
     * @return array<int, int> Gewohnheit → Minute seit Mitternacht
     */
    private function placements(): array
    {
        if ($this->placements !== null) {
            return $this->placements;
        }

        $fixed = [];
        $movable = [];

        // Wer an einer wandernden Situation hängt, wird mit ihr platziert und
        // nicht vorher: Sonst stünde er an der alten Stelle im Weg und bliebe
        // dort liegen, während sein Anker ausweicht.
        $carried = [];

        foreach ($this->habits as $habit) {
            $anchor = $habit->anchorHabit();

            if ($anchor !== null
                && ! $anchor->is($habit)
                && $anchor->situationWindow($this->date) !== null) {
                $carried[$habit->id] = true;
            }
        }

        foreach ($this->habits as $habit) {
            if (isset($carried[$habit->id])) {
                continue;
            }

            $start = $habit->dayStartMinute($this->date);

            if ($start === null) {
                continue;
            }

            // Ein Umzug für genau diesen Tag ist eine Ansage, keine Näherung:
            // Wer sein Lesen heute auf 14:00 gelegt hat, will es dort haben und
            // nicht irgendwo in der Spanne seiner Situation.
            $window = $habit->shiftedTimeOn($this->date) === null
                ? $habit->situationWindow($this->date)
                : null;

            if ($window === null) {
                // „Nach der Vorlesung" ohne Vorlesung: kein Auslöser, keine
                // Stelle. Sie fällt an diesem Tag aus, statt an einer
                // erfundenen Uhrzeit zu liegen.
                if ($this->date !== null
                    && $habit->shiftedTimeOn($this->date) === null
                    && ! $habit->hasTriggerOn($this->date)) {
                    continue;
                }

                $fixed[$habit->id] = $start;

                continue;
            }

            $movable[] = ['habit' => $habit, 'window' => $window, 'start' => $start];
        }

        // Was steht, steht — samt allem, was von außen kommt.
        $taken = array_map(
            fn (array $block): array => ['from' => $block['from'], 'to' => $block['to']],
            $this->extraBlocks,
        );

        foreach ($this->habits as $habit) {
            if (isset($fixed[$habit->id])) {
                $taken[] = [
                    'from' => $fixed[$habit->id],
                    'to' => $fixed[$habit->id] + ($habit->durationMinutes() ?? self::AssumedMinutes),
                ];
            }
        }

        // Die frühere Situation zuerst: Sonst nähme die spätere den Platz weg,
        // an den die frühere gehört, und beide rutschten weiter als nötig.
        usort($movable, fn (array $a, array $b): int => $a['start'] <=> $b['start']);

        $places = $fixed;

        foreach ($movable as $entry) {
            // Die ganze Kette auf einmal: Was an der Situation hängt, rutscht
            // mit ihr, und der Platz muss für alle zusammen reichen. Sonst
            // bliebe der Nachfolger an der Stelle liegen, der sein Anker
            // gerade ausgewichen ist.
            $offsets = $entry['habit']->spansFrom(0);
            $ends = array_column($offsets, 'to');
            $needed = $ends === [] ? self::AssumedMinutes : max($ends);

            // Das Fenster fasst, was hineinsoll — samt Kette. Sonst fiele die
            // Suche durch und die Gewohnheit bliebe an ihrer alten Stelle
            // liegen, obwohl gleich daneben Platz wäre.
            $window = $entry['window'];
            $window['to'] = max($window['to'], $window['from'] + $needed);

            // Gesucht wird von der Kante her, an der die Situation klebt:
            // „Nach dem Aufstehen" beginnt beim Aufstehen und weicht nach
            // hinten aus, „vor dem Schlafengehen" endet an der Schlafenszeit
            // und weicht nach vorn. Immer von vorn zu suchen legte den
            // Abendblock an den Anfang seiner Stunde — also eine Stunde vor
            // die Schlafenszeit, sobald dort gerade Platz ist. Das ist nicht,
            // was „vor dem Schlafengehen" heißt.
            //
            // Ist die Spanne zu, gilt der Rest des Tages in derselben
            // Richtung: Später als der Anlass ist besser als übereinander,
            // und früher als der Abend besser als nach dem Zubettgehen.
            // Findet sich auch dort nichts, bekommt sie keine Stelle — dann
            // steht sie unter dem Raster, sichtbar.
            $start = $entry['window']['anchor'] === 'end'
                ? $this->lastFreeWithin($window, $needed, $taken)
                    ?? $this->lastFreeWithin(
                        ['from' => $this->frame()['from'], 'to' => $window['to']],
                        $needed,
                        $taken,
                    )
                : $this->firstFreeWithin($window, $needed, $taken)
                    ?? $this->firstFreeWithin(
                        ['from' => $window['from'], 'to' => $this->frame()['to']],
                        $needed,
                        $taken,
                    );

            if ($start === null) {
                continue;
            }

            foreach ($offsets as $span) {
                $places[$span['id']] = $start + $span['from'];
                $taken[] = ['from' => $start + $span['from'], 'to' => $start + $span['to']];
            }
        }

        return $this->placements = $places;
    }

    /**
     * Der **letzte** Platz im Fenster, an dem `$minutes` frei sind.
     *
     * Das Gegenstück zu {@see firstFreeWithin()} für die Situationen, die an
     * ihrem Ende hängen: „Vor dem Schlafengehen" soll so spät wie möglich
     * liegen, nicht so früh wie möglich. Gesucht wird deshalb von hinten —
     * der Block endet an der Grenze und rückt nur so weit nach vorn, wie das,
     * was dort schon liegt, es erzwingt.
     *
     * Dieselbe Viertelstunde Luft wie überall. `null`, wenn das Fenster nichts
     * mehr hergibt.
     *
     * @param  array{from: int, to: int}  $window
     * @param  list<array{from: int, to: int}>  $taken
     */
    private function lastFreeWithin(array $window, int $minutes, array $taken): ?int
    {
        $cursor = $window['to'] - $minutes;

        // Höchstens so oft, wie es Blöcke gibt: Jeder schiebt den Zeiger
        // einmal vor sich, danach ist entweder Platz oder das Fenster aus.
        for ($step = 0; $step <= count($taken); $step++) {
            if ($cursor < $window['from']) {
                return null;
            }

            $blocking = null;

            foreach ($taken as $block) {
                if ($cursor < $block['to'] + self::BreatherMinutes
                    && $cursor + $minutes + self::BreatherMinutes > $block['from']) {
                    $candidate = $block['from'] - self::BreatherMinutes - $minutes;
                    $blocking = $blocking === null ? $candidate : min($blocking, $candidate);
                }
            }

            if ($blocking === null) {
                return $cursor;
            }

            $cursor = $blocking;
        }

        return null;
    }

    /**
     * Der erste Platz im Fenster, an dem `$minutes` frei sind.
     *
     * Mit derselben Viertelstunde Luft wie überall: Was die App nie
     * vorschlüge, soll auch keine Situation still einnehmen. `null`, wenn das
     * Fenster nichts mehr hergibt.
     *
     * @param  array{from: int, to: int}  $window
     * @param  list<array{from: int, to: int}>  $taken
     */
    private function firstFreeWithin(array $window, int $minutes, array $taken): ?int
    {
        $cursor = $window['from'];

        // Höchstens so oft, wie es Blöcke gibt: Jeder schiebt den Zeiger
        // einmal hinter sich, danach ist entweder Platz oder das Fenster aus.
        for ($step = 0; $step <= count($taken); $step++) {
            if ($cursor + $minutes > $window['to']) {
                return null;
            }

            $blocking = null;

            foreach ($taken as $block) {
                if ($cursor < $block['to'] + self::BreatherMinutes
                    && $cursor + $minutes + self::BreatherMinutes > $block['from']) {
                    $blocking = max($blocking ?? 0, $block['to'] + self::BreatherMinutes);
                }
            }

            if ($blocking === null) {
                return $cursor;
            }

            $cursor = $blocking;
        }

        return null;
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
