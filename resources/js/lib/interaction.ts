/**
 * Das Bewegungs- und Berührungs-Vokabular der App — an einer Stelle.
 *
 * Apple, „Designing Fluid Interfaces" §1: Rückmeldung gehört auf das Drücken,
 * nicht auf das Loslassen. §16 (Craft): „Nothing is random — every spacing,
 * timing, and alignment value is a deliberate choice you can defend." Genau
 * dafür stehen diese Konstanten: Ein Knopf in dieser App fühlt sich überall
 * gleich an, weil überall dieselbe Zeile steht.
 *
 * Die Zeitwerte selbst stehen in `app.css` als Tokens (`--duration-press`,
 * `--ease-fluid`, …) — hier werden sie nur zu Gesten zusammengesetzt.
 */

/** Der Druckpunkt: Skalierung auf dem Pointer-Down, nicht auf dem Klick. */
const PRESS = 'motion-safe:active:scale-[0.97]';

/** Sichtbarer Fokus, überall derselbe. */
const FOCUS =
    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';

/** Was jede berührbare Fläche mitbringt. */
const TOUCHABLE = `cursor-pointer ${FOCUS} ${PRESS}`;

/**
 * Die gefüllte Haupthandlung — „Weiter", „Ich nehme mir das vor".
 *
 * Volle Breite, weil sie in dieser App immer am Ende einer Spalte steht.
 */
export const PRIMARY_BUTTON = `inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 text-[15px] font-semibold text-primary-foreground transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50 ${TOUCHABLE}`;

/** Dieselbe Handlung als Kontur — für den zweiten Weg zum selben Ziel. */
export const OUTLINE_BUTTON = `inline-flex h-11 items-center gap-1.5 rounded-full border border-primary px-4 text-sm font-semibold text-primary transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-accent ${TOUCHABLE}`;

/** Der leise Weg: Text, unterstrichen, ohne Fläche. */
export const QUIET_LINK = `font-semibold text-primary underline underline-offset-4 transition-colors duration-[var(--duration-press)] ease-out hover:text-primary/80 active:text-primary/60 cursor-pointer ${FOCUS}`;

/**
 * Der Weg zu einer KI-Funktion — Designsprache §8.
 *
 * Wie {@see QUIET_LINK}, nur mit Platz für die Figur davor. Sie stand hier
 * lange als Buchstabe `✦` im Text; als eigenes Element braucht sie eine
 * eigene Lücke, sonst klebt sie am ersten Wort. Das Zeichen ist der KI
 * vorbehalten und wird für nichts anderes verwendet.
 */
export const AI_LINK = `inline-flex items-center gap-1.5 ${QUIET_LINK}`;

/** Der Abbruch daneben — kein Rahmen, keine Farbe, gleiches Gewicht wie Text. */
export const QUIET_BUTTON = `text-sm text-muted-foreground transition-colors duration-[var(--duration-press)] ease-out hover:text-foreground cursor-pointer ${FOCUS}`;

/**
 * Die Auswahlkachel — Designsprache §5.5.
 *
 * Selektion ist ein 2px-Rahmen, die Füllung ändert sich nicht: ein bewusst
 * leises Muster, das für alle Einfachauswahlen gilt. Die Kante wechselt über
 * die flüssige Grundkurve statt hart umzuspringen.
 */
export const CHOICE_TILE = `rounded-[14px] border-2 bg-card text-left transition-[border-color,scale] duration-[var(--duration-press)] ease-out ${TOUCHABLE}`;

/** Gewählt und nicht gewählt — damit die beiden Zustände nirgends auseinanderlaufen. */
export const CHOICE_TILE_ON = 'border-primary';
export const CHOICE_TILE_OFF = 'border-border hover:border-secondary';

/**
 * Der runde Stepper-Knopf für Uhrzeit und Dauer.
 *
 * Die Fläche ist größer als das Zeichen darin: Ein Pfeil, den man treffen
 * muss, ist kein Pfeil (§10 — Trefferfläche mit Rand).
 */
export const STEPPER_BUTTON = `flex size-8 items-center justify-center rounded-full text-muted-foreground transition-[background-color,color,scale] duration-[var(--duration-press)] ease-out hover:bg-accent hover:text-foreground disabled:pointer-events-none disabled:opacity-30 ${TOUCHABLE}`;

/**
 * Eine Karte, die sich anfassen lässt — sie hebt sich beim Zeigen leicht.
 *
 * Nur für Karten, die irgendwohin führen. Eine Karte, die nur trägt, bleibt
 * flach: Bewegung ohne Ziel ist Dekoration.
 */
export const INTERACTIVE_CARD = `block rounded-xl transition-[scale,box-shadow] duration-[var(--duration-press)] ease-out ${FOCUS} motion-safe:active:scale-[0.995]`;

/**
 * Die Schale jedes Sheets, das von unten kommt.
 *
 * Zehn Sheets tragen dieselbe Zeile — von der Kursliste bis zum Vorschlag der
 * KI. Sie einmal zu schreiben heißt, dass Höhe, Breite und Rundung überall
 * dieselben bleiben: Ein Sheet, das anders sitzt als das davor, liest sich als
 * anderer Ort, obwohl es dieselbe Geste ist.
 */
export const BOTTOM_SHEET =
    'mx-auto max-h-[85vh] max-w-lg gap-0 overflow-y-auto rounded-t-2xl px-5 pt-6 pb-8';
