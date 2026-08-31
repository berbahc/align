import { Link } from '@inertiajs/react';
import { Bell, CircleCheck, MoreHorizontal, Pencil } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ToggleSwitch } from '@/components/ui/toggle-switch';
import { BEHAVIOR_ICONS } from '@/lib/behavior-icons';
import { edit } from '@/routes/habits';
import type { ManagedHabit } from '@/types';

/**
 * Eine aktive Gewohnheit in der Verwaltungsansicht.
 *
 * Hier geht es um die Gewohnheit an sich, nicht um den heutigen Tag: Erinnerung
 * setzen, Serie ablesen, beenden. Abgehakt wird auf der Übersicht.
 *
 * Zwei Zeilen statt einer: Auf einem Telefon konkurrierten Icon, Titel,
 * Schalter und Menü um dieselbe Breite, und der Titel verlor — „Vorlesung
 * na…" nennt die Gewohnheit nicht mehr. Die Erinnerung steht deshalb darunter,
 * wo sie Platz für ihre eigene Beschriftung hat (§16 Craft).
 */
export function ManagedHabitRow({
    habit,
    onToggleReminder,
    onEnd,
}: {
    habit: ManagedHabit;
    onToggleReminder: (habit: ManagedHabit, enabled: boolean) => void;
    onEnd: (habit: ManagedHabit) => void;
}) {
    const Icon = BEHAVIOR_ICONS[habit.behaviorType];

    return (
        <li>
            <Card>
                <CardContent className="flex flex-col gap-3">
                    <div className="flex items-center gap-3">
                        <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sand text-primary">
                            <Icon
                                className="size-5"
                                strokeWidth={1.5}
                                aria-hidden="true"
                            />
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

                            {/* Leise Zeile, kein Abzeichen: die Serie steht
                                neben der Planung, nicht über ihr. */}
                            {habit.streak !== null && (
                                <span className="mt-0.5 block truncate text-xs text-muted-foreground">
                                    {habit.streak}
                                </span>
                            )}
                        </span>

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

                    {/* Nur wo es etwas zu erinnern gibt. Ein Schalter, der
                        dauerhaft aus und nicht bedienbar ist, sieht aus wie
                        ein Angebot und ist keins — warum es ihn für manche
                        Gewohnheiten nicht gibt, steht einmal am Seitenende
                        (§1.5: benannt wird, was gilt). */}
                    {habit.canRemind && (
                        <div className="flex items-center justify-between gap-3 border-t border-border pt-3">
                            <span className="flex min-w-0 items-center gap-2 text-xs text-muted-foreground">
                                <Bell
                                    className="size-3.5 shrink-0"
                                    strokeWidth={1.5}
                                    aria-hidden="true"
                                />
                                Erinnerung 10 Min vorher
                            </span>
                            <ToggleSwitch
                                checked={habit.reminderEnabled}
                                onChange={(enabled) =>
                                    onToggleReminder(habit, enabled)
                                }
                                label={`Erinnerung für ${habit.title}`}
                            />
                        </div>
                    )}
                </CardContent>
            </Card>
        </li>
    );
}
