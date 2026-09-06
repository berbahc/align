import { Link } from '@inertiajs/react';
import { Bell, CircleCheck, MoreHorizontal, Pencil } from 'lucide-react';
import { HabitGlyph } from '@/components/habit-glyph';
import { RhythmStrip } from '@/components/rhythm-strip';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import type { ManagedHabit } from '@/types';

/**
 * Eine aktive Gewohnheit in der Verwaltungsansicht.
 *
 * Hier geht es um die Gewohnheit an sich, nicht um den heutigen Tag: Verlauf
 * ablesen, Erinnerung setzen, beenden. Abgehakt wird auf der Übersicht.
 *
 * **Die Zeile zeigt jetzt einen Verlauf statt einer Einstellung.** Vorher trug
 * sie eine zweite Zeile mit Glocke und Schalter, die rund die halbe Karte
 * einnahm — auf einer Seite mit fünf Gewohnheiten waren damit fünf
 * Benachrichtigungs-Schalter das Auffälligste, und alle Karten sahen gleich
 * aus, weil Einstellungen immer gleich aussehen. Der Schalter steht jetzt im
 * ⋯-Menü, wo schon Bearbeiten und Beenden liegen; an seine Stelle tritt der
 * {@see RhythmStrip}, und der ist bei jeder Gewohnheit ein anderer.
 *
 * Die Karte wird dadurch **niedriger als vorher**, obwohl sie mehr zeigt.
 */
export function ManagedHabitRow({
    habit,
    onToggleReminder,
    onEnd,
    highlighted = false,
}: {
    habit: ManagedHabit;
    onToggleReminder: (habit: ManagedHabit, enabled: boolean) => void;
    onEnd: (habit: ManagedHabit) => void;
    /**
     * Kurz betont, nachdem die Gewohnheit gerade hier gelandet ist.
     *
     * Dieselbe Antwort wie auf der Übersicht: Wer etwas beendet oder wieder
     * aufnimmt, soll sehen, wo es hingewandert ist, statt es suchen zu müssen.
     */
    highlighted?: boolean;
}) {
    /**
     * Wie es läuft — in der Reihenfolge vom weiten zum nahen Blick.
     *
     * Beide Angaben sind optional: Vor dem ersten vorgesehenen Tag gibt es
     * kein Fenster für eine Rate, und unterhalb der Mindestlänge behauptet die
     * Serie lieber nichts, als eine „1" zu zeigen.
     */
    const stats = [
        habit.consistency !== null && `${habit.consistency} % in 30 Tagen`,
        habit.streak,
    ].filter((entry): entry is string => typeof entry === 'string');

    return (
        <li
            id={`managed-habit-${habit.id}`}
            className={cn(
                'rounded-xl transition-shadow duration-300',
                highlighted &&
                    'ring-2 ring-primary ring-offset-4 ring-offset-background',
            )}
        >
            {/* Enger als die Vorgabe aus `ui/card.tsx` (py-6/px-6): Fünf Karten
                mit 24px Innenabstand ergeben eine Seite aus Luft zwischen
                immer gleichen Rechtecken. */}
            <Card className="gap-0 py-4 shadow-none">
                <CardContent className="flex flex-col gap-3 px-4">
                    <div className="flex items-center gap-3">
                        <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sand text-primary">
                            <HabitGlyph habit={habit} className="size-5" />
                        </span>

                        <span className="min-w-0 flex-1">
                            <span className="block truncate text-[15px] leading-snug font-semibold">
                                {habit.title}
                            </span>

                            {/* Der nächste Termin führt die Zeile an — er ist
                                es, wonach die Liste sortiert ist. Was heute
                                ansteht, sagt das über seinen Block und
                                schweigt hier, sonst stünde „heute" in jeder
                                zweiten Zeile. */}
                            <span className="mt-0.5 flex min-w-0 items-baseline gap-1.5 text-xs text-muted-foreground">
                                {habit.group !== 'today' &&
                                    habit.nextOccurrence !== null && (
                                        <>
                                            <span className="shrink-0 font-semibold text-foreground">
                                                {habit.nextOccurrence}
                                            </span>
                                            <span aria-hidden="true">·</span>
                                        </>
                                    )}
                                <span className="truncate">
                                    {habit.scheduleLabel}
                                </span>
                                {habit.measureLabel !== null && (
                                    <>
                                        <span aria-hidden="true">·</span>
                                        <span className="shrink-0">
                                            {habit.measureLabel}
                                        </span>
                                    </>
                                )}
                            </span>
                        </span>

                        {/* Die Glocke steht still in der Kopfzeile, wenn die
                            Erinnerung an ist — sie schaltet nichts, sie sagt
                            nur, dass etwas eingestellt ist. Ohne sie wäre der
                            Zustand hinter dem Menü unsichtbar geworden. */}
                        {habit.canRemind && habit.reminderEnabled && (
                            <Bell
                                className="size-3.5 shrink-0 text-muted-foreground"
                                strokeWidth={1.5}
                                aria-label="Erinnerung ist an"
                            />
                        )}

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="size-11 shrink-0 cursor-pointer text-muted-foreground transition-[background-color,scale] duration-[var(--duration-press)] ease-out hover:bg-accent motion-safe:active:scale-[0.94]"
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
                                    bedienbar ist, sieht aus wie ein Angebot
                                    und ist keins — warum es ihn für manche
                                    Gewohnheiten nicht gibt, steht einmal am
                                    Seitenende (§1.5: benannt wird, was gilt). */}
                                {habit.canRemind && (
                                    <>
                                        <DropdownMenuCheckboxItem
                                            checked={habit.reminderEnabled}
                                            // Das Menü bleibt beim Schalten
                                            // offen: Der Haken ist die
                                            // Rückmeldung, und ein Menü, das
                                            // im selben Moment zuklappt,
                                            // verbirgt sie.
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

                    {/* Eingerückt auf die Textspalte, damit der Streifen als
                        Fortsetzung der Gewohnheit gelesen wird und nicht als
                        eigener Eintrag.

                        Streifen und Zahlen stehen nebeneinander, wo der Platz
                        reicht, und untereinander, wo nicht — auf 375px passt
                        beides nicht in eine Zeile, und ein abgeschnittener
                        Prozentwert wäre schlimmer als ein Umbruch. */}
                    <div className="flex flex-wrap items-end gap-x-4 gap-y-2 pl-14">
                        <RhythmStrip days={habit.rhythm} title={habit.title} />

                        {/* Zwei Zeitachsen, die einander nicht wiederholen:
                            Der Streifen zeigt die Woche und lässt sich
                            abzählen, die Rate blickt über dreißig Tage und
                            springt bei einem Fehltag nicht. Daneben die Serie —
                            {@see Habit::consistencyRate()} nennt sie „den
                            Antrieb" und die Rate „den ehrlicheren Blick".
                            Beides gehört in dieselbe Zeile, weil beides
                            dieselbe Frage beantwortet: wie läuft es. */}
                        {stats.length > 0 && (
                            <p className="pb-3.5 text-xs text-muted-foreground">
                                {stats.join(' · ')}
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>
        </li>
    );
}
