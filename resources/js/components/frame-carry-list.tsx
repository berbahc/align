import { ArrowRight } from 'lucide-react';
import { Skeleton } from '@/components/ui/skeleton';
import type { BlockedHabit, CarriedHabit } from '@/hooks/use-frame-carry';

/**
 * Was der neue Rahmen mit den Gewohnheiten macht — als Liste zum Lesen.
 *
 * Zwei Wege führen hierher: der dauerhafte Schlafplan und der einzelne Tag.
 * Beide zeigen dasselbe, weil beide dieselbe Rechnung gestellt haben — zwei
 * Darstellungen wären zwei Versprechen über denselben Zug.
 *
 * Was mitzieht, steht als „vorher → nachher"; was nicht mitkann, steht
 * darunter mit seinem Grund. Die Trennung ist Absicht: Das eine ist eine
 * Ankündigung, das andere eine Auskunft, und sie in eine Liste zu mischen
 * hieße, den Unterschied zu verwischen.
 */
export function FrameCarryList({
    moves,
    blocked,
    loading = false,
}: {
    moves: CarriedHabit[];
    blocked: BlockedHabit[];
    loading?: boolean;
}) {
    if (loading) {
        return (
            <div className="flex flex-col gap-2">
                <Skeleton className="h-14 rounded-[14px]" />
                <Skeleton className="h-14 rounded-[14px]" />
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-4">
            {moves.length > 0 && (
                <ul className="flex flex-col gap-2">
                    {moves.map((move) => (
                        <li
                            key={move.habitId}
                            className="rounded-[14px] bg-card px-4 py-3"
                        >
                            <p className="text-[15px] leading-snug font-semibold">
                                {move.title}
                            </p>
                            <p className="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground tabular-nums">
                                <span className="line-through">
                                    {move.from}
                                </span>
                                <ArrowRight
                                    className="size-3"
                                    aria-hidden="true"
                                />
                                <span className="font-semibold text-foreground">
                                    {move.to}
                                </span>
                            </p>
                        </li>
                    ))}
                </ul>
            )}

            {blocked.length > 0 && (
                <div className="flex flex-col gap-2">
                    <p className="type-eyebrow text-muted-foreground">
                        Bleibt außerhalb
                    </p>
                    <ul className="flex flex-col gap-2">
                        {blocked.map((entry) => (
                            <li
                                key={entry.habitId}
                                className="rounded-[14px] border border-border px-4 py-3"
                            >
                                <p className="text-[15px] leading-snug font-semibold">
                                    {entry.title}
                                </p>
                                <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                                    {entry.reason}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}
