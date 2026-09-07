import { TriangleAlert } from 'lucide-react';
import { TimeStepper } from '@/components/time-stepper';
import { ToggleSwitch } from '@/components/ui/toggle-switch';
import {
    CHOICE_TILE,
    CHOICE_TILE_OFF,
    CHOICE_TILE_ON,
    QUIET_LINK,
} from '@/lib/interaction';
import {
    asleepWeekdays,
    formatWindow,
    outsideSleepWindowPerDay,
} from '@/lib/sleep';
import { findConflictPerDay, nextFreeTime } from '@/lib/slots';
import { cn } from '@/lib/utils';
import type {
    BusySlot,
    ChainCandidate,
    ScheduleType,
    SituationChoice,
    SleepWindow,
    Weekday,
} from '@/types';

export interface ScheduleTypeOption {
    value: ScheduleType;
    label: string;
    /** Ein Satz unter der Kachel — die Wahl ist ohne ihn nicht zu treffen. */
    description: string;
}

const WEEKDAYS: { value: Weekday; label: string; full: string }[] = [
    { value: 1, label: 'Mo', full: 'Montag' },
    { value: 2, label: 'Di', full: 'Dienstag' },
    { value: 3, label: 'Mi', full: 'Mittwoch' },
    { value: 4, label: 'Do', full: 'Donnerstag' },
    { value: 5, label: 'Fr', full: 'Freitag' },
    { value: 6, label: 'Sa', full: 'Samstag' },
    { value: 7, label: 'So', full: 'Sonntag' },
];

/**
 * Die vollen Namen einiger Tage als Aufzählung — „Samstag und Sonntag".
 *
 * Voll ausgeschrieben und nicht „Sa, So": Der Satz daneben nennt den Tag auch
 * ausgeschrieben, und ein Knopf, der etwas abwählt, sollte klar sagen, was.
 */
function namesOf(days: Weekday[]): string {
    const names = [...days]
        .sort((a, b) => a - b)
        .map(
            (day) =>
                WEEKDAYS.find((candidate) => candidate.value === day)!.full,
        );

    if (names.length <= 1) {
        return names[0] ?? '';
    }

    return `${names.slice(0, -1).join(', ')} und ${names.at(-1)}`;
}

/**
 * Wochentage als lesbare Aufzählung, zusammenhängende Läufe gerafft.
 *
 * Spiegelt `Habit::weekdayLabel()` im Backend. Doppelt gehalten, weil die
 * Vorschau im Wizard noch keinen Server-Umlauf gemacht hat — beide müssen
 * dieselbe Zeile ergeben, sonst ändert sich der Text beim Speichern.
 */
export function formatWeekdays(days: Weekday[]): string {
    const sorted = [...days].sort((a, b) => a - b);

    if (sorted.length === 0) {
        return 'kein Tag gewählt';
    }

    if (sorted.length === 7) {
        return 'täglich';
    }

    const runs = sorted.reduce<Weekday[][]>((groups, day) => {
        const current = groups.at(-1);

        if (current && day === current[current.length - 1]! + 1) {
            current.push(day);
        } else {
            groups.push([day]);
        }

        return groups;
    }, []);

    const label = (day: Weekday) =>
        WEEKDAYS.find((candidate) => candidate.value === day)!.label;

    return runs
        .map((run) =>
            run.length >= 3
                ? `${label(run[0]!)}–${label(run.at(-1)!)}`
                : run.map(label).join(', '),
        )
        .join(', ');
}

/**
 * An welchen Tagen — die Reihe, die beide Anker teilen.
 *
 * Eine Situation braucht sie so sehr wie eine Uhrzeit: „Nach dem Aufstehen
 * lesen" hieß, solange es sie hier nicht gab, zwangsläufig auch sonntags. Eine
 * Komponente für beide, damit die Bedienung dieselbe bleibt — der Unterschied
 * zwischen den Ankern ist der Moment, nicht der Rhythmus.
 *
 * `blocked` sind Tage, an denen der gewählte Moment schon vergeben ist. Sie
 * bleiben sichtbar, damit erkennbar ist, was fehlt, und der Satz darunter sagt,
 * wem sie gehören — dieselbe Behandlung wie bei einer vergebenen Situation.
 */
export function WeekdayPicker({
    days,
    onChange,
    blocked = [],
    blockedBy = null,
    blockedHint,
}: {
    days: Weekday[];
    onChange: (days: Weekday[]) => void;
    blocked?: Weekday[];
    blockedBy?: string | null;
    /**
     * Warum die Tage gesperrt sind — als fertiger Satz.
     *
     * Zwei Gründe teilen sich diese Reihe: Bei einer Situation hält den Tag
     * schon jemand anderes, bei einer Kette läuft der Vorgänger dort gar
     * nicht. „Hängt dort schon" wäre im zweiten Fall schlicht falsch.
     */
    blockedHint?: (blocked: Weekday[], owner: string) => string;
}) {
    const free = WEEKDAYS.filter((day) => !blocked.includes(day.value));
    const isDaily =
        free.length > 0 && free.every((day) => days.includes(day.value));

    function toggleDay(day: Weekday) {
        onChange(
            days.includes(day)
                ? days.filter((candidate) => candidate !== day)
                : [...days, day].sort((a, b) => a - b),
        );
    }

    return (
        <div className="flex flex-col gap-3">
            <div className="flex items-baseline justify-between gap-3">
                <p className="type-eyebrow text-muted-foreground">
                    An diesen Tagen
                </p>
                {/* Die Abkürzung für den Regelfall: Wer täglich will, tippt
                    nicht siebenmal. Gedrückt, sobald alle wählbaren Tage an
                    sind — der Schalter beschreibt den Zustand, er merkt sich
                    keinen eigenen. */}
                <button
                    type="button"
                    aria-pressed={isDaily}
                    onClick={() =>
                        onChange(
                            free.map((day) => day.value).sort((a, b) => a - b),
                        )
                    }
                    className={cn(
                        'cursor-pointer rounded-full border px-3 py-1 text-xs font-semibold',
                        'transition-[background-color,border-color,color] duration-[var(--duration-press)] ease-out',
                        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
                        isDaily
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-card text-muted-foreground hover:border-secondary',
                    )}
                >
                    täglich
                </button>
            </div>

            <div
                role="group"
                aria-label="Wochentage"
                className="flex flex-wrap gap-2"
            >
                {WEEKDAYS.map((day) => {
                    const isBlocked = blocked.includes(day.value);
                    const isSelected = !isBlocked && days.includes(day.value);

                    return (
                        <button
                            key={day.value}
                            type="button"
                            disabled={isBlocked}
                            aria-pressed={isSelected}
                            aria-label={day.full}
                            onClick={() => toggleDay(day.value)}
                            className={cn(
                                'flex size-12 items-center justify-center rounded-full border text-[15px] font-semibold',
                                'transition-[background-color,border-color,color,scale] duration-[var(--duration-press)] ease-out',
                                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
                                isBlocked
                                    ? /* Ohne Kante und ohne Zeiger: Was keine
                                         Wahl ist, sieht auch nicht wie eine aus
                                         — dieselbe Behandlung wie die vergebene
                                         Situationszeile (§16). */
                                      'border-transparent text-muted-foreground/50'
                                    : cn(
                                          'cursor-pointer motion-safe:active:scale-[0.92]',
                                          isSelected
                                              ? 'border-primary bg-primary text-primary-foreground'
                                              : 'border-border bg-card text-foreground hover:border-secondary',
                                      ),
                            )}
                        >
                            {day.label}
                        </button>
                    );
                })}
            </div>

            {/* §1.5 — sagt, was gilt, ohne Vorwurf: Der Satz nennt den
                Besitzer der gesperrten Tage, damit die grauen Kreise nicht
                unerklärt dastehen. */}
            {blocked.length > 0 && blockedBy !== null && (
                <p className="text-xs leading-relaxed text-muted-foreground">
                    {blockedHint === undefined
                        ? `${formatWeekdays(blocked)} hängt dort schon „${blockedBy}".`
                        : blockedHint(blocked, blockedBy)}
                </p>
            )}
        </div>
    );
}

/**
 * Die Situationsauswahl — der `dynamic`-Zweig des SchedulePickers.
 *
 * Die Liste ist abschließend, und das ist der Punkt: Angeboten wird nur, was
 * die App ausrechnen kann — der Schlafplan sagt, wann jemand aufsteht und ins
 * Bett geht, der Stundenplan, wann die Vorlesungen enden.
 *
 * Das Freitextfeld stand hier einmal daneben. Es klang nach Freiheit, war aber
 * das Gegenteil: „Wenn ich aus der Bib komme" konnte die App nirgends
 * hinlegen, also landete es mittags — bei jedem, egal wann er aus der Bib
 * kommt. Wer einen Moment braucht, den die Liste nicht kennt, hängt seine
 * Gewohnheit an eine andere: Eine Kette weiß die Uhrzeit, eine Annahme rät sie.
 *
 * Unter der Liste stehen die Wochentage — aber erst, wenn ein Moment gewählt
 * ist: Tage ohne Moment sind keine Aussage, und welche davon frei sind, hängt
 * am gewählten Moment.
 */
export function SituationPicker({
    suggestions,
    value,
    onChange,
    days,
    onDaysChange,
}: {
    suggestions: SituationChoice[];
    value: string;
    onChange: (value: string) => void;
    days: Weekday[];
    onDaysChange: (days: Weekday[]) => void;
}) {
    const chosen = suggestions.find(
        (suggestion) => suggestion.situation === value,
    );

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col gap-2">
                {suggestions.map(({ situation, takenBy, takenDays }) => {
                    const isSelected = value === situation;

                    // Ein an allen sieben Tagen vergebener Moment ist keine Wahl:
                    // Er bleibt sichtbar, damit erkennbar ist, wohin die Gewohnheit
                    // gehört, die ihn hält — aber er lässt sich nicht ein zweites
                    // Mal nehmen. An einzelnen Tagen belegt heißt dagegen nur, dass
                    // diese Kreise unten fehlen.
                    if (takenDays.length === 7 && takenBy !== null) {
                        return (
                            <div
                                key={situation}
                                /* Ohne Kante: Was keine Wahl ist, sieht auch nicht
                               wie eine aus. Die gestrichelte Kante gehört
                               „Eigene Situation" — sie steht für offen, nicht
                               für vergeben, und beide dürfen sich nicht
                               gleichen (§16 Familiarity). */
                                className="flex items-baseline justify-between gap-3 px-4 py-2 text-[15px] text-muted-foreground/70"
                            >
                                <span>{situation}</span>
                                <span className="shrink-0 truncate text-xs">
                                    {takenBy}
                                </span>
                            </div>
                        );
                    }

                    return (
                        <button
                            key={situation}
                            type="button"
                            aria-pressed={isSelected}
                            onClick={() => {
                                onChange(situation);
                                // Belegte Tage fallen mit der Wahl heraus.
                                // Sonst stünden sie ausgegraut da und wären
                                // trotzdem mitgeschickt — der Server wiese es
                                // ab, und die Absage käme für etwas, das gar
                                // nicht zu sehen war.
                                onDaysChange(
                                    days.filter(
                                        (day) => !takenDays.includes(day),
                                    ),
                                );
                            }}
                            className={cn(
                                CHOICE_TILE,
                                'flex items-baseline justify-between gap-3 px-4 py-3 text-[15px]',
                                isSelected ? CHOICE_TILE_ON : CHOICE_TILE_OFF,
                            )}
                        >
                            <span>{situation}</span>
                            {/* Teilweise vergeben: Der Moment steht offen, aber
                            nicht an jedem Tag — das gehört vor die Wahl, nicht
                            erst danach. */}
                            {takenDays.length > 0 && takenBy !== null && (
                                <span className="shrink-0 text-xs text-muted-foreground">
                                    {formatWeekdays(takenDays)} belegt
                                </span>
                            )}
                        </button>
                    );
                })}
            </div>

            {chosen !== undefined && (
                <WeekdayPicker
                    days={days}
                    onChange={onDaysChange}
                    blocked={chosen.takenDays}
                    blockedBy={chosen.takenBy}
                />
            )}
        </div>
    );
}

/**
 * Der Wann-Teil der Wenn-Dann-Planung, in zwei Formen.
 *
 * Situation zuerst und voreingestellt: time-blocking.md begründet, dass eine
 * Situation Verhalten von selbst auslöst, während eine Uhrzeit aktiv erinnert
 * werden muss. Die feste Uhrzeit ist die Ausnahme für Gewohnheiten, die real
 * an einem Zeitpunkt hängen — kein gleichrangiger zweiter Weg.
 */
export function SchedulePicker({
    scheduleTypes,
    scheduleType,
    onScheduleTypeChange,
    time,
    onTimeChange,
    days,
    onDaysChange,
    times,
    onTimesChange,
    chainCandidates = [],
    chainedTo = null,
    onChainedToChange,
    busySlots = [],
    sleepWindows = [],
    durationMinutes = 0,
    children,
}: {
    scheduleTypes: ScheduleTypeOption[];
    scheduleType: ScheduleType;
    onScheduleTypeChange: (value: ScheduleType) => void;
    time: string;
    onTimeChange: (value: string) => void;
    days: Weekday[];
    onDaysChange: (days: Weekday[]) => void;
    /**
     * Abweichende Uhrzeiten je Wochentag — leer heißt: überall dieselbe.
     *
     * Der Regelfall bleibt damit eine Zahl. Erst wer den Schalter umlegt,
     * bekommt sieben, und auch dann steht nur drin, was wirklich abweicht.
     */
    times: Partial<Record<Weekday, string>>;
    onTimesChange: (times: Partial<Record<Weekday, string>>) => void;
    /** Woran sich anhängen lässt — leer heißt: es gibt noch nichts. */
    chainCandidates?: ChainCandidate[];
    chainedTo?: number | null;
    onChainedToChange?: (id: number) => void;
    /** Was im Tag schon belegt ist, für den Überschneidungshinweis. */
    busySlots?: BusySlot[];
    /** Der Schlafrahmen je Wochentag — für den Hinweis, wenn die Uhrzeit außerhalb läge. */
    sleepWindows?: SleepWindow[];
    /** Wie lange die Gewohnheit dauert — damit auch ihr Ende die Luft einhält. */
    durationMinutes?: number;
    /** Die Situationsauswahl — sie bleibt im Wizard, wo ihre Vorschläge herkommen. */
    children: React.ReactNode;
}) {
    // Abweichende Zeiten sind kein eigener Zustand, sondern eine Beobachtung:
    // Steht etwas in der Abbildung, gilt sie. So kann die Anzeige nicht von
    // dem abweichen, was gespeichert wird.
    const perDay = Object.keys(times).length > 0;

    const chosenChain = chainCandidates.find(
        (candidate) => candidate.id === chainedTo,
    );

    // Je Tag mit seiner Uhrzeit: Läge nur der Dienstag falsch, meldete eine
    // Prüfung über die gemeinsame Zeit entweder nichts oder alles.
    const conflict = findConflictPerDay(
        time,
        times,
        days,
        busySlots,
        durationMinutes,
    );
    const free =
        conflict === null
            ? null
            : nextFreeTime(time, days, busySlots, durationMinutes);

    // Der Rahmen aus dem Schlafplan: Der Server weist eine Uhrzeit außerhalb
    // ohnehin ab — die Oberfläche sagt es vorher, mit demselben Ergebnis.
    const asleep = outsideSleepWindowPerDay(time, times, days, sleepWindows);

    // Und alle betroffenen Tage, für den Ausweg daneben. Er wird nur
    // angeboten, wenn danach noch ein Tag übrig bleibt: Alles abzuwählen ist
    // keine Lösung, sondern eine Gewohnheit ohne Tag.
    const asleepDays = asleepWeekdays(time, days, sleepWindows);
    const daysLeft = days.filter((day) => !asleepDays.includes(day));

    return (
        <div className="flex flex-col gap-4">
            {/* Kacheln statt einer Tab-Leiste: Drei Formen nebeneinander sind
                auf einem Telefon nicht mehr zu lesen, und die dritte braucht
                ohnehin einen erklärenden Satz — „Wenn es sich ergibt" erklärt
                sich nicht aus zwei Wörtern. Dieselbe Kachel wie bei der
                Richtungswahl in Schritt 1. */}
            <div
                role="group"
                aria-label="Art der Planung"
                className="grid gap-2 sm:grid-cols-2"
            >
                {scheduleTypes.map((option) => {
                    const isSelected = scheduleType === option.value;

                    return (
                        <button
                            key={option.value}
                            type="button"
                            aria-pressed={isSelected}
                            onClick={() => onScheduleTypeChange(option.value)}
                            className={cn(
                                CHOICE_TILE,
                                'flex flex-col gap-1 px-4 py-3',
                                isSelected ? CHOICE_TILE_ON : CHOICE_TILE_OFF,
                            )}
                        >
                            {/* Kein ✦: Das Zeichen ist den Momenten
                                vorbehalten, in denen die App mitdenkt (§8) —
                                eine Situation zu wählen ist eine eigene
                                Entscheidung, keine KI-Funktion. */}
                            <span className="text-[15px] font-semibold">
                                {option.label}
                            </span>
                            <span className="text-xs leading-relaxed text-muted-foreground">
                                {option.description}
                            </span>
                        </button>
                    );
                })}
            </div>

            {scheduleType === 'chained' ? (
                /* Das Domino-Prinzip: Eine bestehende Gewohnheit ist der
                   zuverlässigste Auslöser, den es gibt (time-blocking.md).
                   Alissa beschreibt es im Interview von selbst — „wenn ich dann
                   im Bett bin, kann ich es direkt machen". */
                <div className="flex flex-col gap-2">
                    {chainCandidates.length === 0 ? (
                        <p className="rounded-2xl bg-card p-4 text-sm leading-relaxed text-muted-foreground">
                            Dafür braucht es eine Gewohnheit, die schon läuft.
                            Sobald du eine zweite hast, kannst du sie hier
                            aneinanderhängen.
                        </p>
                    ) : (
                        <>
                            {/* Die Luft gilt auch in der Kette: „danach"
                                heißt nach dem Ende plus einer Viertelstunde,
                                nicht in derselben Minute. */}
                            <p className="text-xs leading-relaxed text-muted-foreground">
                                Eine angehängte Gewohnheit beginnt eine
                                Viertelstunde nach dem Ende der vorherigen. Das
                                ist Zeit zum Umschalten. Rückt die eine, rückt
                                die andere mit.
                            </p>
                            {chainCandidates.map((candidate) => {
                                const isSelected = chainedTo === candidate.id;

                                return (
                                    <button
                                        key={candidate.id}
                                        type="button"
                                        aria-pressed={isSelected}
                                        onClick={() => {
                                            onChainedToChange?.(candidate.id);
                                            // Die Tage des neuen Vorgängers
                                            // werden zur Vorgabe: Wer wechselt,
                                            // nimmt sonst eine Auswahl mit, die
                                            // beim anderen gar nicht existiert.
                                            onDaysChange(candidate.weekdays);
                                        }}
                                        className={cn(
                                            CHOICE_TILE,
                                            'flex flex-col gap-0.5 px-4 py-3',
                                            isSelected
                                                ? CHOICE_TILE_ON
                                                : CHOICE_TILE_OFF,
                                        )}
                                    >
                                        <span className="text-[15px] font-semibold">
                                            {candidate.title}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {candidate.anchor}
                                            {/* Erst die Dauer der vorigen
                                            Gewohnheit macht diesen Satz
                                            möglich — sie sagt, wann sie fertig
                                            ist. */}
                                            {candidate.startsAt !== null &&
                                                ` · danach ab ${candidate.startsAt}`}
                                        </span>
                                    </button>
                                );
                            })}

                            {/* An welchen der Tage des Vorgängers. „Immer wenn
                                die andere läuft" war bisher die einzige
                                Möglichkeit — wer montags und mittwochs joggt,
                                will danach aber vielleicht nur mittwochs
                                dehnen.

                                Gesperrt ist, woran der Vorgänger nicht läuft:
                                Dort gäbe es kein „danach". */}
                            {chosenChain !== undefined && (
                                <WeekdayPicker
                                    days={days}
                                    onChange={onDaysChange}
                                    blocked={WEEKDAYS.map(
                                        (day) => day.value,
                                    ).filter(
                                        (day) =>
                                            !chosenChain.weekdays.includes(day),
                                    )}
                                    blockedBy={chosenChain.name}
                                    blockedHint={(gesperrt, wer) =>
                                        `„${wer}" läuft ${formatWeekdays(gesperrt)} nicht.`
                                    }
                                />
                            )}
                        </>
                    )}
                </div>
            ) : scheduleType === 'dynamic' ? (
                children
            ) : (
                <div className="flex flex-col gap-5">
                    {/* Eine Uhrzeit für alle Tage — der Regelfall, und deshalb
                        der große Auftritt. Wer den Schalter darunter umlegt,
                        bekommt stattdessen eine Zeile je Tag.

                        Dieselbe Sprache wie im Schlafplan: dort steht „Diese
                        Zeiten für alle Tage" neben den Zeiten eines einzelnen
                        Tages. Zwei Wege für dieselbe Frage sähen aus wie zwei
                        verschiedene Fragen. */}
                    {!perDay && (
                        <div className="flex flex-col items-center gap-2 rounded-2xl bg-card p-6">
                            <TimeStepper
                                value={time}
                                onChange={onTimeChange}
                                label="Uhrzeit"
                            />
                        </div>
                    )}

                    {perDay && (
                        <div className="flex flex-col gap-3 rounded-2xl bg-card p-4">
                            {days.length === 0 ? (
                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    Wähl unten die Tage, dann steht hier für
                                    jeden eine eigene Uhrzeit.
                                </p>
                            ) : (
                                [...days]
                                    .sort((a, b) => a - b)
                                    .map((day) => (
                                        <div
                                            key={day}
                                            className="flex items-center justify-between gap-3 border-b border-border pb-3 last:border-0 last:pb-0"
                                        >
                                            <span className="text-[15px] font-semibold">
                                                {
                                                    WEEKDAYS.find(
                                                        (candidate) =>
                                                            candidate.value ===
                                                            day,
                                                    )?.full
                                                }
                                            </span>
                                            <TimeStepper
                                                value={times[day] ?? time}
                                                onChange={(value) =>
                                                    onTimesChange({
                                                        ...times,
                                                        [day]: value,
                                                    })
                                                }
                                                label={`Uhrzeit am ${
                                                    WEEKDAYS.find(
                                                        (candidate) =>
                                                            candidate.value ===
                                                            day,
                                                    )?.full
                                                }`}
                                                size="compact"
                                            />
                                        </div>
                                    ))
                            )}
                        </div>
                    )}

                    <label className="flex cursor-pointer items-center justify-between gap-4 rounded-2xl bg-card px-4 py-3">
                        <span className="text-[15px] font-semibold">
                            Jeden Tag zur selben Uhrzeit
                        </span>
                        <ToggleSwitch
                            checked={!perDay}
                            onChange={(gleich) =>
                                // Aus heißt: jeder Tag startet bei der
                                // gemeinsamen Zeit und lässt sich von dort
                                // wegdrehen. An heißt: die Abweichungen fallen
                                // weg — und zwar sichtbar, nicht still im
                                // Hintergrund gespeichert.
                                onTimesChange(
                                    gleich
                                        ? {}
                                        : Object.fromEntries(
                                              days.map((day) => [day, time]),
                                          ),
                                )
                            }
                            label="Jeden Tag zur selben Uhrzeit"
                        />
                    </label>

                    {/* Der Server weist dasselbe ab — hier steht es vorher,
                        mit der Erklärung dazu und einem Sprung zur nächsten
                        freien Zeit. Möglich wird der Satz erst durch die
                        Dauer, die das Ende des anderen Blocks kennt. */}
                    {conflict !== null && (
                        <p className="flex items-start gap-2 rounded-2xl bg-sand/50 p-3 text-xs leading-relaxed text-muted-foreground">
                            <TriangleAlert
                                className="mt-0.5 size-4 shrink-0 text-primary"
                                strokeWidth={1.5}
                                aria-hidden="true"
                            />
                            <span>
                                Zu nah an „{conflict.title}" ({conflict.from}
                                {conflict.to !== conflict.from &&
                                    ` – ${conflict.to}`}
                                ): Davor und danach hält Align eine
                                Viertelstunde Luft zum Umschalten.
                                {free !== null && (
                                    <>
                                        {' '}
                                        Frei ist es ab{' '}
                                        <button
                                            type="button"
                                            onClick={() => onTimeChange(free)}
                                            className={QUIET_LINK}
                                        >
                                            {free}
                                        </button>
                                        .
                                    </>
                                )}
                            </span>
                        </p>
                    )}

                    {/* Derselbe Ton wie beim Überschneidungshinweis, aber hier
                        wird der Server die Uhrzeit wirklich abweisen: Der
                        Schlafplan ist der Rahmen, kein Vorschlag. */}
                    {asleep !== null && (
                        <p className="flex items-start gap-2 rounded-2xl bg-sand/50 p-3 text-xs leading-relaxed text-muted-foreground">
                            <TriangleAlert
                                className="mt-0.5 size-4 shrink-0 text-primary"
                                strokeWidth={1.5}
                                aria-hidden="true"
                            />
                            <span>
                                Um {time} Uhr schläfst du laut deinem
                                Schlafplan.{' '}
                                {
                                    WEEKDAYS.find(
                                        (day) => day.value === asleep.weekday,
                                    )?.full
                                }{' '}
                                geht dein Tag von {formatWindow(asleep)} Uhr.
                                {/* Der Ausweg steht neben der Absage, nicht
                                    dahinter — wie „Frei ist es ab 08:15" beim
                                    Überschneidungshinweis. Der Satz allein
                                    erklärte zwar, ließ aber jemanden vor einem
                                    gesperrten „Weiter" stehen und selbst
                                    herausfinden, welcher Kreis daran schuld
                                    ist. */}
                                {daysLeft.length > 0 && (
                                    <>
                                        {' '}
                                        <button
                                            type="button"
                                            onClick={() =>
                                                onDaysChange(daysLeft)
                                            }
                                            className={QUIET_LINK}
                                        >
                                            {namesOf(asleepDays)} abwählen
                                        </button>
                                        .
                                    </>
                                )}
                            </span>
                        </p>
                    )}

                    {/* Dieselbe Reihe wie bei der Situation — ohne gesperrte
                        Tage: Eine Uhrzeit lässt sich prüfen, dafür steht der
                        Überschneidungshinweis darüber. */}
                    <WeekdayPicker days={days} onChange={onDaysChange} />
                </div>
            )}
        </div>
    );
}
