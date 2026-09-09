import { GraduationCap } from 'lucide-react';
import type { ReactNode } from 'react';
import { AiMascot } from '@/components/ai-mascot';
import { HabitRow } from '@/components/habit-row';
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
     * Das Kapitel, in dem die Szene steht.
     *
     * Es beantwortet die Frage, die ein Film ohne Kapitel offen lässt: Sehe
     * ich gerade ein Problem oder eine Antwort? Zwei Wörter genügen dafür —
     * und die Zeile trägt zugleich den Strich, der den Umschlag zeigt.
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
 * Die Bühne für ein Bild aus der App.
 *
 * Sie hält nur die Höhe und stellt das Bild in die Mitte — welche Fläche
 * darunter liegt, entscheidet die Szene. Eine Karte, die auf 22rem aufgeblasen
 * wird, stünde mit zwei Zeilen verloren in ihrer Fläche; eine Bühne, die mit
 * dem Inhalt wächst, ließe die Textkante bei jedem Schnitt wandern.
 *
 * `pointer-events-none` und `aria-hidden`, weil hier eine Gewohnheit gezeigt
 * und nicht bedient wird: Die Zeile bringt ihre eigenen Knöpfe mit („Kleinen
 * ersten Schritt", „Mit jemandem zusammen?"), und die führen im Film
 * nirgendwohin. Was die Szene sagt, sagt ihr Text — der wird vorgelesen, das
 * Bild nicht.
 */
function IntroSurface({ children }: { children: ReactNode }) {
    return (
        <div
            aria-hidden="true"
            className={`intro-figure intro-ground pointer-events-none flex items-center justify-center overflow-hidden rounded-[18px] px-6 ${STAGE}`}
        >
            <div className="w-full">{children}</div>
        </div>
    );
}

/** Die Blattkarte, auf der eine Gewohnheit im Tag liegt. */
function IntroCard({ children }: { children: ReactNode }) {
    return (
        <div className="shadow-lift mx-auto flex w-full max-w-md flex-col gap-3 rounded-2xl bg-card px-4 py-5">
            {children}
        </div>
    );
}

/**
 * Der Vorlesungsblock aus dem Kalender.
 *
 * Nachgebaut statt {@see CourseBlock} wiederverwendet: Der echte Block sitzt
 * absolut in einem Stundenraster und braucht eine `PlacedBlock`-Berechnung mit
 * Spalten und Höhe. Was ihn erkennbar macht, sind seine drei Merkmale, und die
 * stehen hier unverändert — `bg-sand` als oberste Stufe der Flächenleiter,
 * eine **durchgezogene** linke Kante in `olive-mid` (eine Vorlesung hat eine
 * echte Uhrzeit) und keine Hakenspalte.
 */
function LectureBlock() {
    return (
        <div className="rounded-xl border-l-[3px] border-olive-mid bg-sand px-3 py-2.5">
            <p className="flex items-center gap-1.5 text-[13px] font-semibold text-olive-mid">
                <GraduationCap className="size-3.5" strokeWidth={1.5} />
                10:00 – 11:30
            </p>
            <p className="mt-0.5 text-[15px] font-semibold">Statistik</p>
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
        chapter: 'Dein Alltag',
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
        chapter: 'Dein Alltag',
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
                Deine Lerngruppe siehst du täglich. Deine Freunde{' '}
                <Mark>seit zwei Wochen nicht</Mark>.
            </>
        ),
        aside: (
            <>
                <Strong>Das hat nichts mit Faulheit zu tun.</Strong> In einem
                vollen Semester hat nichts davon einen festen Platz im Tag.
            </>
        ),
        chapter: 'Dein Alltag',
        duration: 7000,
        visual: (
            <IntroClip
                src="/onboarding/freunde.mp4"
                poster="/onboarding/freunde.jpg"
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
        chapter: 'Mit Align',
        duration: 6500,
        visual: (
            <IntroSurface>
                <IntroCard>
                    <LectureBlock />
                    {/* Die Gewohnheit kommt nach dem Block herein und rastet
                    unter ihm ein. Das ist die eine Stelle des Films, an der
                    die Feder gilt: §4 erlaubt Überschwingen nur, wenn etwas
                    einrastet — und genau das ist hier zu sehen. */}
                    <div className="intro-snap">
                        <DemoHabitRow habit={FLASHCARDS} />
                    </div>
                </IntroCard>
            </IntroSurface>
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
        chapter: 'Mit Align',
        duration: 6000,
        visual: (
            <IntroSurface>
                {/* Die Karte der KI, wie sie überall steht: gestrichelte
                    `ai-line` auf `ai-fill`, die Figur davor. Gestrichelt heißt
                    „noch nicht festgelegt" — ein Vorschlag ist nie schon
                    Zustand (§7.2).

                    Ohne Blattkarte darunter: `ai-fill` ist fast weiß und läge
                    auf `card` als Fläche auf einer gleich hellen Fläche. Auf
                    dem beigen Seitengrund trägt sie sich selbst. */}
                <div className="mx-auto w-full max-w-lg rounded-[14px] border-[1.5px] border-dashed border-ai-line bg-ai-fill p-4">
                    <p className="type-eyebrow flex items-center gap-2 text-primary">
                        <AiMascot
                            state="speaking"
                            className="size-7 shrink-0"
                        />
                        Vorschlag
                    </p>
                    <p className="mt-3 text-[15px] leading-relaxed">
                        Leg die Karteikarten auf den Schreibtisch.
                    </p>
                </div>
            </IntroSurface>
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
        chapter: 'Mit Align',
        duration: 6000,
        visual: (
            <IntroSurface>
                <IntroCard>
                    <DemoHabitRow habit={FLASHCARDS_TOGETHER} />
                    <p className="px-1 text-xs leading-relaxed text-muted-foreground">
                        Was ihr tut, sieht niemand. Nur, dass ihr euch kennt.
                    </p>
                </IntroCard>
            </IntroSurface>
        ),
    },
];
