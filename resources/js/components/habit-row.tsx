import { Check, Undo2 } from 'lucide-react';
import { AiMascot } from '@/components/ai-mascot';
import { HabitGlyph } from '@/components/habit-glyph';
import { PersonCircle } from '@/components/person-circle';
import { useSwipeToggle } from '@/hooks/use-swipe-toggle';
import { AI_LINK, QUIET_LINK } from '@/lib/interaction';
import { cn } from '@/lib/utils';
import type { Habit } from '@/types';

/**
 * Habit-Zeile nach Designsprache §5.2.
 *
 * Die Icon-Kachel wechselt von eckig-hell zu rund-gefüllt: Form und Farbe
 * ändern sich gemeinsam, damit der Zustand auch ohne Farbwahrnehmung
 * unterscheidbar bleibt. Der offene Kreis ist gestrichelt („wartet"), nie
 * leer-durchgezogen („fehlt"). Kein Durchstreichen, kein Ausgrauen.
 *
 * **Die Uhrzeit steht links in einer eigenen Spalte.** Vorher lief sie in
 * einer Kette aus Punkten mit: „mit Test2 · 09:00 · nur an diesem Tag · 90
 * Min" — vier verschiedene Auskünfte in einem Gewicht. Wer zwei gleichnamige
 * Gewohnheiten am selben Tag hat, konnte sie darin nicht auseinanderhalten.
 * Die Liste ist ohnehin nach der Stunde sortiert; die Spalte macht diese
 * Ordnung sichtbar und liest den Tag als Plan von früh nach spät — dieselbe
 * Stundenspalte wie im Kalender. Sie bleibt leer, wo es keine Uhr gibt („nach
 * dem Aufstehen"): Eine abgeleitete Stunde dorthin zu schreiben wäre eine
 * Festlegung, die niemand getroffen hat.
 */
export function HabitRow({
    habit,
    selfInitial,
    onToggle,
    onStuck,
    onAskCompany,
    onWithdraw,
    onRepeat,
    highlighted = false,
}: {
    habit: Habit;
    /** Die eigene Initiale — die linke Hälfte des Doppel-Zeichens aus §3.2. */
    selfInitial: string;
    onToggle: (habit: Habit) => void;
    onStuck: (habit: Habit) => void;
    /** Null blendet den Weg zur Verabredung aus — Screen A5. */
    onAskCompany: ((habit: Habit) => void) | null;
    /**
     * Die Verabredung wieder auflösen — zurückziehen oder absagen.
     *
     * Sie steht jetzt in dieser Zeile statt in einer eigenen Karte, also muss
     * auch der Weg zurück hier liegen. Null, wo es ihn nicht gibt.
     */
    onWithdraw: ((habit: Habit) => void) | null;
    /**
     * Dieselbe Person nochmal fragen — `community_feature3.md` §7.
     *
     * Der ganze Wiederholungs-Mechanismus des Features: jedes Mal eine neue
     * Einzelentscheidung, nie ein Abo. Null blendet ihn aus.
     */
    onRepeat: ((habit: Habit) => void) | null;
    /**
     * Kurz betont, nachdem eine Absage hierher verwiesen hat.
     *
     * Kein Zustand der Gewohnheit, sondern eine Antwort auf einen Klick: Wer
     * „Mach ich trotzdem" tippt, soll sehen, welche Zeile gemeint ist.
     */
    highlighted?: boolean;
}) {
    const isDone = habit.completedAt !== null;
    const companion = habit.companion;

    /**
     * §7 — was nach rechts hinausgeht, kommt von rechts zurück. Abgehakt wird
     * nach rechts, zurückgenommen nach links; die Richtung benennt also, was
     * passiert, und die Rücknahme ist der sichtbare Umkehrweg.
     *
     * Der Knopf bleibt der eigentliche Bedienweg — die Geste ist eine Zugabe
     * für den Daumen und wird von Tastatur und Vorlesehilfe nicht gebraucht.
     */
    const {
        offset: swipeOffset,
        threshold: swipeThreshold,
        dragging: swiping,
        attach: swipeRow,
        onPointerDown,
        onClickCapture,
    } = useSwipeToggle({
        direction: isDone ? 'left' : 'right',
        onCommit: () => onToggle(habit),
    });

    /** Wie weit die Fläche hinter der Zeile freiliegt, 0…1. */
    const revealed = Math.min(Math.abs(swipeOffset) / swipeThreshold, 1);

    /**
     * Die Nebenzeile — alles außer der Uhrzeit, die links in der Spalte steht.
     *
     * Die Reihenfolge ist die der Fragen: mit wem, wie oft, wie lange.
     */
    const subtitle = isDone
        ? `Abgeschlossen · ${habit.completedAt} Uhr`
        : [
              companion &&
                  (companion.pending
                      ? `${companion.name} ist gefragt`
                      : `mit ${companion.name}`),
              habit.repeatLabel,
              habit.measureLabel,
          ]
              .filter(Boolean)
              .join(' · ');

    return (
        <li
            id={`habit-${habit.id}`}
            className={cn(
                'rounded-2xl transition-shadow duration-300',
                highlighted &&
                    'ring-2 ring-primary ring-offset-4 ring-offset-card',
            )}
        >
            <div className="relative overflow-hidden rounded-2xl">
                {/* Was hinter der Zeile liegt und beim Ziehen freigegeben
                    wird. §8: Die Zwischenbilder sollen schon zeigen, worauf
                    die Bewegung hinausläuft — das Zeichen wächst mit der
                    Geste und steht erst voll da, wenn Loslassen auslöst.

                    Seite und Zeichen hängen an der Auslenkung, nicht am
                    Zustand der Gewohnheit: Beim Auslösen kippt der Zustand
                    sofort, die Zeile federt aber noch zurück. Hinge die
                    Fläche am Zustand, würde sie mitten in der Bewegung die
                    Seite wechseln und die Lücke bliebe leer. */}
                <span
                    aria-hidden="true"
                    className={cn(
                        // Das Zeichen sitzt an der Außenkante, nicht in der
                        // Mitte der Fläche: Beim Vorführen liegen nur ~26px
                        // frei, und ein zentriertes Zeichen bliebe darin
                        // unsichtbar — die Vorführung zeigte dann nur Farbe.
                        'absolute inset-y-0 flex w-24 items-center',
                        swipeOffset >= 0
                            ? 'left-0 justify-start bg-primary pl-3 text-primary-foreground'
                            : 'right-0 justify-end bg-sand pr-3 text-primary',
                    )}
                    style={{ opacity: Math.min(revealed * 1.6, 1) }}
                >
                    {/* Nur das Zeichen wächst — die Fläche selbst bleibt
                        stehen, sonst schrumpfte die Farbe mit. */}
                    <span
                        style={{
                            transform: `scale(${0.7 + revealed * 0.3})`,
                        }}
                    >
                        {swipeOffset >= 0 ? (
                            <Check className="size-5" strokeWidth={2.5} />
                        ) : (
                            <Undo2 className="size-5" strokeWidth={2} />
                        )}
                    </span>
                </span>

                <div
                    ref={swipeRow}
                    onPointerDown={onPointerDown}
                    onClickCapture={onClickCapture}
                    className={cn(
                        // `touch-pan-y` überlässt das senkrechte Scrollen dem
                        // Browser und behält nur die Waagerechte für uns.
                        //
                        // Der Greif-Zeiger bleibt, auch wenn die Vorführung
                        // längst aufgehört hat: Er kostet nichts, steht immer
                        // da und sagt dasselbe wie die Bewegung.
                        'relative flex cursor-grab touch-pan-y flex-col gap-2 bg-card',
                        swiping && 'cursor-grabbing select-none',
                    )}
                    style={{
                        transform: `translate3d(${swipeOffset}px, 0, 0)`,
                    }}
                >
                    <div className="flex items-center gap-3">
                        {/* Die Uhrspalte. Feste Breite, damit die Zeilen
                            untereinander eine Achse bilden — sonst wandert der
                            Titel mit der Länge der Uhrzeit und die Liste liest
                            sich nicht mehr als Plan. Tabellenziffern halten die
                            Spalte auch dann ruhig, wenn 09:00 über 15:00 steht.

                            Leer, wo es keine Uhr gibt: Der Zeitpunkt („nach dem
                            Aufstehen") steht dann in der Nebenzeile. Die Lücke
                            ist die Aussage — diese Gewohnheit hängt an einer
                            Situation, nicht an einer Stunde. */}
                        <span
                            aria-hidden={habit.timeLabel === null}
                            className="w-10 shrink-0 text-right text-[13px] leading-none font-semibold text-muted-foreground tabular-nums"
                        >
                            {habit.timeLabel}
                        </span>

                        {/* §3.2 — die dritte Ausprägung der Icon-Kachel: zwei Kreise
                    statt einem. Kein neues Element, keine neue Farbe, kein
                    neues Symbol. Form *und* Anzahl unterscheiden sich, die
                    Information hängt also nicht an der Farbe. */}
                        {companion !== null && !isDone ? (
                            <span
                                className="flex w-11 shrink-0 justify-center -space-x-1"
                                aria-label={
                                    companion.pending
                                        ? `${companion.name} ist gefragt`
                                        : `Zusammen mit ${companion.name}`
                                }
                            >
                                <PersonCircle
                                    initial={selfInitial}
                                    className="size-6 text-[10px]"
                                />
                                {/* Gestrichelt, solange die Antwort aussteht —
                                    dieselbe Bedeutung wie beim offenen
                                    Habit-Kreis (§7.3). Die Initiale steht erst
                                    da, wenn zugesagt ist: Vorher wäre sie eine
                                    Behauptung über jemanden, der noch nichts
                                    gesagt hat. */}
                                {companion.pending ? (
                                    <PersonCircle
                                        pending
                                        className="size-6 bg-card text-[10px] ring-2 ring-background"
                                    />
                                ) : (
                                    <PersonCircle
                                        initial={companion.initial}
                                        className="size-6 text-[10px] ring-2 ring-background"
                                    />
                                )}
                            </span>
                        ) : (
                            /* Die Kachel wechselt nicht, sie verformt sich: Radius und
                       Farbe laufen gemeinsam auf der Grundkurve. Ein harter
                       Umschlag läse sich als Austausch, ein Übergang als
                       derselbe Gegenstand in einem anderen Zustand. */
                            <span
                                className={cn(
                                    'flex size-11 shrink-0 items-center justify-center transition-[background-color,color,border-radius] duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                    isDone
                                        ? 'rounded-full bg-primary text-primary-foreground'
                                        : 'rounded-xl bg-sand text-primary',
                                )}
                            >
                                <HabitGlyph habit={habit} className="size-5" />
                            </span>
                        )}

                        <span className="min-w-0 flex-1">
                            <span
                                className={cn(
                                    // Der Titel bricht um, statt abgeschnitten
                                    // zu werden: Er ist der Name der Sache. Die
                                    // Uhrspalte nimmt schmalen Bildschirmen
                                    // Platz weg, und „Tagebuch schrei…" nennt
                                    // die Gewohnheit nicht mehr. Die Nebenzeile
                                    // darunter darf kürzen — sie beschreibt nur.
                                    'block text-[15px] leading-snug font-semibold break-words transition-colors duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                    // §2.3 empfiehlt für erledigte Titel `olive-mid` statt
                                    // Gold — Gold erreicht auf `surface` nur 2.46:1.
                                    isDone
                                        ? 'text-olive-mid'
                                        : 'text-foreground',
                                )}
                            >
                                {habit.title}
                            </span>
                            <span className="mt-0.5 block truncate text-xs text-muted-foreground">
                                {subtitle}
                            </span>
                        </span>

                        {/* Die Fläche ist 44px groß, damit sie als Tippziel taugt; der
                sichtbare Kreis bleibt bei 28px wie im Mockup. */}
                        <button
                            type="button"
                            onClick={() => onToggle(habit)}
                            aria-pressed={isDone}
                            aria-label={
                                isDone
                                    ? `${habit.title} als noch offen markieren`
                                    : `${habit.title} als erledigt markieren`
                            }
                            className="group/check flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full transition-colors duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            {/* §1 — der Druckpunkt sitzt auf `:active`, also auf dem
                        Drücken, nicht auf dem Loslassen. Eigene Ebene, weil
                        er in 110ms fällt, während der Zustand darunter in
                        einer halben Sekunde umschlägt. */}
                            <span className="transition-transform duration-[var(--duration-press)] ease-out motion-safe:group-active/check:scale-90">
                                <span
                                    className={cn(
                                        'flex size-7 items-center justify-center rounded-full transition-[background-color,border-color] duration-[var(--duration-fluid)] ease-[var(--ease-fluid)]',
                                        isDone
                                            ? 'bg-primary'
                                            : 'border-2 border-dashed border-sand',
                                    )}
                                >
                                    {/* §4 — Überschwingen nur da, wo etwas einrastet.
                                Das Häkchen ist der eine Moment auf dieser
                                Seite, an dem eine Handlung greift; es federt
                                minimal über 1 hinaus und setzt sich. */}
                                    {isDone && (
                                        <Check
                                            className="size-4 text-primary-foreground duration-[var(--duration-pop)] ease-[var(--ease-pop)] motion-safe:animate-in motion-safe:zoom-in-50"
                                            strokeWidth={2.5}
                                            aria-hidden="true"
                                        />
                                    )}
                                </span>
                            </span>
                        </button>
                    </div>

                    {/* Der vorbereitete Handgriff und der Weg ins Starthilfe-Sheet
                gehören zum offenen Zustand. Bei einer erledigten Gewohnheit
                verschwinden beide — es gibt nichts mehr anzustoßen, und ein
                stehengebliebener Anstoß läse sich wie eine Nachforderung.

                Eingerückt auf Höhe des Titels, damit die Zeile als Fortsetzung
                der Gewohnheit gelesen wird und nicht als eigener Eintrag. */}
                    {/* Der erledigte Zustand ist sonst bewusst leer — ein
                        stehengebliebener Anstoß läse sich wie eine
                        Nachforderung. Hier fordert nichts nach: Es ist das
                        Angebot, etwas zu wiederholen, das gerade gelungen ist,
                        und es steht nur, wenn wirklich jemand dabei war. §7
                        nennt das den ganzen Wiederholungs-Mechanismus —
                        „immer als neue Einzelentscheidung, nie als Abo". */}
                    {isDone &&
                        companion !== null &&
                        companion.repeatHabitId !== null &&
                        onRepeat !== null && (
                            <div className="pl-[3.25rem]">
                                {/* Mit Namen, anders als in der Karte unter
                                    „Zusammen": Dort steht das Doppel-Zeichen
                                    daneben, hier ist es im erledigten Zustand
                                    dem gefüllten Haken gewichen. „Nochmal
                                    ausmachen?" allein sagte nicht, mit wem —
                                    und die Vorwahl im Sheet käme dann
                                    unangekündigt. */}
                                <button
                                    type="button"
                                    onClick={() => onRepeat(habit)}
                                    className={`${QUIET_LINK} text-xs`}
                                >
                                    Nochmal mit {companion.name}?
                                </button>
                            </div>
                        )}

                    {!isDone && (
                        <div className="flex flex-col gap-1.5 pl-[3.25rem]">
                            {/* Der vorbereitete Schritt steht für sich, die
                                Angebote darunter. Vorher lagen alle drei in
                                einer umbrechenden Reihe: Ein längerer Schritt
                                schob die Knöpfe in die nächste Zeile, und je
                                nach Satzlänge stand die Reihe mal so und mal
                                so. Zwei feste Zeilen sind ruhiger — der Satz
                                sagt, was zu tun ist, die Zeile darunter, was
                                man sonst noch kann. */}
                            {habit.smallestStep && (
                                <span className="text-xs leading-relaxed text-muted-foreground">
                                    → {habit.smallestStep}
                                </span>
                            )}

                            {/* Der Weg zur Verabredung steht neben der Starthilfe:
                        beides sind Angebote für denselben Moment, in dem eine
                        Gewohnheit noch offen ist. Er verschwindet, sobald
                        jemand mitmacht — eine zweite Person pro Verabredung
                        ist die Obergrenze (§4). */}
                            <div className="flex flex-wrap items-center gap-x-4 gap-y-1">
                                {onAskCompany !== null &&
                                    companion === null && (
                                        <button
                                            type="button"
                                            onClick={() => onAskCompany(habit)}
                                            className={`${QUIET_LINK} text-xs`}
                                        >
                                            Mit jemandem zusammen?
                                        </button>
                                    )}
                                {/* Der Rückweg steht an der Stelle, an der
                                    eben noch der Hinweg stand — und im selben
                                    Ton wie die Wege daneben. Er war vorher
                                    grau, damit die Korrektur nicht wie ein
                                    Vorschlag aussieht; in einer Zeile mit zwei
                                    olivfarbenen Knöpfen las sich das aber als
                                    zweite Art von Element und nicht als
                                    Zurückhaltung. Was ihn weiterhin von einem
                                    Angebot unterscheidet, ist sein Wort. */}
                                {companion !== null && onWithdraw !== null && (
                                    <button
                                        type="button"
                                        onClick={() => onWithdraw(habit)}
                                        className={`${QUIET_LINK} text-xs`}
                                    >
                                        {companion.pending
                                            ? 'Zurückziehen'
                                            : 'Absagen'}
                                    </button>
                                )}
                                {/* Derselbe Wortlaut wie im Kalender: Es ist
                            dieselbe Hilfe, und zwei Namen dafür wären zwei
                            Angebote. Die Figur davor steht für die KI dahinter. */}
                                <button
                                    type="button"
                                    onClick={() => onStuck(habit)}
                                    className={`${AI_LINK} text-xs`}
                                >
                                    <AiMascot
                                        variant="mark"
                                        className="size-4 shrink-0"
                                    />
                                    Kleinen ersten Schritt
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </li>
    );
}
