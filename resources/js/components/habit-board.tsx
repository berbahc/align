import { Link } from '@inertiajs/react';
import { Bell, CircleCheck, Info, MoreHorizontal, Pencil } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { HabitGlyph } from '@/components/habit-glyph';
import { RhythmMark, rhythmState } from '@/components/rhythm-strip';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { LEAD_MINUTES } from '@/hooks/use-habit-reminders';
import { cn } from '@/lib/utils';
import { edit } from '@/routes/habits';
import type { HabitGroup, ManagedHabit, RhythmDay } from '@/types';

/**
 * Die Spaltenbreiten des Blatts — eine Quelle für Achse, Zeilen und Trenner.
 *
 * Alle drei bauen ihre Zeile aus denselben Kästen. Stünde eine Breite an drei
 * Stellen, liefen Achse und Marken irgendwann ein paar Pixel auseinander — und
 * eine Achse, die nicht über ihrer Spalte sitzt, ist schlimmer als keine.
 */
/**
 * „Mittwoch, 3. September" — für die Vorlesehilfe.
 *
 * Die Marke zeigt nur einen Ton, der Knopf muss sagen, welchen Tag er meint.
 * `RhythmDay.label` trägt bloß „Mi": genug als Spaltenkopf, nicht als Ansage.
 */
function dayName(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('de-DE', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
}

const DAY_CELL = 'w-[1.125rem] sm:w-7';
const DAY_MARK = 'size-3.5 sm:size-5';
const BALANCE_COLUMN = 'w-28 lg:w-32';
const MENU_COLUMN = 'w-10 sm:w-11';
const ROW = 'flex gap-2 sm:gap-3';

/**
 * Das Band des heutigen Tages.
 *
 * `track` und nicht `accent`: Auf `accent` läge die offene Marke (`sand`) nur
 * noch ΔL* 4 über ihrem Grund und verschwände genau in der Spalte, auf die man
 * schaut. Die Kante gehört dazu — ohne sie ist es ein Fleck, mit ihr eine
 * Spalte.
 */
const BAND = 'border-x border-border/70 bg-track';

/**
 * Die zwei Zahlen der Konsistenz, das Zeichen dahinter und die Serie.
 *
 * Steht zweimal im Baum, weil sie zweimal woanders hingehört: auf breiten
 * Schirmen als eigene Spalte rechts, auf dem Telefon als Zeile unter dem Namen.
 * Beide Male derselbe Auslöser desselben Kastens — ein Aufklappen, egal wo
 * geklickt wurde.
 */
function Balance({
    habit,
    explaining,
    className,
}: {
    habit: ManagedHabit;
    explaining: boolean;
    className?: string;
}) {
    /* Die Erklärung steht dort, wo die Frage entsteht: „22 Tage wovon?" fragt
       man an der Zahl, nicht am Seitenkopf. Aufklappen statt Tooltip, weil es
       auf dem Handy kein Hover gibt — derselbe Weg wie bei
       {@see HabitLimitNote}. */
    const trigger = habit.consistency !== null && (
        <CollapsibleTrigger className="ml-1 inline-flex size-6 -translate-y-px cursor-pointer items-center justify-center rounded-full align-middle text-muted-foreground transition-colors duration-[var(--duration-press)] ease-out hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring">
            <Info className="size-3.5" aria-hidden="true" />
            <span className="sr-only">
                {explaining
                    ? 'Erklärung ausblenden'
                    : `Was zählt die Zahl bei ${habit.title}?`}
            </span>
        </CollapsibleTrigger>
    );

    return (
        <p className={cn('text-[11px] text-muted-foreground', className)}>
            {/* **Am ersten Tag steht dort das Anfangsdatum.** „1 von 1" ist
                keine Auskunft, sondern eine Tautologie: Es gab eine
                Gelegenheit, du hast sie genutzt. Der gefüllte Punkt im Blatt
                sagt es ohnehin schon. Leer bleiben darf die Zeile trotzdem
                nicht — hinter dem Zeichen liegt der Kasten mit dem
                Anfangsdatum, und genau danach fragt man am ersten Tag. */}
            {habit.consistency !== null && habit.consistency.scheduled > 1 ? (
                <>
                    {/* Zahl und Zeichen bleiben zusammen. In einer 112 Pixel
                        schmalen Spalte brach das Zeichen sonst allein auf eine
                        weitere Zeile und stand dort wie ein vergessener
                        Punkt. */}
                    <span className="font-semibold text-foreground tabular-nums">
                        {habit.consistency.done} von{' '}
                        {habit.consistency.scheduled}
                    </span>{' '}
                    <span className="whitespace-nowrap">
                        {habit.consistency.sinceStart
                            ? 'Tagen seit dem Start'
                            : 'Tagen'}
                        {trigger}
                    </span>
                </>
            ) : habit.startedOn !== null ? (
                <>
                    Angefangen am{' '}
                    <span className="font-semibold whitespace-nowrap text-foreground tabular-nums">
                        {habit.startedOn}
                        {trigger}
                    </span>
                </>
            ) : (
                trigger
            )}

            {/* Zwei Zeitachsen, die einander nicht wiederholen: Das Blatt zeigt
                die Woche und lässt sich abzählen, die Konsistenz blickt über
                dreißig Tage und springt bei einem Fehltag nicht. Daneben die
                Serie, die {@see Habit::consistencyRate()} „den Antrieb" nennt
                und die Rate „den ehrlicheren Blick".

                Eigene Zeile statt „ · " dahinter: In einer 112 Pixel schmalen
                Spalte bräche der Punkt an einer beliebigen Stelle um. */}
            {habit.streak !== null && (
                <span className="mt-0.5 block text-[10px] text-faintest">
                    {habit.streak}
                </span>
            )}
        </p>
    );
}

/**
 * Eine Zeile des Blatts: links die Gewohnheit, rechts ihre sieben Tage.
 *
 * Hier geht es um die Gewohnheit an sich, nicht um den heutigen Tag: Verlauf
 * ablesen, Erinnerung setzen, beenden. Abgehakt wird auf der Übersicht.
 */
function BoardRow({
    habit,
    bandBottom,
    tinted,
    highlighted,
    onToggleReminder,
    onToggleDay,
    onEnd,
}: {
    habit: ManagedHabit;
    /** Ob das Band des heutigen Tages hier endet — nur die letzte Zeile rundet. */
    bandBottom: boolean;
    /** Wahr im Abschnitt „Braucht einen neuen Platz". */
    tinted: boolean;
    /**
     * Kurz betont, nachdem die Gewohnheit gerade hier gelandet ist.
     *
     * Dieselbe Antwort wie auf der Übersicht: Wer etwas beendet oder wieder
     * aufnimmt, soll sehen, wo es hingewandert ist, statt es suchen zu müssen.
     */
    highlighted: boolean;
    onToggleReminder: (habit: ManagedHabit, enabled: boolean) => void;
    /** Einen Tag der Woche abhaken oder zurücknehmen. */
    onToggleDay: (habit: ManagedHabit, day: RhythmDay) => void;
    onEnd: (habit: ManagedHabit) => void;
}) {
    // Ob der Erklärkasten unter dieser Zeile offen ist.
    const [explaining, setExplaining] = useState(false);

    // Der letzte Eintrag ist immer heute: `weekOverview()` zählt von `until`
    // rückwärts und liefert die Reihe in zeitlicher Ordnung.
    const todayIndex = habit.rhythm.length - 1;
    const scheduled = habit.rhythm.filter((day) => day.scheduled).length;
    const done = habit.rhythm.filter((day) => day.completed).length;

    return (
        <li
            id={`managed-habit-${habit.id}`}
            className={cn(
                'transition-colors duration-[var(--duration-press)] ease-out',
                // Was seinen Platz verloren hat, wartet auf eine Entscheidung.
                // Accent statt Rot: Hier ist nichts schiefgegangen, hier fehlt
                // eine Wahl (§1.4).
                tinted ? 'bg-accent' : 'hover:bg-accent/40',
                // Kein Ring mit Abstand: Auf einem Blatt aus Haarlinien säße er
                // zwischen zwei Zeilen und sähe aus wie eine dritte.
                highlighted && 'outline-2 -outline-offset-2 outline-primary',
            )}
        >
            <Collapsible open={explaining} onOpenChange={setExplaining}>
                <div className={cn(ROW, 'items-stretch')}>
                    <div className="flex min-w-0 flex-1 items-center gap-2 py-2.5 sm:gap-3">
                        <span className="hidden size-10 shrink-0 items-center justify-center rounded-xl bg-sand text-primary sm:flex">
                            <HabitGlyph habit={habit} className="size-5" />
                        </span>

                        <span className="min-w-0">
                            {/* Umbrechen statt abschneiden: Auf dem Telefon
                                bleiben der Namensspalte rund 140 Pixel, und
                                „Karteikarten wied…" ist kein Name. Zwei Zeilen
                                sind hier die ehrlichere Kürzung. */}
                            <span className="block text-[15px] leading-snug font-semibold max-sm:line-clamp-2 sm:truncate">
                                {habit.title}
                            </span>

                            <span className="mt-0.5 flex min-w-0 items-baseline gap-1.5 text-xs text-muted-foreground">
                                {/* Die Glocke schaltet nichts, sie sagt nur,
                                    dass etwas eingestellt ist — ohne sie wäre
                                    der Zustand hinter dem ⋯-Menü unsichtbar. */}
                                {habit.canRemind && habit.reminderEnabled && (
                                    <Bell
                                        className="size-3 shrink-0 -translate-y-px"
                                        strokeWidth={1.75}
                                        aria-label="Erinnerung ist an"
                                    />
                                )}

                                {/* **Kein „morgen · " davor.** Der nächste
                                    Termin führte die Zeile einmal an, weil das
                                    Blatt nach ihm sortiert ist. Er ist aber
                                    dieselbe Auskunft, die die Übersicht gibt:
                                    ein Blick auf die nächsten Stunden. Hier
                                    zählt der Plan, und den nennt die Zeile
                                    ohnehin — „Mo, Di, Do–Sa" sagt, an welchen
                                    Tagen sie läuft, und die Spalte des heutigen
                                    Tages sagt, ob heute einer davon ist. */}
                                <span className="truncate">
                                    {habit.scheduleLabel}
                                </span>

                                {habit.measureLabel !== null && (
                                    <>
                                        <span
                                            aria-hidden="true"
                                            className="max-sm:hidden"
                                        >
                                            ·
                                        </span>
                                        <span className="shrink-0 max-sm:hidden">
                                            {habit.measureLabel}
                                        </span>
                                    </>
                                )}
                            </span>

                            {/* Auf dem Telefon reicht die Breite für keine
                                vierte Spalte. Die Bilanz rutscht unter den
                                Namen statt zu verschwinden — und bleibt dabei
                                **innerhalb** der Zeile, damit die Spalte des
                                heutigen Tages nicht zwischen zwei Zeilen
                                abreißt. */}
                            <Balance
                                habit={habit}
                                explaining={explaining}
                                className="mt-1 sm:hidden"
                            />
                        </span>
                    </div>

                    {/* Die Woche ist nicht nur Auskunft, sondern der kürzeste
                        Weg zum häufigsten Fall: Wer gestern vergessen hat, sieht
                        die Lücke hier — und musste dafür bisher über Kalender,
                        Tag und Haken gehen. Ein Tag, der anstand, lässt sich
                        deshalb antippen.

                        Aus `role="img"` wird eine Gruppe: Solange die Marken
                        nichts konnten, war es richtig, sie nicht einzeln
                        vorzulesen — sieben Tage nacheinander sind eine Litanei.
                        Was sich bedienen lässt, muss sich aber auch ansagen
                        lassen. Der Satz der Gruppe bleibt die Zusammenfassung,
                        die Knöpfe darin nennen ihren eigenen Tag. */}
                    <div
                        role="group"
                        aria-label={
                            scheduled === 0
                                ? `${habit.title}: stand in den letzten sieben Tagen nicht an`
                                : `${habit.title}: in der letzten Woche an ${done} von ${scheduled} Tagen erledigt`
                        }
                        className="flex shrink-0"
                    >
                        {habit.rhythm.map((day, index) => {
                            const mark = (
                                <RhythmMark
                                    state={rhythmState(day)}
                                    size={DAY_MARK}
                                    // Der Einzug läuft von links nach rechts
                                    // durch die Woche — und weil alle Zeilen
                                    // dieselbe Achse haben, füllt sich das
                                    // ganze Blatt in einer Welle statt in fünf
                                    // getrennten Streifen.
                                    className="motion-safe:animate-in motion-safe:fill-mode-backwards motion-safe:zoom-in-75 motion-safe:fade-in"
                                    style={{
                                        animationDuration:
                                            'var(--duration-fluid)',
                                        animationTimingFunction:
                                            'var(--ease-fluid)',
                                        animationDelay: `${index * 40}ms`,
                                    }}
                                />
                            );

                            const cell = cn(
                                'flex items-center justify-center',
                                DAY_CELL,
                                index === todayIndex && [
                                    BAND,
                                    // Nur ganz unten gerundet. Rundete jeder
                                    // Abschnitt sein eigenes Ende, zerfiele die
                                    // Spalte in Kapseln — und eine Kapsel neben
                                    // einer Kapsel sieht aus wie zwei Sachen,
                                    // nicht wie ein Tag.
                                    bandBottom && 'rounded-b-lg border-b',
                                ],
                            );

                            // Nur was anstand, lässt sich abhaken. An einem
                            // Tag ohne Vorsehung gäbe es nichts nachzutragen —
                            // der Server weist ihn ohnehin ab, und ein Knopf,
                            // der das erst hinterher sagt, ist kein Angebot.
                            if (!day.scheduled) {
                                return (
                                    <span
                                        key={day.date}
                                        aria-hidden="true"
                                        className={cell}
                                    >
                                        {mark}
                                    </span>
                                );
                            }

                            return (
                                <button
                                    key={day.date}
                                    type="button"
                                    aria-pressed={day.completed}
                                    aria-label={`${habit.title} am ${dayName(day.date)}: ${
                                        day.completed
                                            ? 'erledigt, antippen zum Zurücknehmen'
                                            : 'offen, antippen zum Nachtragen'
                                    }`}
                                    onClick={() => onToggleDay(habit, day)}
                                    className={cn(
                                        cell,
                                        'cursor-pointer transition-[background-color,scale] duration-[var(--duration-press)] ease-out',
                                        'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
                                        'motion-safe:active:scale-[0.88]',
                                        index !== todayIndex &&
                                            'hover:bg-accent/60',
                                    )}
                                >
                                    {mark}
                                </button>
                            );
                        })}
                    </div>

                    <div
                        className={cn(
                            'hidden shrink-0 items-center justify-end text-right max-sm:hidden sm:flex',
                            BALANCE_COLUMN,
                        )}
                    >
                        <Balance habit={habit} explaining={explaining} />
                    </div>

                    <div
                        className={cn(
                            'flex shrink-0 items-center justify-end',
                            MENU_COLUMN,
                        )}
                    >
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="size-11 shrink-0 cursor-pointer text-muted-foreground transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-accent motion-safe:active:scale-[0.94] max-sm:-mr-1"
                                >
                                    <MoreHorizontal
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                    <span className="sr-only">
                                        Aktionen für {habit.title}
                                    </span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                {/* Nur wo es etwas zu erinnern gibt. Ein
                                    Eintrag, der dauerhaft aus und nicht
                                    bedienbar ist, sieht aus wie ein Angebot und
                                    ist keins — warum es ihn für manche
                                    Gewohnheiten nicht gibt, steht einmal am
                                    Seitenende (§1.5: benannt wird, was gilt). */}
                                {habit.canRemind && (
                                    <>
                                        <DropdownMenuCheckboxItem
                                            checked={habit.reminderEnabled}
                                            // Das Menü bleibt beim Schalten
                                            // offen: Der Haken ist die
                                            // Rückmeldung, und ein Menü, das im
                                            // selben Moment zuklappt, verbirgt
                                            // sie.
                                            onSelect={(event) =>
                                                event.preventDefault()
                                            }
                                            onCheckedChange={(enabled) =>
                                                onToggleReminder(habit, enabled)
                                            }
                                            className="cursor-pointer"
                                        >
                                            Erinnerung {LEAD_MINUTES} Min vorher
                                        </DropdownMenuCheckboxItem>
                                        <DropdownMenuSeparator />
                                    </>
                                )}

                                {/* Der einzige Weg zum Bearbeiten in der App:
                                    Hier geht es um die Gewohnheit an sich, auf
                                    der Übersicht um den heutigen Tag. */}
                                <DropdownMenuItem
                                    asChild
                                    className="cursor-pointer"
                                >
                                    <Link href={edit(habit.id)}>
                                        <Pencil aria-hidden="true" />
                                        Bearbeiten
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    className="cursor-pointer"
                                    onSelect={() => onEnd(habit)}
                                >
                                    <CircleCheck aria-hidden="true" />
                                    Beenden
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                {/* Mit den echten Zahlen dieser Gewohnheit statt allgemein.
                    Wer hier klickt, will wissen, was die zwei Zahlen bedeuten,
                    die er gerade vor sich hat. */}
                {habit.consistency !== null && (
                    <CollapsibleContent>
                        <div className="mb-3 rounded-xl border border-dashed bg-card px-4 py-3 sm:ml-13">
                            <p className="text-[13px] font-semibold">
                                Was die Zahl bedeutet
                            </p>
                            <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                                {habit.consistency.sinceStart
                                    ? 'Seit du '
                                    : 'In den letzten 30 Tagen stand '}
                                <span className="font-semibold text-foreground">
                                    {habit.title}
                                </span>{' '}
                                {habit.consistency.sinceStart
                                    ? 'angelegt hast, stand sie an '
                                    : 'an '}
                                <span className="font-semibold text-foreground tabular-nums">
                                    {habit.consistency.scheduled}
                                </span>{' '}
                                Tagen an. An{' '}
                                <span className="font-semibold text-foreground tabular-nums">
                                    {habit.consistency.done}
                                </span>{' '}
                                davon hast du sie erledigt.
                            </p>

                            {/* Das Blatt darüber zeigt schon, welche Tage nicht
                                mitzählen. Ein Beispiel („bei einer
                                Mo–Fr-Gewohnheit also keine Wochenenden") stand
                                auch über einer täglichen Gewohnheit und
                                erklärte dort nichts. */}
                            <p className="mt-2 text-xs leading-relaxed text-muted-foreground">
                                Tage, an denen sie nicht anstand, zählen nicht
                                mit.
                            </p>

                            {/* Der Anfang steht hier, weil er sonst nirgends
                                steht: Das Archiv nennt „Beendet am", eine
                                laufende Gewohnheit sagte bis hierher nicht,
                                seit wann es sie gibt.

                                Nicht, solange die Zeile draußen ihn ohnehin
                                nennt. Zweimal dasselbe Datum liest sich wie
                                zwei verschiedene Angaben. */}
                            {habit.startedOn !== null &&
                                habit.consistency.scheduled > 1 && (
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        Angefangen am{' '}
                                        <span className="font-semibold text-foreground tabular-nums">
                                            {habit.startedOn}
                                        </span>
                                    </p>
                                )}
                        </div>
                    </CollapsibleContent>
                )}
            </Collapsible>
        </li>
    );
}

/**
 * Die sieben Kästen ohne Marken darin.
 *
 * Die Überschrift eines Abschnitts trägt sie leer mit, damit die Spalte des
 * heutigen Tages durchläuft. Ohne sie zerfiele das Band an jeder Überschrift,
 * und aus einer Spalte würden drei Kapseln.
 */
function EmptyDays({ days, todayIndex }: { days: number; todayIndex: number }) {
    return (
        <div className="flex shrink-0" aria-hidden="true">
            {Array.from({ length: days }, (_, index) => (
                <span
                    key={index}
                    className={cn(DAY_CELL, index === todayIndex && BAND)}
                />
            ))}
        </div>
    );
}

/** Ein Abschnitt des Blatts, so wie er gezeichnet wird. */
export interface BoardSection {
    key: string;
    /**
     * Die Überschrift des Abschnitts.
     *
     * Sie steht immer im Baum, aber nicht immer sichtbar: Ein Abschnitt braucht
     * einen Namen, damit die Vorlesehilfe die Zeilen einordnen kann, auch wenn
     * das Auge die Einteilung ohne Wort erkennt.
     */
    heading: string;
    /**
     * Ob die Überschrift auch zu sehen ist.
     *
     * **Nur wo eine Entscheidung offen ist.** Das Blatt gliederte sich einmal
     * in „Steht heute an" und „Steht später an" — und sagte damit dasselbe wie
     * die Übersicht, die genau dafür da ist. Heute steht auf dieser Seite nur
     * noch in der Spalte des heutigen Tages: gefülltes Kästchen heißt, sie
     * steht an, ein Punkt heißt, heute nicht. Eine Überschrift, die dasselbe
     * noch einmal sagt, ist keine Gliederung, sondern ein Echo.
     */
    showHeading: boolean;
    group: HabitGroup;
    entries: ManagedHabit[];
    /** Steht unter der Überschrift, wo der Abschnitt eine Erklärung braucht. */
    note?: ReactNode;
}

/**
 * Alle Gewohnheiten auf einem Blatt: Zeilen mal Tage.
 *
 * **Vorher war die Seite ein Stapel.** Jede Gewohnheit hatte eine eigene weiße
 * Karte, und in jeder Karte stand derselbe Streifen mit eigenen
 * Tagesbuchstaben darunter. Vier Karten hießen vier Achsen, viermal „Mo Di Mi
 * Do Fr Sa So" und viermal „Angefangen am 06.09.2026" — viermal dasselbe
 * Gerüst mit anderem Inhalt. Was die Seite zeigte, war ihr eigener Aufbau.
 *
 * Ein Verlauf ist Gewohnheit **mal** Tag, also zweidimensional — dieselbe
 * Beobachtung, aus der {@see SleepWeek} entstanden ist. Hier liegen die sieben
 * Tage einmal oben als Achse, und alle Gewohnheiten hängen darunter an
 * denselben Spalten. Nichts kommt hinzu und nichts fällt weg; es steht nur
 * einmal statt fünfmal da, und die Seite wird dabei deutlich kürzer.
 *
 * **Der heutige Tag ist eine Spalte, keine Beschriftung.** Er läuft als Band
 * von der Achse bis zur letzten Zeile durch das Blatt. Das ist die eine
 * Stelle, an der das Blatt von einer Tabelle abweicht — und die einzige, die
 * man sich merkt.
 *
 * **Kein Vergleich zwischen den Zeilen.** Keine Spaltensumme, keine
 * Sortierung nach Erfolg, keine Balken nebeneinander. Ausgerichtet wird, damit
 * eine Zeile weniger Platz braucht, nicht damit sie gegen die andere antritt —
 * `progress-tracking.md` verlangt, dass fehlende Tage nie prominent angezeigt
 * werden, und eine Rangliste wäre genau das.
 */
export function HabitBoard({
    sections,
    landed,
    onToggleReminder,
    onToggleDay,
    onEnd,
}: {
    sections: BoardSection[];
    /** Die Zeile, die gerade hier gelandet ist — kurz betont. */
    landed: string | null;
    onToggleReminder: (habit: ManagedHabit, enabled: boolean) => void;
    /** Einen Tag der Woche abhaken oder zurücknehmen. */
    onToggleDay: (habit: ManagedHabit, day: RhythmDay) => void;
    onEnd: (habit: ManagedHabit) => void;
}) {
    // Die Achse kommt aus der ersten Zeile: Alle Gewohnheiten teilen dasselbe
    // Fenster, und eine zweite Quelle könnte davon abweichen.
    const axis = sections[0]?.entries[0]?.rhythm ?? [];
    const todayIndex = axis.length - 1;

    return (
        <div>
            {/* Die Achse steht oben, damit alle Zeilen darunter dieselbe
                Bezugslinie haben. Sie trägt dieselben Spalten wie eine Zeile,
                nur leer — ein geschätzter Randabstand säße bei jeder
                Bildschirmbreite ein paar Pixel daneben, und eine Achse, die
                nicht über ihren Marken liegt, ist schlimmer als keine. */}
            <div className={cn(ROW, 'items-end')} aria-hidden="true">
                <p className="type-eyebrow min-w-0 flex-1 pb-1.5 text-muted-foreground">
                    Letzte 7 Tage
                </p>

                <div className="flex shrink-0">
                    {axis.map((day, index) => (
                        <span
                            key={day.date}
                            className={cn(
                                'flex items-end justify-center pt-2 pb-1.5 text-[10px] leading-none',
                                DAY_CELL,
                                index === todayIndex
                                    ? cn(
                                          BAND,
                                          'rounded-t-lg border-t font-semibold text-primary',
                                      )
                                    : 'text-faintest',
                            )}
                        >
                            {day.label}
                        </span>
                    ))}
                </div>

                {/* **Kein Kopf über der Bilanz.** Dort stand „30 Tage", und
                    das stimmte nur für einen ihrer drei Zustände: „12 von 14
                    Tagen" passt, „Angefangen am 06.09.2026" ist ein Datum und
                    kein Fenster, und eine Serie hört nicht nach dreißig Tagen
                    auf — sie kann sechzig sein. Ein Kopf, der zwei von drei
                    Zellen widerspricht, erklärt nichts.

                    Die Zellen sind ganze Sätze und tragen sich selbst; das
                    Fenster nennt der Kasten hinter dem ⓘ, wo die Frage
                    entsteht. Beschriftet bleibt, was ohne Wort nicht lesbar
                    wäre: sieben Kästchen. */}
                <span
                    className={cn('hidden shrink-0 sm:block', BALANCE_COLUMN)}
                />

                <span className={cn('shrink-0', MENU_COLUMN)} />
            </div>

            {/* Haarlinien statt Abständen: Die Zeilen gehören zusammen, und
                jeder Zwischenraum hätte das Band des heutigen Tages
                unterbrochen. */}
            <ul className="divide-y divide-border/60 border-t border-border/60">
                {sections.flatMap((section, sectionIndex) => [
                    section.showHeading ? (
                        /* Die Überschrift trennt zwei Fälle voneinander und
                           lässt die Spalte trotzdem durch: Sie führt dieselben
                           leeren Kästen mit wie jede Zeile. */
                        <li
                            key={`${section.key}-kopf`}
                            className={cn(
                                section.group === 'displaced' && 'bg-accent',
                            )}
                        >
                            <div className={cn(ROW, 'items-stretch')}>
                                <h2
                                    id={section.key}
                                    className={cn(
                                        'type-eyebrow min-w-0 flex-1 pb-2 text-muted-foreground',
                                        sectionIndex === 0 ? 'pt-3' : 'pt-5',
                                    )}
                                >
                                    {section.heading}
                                </h2>

                                <EmptyDays
                                    days={axis.length}
                                    todayIndex={todayIndex}
                                />

                                <span
                                    className={cn(
                                        'hidden shrink-0 sm:block',
                                        BALANCE_COLUMN,
                                    )}
                                />
                                <span className={cn('shrink-0', MENU_COLUMN)} />
                            </div>

                            {/* Über die ganze Breite und nicht in der
                                Namensspalte: Auf dem Telefon bleiben dort
                                150 Pixel, und der Satz stünde als fünfzeiliger
                                Turm neben leeren Kästen. Dass das Band hier
                                aussetzt, ist richtig — der Abschnitt ist
                                ohnehin eine Unterbrechung. */}
                            {section.note !== undefined && (
                                <div className="pb-3">{section.note}</div>
                            )}
                        </li>
                    ) : (
                        <li key={`${section.key}-kopf`} className="sr-only">
                            <h2 id={section.key}>{section.heading}</h2>
                        </li>
                    ),
                    ...section.entries.map((habit, index) => (
                        <BoardRow
                            key={habit.id}
                            habit={habit}
                            bandBottom={
                                sectionIndex === sections.length - 1 &&
                                index === section.entries.length - 1
                            }
                            tinted={section.group === 'displaced'}
                            highlighted={landed === `managed-habit-${habit.id}`}
                            onToggleReminder={onToggleReminder}
                            onToggleDay={onToggleDay}
                            onEnd={onEnd}
                        />
                    )),
                ])}
            </ul>
        </div>
    );
}
