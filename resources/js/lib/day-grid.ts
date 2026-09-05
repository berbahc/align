import type { CalendarBlock, CourseBlock } from '@/types';

/**
 * Die Rechnung hinter dem Stundenraster — getrennt von seiner Zeichnung.
 *
 * Wo ein Block liegt und wie hoch er ist, hängt an vier Zahlen und keiner
 * Farbe. Das hier auszulagern hat einen praktischen Grund: Die Verteilung auf
 * Spalten bei Überschneidung ist die einzige Stelle im Kalender mit echtem
 * Algorithmus, und in einer Komponente zwischen Klassennamen wäre sie nicht
 * mehr zu lesen.
 */

/**
 * Wie hoch eine Stunde ist.
 *
 * 96 Pixel, weil erst dort die Dauer wieder etwas bedeutet: Die Gewohnheiten
 * dieser App dauern zehn bis sechzig Minuten, und bei 72 landeten sie alle auf
 * der Mindesthöhe — ein Raster, in dem eine Viertelstunde so hoch ist wie eine
 * Dreiviertelstunde, ist kein Maßstab, sondern eine Liste mit Linien dahinter.
 * So ist eine Viertelstunde 24 Pixel und eine halbe 48.
 */
export const HOUR_HEIGHT = 96;

/**
 * Wie klein ein Block werden darf.
 *
 * 44 Pixel ist die kleinste verlässliche Trefferfläche (Apple HIG). Ein
 * Zehn-Minuten-Block wäre bei maßstabsgetreuer Höhe 12 Pixel hoch und damit
 * nicht mehr zu treffen — er wird deshalb größer gezeichnet, als er dauert.
 * Die Zeile im Block sagt weiter die Wahrheit; nur das Rechteck lügt ein
 * bisschen, und zwar zugunsten der Bedienbarkeit.
 */
export const MIN_BLOCK_HEIGHT = 44;

/**
 * Der Spalt zwischen zwei Blöcken, damit zwei Kanten zwei bleiben.
 *
 * Eine Haarlinie, mehr nicht: Ein Block soll bis an die Stundenlinie reichen,
 * die seine Dauer begrenzt. Wäre der Spalt sichtbar, sähe eine Stunde kürzer
 * aus als eine Stunde.
 */
const BLOCK_GAP = 1;

/**
 * Wie lange ein Block ohne eigene Dauer belegt.
 *
 * Derselbe Wert wie in `DayPlan::AssumedMinutes` — nur alte Zeilen aus der
 * Zeit der freien Eingabe haben keine Dauer, der Katalog vergibt immer eine.
 * Wo damit gerechnet wird, soll es auch dastehen ({@see spanLabel}).
 */
export const ASSUMED_MINUTES = 15;

/**
 * Die Luft zwischen zwei Blöcken — dieselbe wie `DayPlan::BreatherMinutes`.
 *
 * Gilt für jede Hand, nicht nur für die KI: Was sie nie vorschlüge, soll sich
 * auch nicht hinziehen lassen. Zwei Blöcke direkt hintereinander sind zu eng.
 */
export const BREATHER_MINUTES = 15;

/** Der Ausschnitt des Tages, den das Raster zeigt — volle Stunden. */
export interface GridBounds {
    /** Minute seit Mitternacht, abgerundet auf die volle Stunde. */
    from: number;
    /** Minute seit Mitternacht, aufgerundet. Kann über 1440 liegen. */
    to: number;
    /** Die Gesamthöhe in Pixeln. */
    height: number;
}

/**
 * Alles, was das Raster tragen kann.
 *
 * Unterschieden über `kind`: Eine Gewohnheit lässt sich abhaken und ziehen,
 * ein Kurs nicht. Beide brauchen aber dieselbe Spaltenverteilung — läge eine
 * Gewohnheit auf einer Vorlesung, müssten sie sich die Breite teilen wie zwei
 * Gewohnheiten auch.
 */
export type GridBlock = CalendarBlock | CourseBlock;

/**
 * Das Wenigste, was ein Block zum Platzieren mitbringen muss.
 *
 * Die Rechnung kennt weder Haken noch Titel — nur wann etwas anfängt und wie
 * lange es dauert.
 */
interface Placeable {
    id: number;
    startMinute: number | null;
    durationMinutes: number | null;
}

/** Ein Block mit seinem Platz im Raster. */
export interface PlacedBlock<T extends Placeable = GridBlock> {
    block: T;
    top: number;
    height: number;
    /** Die Spalte, in der er liegt — bei Überschneidung teilen sich Blöcke die Breite. */
    lane: number;
    /** Wie viele Spalten seine Gruppe braucht. */
    lanes: number;
}

/**
 * Der Rahmen als Achse — genau von der Aufstehzeit bis zur Schlafenszeit.
 *
 * Bewusst nicht auf volle Stunden gedehnt: Die beiden Enden tragen ohnehin
 * ihre eigene Marke („07:20 Aufstehen"), und eine angebrochene Stunde davor
 * wäre Platz, in den nichts geplant werden darf.
 */
export function gridBounds(frameFrom: number, frameTo: number): GridBounds {
    const to = Math.max(frameTo, frameFrom + 60);

    return {
        from: frameFrom,
        to,
        height: ((to - frameFrom) / 60) * HOUR_HEIGHT,
    };
}

/**
 * Die vollen Stunden im Raster.
 *
 * `skip` nimmt die Minuten heraus, an denen schon eine Marke des Tagesrandes
 * steht: „23:00" zweimal übereinander wäre eine Zeile, die nichts hinzufügt.
 */
export function hourMarks(bounds: GridBounds, skip: number[] = []): number[] {
    const marks: number[] = [];

    for (
        let minute = Math.ceil(bounds.from / 60) * 60;
        minute <= bounds.to;
        minute += 60
    ) {
        if (!skip.includes(minute)) {
            marks.push(minute);
        }
    }

    return marks;
}

/**
 * Der Ausschnitt, der alles zeigt: den Rahmen des Tages und jeden Block.
 *
 * Normalerweise ist das der Rahmen allein. Aber ein Schlafplan lässt sich
 * ändern, nachdem eine Gewohnheit angelegt wurde — dann liegt eine feste
 * Uhrzeit plötzlich nach der Schlafenszeit. Sie deshalb aus dem Raster fallen
 * zu lassen wäre das Gegenteil von hilfreich: Sie stünde über dem Text darunter
 * und wäre nicht mehr zu greifen. Das Raster wächst stattdessen mit, und die
 * Nacht darum herum wird sichtbar gemacht.
 */
export function boundsFor(
    frameFrom: number,
    frameTo: number,
    blocks: Placeable[],
): GridBounds {
    let from = frameFrom;
    let to = frameTo;

    for (const block of blocks) {
        if (block.startMinute === null) {
            continue;
        }

        from = Math.min(from, block.startMinute);
        to = Math.max(
            to,
            block.startMinute + (block.durationMinutes ?? ASSUMED_MINUTES),
        );
    }

    return gridBounds(from, to);
}

/** Wo eine Minute im Raster liegt, in Pixeln von oben. */
export function offsetOf(minute: number, bounds: GridBounds): number {
    return ((minute - bounds.from) / 60) * HOUR_HEIGHT;
}

/**
 * Die belegte Spanne, wie sie im Block steht — auch die angenommene.
 *
 * Ohne eigene Dauer rechnet der Tag mit einer Viertelstunde. Das soll
 * dastehen („10:45 – ca. 11:00") und nicht nur gelten: Sonst sieht man einen
 * Block ohne Ende, und was direkt danach liegt, wirkt, als läge es darin.
 */
export function spanLabel(block: CalendarBlock): string {
    if (block.timeRange !== null) {
        return block.timeRange;
    }

    if (block.startMinute === null || !block.exact) {
        return block.anchor;
    }

    return `${timeLabel(block.startMinute)} – ca. ${timeLabel(block.startMinute + ASSUMED_MINUTES)}`;
}

/** Eine Minute seit Mitternacht als „07:30" — auch über den Tagesrand hinaus. */
export function timeLabel(minute: number): string {
    const wrapped = ((minute % 1440) + 1440) % 1440;
    const hours = Math.floor(wrapped / 60);

    return `${String(hours).padStart(2, '0')}:${String(wrapped % 60).padStart(2, '0')}`;
}

/**
 * Legt die Blöcke ins Raster und verteilt Überschneidungen auf Spalten.
 *
 * Das Verfahren ist das übliche für Kalender: Blöcke nach Beginn sortieren, in
 * Gruppen zerlegen, die sich berühren, und innerhalb einer Gruppe jedem Block
 * die erste Spalte geben, die frei geworden ist. Die Breite teilt sich dann
 * die Gruppe — nicht der ganze Tag, sonst würde eine einzige Überschneidung am
 * Morgen den Abend halb so breit machen.
 *
 * Gerechnet wird mit der **echten** Spanne, nicht der gezeichneten: Zwei
 * Blöcke direkt hintereinander sind eine Reihenfolge, keine Überschneidung —
 * und nebeneinander gemalt läsen sie sich als „liegt drin". Ragt die
 * Mindesthöhe des ersten in den zweiten, deckt der zweite sie ab: Er kommt
 * später und liegt darum obenauf. Die Zeile des ersten steht oben im Block
 * und bleibt lesbar.
 */
export function placeBlocks<T extends Placeable>(
    blocks: T[],
    bounds: GridBounds,
    /**
     * Die Schlafenszeit, als Minute — bis hierher darf gezeichnet werden.
     *
     * Ein Zehn-Minuten-Block wird 44 Pixel hoch gezeichnet, damit er sich
     * treffen lässt (siehe {@see MIN_BLOCK_HEIGHT}). Wer „vor dem
     * Schlafengehen" meditiert, bekäme dadurch ein Rechteck, das über die
     * Schlafenszeit hinausragt — ausgerechnet die Grenze, an der die
     * Gewohnheit enden soll. Passt sie in Wahrheit hinein, wird sie so weit
     * nach oben gerückt, dass auch die Zeichnung hineinpasst.
     */
    limit?: number,
): PlacedBlock<T>[] {
    const spans = blocks
        .filter((block) => block.startMinute !== null)
        .map((block) => {
            const from = block.startMinute as number;
            const minutes = block.durationMinutes ?? ASSUMED_MINUTES;

            return {
                block,
                from,
                to: from + minutes,
            };
        })
        .sort((a, b) => a.from - b.from || b.to - a.to);

    const placed: PlacedBlock<T>[] = [];
    let group: PlacedBlock<T>[] = [];
    let laneEnds: number[] = [];
    let groupEnd = -Infinity;

    const closeGroup = () => {
        const lanes = group.reduce(
            (most, entry) => Math.max(most, entry.lane + 1),
            1,
        );
        group.forEach((entry) => placed.push({ ...entry, lanes }));
        group = [];
        laneEnds = [];
        groupEnd = -Infinity;
    };

    for (const span of spans) {
        // Berührt der Block die laufende Gruppe nicht mehr, fängt eine neue an.
        if (span.from >= groupEnd) {
            closeGroup();
        }

        const free = laneEnds.findIndex((end) => end <= span.from);
        const lane = free === -1 ? laneEnds.length : free;

        laneEnds[lane] = span.to;
        groupEnd = Math.max(groupEnd, span.to);

        const height =
            Math.max(
                ((span.to - span.from) / 60) * HOUR_HEIGHT,
                MIN_BLOCK_HEIGHT,
            ) - BLOCK_GAP;

        let top = offsetOf(span.from, bounds);

        if (limit !== undefined && span.to <= limit) {
            const edge = offsetOf(limit, bounds);

            top = Math.min(top, Math.max(edge - height - BLOCK_GAP, 0));
        }

        group.push({ block: span.block, top, height, lane, lanes: 1 });
    }

    closeGroup();

    return placed;
}

/**
 * Wie fein sich ein Block schieben lässt.
 *
 * Eine Viertelstunde — derselbe Takt wie `DayPlan::BreatherMinutes` auf dem
 * Server, und 24 Pixel bei `HOUR_HEIGHT`. Minutengenau zu schieben hieße, mit
 * dem Finger eine Genauigkeit zu verlangen, die niemand hat.
 */
const SNAP_MINUTES = 15;

/** Auf die nächste Viertelstunde, aber nie aus dem Rahmen heraus. */
export function snapMinute(
    minute: number,
    bounds: GridBounds,
    durationMinutes: number,
): number {
    const snapped = Math.round(minute / SNAP_MINUTES) * SNAP_MINUTES;

    // Nie über Mitternacht: Die Ausnahme wird als Uhrzeit gespeichert, und
    // 00:10 läse sich beim nächsten Aufschlagen als früher Vormittag. Wessen
    // Tag nach Mitternacht endet, kann bis dorthin schieben und nicht weiter.
    const latest = Math.min(bounds.to, 1440);

    return Math.min(
        Math.max(snapped, bounds.from),
        Math.max(latest - durationMinutes, bounds.from),
    );
}

/**
 * Der Tag, wie er nach dem Zug aussähe.
 *
 * Der gezogene Block liegt an der neuen Minute — und alles, was an ihm hängt,
 * rutscht mit. Das ist keine Kulanz, sondern die Bedeutung einer Kette: Sie
 * heißt „danach". Bliebe der Nachfolger stehen, wäre sein eigener Anker
 * gelogen. Der Server rechnet dasselbe ({@see Habit::placementOn()}); hier
 * geschieht es nur, damit man es sieht, bevor man loslässt.
 */
export function withDrag(
    blocks: CalendarBlock[],
    draggedId: number,
    minute: number,
): CalendarBlock[] {
    const byId = new Map(blocks.map((block) => [block.id, block]));
    const moved = new Map<number, number>([[draggedId, minute]]);

    // Eine Kette ist höchstens fünf Glieder lang (`Habit::MaxChainDepth`), und
    // die Blöcke stehen nicht zwingend in ihrer Reihenfolge da.
    for (let pass = 0; pass < 5; pass++) {
        for (const block of blocks) {
            if (block.chainedToId === null || moved.has(block.id)) {
                continue;
            }

            const start = moved.get(block.chainedToId);

            if (start === undefined) {
                continue;
            }

            const previous = byId.get(block.chainedToId);

            // „Danach" heißt: nach dem Ende plus der Viertelstunde Luft —
            // dieselbe Rechnung wie `Habit::resolveStart()`.
            moved.set(
                block.id,
                start +
                    (previous?.durationMinutes ?? ASSUMED_MINUTES) +
                    BREATHER_MINUTES,
            );
        }
    }

    return blocks.map((block) => {
        const start = moved.get(block.id);

        return start === undefined
            ? block
            : { ...block, startMinute: start, exact: true };
    });
}

/** Die Gewohnheiten, die beim Zug mitrutschen — ohne die gezogene selbst. */
export function followersOf(
    blocks: CalendarBlock[],
    draggedId: number,
    minute: number,
): { title: string; minute: number }[] {
    const before = new Map(
        blocks.map((block) => [block.id, block.startMinute]),
    );

    return withDrag(blocks, draggedId, minute)
        .filter(
            (block) =>
                block.id !== draggedId &&
                block.startMinute !== before.get(block.id),
        )
        .map((block) => ({
            title: block.title,
            minute: block.startMinute as number,
        }));
}

/** Was im Weg liegt: sein Titel und ob es sich überhaupt bewegen lässt. */
export interface BlockConflict {
    title: string;
    kind: 'habit' | 'course';
    /** Wo das Hindernis liegt — für den Satz, bis wann und ab wann Platz ist. */
    from: number;
    to: number;
}

/**
 * Was dem Zug an diesem Tag im Weg liegt — oder nichts.
 *
 * Geprüft wird mit der Viertelstunde Luft, genau wie in
 * `DayPlan::collisionWith()`: Ein Block braucht davor und danach Platz zum
 * Atmen. Die Antwort steht dadurch sofort im Pop-up, statt erst nach einem
 * Rundweg über den Server.
 *
 * Die Art kommt mit, weil sie den Ausweg bestimmt: Eine Gewohnheit lässt sich
 * verschieben, eine Vorlesung nicht — sie kommt von der Uni.
 */
export function collisionOf(
    blocks: CalendarBlock[],
    courses: CourseBlock[],
    draggedId: number,
    minute: number,
): BlockConflict | null {
    const after = withDrag(blocks, draggedId, minute);
    const before = new Map(
        blocks.map((block) => [block.id, block.startMinute]),
    );

    const moving = after.filter(
        (block) =>
            block.id === draggedId ||
            block.startMinute !== before.get(block.id),
    );
    // Kurse ruhen immer: Sie ziehen nicht mit und weichen nicht aus. Sie
    // stehen hier aus demselben Grund, aus dem `DayPlan::occupied()` die
    // Fremdblöcke mitzählt — sonst sagte das Pop-up „frei", und der Server
    // wiese den Zug danach ab.
    const resting: GridBlock[] = [
        ...after.filter(
            (block) => !moving.some((other) => other.id === block.id),
        ),
        ...courses,
    ];

    for (const block of moving) {
        const from = block.startMinute as number;
        const to = from + (block.durationMinutes ?? ASSUMED_MINUTES);

        for (const other of resting) {
            if (other.startMinute === null) {
                continue;
            }

            const otherTo =
                other.startMinute + (other.durationMinutes ?? ASSUMED_MINUTES);

            if (
                from < otherTo + BREATHER_MINUTES &&
                to + BREATHER_MINUTES > other.startMinute
            ) {
                return {
                    title: other.title,
                    kind: other.kind,
                    from: other.startMinute,
                    to: otherTo,
                };
            }
        }
    }

    return null;
}
