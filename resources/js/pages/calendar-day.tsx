import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, LayoutGrid } from 'lucide-react';
import { useState } from 'react';
import {
    AdjustmentSheet,
    alternativeLabel,
} from '@/components/adjustment-sheet';
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
import { collisionOf, followersOf } from '@/lib/day-grid';
import { QUIET_LINK } from '@/lib/interaction';
import { calendar } from '@/routes';
import { day as calendarDay } from '@/routes/calendar';
import { destroy, store } from '@/routes/habits/completions';
import {
    destroy as destroyShift,
    move as moveShift,
} from '@/routes/habits/shifts';
import type {
    AnchorAlternative,
    CalendarBlock as Block,
    CourseBlock as Course,
    CourseKindOption,
    CourseRow,
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
    /** Null, sobald es davor keine Gewohnheiten mehr gab. */
    previousDate: string | null;
    nextDate: string;
    /** Der Monat, aus dem dieser Tag kommt — das Ziel des Wegs zurück. */
    month: string;
    blocks: Block[];
    /** Die Veranstaltungen dieses Tages als Blöcke — sie belegen Zeit. */
    courseBlocks: Course[];
    /** Dieselben als Zeilen, zum Anfassen: Ändern, Ausfall, Löschen. */
    courses: CourseRow[];
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
    previousDate,
    nextDate,
    month,
    blocks,
    courseBlocks,
    courses,
    kinds,
    semester,
    wakeTime,
    bedtime,
    frameFrom,
    frameTo,
    canShift,
}: CalendarDayProps) {
    /** Welcher Block gerade aufgeschlagen ist; null heißt zu. */
    const [opened, setOpened] = useState<Block | null>(null);
    /** Welcher Kurs gerade aufgeschlagen ist; null heißt zu. */
    const [openedCourse, setOpenedCourse] = useState<CourseRow | null>(null);
    const [editingCourse, setEditingCourse] = useState<CourseRow | null>(null);
    const [courseSheetOpen, setCourseSheetOpen] = useState(false);
    const [cancellingCourse, setCancellingCourse] = useState<CourseRow | null>(
        null,
    );

    /** Vom Block im Raster zur Zeile — die Kennung im Raster ist negativ. */
    function openCourse(block: Course) {
        setOpenedCourse(
            courses.find((course) => course.id === -block.id) ?? null,
        );
    }
    /** Welcher Block gerade im Anpassungs-Sheet steht; null heißt zu. */
    const [adjusting, setAdjusting] = useState<Block | null>(null);
    /** Welcher Block gerade in der Starthilfe steht; null heißt zu. */
    const [stuckOn, setStuckOn] = useState<Block | null>(null);
    /** Steht die Frage nach der Tagesordnung offen? */
    const [ordering, setOrdering] = useState(false);
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
    const ghost =
        adjusting && preview
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

                <Card className="gap-0 py-5">
                    <CardContent className="px-4 sm:px-5">
                        <DayGrid
                            blocks={blocks}
                            courseBlocks={courseBlocks}
                            onOpenCourse={openCourse}
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
                            ghost={ghost}
                        />

                        {/* Der Weg zur Tagesordnung steht unter dem Raster, weil
                            er den ganzen Tag betrifft und nicht eine Zeile. Ab
                            zwei Gewohnheiten: bei einer gibt es keine
                            Reihenfolge. */}
                        {blocks.length > 1 && canComplete && (
                            <button
                                type="button"
                                onClick={() => setOrdering(true)}
                                className={`${QUIET_LINK} mt-5 block text-xs`}
                            >
                                ✦ Tag neu ordnen
                            </button>
                        )}

                        {/* Kein Fehler, sondern eine Grenze: Was der
                            Wochenstreifen nicht mehr zeigt, lässt sich auch
                            nicht mehr nachtragen. */}
                        {!canComplete && (
                            <p className="mt-5 border-t border-border pt-4 text-sm leading-relaxed text-muted-foreground">
                                Nachtragen geht für die letzten sieben Tage.
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>

            <BlockSheet
                block={opened}
                canComplete={canComplete}
                onOpenChange={(open) => !open && setOpened(null)}
                onToggle={toggle}
                onAdjust={setAdjusting}
                onStuck={setStuckOn}
                onUndoShift={undoShift}
            />

            {/* Die Frage nach dem Loslassen. Der Konflikt für heute steht sofort
                darin — die Blöcke dieses Tages liegen ohnehin als Props da, und
                eine Antwort nach einem Rundweg über den Server wäre langsamer
                ohne genauer zu sein. */}
            <ShiftSheet
                block={shifting}
                minute={dropped?.minute ?? 0}
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
