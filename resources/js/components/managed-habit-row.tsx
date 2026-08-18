import { Link } from '@inertiajs/react';
import { CircleCheck, MoreHorizontal, Pencil } from 'lucide-react';
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

const EYEBROW = 'text-[11px] font-semibold tracking-[0.11em] uppercase';

/**
 * Eine aktive Gewohnheit in der Verwaltungsansicht.
 *
 * Hier geht es um die Gewohnheit an sich, nicht um den heutigen Tag: Erinnerung
 * setzen, Serie ablesen, beenden. Abgehakt wird auf der Übersicht.
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
                <CardContent className="flex items-center gap-3">
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
                            {/* Der Umfang steht leiser als die Handlung: Er
                                sagt, wie viel — nicht, worum es geht. */}
                            {habit.measureLabel !== null && (
                                <span className="font-normal text-muted-foreground">
                                    {' · '}
                                    {habit.measureLabel}
                                </span>
                            )}
                        </span>

                        {/* Der nächste Termin führt die Zeile an — er ist es,
                            wonach die Liste sortiert ist. Was heute ansteht,
                            sagt das über seinen Block und schweigt hier, sonst
                            stünde „heute" in jeder zweiten Zeile. */}
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
                        </span>

                        {/* Leise Zeile, kein Abzeichen: die Serie steht neben
                            der Planung, nicht über ihr. Wo es keine Serie geben
                            kann, steht die blanke Zahl — „7× in 30 Tagen"
                            behauptet kein Soll, an dem sie scheitern könnte. */}
                        {(habit.streak ?? habit.recentCount) !== null && (
                            <span className="mt-0.5 block truncate text-xs text-muted-foreground">
                                {habit.streak ?? habit.recentCount}
                            </span>
                        )}
                    </span>

                    <span className="flex shrink-0 flex-col items-end gap-1">
                        <ToggleSwitch
                            checked={habit.reminderEnabled}
                            disabled={!habit.canRemind}
                            onChange={(enabled) =>
                                onToggleReminder(habit, enabled)
                            }
                            label={`Erinnerung für ${habit.title}`}
                        />
                        <span className={`${EYEBROW} text-muted-foreground`}>
                            {habit.canRemind ? '10 Min vorher' : 'ohne Uhrzeit'}
                        </span>
                    </span>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-11 shrink-0 cursor-pointer text-muted-foreground hover:bg-accent"
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
                            {/* Der einzige Weg zum Bearbeiten in der App: Hier
                                geht es um die Gewohnheit an sich, auf der
                                Übersicht um den heutigen Tag. */}
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
                </CardContent>
            </Card>
        </li>
    );
}
