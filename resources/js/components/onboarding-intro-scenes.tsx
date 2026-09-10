import {
    AlarmClock,
    Check,
    ChevronLeft,
    ChevronRight,
    GraduationCap,
    LayoutGrid,
    Moon,
    Sun,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { AiMascot } from '@/components/ai-mascot';
import { AiSuggestion } from '@/components/ai-suggestion';
import AppLogoIcon from '@/components/app-logo-icon';
import { HabitRow } from '@/components/habit-row';
import { CHOICE_TILE } from '@/lib/interaction';
import { cn } from '@/lib/utils';
import type { Habit } from '@/types';

/**
 * Eine Szene des Auftakts.
 *
 * Die Texte stehen hier und nicht im Abspieler: Der Abspieler kümmert sich um
 * Zeit, Tasten und Fortschritt und weiß nichts davon, wovon der Film handelt.
 */
export interface IntroScene {
    /** Stabiler Schlüssel — auch der Auslöser für React, neu zu montieren. */
    id: string;
    /** Der große Satz. Trägt Auszeichnung, deshalb kein blanker Text. */
    headline: ReactNode;
    /** Die leise Zeile darunter. */
    aside: ReactNode;
    /**
     * Die Überschrift der Szene, klein über dem Satz.
     *
     * Sie benennt, wovon dieses eine Bild handelt, und nicht, in welcher
     * Hälfte des Films es steht: „Dein Alltag" dreimal hintereinander sagt
     * beim zweiten Mal nichts mehr. Der Umschlag zur Antwort trägt sich
     * ohnehin selbst — ab Bild 04 ist die Oberfläche der App zu sehen, und
     * das Bild heißt dann auch so.
     */
    chapter: string;
    /**
     * Wie lange die Szene steht, in Millisekunden.
     *
     * Bei der letzten Szene nur noch die Länge ihres Balkens: Dort läuft keine
     * Uhr mehr, der Film wartet auf den Knopf.
     */
    duration: number;
    /** Das Bild über dem Text. */
    visual: ReactNode;
}

/**
 * Das eine Wort im großen Satz, auf das es ankommt.
 *
 * Oliv und nicht fett: Die Überschrift ist ohnehin fett, „noch fetter" wäre
 * kein Unterschied. Farbe ist hier der stärkere Griff — und §1.2 gibt ihr die
 * Bedeutung, die genau hier gemeint ist: Wo Oliv erscheint, passiert etwas.
 *
 * Genau eine Stelle je Satz. Zwei hervorgehobene Stellen heben sich
 * gegenseitig auf.
 */
function Mark({ children }: { children: ReactNode }) {
    return <span className="text-primary">{children}</span>;
}

/**
 * Die betonte Stelle in der leisen Zeile.
 *
 * Dort trägt Gewicht, was in der Überschrift die Farbe trägt: Die Zeile läuft
 * in normaler Stärke, ein halbfettes Stück darin springt heraus, ohne laut zu
 * werden.
 */
function Strong({ children }: { children: ReactNode }) {
    return <span className="font-semibold text-foreground">{children}</span>;
}

/**
 * Der Platz, den jedes Bild einnimmt — Film wie Oberfläche.
 *
 * Auf dem Handy eine feste Höhe und kein Seitenverhältnis: Ein hohes Bild wäre
 * dort so groß, dass Satz und Knopf darunter aus dem Bild rutschen, und
 * zwischen einem hohen Clip und einer flachen Karte spränge die Bühne bei
 * jedem Schnitt. Ein Film, dessen Bildkante wandert, ist kein Film.
 *
 * Auf dem Desktop steht das Bild neben dem Text statt darüber, und dort ist
 * Breite kein knappes Gut mehr — deshalb bekommt es das Kinoformat der Clips
 * selbst. Der Ausschnitt ist damit auf dem großen Schirm vollständig und auf
 * dem Handy an den Seiten beschnitten; die Clips sind so angelegt, dass alles
 * Wichtige in der Mitte liegt.
 */
const STAGE = 'h-[min(38vh,19rem)] w-full lg:aspect-video lg:h-auto';

/**
 * Die Bühne für die Bilder aus der App — höher als die für die Clips.
 *
 * Ein Telefon ist hoch, ein Filmbild ist breit. In eine 16:9-Fläche gestellt
 * bliebe vom Bildschirm ein Streifen, auf dem man die Schrift sucht. Die
 * Textkante wandert dadurch genau einmal, beim Schnitt von Bild 03 auf 04 —
 * und das ist ohnehin die Stelle, an der der Film seine Hälfte wechselt.
 */
const PHONE_STAGE = 'h-[min(56vh,30rem)] w-full lg:aspect-[4/3] lg:h-auto';

/**
 * Die Fläche, auf der ein Filmbild liegt.
 *
 * Der Clip füllt sie ganz und läuft unten in den Seitengrund aus — deshalb
 * steht der Text nie darauf, sondern darunter, und der Kontrast von 4.5:1 ist
 * ohne Textschatten und ohne Abdunkelung erreicht.
 *
 * Darunter liegt `bg-track`, eine echte Stufe der Flächenleiter. Solange ein
 * Clip noch lädt — oder noch gar nicht da ist —, sieht man damit eine ruhige
 * sandfarbene Fläche und kein schwarzes Loch. Der Abspieler wartet ohnehin
 * nicht auf das Video: Sein Zähler läuft unabhängig, sonst hinge der ganze
 * Film an einer Leitung.
 */
function IntroClip({
    src,
    poster,
    eager = false,
}: {
    src: string;
    poster: string;
    /** Nur das erste Bild lädt sofort; die anderen erst, wenn sie dran sind. */
    eager?: boolean;
}) {
    return (
        <div
            className={`intro-figure relative overflow-hidden rounded-[18px] bg-track ${STAGE}`}
        >
            <video
                src={src}
                poster={poster}
                muted
                playsInline
                autoPlay
                loop
                preload={eager ? 'auto' : 'metadata'}
                aria-hidden="true"
                className="intro-clip size-full object-cover"
            />
        </div>
    );
}

/**
 * Das Telefon, in dem die App steht.
 *
 * Die drei Antwort-Bilder zeigen keine Kartenausschnitte mehr, sondern ein
 * ganzes Gerät mit einem ganzen Bildschirm: Statusleiste, Kopfzeile der App,
 * Inhalt und unten die Navigation. Ein Ausschnitt beweist nichts — erst der
 * vollständige Bildschirm zeigt, dass es die App wirklich gibt.
 *
 * **Alles darin ist in echten Gerätepixeln gemaßt** (Körper 414, Bildschirm
 * 390 × 844) und als ein Stück skaliert. Die Bausteine der App sind für diese
 * Breite gezeichnet; in einen schmaleren Kasten gepresst brächen sie um und
 * wären dann keine echte Oberfläche mehr, sondern ein Nachbau.
 *
 * Die Bühne ist genau so hoch wie das skalierte Gerät ({@see intro-phone} in
 * `app.css`) — nichts wird abgeschnitten, nichts steht daneben.
 */
function IntroPhone({
    title,
    children,
}: {
    /** Der Name in der Kopfzeile — derselbe, der unten in der Leiste leuchtet. */
    title: string;
    children: ReactNode;
}) {
    return (
        <div
            aria-hidden="true"
            className={cn(
                'intro-figure pointer-events-none relative overflow-hidden rounded-[18px]',
                PHONE_STAGE,
            )}
        >
            {/* Der Grund ist ein Foto und kein Verlauf: Blattschatten von
                links, eine Fläche unten, sonst nichts. Ein Gerät auf einer
                glatten Farbfläche sieht aus wie ausgeschnitten; erst echtes
                Licht dahinter stellt es in einen Raum. */}
            <img
                src="/onboarding/ground.jpg"
                alt=""
                className="intro-ground-photo absolute inset-0 size-full object-cover"
            />

            <div className="relative flex h-full justify-center pt-5">
                <div className="intro-phone">
                    <div className="intro-phone-body">
                        {/* Die Insel liegt über dem Bildschirm, nicht darin:
                            Sie gehört zum Gerät. */}
                        <span className="intro-phone-island" />

                        <div className="intro-phone-screen bg-background">
                            <PhoneStatusBar />
                            <PhoneAppBar title={title} />
                            {/* `overflow-hidden`, damit ein gescrollter
                                Bildschirm oben abgeschnitten wird und nicht
                                über der Kopfzeile liegt. */}
                            <div className="overflow-hidden px-4 pt-5">
                                {children}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

/**
 * Die Statusleiste des Geräts.
 *
 * 9:41 ist die Uhrzeit, auf die jedes Gerätemockup steht — sie liest sich als
 * „Bildschirmfoto" und nicht als „gerade eben", und genau das ist hier
 * richtig. Die drei Zeichen rechts sind gezeichnet und nicht geliehen: Es
 * gehört kein fremdes Markenzeichen in unseren Film.
 */
function PhoneStatusBar() {
    return (
        <div className="flex h-11 shrink-0 items-center justify-between px-7 text-foreground">
            <span className="text-[15px] font-semibold tabular-nums">9:41</span>

            <span className="flex items-center gap-1.5">
                {/* Netz: vier steigende Balken. */}
                <svg
                    viewBox="0 0 18 12"
                    className="h-3 w-[18px]"
                    fill="currentColor"
                >
                    <rect x="0" y="8" width="3" height="4" rx="1" />
                    <rect x="5" y="5.5" width="3" height="6.5" rx="1" />
                    <rect x="10" y="3" width="3" height="9" rx="1" />
                    <rect x="15" y="0" width="3" height="12" rx="1" />
                </svg>

                {/* WLAN: drei Bögen über einem Punkt. */}
                <svg
                    viewBox="0 0 16 12"
                    className="h-3 w-4"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="1.6"
                    strokeLinecap="round"
                >
                    <path d="M1.2 4.2a10 10 0 0 1 13.6 0" />
                    <path d="M3.8 7a6.2 6.2 0 0 1 8.4 0" />
                    <path d="M6.4 9.7a2.4 2.4 0 0 1 3.2 0" />
                </svg>

                {/* Akku: gut gefüllt, ohne Warnfarbe — nichts in diesem Film
                    ist ein Alarm (Designsprache §1.4). */}
                <svg viewBox="0 0 27 12" className="h-3 w-[27px]" fill="none">
                    <rect
                        x="0.6"
                        y="0.6"
                        width="22"
                        height="10.8"
                        rx="3.2"
                        stroke="currentColor"
                        strokeOpacity="0.4"
                        strokeWidth="1.2"
                    />
                    <rect
                        x="2.4"
                        y="2.4"
                        width="16"
                        height="7.2"
                        rx="1.8"
                        fill="currentColor"
                    />
                    <path
                        d="M24.4 4.2a2.6 2.6 0 0 1 0 3.6"
                        stroke="currentColor"
                        strokeOpacity="0.4"
                        strokeWidth="1.2"
                        strokeLinecap="round"
                    />
                </svg>
            </span>
        </div>
    );
}

/**
 * Die Kopfzeile der App — dieselben drei Spalten wie in `AppSidebarHeader`.
 *
 * Nachgebaut statt eingebunden: Die echte Kopfzeile liest den angemeldeten
 * Nutzer und den aktuellen Pfad aus dem Inertia-Zustand. Im Onboarding gibt es
 * beides so nicht, und ein Kopf, der beim Blättern die Seite wechseln will,
 * wäre im Film ein Fehler. Was ihn ausmacht, steht hier unverändert: 64 px
 * hoch, Marke links, Titel mittig in `text-primary`, Konto rechts.
 */
function PhoneAppBar({ title }: { title: string }) {
    return (
        <div className="flex h-16 shrink-0 items-center border-b border-sidebar-border/50 px-4">
            <div className="grid w-full grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2">
                <AppLogoIcon className="size-8 justify-self-start" />
                <span className="truncate text-[17px] leading-tight font-semibold text-primary">
                    {title}
                </span>
                <span className="flex items-center justify-end">
                    <span className="flex size-8 items-center justify-center rounded-full bg-sand text-sm font-semibold text-primary">
                        B
                    </span>
                </span>
            </div>
        </div>
    );
}

/** Die Stundenspalte links — dieselbe Breite wie `GUTTER` im echten Raster. */
const GUTTER = 52;

/** Wie hoch eine Stunde im Raster steht. */
const HOUR = 46;

/** Der erste Strich des Rasters: Hier beginnt der Tag. */
const DAY_FROM = 7;

/** Eine Uhrzeit als Pixelhöhe im Raster. */
function at(time: number) {
    return (time - DAY_FROM) * HOUR;
}

/**
 * Ein Block im Stundenraster, wie ihn {@see CalendarBlock} zeichnet.
 *
 * Die Kante trägt die Auskunft: **durchgezogen** heißt Uhrzeit, **gestrichelt**
 * heißt ungefähr hier (§7.3). Erledigt füllt sich der Block auf `accent`, der
 * Haken wird voll und der Titel geht nach `olive-mid` — dieselben drei
 * Merkmale wie in der App.
 */
function DayBlock({
    top,
    height,
    label,
    title,
    icon: Icon,
    exact = true,
    done = false,
    snap = false,
}: {
    top: number;
    height: number;
    /** Die Zeile über dem Titel: Spanne bei fester Uhrzeit, sonst der Anker. */
    label: string;
    title: string;
    icon: typeof Sun;
    exact?: boolean;
    done?: boolean;
    /** Fährt der Block gerade an seinen Platz? */
    snap?: boolean;
}) {
    return (
        <div
            className={cn('absolute right-0', snap && 'intro-snap')}
            // Dieselbe Mindesthöhe wie im echten Raster (`MIN_BLOCK_HEIGHT`):
            // Darunter bleibt vom Titel ein Wort und drei Punkte.
            style={{ top, height: Math.max(height, 44), left: GUTTER }}
        >
            <div
                className={cn(
                    'flex h-full overflow-hidden rounded-[10px]',
                    exact
                        ? 'border-l-[3px] border-primary'
                        : 'border-l-[3px] border-dashed border-olive-mid',
                    done ? 'bg-accent' : 'bg-track',
                )}
            >
                <span className="flex min-w-0 flex-1 items-start gap-2 px-2.5 py-1">
                    <span
                        className={cn(
                            'mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-lg',
                            done
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-sand text-primary',
                        )}
                    >
                        <Icon className="size-3.5" strokeWidth={1.75} />
                    </span>

                    <span className="min-w-0 flex-1">
                        <span className="type-eyebrow block truncate text-muted-foreground">
                            {label}
                        </span>
                        <span
                            className={cn(
                                'block truncate text-[13px] leading-tight font-semibold',
                                done ? 'text-olive-mid' : 'text-foreground',
                            )}
                        >
                            {title}
                        </span>
                    </span>
                </span>

                <span className="flex w-11 shrink-0 items-center justify-center self-stretch">
                    <span
                        className={cn(
                            'flex size-6 items-center justify-center rounded-full',
                            done ? 'bg-primary' : 'hollow border-2',
                        )}
                    >
                        {done && (
                            <Check
                                className="size-3.5 text-primary-foreground"
                                strokeWidth={2.5}
                            />
                        )}
                    </span>
                </span>
            </div>
        </div>
    );
}

/** Eine Vorlesung im Raster, wie {@see CourseBlock} sie zeichnet. */
function DayCourse({
    top,
    height,
    range,
    title,
}: {
    top: number;
    height: number;
    range: string;
    title: string;
}) {
    return (
        <div className="absolute right-0" style={{ top, height, left: GUTTER }}>
            <div className="flex h-full w-full items-center gap-2.5 overflow-hidden rounded-xl border-l-[3px] border-l-olive-mid bg-sand px-2.5 py-2">
                <GraduationCap
                    className="size-5 shrink-0 text-olive-mid"
                    strokeWidth={1.5}
                />
                <span className="min-w-0 flex-1">
                    <span className="type-eyebrow block truncate text-olive-mid">
                        {range}
                    </span>
                    <span className="block truncate text-sm font-semibold text-foreground">
                        {title}
                    </span>
                </span>
            </div>
        </div>
    );
}

/**
 * Der Kalendertag, wie ihn die App zeigt.
 *
 * Nachgebaut in klein, aber Stück für Stück dasselbe: die Datumszeile mit den
 * beiden Pfeilen, der Weg zurück in den Monat, der Knopf „Tag neu ordnen" mit
 * der Figur davor, und darunter das Raster auf seiner Blattkarte — Stunden
 * links in `text-faintest`, Haarlinien in `border`, die Aufsteh-Marke an ihrer
 * echten Minute.
 *
 * Der echte Tag rechnet seine Höhen aus Aufsteh- und Schlafenszeit und legt
 * jeden Block über `PlacedBlock` hinein. Hier stehen die Minuten fest — es ist
 * ein Bild und kein Kalender.
 */
function PhoneDay() {
    const hours = [8, 9, 10, 11, 12];

    return (
        <div className="flex flex-col gap-3">
            <header className="flex items-center gap-2">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-full text-primary opacity-30">
                    <ChevronLeft className="size-5" />
                </span>
                <span className="flex-1 text-center text-[16px] leading-tight font-bold text-primary">
                    Heute · Mittwoch, 9. September
                </span>
                <span className="flex size-9 shrink-0 items-center justify-center rounded-full text-primary">
                    <ChevronRight className="size-5" />
                </span>
            </header>

            <div className="flex items-center justify-center">
                <span className="flex items-center gap-1.5 text-sm font-semibold text-primary">
                    <LayoutGrid className="size-3.5" />
                    Monat
                </span>
            </div>

            {/* Der Knopf, mit dem die KI den Tag umsortiert — die Figur steht
                davor, weil hier die KI spricht (§8). */}
            <div className="flex justify-center">
                <span className="inline-flex h-11 items-center gap-2 rounded-full border border-primary/25 bg-accent px-5 text-sm font-semibold text-primary">
                    <AiMascot variant="mark" className="size-4 shrink-0" />
                    Tag neu ordnen
                </span>
            </div>

            <div className="rounded-2xl bg-card px-4 py-5">
                <div className="relative" style={{ height: 5.75 * HOUR }}>
                    {hours.map((hour) => (
                        <div
                            key={hour}
                            className="absolute inset-x-0 flex h-0 items-center gap-2"
                            style={{ top: at(hour) }}
                        >
                            <span
                                className="shrink-0 pr-2 text-right text-[11px] leading-none font-medium text-faintest tabular-nums"
                                style={{ width: GUTTER }}
                            >
                                {String(hour).padStart(2, '0')}:00
                            </span>
                            <span className="h-px flex-1 bg-border" />
                        </div>
                    ))}

                    {/* Die Aufsteh-Marke an ihrer echten Minute. */}
                    <div
                        className="absolute inset-x-0 flex h-0 -translate-y-1/2 items-center gap-2"
                        style={{ top: at(7) }}
                    >
                        <span
                            className="flex shrink-0 items-center justify-end gap-1 pr-1.5 text-[11px] leading-none font-semibold text-foreground tabular-nums"
                            style={{ width: GUTTER }}
                        >
                            <Sun className="size-3" strokeWidth={2} />
                            07:00
                        </span>
                        <span className="h-px flex-1 bg-border" />
                    </div>

                    {/* Was heute schon getan ist. */}
                    <DayBlock
                        top={at(8)}
                        height={0.5 * HOUR - 4}
                        label="08:00 – 08:30"
                        title="Frühstücken"
                        icon={Sun}
                        done
                    />

                    <DayCourse
                        top={at(10)}
                        height={1.5 * HOUR - 4}
                        range="10:00 – 11:30"
                        title="Englisch"
                    />

                    {/* Die Gewohnheit rastet nach der Vorlesung ein.
                        Gestrichelt, weil sie an einem Moment hängt und nicht
                        an einer Uhrzeit — und das ist die eine Stelle des
                        Films, an der die Feder gilt: §4 erlaubt Überschwingen
                        nur, wenn etwas einrastet. */}
                    <DayBlock
                        top={at(11.75)}
                        height={0.75 * HOUR}
                        label="nach der Vorlesung"
                        title="Karteikarten wiederholen"
                        icon={LayoutGrid}
                        exact={false}
                        snap
                    />
                </div>
            </div>
        </div>
    );
}

/** Eine Gewohnheit, wie sie nach dem Anlegen in der Übersicht steht. */
const FLASHCARDS: Habit = {
    id: -1,
    title: 'Karteikarten wiederholen',
    scheduleLabel: 'nach der Vorlesung',
    // Keine Uhr: Die Gewohnheit hängt an einem Moment, nicht an einer Stunde —
    // und genau das ist der Satz, den die Szene daneben sagt.
    timeLabel: null,
    repeatLabel: 'nach der Vorlesung',
    behaviorType: 'learning',
    templateKey: 'karteikarten',
    measureLabel: '15 Min',
    smallestStep: null,
    motivation: null,
    completedAt: null,
    companion: null,
    appointmentId: null,
    appointmentDays: [],
};

/**
 * Was heute schon getan ist — dieselbe Gewohnheit wie im Kalendertag.
 *
 * Sie steht in der Liste, weil die Quote darüber sonst nicht aufgeht: „1 von 2
 * Gewohnheiten" mit einer einzigen Zeile darunter ist eine Rechnung, die man
 * nachprüft und die nicht stimmt.
 */
const BREAKFAST: Habit = {
    ...FLASHCARDS,
    id: -3,
    title: 'Frühstücken',
    scheduleLabel: '08:00',
    timeLabel: '08:00',
    repeatLabel: 'täglich',
    behaviorType: 'nutrition',
    templateKey: 'fruehstuecken',
    measureLabel: '30 Min',
    completedAt: '08:30',
};

/** Dieselbe Gewohnheit, am Donnerstag zu zweit. */
const FLASHCARDS_TOGETHER: Habit = {
    ...FLASHCARDS,
    id: -2,
    companion: {
        name: 'Lea',
        initial: 'L',
        pending: false,
        insteadOf: null,
        repeatHabitId: null,
        repeatDays: [],
    },
};

/** Der Abspieler braucht die Zeilen, fasst sie aber nie an. */
const NOOP = () => undefined;

function DemoHabitRow({ habit }: { habit: Habit }) {
    return (
        <ul className="contents">
            <HabitRow
                habit={habit}
                selfInitial="B"
                onToggle={NOOP}
                onStuck={NOOP}
                onAskCompany={null}
                onWithdraw={null}
                onRepeat={null}
            />
        </ul>
    );
}

/**
 * Die sieben Bilder des Auftakts — drei aus dem Studienalltag, drei aus der
 * App, dann die erste Frage.
 *
 * Die drei ersten sind bewusst konkret und nicht stimmungsvoll: eine
 * Sporttasche, die stehen bleibt; Nudeln mit Pesto zum dritten Mal; ein
 * Schreibtisch, während draußen etwas los ist. Sie treffen die drei Bereiche,
 * die die Umfrage vorn sieht — Bewegung 18, Ernährung 14, Soziales 7 von 25 —
 * und sie sind wiedererkennbar. Ein Stift auf einem Blatt wäre schön gewesen
 * und hätte über das Studium nichts gesagt.
 *
 * Der Bruch nach dem dritten Bild ist die Aussage des ganzen Films: Das
 * Problem ist ein Bild, das jeder kennt; die Antwort ist die Oberfläche
 * selbst, ungeschminkt und in ihren echten Bausteinen. Eine gezeichnete
 * Antwort wäre ein Versprechen, das die App danach einlösen müsste.
 *
 * Die Dauern sind ungleich und großzügig: sieben Sekunden für die Filmbilder,
 * sechs bis sechseinhalb für die Bilder aus der App. Ein Bild und ein Satz
 * daneben brauchen zusammen länger als das Bild allein — und sieben gleich
 * lange Szenen wären eine Diaschau, ungleiche lesen sich als Schnitt.
 *
 * Sie sind länger als die Clips, und die laufen deshalb einmal von vorn los.
 * Das ist gewollt: Ein Clip in halber Geschwindigkeit sähe aus wie eine
 * Zeitlupe, und die Szene soll stehen dürfen, ohne dass das Bild schleicht.
 *
 * Die letzte Dauer zählt niemand ab: Auf dem sechsten Bild hält der Abspieler
 * an und wartet auf den Knopf.
 */
export const INTRO_SCENES: IntroScene[] = [
    {
        id: 'sport',
        headline: (
            <>
                Der Tag an der Uni ist voll. <Mark>Die Sporttasche</Mark> bleibt
                liegen.
            </>
        ),
        aside: (
            <>
                <Strong>Seit Montag liegt sie neben der Tür.</Strong> Morgens
                ist es zu früh, zwischen den Vorlesungen zu knapp, und abends
                bist du zu müde.
            </>
        ),
        chapter: 'Sport',
        duration: 7000,
        visual: (
            <IntroClip
                eager
                src="/onboarding/sport.mp4"
                poster="/onboarding/sport.jpg"
            />
        ),
    },
    {
        id: 'essen',
        headline: (
            <>
                Nach acht Stunden Uni wird aus Kochen{' '}
                <Mark>Nudeln mit Pesto</Mark>.
            </>
        ),
        aside: (
            <>
                <Strong>Zum dritten Mal diese Woche.</Strong> Nicht, weil du
                nicht willst, sondern weil am Ende des Tages die Zeit fehlt, in
                Ruhe zu kochen.
            </>
        ),
        chapter: 'Ernährung',
        duration: 7000,
        visual: (
            <IntroClip
                src="/onboarding/essen.mp4"
                poster="/onboarding/essen.jpg"
            />
        ),
    },
    {
        id: 'freunde',
        headline: (
            <>
                Deine Dozenten siehst du täglich. Freunde nur,{' '}
                <Mark>wenn der Tag passt</Mark>.
            </>
        ),
        aside: (
            <>
                <Strong>Es liegt nicht am Wollen.</Strong> Jeder hat einen
                anderen Stundenplan, und irgendwann fragt keiner mehr.
            </>
        ),
        chapter: 'Freunde',
        duration: 7000,
        visual: (
            <IntroClip
                src="/onboarding/hoersaal.mp4"
                poster="/onboarding/hoersaal.jpg"
            />
        ),
    },
    {
        id: 'ort',
        headline: (
            <>
                Align gibt jedem Vorhaben <Mark>einen festen Platz</Mark> im
                Tag.
            </>
        ),
        aside: (
            <>
                Nicht irgendwann um 17 Uhr, sondern{' '}
                <Strong>direkt nach der Vorlesung</Strong>. Da, wo du ohnehin
                schon bist.
            </>
        ),
        chapter: 'Dein Tag mit Align',
        duration: 6500,
        visual: (
            <IntroPhone title="Kalender">
                <PhoneDay />
            </IntroPhone>
        ),
    },
    {
        id: 'schritt',
        headline: (
            <>
                Und <Mark>die KI</Mark> sagt dir, womit du anfängst.
            </>
        ),
        aside: (
            <>
                Sie schlägt dir den kleinsten ersten Schritt vor, damit der
                Anfang leicht fällt. <Strong>Entscheiden tust du.</Strong>
            </>
        ),
        chapter: 'Der erste Schritt',
        duration: 6000,
        visual: (
            <IntroPhone title="Gewohnheiten">
                {/* Der vierte Schritt des Assistenten, unverändert — genau der
                    Bildschirm, den der Nutzer wenige Minuten später selbst
                    ausfüllt. Die Karte ist das echte `AiSuggestion`:
                    `bg-sand/60` mit der Figur davor. Die gestrichelte Karte,
                    die hier vorher stand, gibt es in der App nirgends; ein
                    Film, der eine Oberfläche erfindet, verspricht etwas, das
                    die App danach einlösen müsste. */}
                <div className="flex flex-col gap-5">
                    <div className="flex items-center gap-1.5">
                        {[0, 1, 2, 3, 4].map((position) => (
                            <span
                                key={position}
                                className={cn(
                                    'h-1 flex-1 rounded-full',
                                    position <= 3 ? 'bg-primary' : 'bg-sand',
                                )}
                            />
                        ))}
                    </div>

                    <div className="flex flex-col gap-2">
                        <p className="type-eyebrow text-muted-foreground">
                            Schritt 4 von 5
                        </p>
                        <h2 className="type-heading">Womit fängt das an?</h2>
                        <p className="text-sm leading-relaxed text-muted-foreground">
                            Ein einziger Handgriff, der in einer Minute getan
                            ist.
                        </p>
                    </div>

                    <AiSuggestion state="speaking">
                        <div className="flex flex-col gap-2">
                            {[
                                'Leg die Karteikarten auf den Schreibtisch.',
                                'Nimm den Stapel aus der Tasche.',
                                'Lies eine einzige Karte.',
                            ].map((candidate, position) => (
                                <span
                                    key={candidate}
                                    className={cn(
                                        CHOICE_TILE,
                                        'px-4 py-3 text-[15px] leading-snug',
                                        position === 0
                                            ? 'border-primary'
                                            : 'border-transparent',
                                    )}
                                >
                                    {candidate}
                                </span>
                            ))}
                        </div>
                    </AiSuggestion>
                </div>
            </IntroPhone>
        ),
    },
    {
        id: 'zuzweit',
        headline: (
            <>
                Und wenn du willst, machst du das <Mark>nicht allein</Mark>.
            </>
        ),
        aside: (
            <>
                Du fragst eine Person für einen einzigen Tag.{' '}
                <Strong>Mehr braucht es nicht.</Strong>
            </>
        ),
        chapter: 'Zu zweit',
        duration: 6000,
        visual: (
            <IntroPhone title="Übersicht">
                {/* Die Übersicht, ein Stück gescrollt. Das ist kein Kniff,
                    sondern der Zustand, in dem man diesen Bildschirm meistens
                    sieht — und er stellt das Feature in die Mitte statt ans
                    untere Ende. Vom Fortschritt bleibt der Balken mit seinen
                    beiden Zeilen; Begrüßung und Prozentzahl liegen darüber,
                    außerhalb des Bildes.

                    Feste Schriftgrade statt `type-display`: Die Stufe rechnet
                    in `vw` und meint damit das Fenster, nicht dieses Telefon. */}
                <div className="-mt-20 flex flex-col gap-6">
                    <div className="rounded-2xl bg-card px-6 py-7 shadow-[var(--shadow-lift)]">
                        <p className="type-eyebrow text-muted-foreground">
                            Heute
                        </p>
                        <p className="mt-2 flex items-baseline gap-2">
                            <span className="text-[44px] leading-none font-bold tracking-[-0.03em] text-primary tabular-nums">
                                50 %
                            </span>
                            <span className="text-base text-muted-foreground">
                                Erledigt
                            </span>
                        </p>
                        <div className="mt-5 h-2.5 w-full overflow-hidden rounded-full bg-sand">
                            <div className="h-full w-1/2 rounded-full bg-primary" />
                        </div>
                        <p className="mt-2 flex flex-wrap justify-between gap-x-4 text-[11px] text-muted-foreground">
                            <span>1 von 2 Gewohnheiten</span>
                            <span>
                                Letzte 30 Tage:{' '}
                                <span className="font-semibold text-foreground tabular-nums">
                                    24 von 30
                                </span>{' '}
                                Mal erledigt
                            </span>
                        </p>
                    </div>

                    <div>
                        <h2 className="type-subheading">
                            Heutige Gewohnheiten
                        </h2>
                        <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                            Was heute ansteht, von früh nach spät.
                        </p>

                        <div className="mt-3 rounded-2xl bg-card px-5 py-5">
                            <ul className="flex flex-col gap-6">
                                <DemoHabitRow habit={BREAKFAST} />
                                <DemoHabitRow habit={FLASHCARDS_TOGETHER} />
                            </ul>
                        </div>
                    </div>

                    {/* Der Rahmen des Tages, wie {@see SleepCard} ihn zeigt.
                        Er steht am Fuß der Übersicht und wird von der Bildkante
                        angeschnitten — er gehört zur Seite, aber nicht zu dem
                        Satz, der daneben steht. */}
                    <div className="flex items-center gap-3 rounded-2xl border border-border bg-card px-5 py-4">
                        <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sand text-primary">
                            <Moon className="size-5" strokeWidth={1.5} />
                        </span>

                        <span className="min-w-0 flex-1">
                            <span className="type-eyebrow text-muted-foreground">
                                Dein Rahmen
                            </span>
                            <span className="mt-0.5 block text-[15px] leading-snug">
                                <span className="font-semibold">23:00</span>{' '}
                                <span className="text-muted-foreground">
                                    Schlafen ·
                                </span>{' '}
                                <span className="font-semibold">07:00</span>{' '}
                                <span className="text-muted-foreground">
                                    Aufstehen
                                </span>
                            </span>
                            <span className="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                                <AlarmClock
                                    className="size-3.5"
                                    strokeWidth={1.5}
                                />
                                Wecker für morgen früh an
                            </span>
                        </span>

                        <ChevronRight className="size-5 shrink-0 text-muted-foreground" />
                    </div>
                </div>
            </IntroPhone>
        ),
    },
];
