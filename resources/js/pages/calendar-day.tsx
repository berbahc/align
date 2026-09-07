import { Head, Link, router, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, LayoutGrid } from 'lucide-react';
import { useRef, useState } from 'react';
import {
    AdjustmentSheet,
    alternativeLabel,
} from '@/components/adjustment-sheet';
import { AiMascot } from '@/components/ai-mascot';
import { BlockSheet } from '@/components/block-sheet';
import { CourseCancellationSheet } from '@/components/course-cancellation-sheet';
import { CourseDetailSheet } from '@/components/course-detail-sheet';
import { CourseSheet } from '@/components/course-sheet';
import { DayGrid } from '@/components/day-grid';
import { DayOrderSheet } from '@/components/day-order-sheet';
import { ShiftSheet } from '@/components/shift-sheet';
import { StartingHelpSheet } from '@/components/starting-help-sheet';
import { Card, CardContent } from '@/components/ui/card';
import type { BlockDrag } from '@/hooks/use-block-drag';
import { useBlockDrag } from '@/hooks/use-block-drag';
import { HOUR_HEIGHT, collisionOf, followersOf } from '@/lib/day-grid';
import {
    OUTLINE_BUTTON,
    PRIMARY_BUTTON,
    QUIET_BUTTON,
    QUIET_LINK,
} from '@/lib/interaction';
import { cn } from '@/lib/utils';
import { calendar } from '@/routes';
import {
    destroy as appointmentUndone,
    store as appointmentDone,
} from '@/routes/appointments/completion';
import { day as calendarDay } from '@/routes/calendar';
import { store as placesStore } from '@/routes/calendar/semester/places';
import { edit as editHabit } from '@/routes/habits';
import { destroy, store } from '@/routes/habits/completions';
import {
    destroy as destroyShift,
    move as moveShift,
} from '@/routes/habits/shifts';
import type {
    AnchorAlternative,
    AppointmentBlock as Appointment,
    CalendarBlock as Block,
    CourseBlock as Course,
    CourseKindOption,
    CourseRow,
    PlaceProposal,
    SemesterPlan,
} from '@/types';

interface CalendarDayProps {
    /** Der angezeigte Tag als „YYYY-MM-DD". */
    date: string;
    /** Die Datumszeile im Kopf, fertig formatiert. */
    heading: string;
    isToday: boolean;
    /** Nur im Nachtrag-Fenster und nicht in der Zukunft lässt sich abhaken. */
    canComplete: boolean;
    /** Liegt der Tag hinter uns? Entscheidet, ob die Nachtragfrist gilt. */
    isPast: boolean;
    /** Null, sobald es davor keine Gewohnheiten mehr gab. */
    previousDate: string | null;
    nextDate: string;
    /** Der Monat, aus dem dieser Tag kommt — das Ziel des Wegs zurück. */
    month: string;
    blocks: Block[];
    /** Die Veranstaltungen dieses Tages als Blöcke — sie belegen Zeit. */
    courseBlocks: Course[];
    /**
     * Fremde Gewohnheiten, die man für diesen Tag zugesagt hat.
     *
     * Eigene Liste wie die Kurse und aus demselben Grund: Sie lassen sich
     * weder abhaken noch ziehen, und jede Stelle, die einen Block anfasst,
     * müsste sich sonst gegen eine Art verteidigen, die sie nicht behandelt.
     */
    appointmentBlocks: Appointment[];
    /** Dieselben als Zeilen, zum Anfassen: Ändern, Ausfall, Löschen. */
    kinds: CourseKindOption[];
    /** Der Zeitraum — für die Grenzen eines Ausfalls. Null ohne Semester. */
    semester: SemesterPlan | null;
    /** Der Rahmen des Tages als Uhrzeit … */
    wakeTime: string;
    bedtime: string;
    /** … und als Minute, für das Raster. Das Ende kann über 1440 liegen. */
    frameFrom: number;
    frameTo: number;
    /** Nur was noch kommt, lässt sich verlegen. */
    canShift: boolean;
    /** Hat dieser Tag einen eigenen Rahmen statt den seines Wochentags? */
    frameOverridden: boolean;
    /** Ein Vorschlag der KI, gestrichelt ins Raster gelegt — null ohne. */
    proposal: PlaceProposal | null;
}

const NAV_BUTTON =
    'flex size-11 shrink-0 items-center justify-center rounded-full text-primary transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.94]';

/**
 * Ein Tag als Achse von Ankern, hinterlegt mit den Stunden.
 *
 * Das Raster ist Hintergrund und bleibt es: Die Linien geben Orientierung, die
 * Überschrift eines Blocks ist weiter sein Anker. Was keine echte Uhrzeit hat,
 * bekommt auch im Raster keine — es liegt dort ungefähr, und die gestrichelte
 * Kante sagt das.
 *
 * Die Handlungen wanderten dabei in das Block-Sheet. Ein Zehn-Minuten-Block
 * ist 44 Pixel hoch, und zwei KI-Wege passen dort nicht hinein, ohne dass das
 * Rechteck seine Dauer verlieren müsste. Nur der Haken blieb draußen: Er ist
 * die häufigste Geste des Tages.
 */
export default function CalendarDay({
    date,
    heading,
    isToday,
    canComplete,
    isPast,
    previousDate,
    nextDate,
    month,
    blocks,
    courseBlocks,
    appointmentBlocks,
    kinds,
    semester,
    wakeTime,
    bedtime,
    frameFrom,
    frameTo,
    canShift,
    frameOverridden,
    proposal,
}: CalendarDayProps) {
    const { auth } = usePage().props;
    /** Die linke Hälfte des Doppel-Zeichens aus §3.2 — wie auf der Übersicht. */
    const selfInitial = (auth.user?.name.charAt(0) ?? '').toUpperCase();

    /** Der Vorschlag ist verworfen — man legt selbst. */
    const [proposalDismissed, setProposalDismissed] = useState(false);
    const [proposalError, setProposalError] = useState<string | null>(null);
    const [applyingProposal, setApplyingProposal] = useState(false);
    const shownProposal =
        proposal !== null && !proposalDismissed ? proposal : null;

    /**
     * Den eigenen Haken an einer zugesagten Verabredung umlegen.
     *
     * Eigener Weg und nicht `habits.completions`: Die Gewohnheit gehört der
     * anderen Person, und ein Haken dort meldete ihre Erfüllung statt der
     * eigenen.
     */
    function toggleAppointment(block: Appointment) {
        const options = { preserveScroll: true };

        if (block.completed) {
            router.delete(appointmentUndone.url(block.id), options);

            return;
        }

        router.post(appointmentDone.url(block.id), {}, options);
    }

    function applyProposal() {
        if (shownProposal === null) {
            return;
        }

        setApplyingProposal(true);
        setProposalError(null);

        router.post(
            placesStore.url(),
            {
                places: [
                    {
                        id: shownProposal.habitId,
                        time: shownProposal.time,
                        days: shownProposal.days,
                        suggestion_id: shownProposal.suggestionId,
                    },
                ],
            },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setProposalError(
                        Object.values(errors)[0] ??
                            'Das ließ sich gerade nicht übernehmen.',
                    ),
                onFinish: () => setApplyingProposal(false),
            },
        );
    }

    /** Welcher Block gerade aufgeschlagen ist; null heißt zu. */
    const [opened, setOpened] = useState<Block | null>(null);
    /** Welcher Kurs gerade aufgeschlagen ist; null heißt zu. */
    const [openedCourse, setOpenedCourse] = useState<CourseRow | null>(null);
    const [editingCourse, setEditingCourse] = useState<CourseRow | null>(null);
    const [courseSheetOpen, setCourseSheetOpen] = useState(false);
    const [cancellingCourse, setCancellingCourse] = useState<CourseRow | null>(
        null,
    );

    /** Welcher Block gerade im Anpassungs-Sheet steht; null heißt zu. */
    const [adjusting, setAdjusting] = useState<Block | null>(null);
    /** Welcher Block gerade in der Starthilfe steht; null heißt zu. */
    const [stuckOn, setStuckOn] = useState<Block | null>(null);
    /** Steht die Frage nach der Tagesordnung offen? */
    const [ordering, setOrdering] = useState(false);
    /** „Heute bin ich später aufgestanden" — der Rahmen dieses einen Tages. */
    /** Die vorgemerkte Alternative — sie erzeugt den Ghost im Raster. */
    const [preview, setPreview] = useState<AnchorAlternative | null>(null);
    /** Der eben losgelassene Block, solange die Frage nach der Reichweite offen ist. */
    const [dropped, setDropped] = useState<BlockDrag | null>(null);
    /** Was der Server an der Verschiebung auszusetzen hatte. */
    const [shiftError, setShiftError] = useState<string | null>(null);

    /**
     * Der Vorschlag an seiner möglichen neuen Stelle.
     *
     * Der Ghost liegt dort, wohin der Block wandern würde; sein bisheriger
     * Platz bleibt als blasse Kontur stehen. Man sieht das Vorher und das
     * Nachher nebeneinander, bevor irgendetwas entschieden ist
     * (ki-assistent-design3.md §6).
     */
    // Der Vorschlag aus dem Sheet „Neue Plätze" liegt genauso da wie der
    // aus der Einzelanpassung — dieselbe gestrichelte Kontur, derselbe Weg.
    /**
     * Was heute keine Stelle im Tag hat.
     *
     * Fast immer sind das die Gewohnheiten, die ein Kurs verdrängt hat; sehr
     * selten eine, deren Kette gerissen ist. Sie stehen jetzt unter dem
     * Kalender in einem eigenen Bereich statt unten im Raster — dort sahen sie
     * aus wie ein Rest, der nicht mehr hineinpasste.
     */
    const placeless = blocks.filter((block) => block.startMinute === null);

    /** Die Oberkante des Rasters — der Nullpunkt für einen Zug aus der Liste. */
    const gridRef = useRef<HTMLDivElement>(null);

    /**
     * Auch das Platzlose lässt sich anfassen.
     *
     * Ein eigener Griff und nicht der des Rasters: Beide können nie
     * gleichzeitig laufen — es gibt einen Finger —, und so bleibt der Zustand
     * dort, wo auch die Karte steht. Angehoben setzt der Block am Anfang des
     * Tages auf; von dort zieht man ihn hin, wo Platz ist.
     */
    const lift = useBlockDrag({
        bounds: { from: frameFrom, to: frameTo, height: 0 },
        enabled: canShift,
        onDrop: (drag) => {
            setShiftError(null);
            setDropped(drag);
        },
        // Absolut statt relativ: Die Liste steht unter dem Raster, und ein
        // Block ohne Stelle hat keine, von der aus sich schieben ließe. Er
        // folgt dem Finger dorthin, wo dieser über dem Raster steht.
        minuteAt: (clientY) => {
            const top = gridRef.current?.getBoundingClientRect().top;

            return top === undefined
                ? frameFrom
                : frameFrom + ((clientY - top) / HOUR_HEIGHT) * 60;
        },
    });

    const lifted = lift.drag
        ? (placeless.find((block) => block.id === lift.drag?.id) ?? null)
        : null;

    const ghost =
        shownProposal !== null && !adjusting
            ? { replaces: shownProposal.habitId, block: shownProposal.block }
            : adjusting && preview
              ? {
                    replaces: adjusting.id,
                    block: {
                        ...adjusting,
                        anchor: alternativeLabel(preview),
                        anchorHour: preview.anchorHour,
                        startMinute: previewStartMinute(preview),
                        // Eine Uhrzeit ist eine Zusage, ein Moment eine Gegend —
                        // der Ghost zeichnet den Unterschied schon mit.
                        exact: Boolean(preview.time),
                        // Die Spanne des bisherigen Platzes gilt am neuen nicht
                        // mehr. Sie hier nachzurechnen hieße, die Server-Logik im
                        // Browser zu wiederholen — sie kommt zurück, sobald der
                        // Vorschlag übernommen ist.
                        timeRange: null,
                    },
                }
              : null;

    /**
     * Was das Raster als Vorschau zeigt: den Vorschlag der KI — oder den Block,
     * den man gerade aus der Liste heraufzieht. Beides sagt dasselbe („hier
     * läge es"), also trägt es auch dieselbe gestrichelte Kontur.
     */
    const outline =
        lifted && lift.drag
            ? {
                  replaces: lifted.id,
                  block: {
                      ...lifted,
                      startMinute: lift.drag.minute,
                      exact: true,
                      timeRange: null,
                  },
              }
            : ghost;

    /**
     * Abhaken für den angezeigten Tag, nicht für heute.
     *
     * `completed_on` reist bei beiden Richtungen mit — der Server prüft damit
     * das Nachtrag-Fenster und weist Tage ab, an denen die Gewohnheit gar nicht
     * vorgesehen war.
     */
    function toggle(block: Block) {
        if (block.completed) {
            router.delete(destroy.url(block.id), {
                data: { completed_on: date },
                preserveScroll: true,
            });

            return;
        }

        router.post(
            store.url(block.id),
            { completed_on: date },
            { preserveScroll: true },
        );
    }

    /** Der Block, um den es beim Verschieben geht — samt seiner Folgen. */
    const shifting = dropped
        ? (blocks.find((block) => block.id === dropped.id) ?? null)
        : null;

    /**
     * Speichern — und zwar so weit, wie gewählt.
     *
     * Der Server prüft dabei ein zweites Mal, und zwar gründlicher: für jeden
     * künftigen Wochentag statt nur für heute. Kommt er mit einem Einwand
     * zurück, bleibt das Sheet offen und trägt ihn.
     */
    function confirmShift(scope: 'today' | 'always') {
        if (dropped === null) {
            return;
        }

        router.put(
            moveShift.url(dropped.id),
            { date, start_minute: dropped.minute, scope },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDropped(null);
                    setShiftError(null);
                },
                onError: (errors) =>
                    setShiftError(
                        Object.values(errors)[0] ??
                            'Das ließ sich gerade nicht verschieben.',
                    ),
            },
        );
    }

    /** Die Ausnahme für diesen Tag zurücknehmen. */
    function undoShift(block: Block) {
        router.delete(destroyShift.url(block.id), {
            data: { date },
            preserveScroll: true,
        });
        setOpened(null);
    }

    return (
        <>
            <Head title={heading} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
                <header className="flex items-center gap-2">
                    {/* Pfeile sind Links, kein Client-State: die URL trägt den
                        Tag, übersteht ein Neuladen und lässt sich teilen. */}
                    {previousDate ? (
                        <Link
                            href={calendarDay(previousDate)}
                            aria-label="Ein Tag zurück"
                            className={`${NAV_BUTTON} cursor-pointer`}
                        >
                            <ChevronLeft
                                className="size-5"
                                aria-hidden="true"
                            />
                        </Link>
                    ) : (
                        <span
                            className={`${NAV_BUTTON} opacity-30`}
                            aria-hidden="true"
                        >
                            <ChevronLeft className="size-5" />
                        </span>
                    )}

                    <h1 className="flex-1 text-center text-[clamp(1.125rem,4vw,1.5rem)] leading-tight font-bold text-primary">
                        {heading}
                    </h1>

                    <Link
                        href={calendarDay(nextDate)}
                        aria-label="Ein Tag vor"
                        className={`${NAV_BUTTON} cursor-pointer`}
                    >
                        <ChevronRight className="size-5" aria-hidden="true" />
                    </Link>
                </header>

                {/* Der Weg zurück in den Monat, aus dem dieser Tag kommt — die
                    Ebene darüber ist der Ort, an dem man den Kalender betritt. */}
                <div className="flex items-center justify-center gap-4">
                    <Link
                        href={calendar({ query: { month } })}
                        className={`${QUIET_LINK} flex items-center gap-1.5 text-sm no-underline`}
                    >
                        <LayoutGrid className="size-3.5" aria-hidden="true" />
                        Monat
                    </Link>
                    {!isToday && (
                        <Link
                            href={calendarDay(todayDate())}
                            className={`${QUIET_LINK} text-sm`}
                        >
                            Zurück zu heute
                        </Link>
                    )}
                </div>

                {/* Der Weg zur Tagesordnung betrifft den ganzen Tag, nicht eine
                    Zeile — deshalb steht er über dem Raster und nicht darunter.
                    Als leiser Link am Seitenende war er der letzte Satz einer
                    langen Spalte: Wer den Tag ordnen will, will das, bevor er
                    ihn durchgescrollt hat.

                    Unterlegt, aber nicht gesättigt: Die eine kräftige Fläche
                    dieser Seite ist das Raster selbst (§5.4). Der Knopf trägt
                    denselben Ton wie der Vorschlagskasten darunter — hier
                    spricht dieselbe Stimme, und sie soll auch so aussehen.

                    Ab zwei Gewohnheiten: bei einer gibt es keine Reihenfolge.

                    Und nur heute. Nicht `canComplete` — das reicht sieben Tage
                    zurück, weil sich so weit nachtragen lässt. Ordnen ist etwas
                    anderes als Abhaken: Es schreibt feste Uhrzeiten in die
                    Gewohnheiten selbst und gilt ab dann für jeden Tag. Von
                    Montag aus bestellt ordnete es am Donnerstag die Woche neu,
                    und der vergangene Tag bliebe, wie er war. */}
                {blocks.length > 1 && isToday && (
                    <div className="flex justify-center">
                        <button
                            type="button"
                            onClick={() => setOrdering(true)}
                            className="inline-flex h-11 cursor-pointer items-center gap-2 rounded-full border border-primary/25 bg-accent px-5 text-sm font-semibold text-primary transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-accent/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.97]"
                        >
                            <AiMascot
                                variant="mark"
                                className="size-4 shrink-0"
                            />
                            Tag neu ordnen
                        </button>
                    </div>
                )}

                {/* Der Vorschlag über dem Raster: Was er ist, warum, und die
                    zwei Wege — übernehmen oder selbst einordnen. Die Figur
                    steht nur hier, weil hier die KI spricht. */}
                {shownProposal !== null && (
                    <div
                        role="status"
                        className="flex flex-col gap-3 rounded-xl border border-primary/25 bg-accent px-4 py-3"
                    >
                        <p className="type-eyebrow flex items-center gap-2 text-primary">
                            {/* Kein Einstieg, sondern eine Antwort — deshalb
                                die ganze Figur und nicht nur das Zeichen. */}
                            <AiMascot
                                state="speaking"
                                className="size-7 shrink-0"
                            />
                            Vorschlag der KI
                        </p>
                        <p className="text-sm leading-relaxed text-foreground">
                            <span className="font-semibold">
                                {shownProposal.title}
                            </span>{' '}
                            könnte um{' '}
                            <span className="font-semibold">
                                {shownProposal.label}
                            </span>{' '}
                            laufen, gestrichelt im Raster.{' '}
                            {shownProposal.reason}
                        </p>
                        {proposalError !== null && (
                            <p
                                role="alert"
                                className="rounded-[14px] border border-primary/25 bg-card px-3 py-2 text-sm leading-relaxed"
                            >
                                {proposalError}
                            </p>
                        )}
                        <div className="flex flex-wrap gap-2">
                            <button
                                type="button"
                                onClick={applyProposal}
                                disabled={applyingProposal}
                                className={`${PRIMARY_BUTTON} w-auto`}
                            >
                                Übernehmen
                            </button>
                            <Link
                                href={editHabit(shownProposal.habitId)}
                                onClick={() => setProposalDismissed(true)}
                                className={OUTLINE_BUTTON}
                            >
                                Selbst einordnen
                            </Link>
                            <button
                                type="button"
                                onClick={() => setProposalDismissed(true)}
                                className={`${QUIET_BUTTON} px-2`}
                            >
                                Ausblenden
                            </button>
                        </div>
                    </div>
                )}

                <Card className="gap-0 py-5">
                    <CardContent className="px-4 sm:px-5" ref={gridRef}>
                        <DayGrid
                            blocks={blocks}
                            courseBlocks={courseBlocks}
                            appointmentBlocks={appointmentBlocks}
                            selfInitial={selfInitial}
                            onOpenCourse={setOpenedCourse}
                            onToggleAppointment={toggleAppointment}
                            frameFrom={frameFrom}
                            frameTo={frameTo}
                            wakeTime={wakeTime}
                            bedtime={bedtime}
                            canComplete={canComplete}
                            canShift={canShift}
                            isToday={isToday}
                            onToggle={toggle}
                            onOpen={setOpened}
                            onDrop={(drag) => {
                                setShiftError(null);
                                setDropped(drag);
                            }}
                            weekday={weekdayOf(date)}
                            frameOverridden={frameOverridden}
                            ghost={outline}
                        />

                        {/* Kein Fehler, sondern eine Grenze: Was der
                            Wochenstreifen nicht mehr zeigt, lässt sich auch
                            nicht mehr nachtragen. Nur rückwärts — vor einem
                            Tag, der noch kommt, ist nichts verstrichen. */}
                        {!canComplete && isPast && (
                            <p className="mt-5 border-t border-border pt-4 text-sm leading-relaxed text-muted-foreground">
                                Nachtragen geht für die letzten sieben Tage.
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Ein eigener Bereich unter dem Kalender, keine Zeile darin:
                    Was keinen Platz hat, steht auch nicht im Raster. Die Kante
                    in Oliv sagt, dass hier etwas offen ist — ohne Rot und ohne
                    Ausrufezeichen, denn versäumt hat das niemand (§1.4). */}
                {placeless.length > 0 && (
                    <section
                        aria-label="Ohne festen Platz"
                        className="flex flex-col gap-3 rounded-xl border border-primary/25 bg-accent px-4 py-4"
                    >
                        <p className="type-eyebrow text-primary">
                            Ohne festen Platz
                        </p>

                        <ul className="flex flex-col gap-2">
                            {placeless.map((block) => (
                                <li key={block.id}>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            lift.swallowsClick() ||
                                            setOpened(block)
                                        }
                                        onPointerDown={(event) =>
                                            lift.handlers.onPointerDown(
                                                event,
                                                block,
                                                frameFrom,
                                            )
                                        }
                                        onPointerMove={
                                            lift.handlers.onPointerMove
                                        }
                                        onPointerUp={lift.handlers.onPointerUp}
                                        onPointerCancel={
                                            lift.handlers.onPointerCancel
                                        }
                                        style={{
                                            touchAction:
                                                lift.drag?.id === block.id
                                                    ? 'none'
                                                    : 'pan-y',
                                        }}
                                        className={cn(
                                            'w-full cursor-pointer rounded-[10px] bg-card px-3 py-2 text-left transition-[box-shadow,scale] duration-[var(--duration-press)] ease-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
                                            lift.drag?.id === block.id &&
                                                'shadow-lift ring-2 ring-primary',
                                        )}
                                    >
                                        <span className="type-eyebrow block text-muted-foreground">
                                            {block.anchor}
                                        </span>
                                        <span className="block text-[15px] leading-snug font-semibold">
                                            {block.title}
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>

                        {/* Der Weg hinein steht einmal unter der Liste, nicht
                            an jeder Zeile: Er gilt für alle gleich. */}
                        {canShift && (
                            <p className="text-sm leading-relaxed text-muted-foreground">
                                Halte eine gedrückt und zieh sie ins Raster.
                                Oder tippe sie an, um zu sehen, wo sonst Platz
                                wäre.
                            </p>
                        )}
                    </section>
                )}
            </div>

            <BlockSheet
                block={opened}
                canComplete={canComplete}
                onOpenChange={(open) => !open && setOpened(null)}
                onToggle={toggle}
                onAdjust={setAdjusting}
                onStuck={setStuckOn}
                onUndoShift={undoShift}
                date={date}
            />

            {/* Die Frage nach dem Loslassen. Der Konflikt für heute steht sofort
                darin — die Blöcke dieses Tages liegen ohnehin als Props da, und
                eine Antwort nach einem Rundweg über den Server wäre langsamer
                ohne genauer zu sein. */}
            <ShiftSheet
                block={shifting}
                minute={dropped?.minute ?? 0}
                isToday={isToday}
                followers={
                    dropped
                        ? followersOf(blocks, dropped.id, dropped.minute)
                        : []
                }
                conflict={
                    dropped
                        ? collisionOf(
                              blocks,
                              courseBlocks,
                              appointmentBlocks,
                              dropped.id,
                              dropped.minute,
                          )
                        : null
                }
                error={shiftError}
                onOpenChange={(open) => {
                    if (!open) {
                        setDropped(null);
                        setShiftError(null);
                    }
                }}
                onConfirm={confirmShift}
            />

            <AdjustmentSheet
                block={adjusting}
                onOpenChange={(open) => !open && setAdjusting(null)}
                onPreview={setPreview}
            />

            {/* Dieselbe Starthilfe wie auf der Übersicht: Wo die Gewohnheit
                steht, soll auch der Weg stehen, sie kleiner zu machen. */}
            <StartingHelpSheet
                habit={stuckOn}
                onOpenChange={(open) => !open && setStuckOn(null)}
            />

            <DayOrderSheet
                open={ordering}
                date={date}
                blocks={blocks}
                onOpenChange={setOrdering}
            />

            {/* Die Kurse liegen hier, also werden sie hier angefasst — mit
                denselben Sheets, die es dafür gibt, nicht mit eigenen. */}
            <CourseDetailSheet
                course={openedCourse}
                onOpenChange={() => setOpenedCourse(null)}
                onEdit={(course) => {
                    setEditingCourse(course);
                    setCourseSheetOpen(true);
                }}
                onCancelDate={setCancellingCourse}
            />

            <CourseSheet
                open={courseSheetOpen}
                course={editingCourse}
                kinds={kinds}
                onOpenChange={setCourseSheetOpen}
            />

            {semester !== null && (
                <CourseCancellationSheet
                    course={cancellingCourse}
                    semester={semester}
                    onOpenChange={() => setCancellingCourse(null)}
                />
            )}
        </>
    );
}

/**
 * Wo der Ghost im Raster liegt.
 *
 * Eine vorgeschlagene Uhrzeit bringt die Minute mit; ein vorgeschlagener
 * Moment hat nur seine Stunde — dieselbe Näherung, mit der der Server ihn
 * ohnehin einsortiert ({@see Habit::dayStartMinute()}).
 */
function previewStartMinute(alternative: AnchorAlternative): number {
    if (alternative.time) {
        const [hours, minutes] = alternative.time.split(':');

        return Number(hours) * 60 + Number(minutes);
    }

    return alternative.anchorHour * 60;
}

/**
 * Der Wochentag eines Datums, 1 für Montag bis 7 für Sonntag.
 *
 * Dieselbe Zählung wie auf dem Server (`dayOfWeekIso`) und im Schlafplan.
 * `getDay()` zählt ab Sonntag mit 0 — die Null wird deshalb zur Sieben.
 */
function weekdayOf(date: string): number {
    return new Date(`${date}T00:00:00`).getDay() || 7;
}

/** Heute als „YYYY-MM-DD" in der Zeitzone des Geräts. */
function todayDate(): string {
    const now = new Date();

    return [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, '0'),
        String(now.getDate()).padStart(2, '0'),
    ].join('-');
}

CalendarDay.layout = {
    breadcrumbs: [{ title: 'Kalender', href: calendar() }],
};
