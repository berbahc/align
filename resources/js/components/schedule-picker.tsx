import { Sparkles, TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import { TimeStepper } from '@/components/time-stepper';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    CHOICE_TILE,
    CHOICE_TILE_OFF,
    CHOICE_TILE_ON,
    QUIET_LINK,
} from '@/lib/interaction';
import { formatWindow, outsideSleepWindow } from '@/lib/sleep';
import { findConflict, nextFreeTime } from '@/lib/slots';
import { cn } from '@/lib/utils';
import type {
    BusySlot,
    ChainCandidate,
    ScheduleType,
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
 * Die Situationsauswahl — der `dynamic`-Zweig des SchedulePickers.
 *
 * Die Vorschläge sind ein Angebot, kein Katalog: „Eigene Situation" steht
 * gleichberechtigt darunter, weil der eigene Tag selten dem gemittelten
 * entspricht. Ob getippt oder gewählt wird, hält die Komponente selbst fest —
 * es ist eine Frage der Darstellung, nicht des Formulars.
 */
export function SituationPicker({
    suggestions,
    value,
    onChange,
}: {
    suggestions: string[];
    value: string;
    onChange: (value: string) => void;
}) {
    // Eine vorbelegte Situation, die nicht in der Liste steht, ist eine
    // getippte — beim Übernehmen einer fremden Gewohnheit ist das der
    // Normalfall, und das Feld muss dann offen stehen.
    const [ownSituation, setOwnSituation] = useState(
        value !== '' && !suggestions.includes(value),
    );

    return (
        <div className="flex flex-col gap-2">
            {suggestions.map((situation) => {
                const isSelected = !ownSituation && value === situation;

                return (
                    <button
                        key={situation}
                        type="button"
                        aria-pressed={isSelected}
                        onClick={() => {
                            setOwnSituation(false);
                            onChange(situation);
                        }}
                        className={cn(
                            CHOICE_TILE,
                            'px-4 py-3 text-[15px]',
                            isSelected ? CHOICE_TILE_ON : CHOICE_TILE_OFF,
                        )}
                    >
                        {situation}
                    </button>
                );
            })}

            <button
                type="button"
                aria-pressed={ownSituation}
                onClick={() => {
                    setOwnSituation(true);
                    onChange('');
                }}
                className={cn(
                    CHOICE_TILE,
                    'border-dashed px-4 py-3 text-[15px] text-muted-foreground',
                    ownSituation ? CHOICE_TILE_ON : CHOICE_TILE_OFF,
                )}
            >
                Eigene Situation
            </button>

            {ownSituation && (
                <div className="grid gap-2 pt-1">
                    <Label htmlFor="trigger_situation" className="sr-only">
                        Eigene Situation
                    </Label>
                    <Input
                        id="trigger_situation"
                        name="trigger_situation"
                        autoFocus
                        maxLength={120}
                        placeholder="z. B. wenn ich aus der Bib komme"
                        value={value}
                        onChange={(event) => onChange(event.target.value)}
                    />
                </div>
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
    chainCandidates = [],
    chainedTo = null,
    onChainedToChange,
    busySlots = [],
    sleepWindows = [],
    children,
}: {
    scheduleTypes: ScheduleTypeOption[];
    scheduleType: ScheduleType;
    onScheduleTypeChange: (value: ScheduleType) => void;
    time: string;
    onTimeChange: (value: string) => void;
    days: Weekday[];
    onDaysChange: (days: Weekday[]) => void;
    /** Woran sich anhängen lässt — leer heißt: es gibt noch nichts. */
    chainCandidates?: ChainCandidate[];
    chainedTo?: number | null;
    onChainedToChange?: (id: number) => void;
    /** Was im Tag schon belegt ist, für den Überschneidungshinweis. */
    busySlots?: BusySlot[];
    /** Der Schlafrahmen je Wochentag — für den Hinweis, wenn die Uhrzeit außerhalb läge. */
    sleepWindows?: SleepWindow[];
    /** Die Situationsauswahl — sie bleibt im Wizard, wo ihre Vorschläge herkommen. */
    children: React.ReactNode;
}) {
    const conflict = findConflict(time, days, busySlots);
    const free = conflict === null ? null : nextFreeTime(time, days, busySlots);

    // Der Rahmen aus dem Schlafplan: Der Server weist eine Uhrzeit außerhalb
    // ohnehin ab — die Oberfläche sagt es vorher, mit demselben Ergebnis.
    const asleep = outsideSleepWindow(time, days, sleepWindows);

    function toggleDay(day: Weekday) {
        onDaysChange(
            days.includes(day)
                ? days.filter((candidate) => candidate !== day)
                : [...days, day].sort((a, b) => a - b),
        );
    }

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
                            <span className="flex items-center gap-1.5 text-[15px] font-semibold">
                                {/* ✦ ist für Momente reserviert, in denen die App mitdenkt (§8). */}
                                {option.value === 'dynamic' && (
                                    <Sparkles
                                        className="size-4 text-primary"
                                        strokeWidth={1.5}
                                        aria-hidden="true"
                                    />
                                )}
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
                        chainCandidates.map((candidate) => {
                            const isSelected = chainedTo === candidate.id;

                            return (
                                <button
                                    key={candidate.id}
                                    type="button"
                                    aria-pressed={isSelected}
                                    onClick={() =>
                                        onChainedToChange?.(candidate.id)
                                    }
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
                        })
                    )}
                </div>
            ) : scheduleType === 'dynamic' ? (
                children
            ) : (
                <div className="flex flex-col gap-5">
                    <div className="flex flex-col items-center gap-2 rounded-2xl bg-card p-6">
                        <TimeStepper
                            value={time}
                            onChange={onTimeChange}
                            label="Uhrzeit"
                        />
                    </div>

                    {/* Ein Hinweis, keine Sperre: Wer zwei Dinge bewusst
                        übereinanderlegt, darf das — die App sagt nur, was sie
                        sieht. Möglich wird der Satz erst durch die Dauer, die
                        das Ende des anderen Blocks kennt. */}
                    {conflict !== null && (
                        <p className="flex items-start gap-2 rounded-2xl bg-sand/50 p-3 text-xs leading-relaxed text-muted-foreground">
                            <TriangleAlert
                                className="mt-0.5 size-4 shrink-0 text-primary"
                                strokeWidth={1.5}
                                aria-hidden="true"
                            />
                            <span>
                                Um diese Zeit läuft schon „{conflict.title}"
                                {conflict.to !== conflict.from &&
                                    ` bis ${conflict.to}`}
                                .
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
                                Um {time} Uhr schläfst du laut deinem Schlafplan
                                —{' '}
                                {
                                    WEEKDAYS.find(
                                        (day) => day.value === asleep.weekday,
                                    )?.full
                                }{' '}
                                geht dein Tag von {formatWindow(asleep)} Uhr.
                            </span>
                        </p>
                    )}

                    <div className="flex flex-col gap-3">
                        <p className="type-eyebrow text-muted-foreground">
                            An diesen Tagen
                        </p>
                        <div
                            role="group"
                            aria-label="Wochentage"
                            className="flex flex-wrap gap-2"
                        >
                            {WEEKDAYS.map((day) => {
                                const isSelected = days.includes(day.value);

                                return (
                                    <button
                                        key={day.value}
                                        type="button"
                                        aria-pressed={isSelected}
                                        aria-label={day.full}
                                        onClick={() => toggleDay(day.value)}
                                        className={cn(
                                            'flex size-12 cursor-pointer items-center justify-center rounded-full border text-[15px] font-semibold',
                                            'transition-[background-color,border-color,color,scale] duration-[var(--duration-press)] ease-out',
                                            'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-safe:active:scale-[0.92]',
                                            isSelected
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-border bg-card text-foreground hover:border-secondary',
                                        )}
                                    >
                                        {day.label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
