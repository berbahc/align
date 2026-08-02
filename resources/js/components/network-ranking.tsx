import { usePage } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

type NetworkRankingEntry = {
    name: string;
    impactScore: number;
    avatar?: string;
    isCurrentUser?: boolean;
};

const PEER_ENTRIES: NetworkRankingEntry[] = [
    { name: 'Amara Okafor', impactScore: 18420 },
    { name: 'Liang Wei', impactScore: 15980 },
    { name: 'Sofia Marchetti', impactScore: 14275 },
    { name: 'Noah Bergström', impactScore: 11640 },
    { name: 'Priya Nair', impactScore: 9310 },
    { name: 'Diego Fuentes', impactScore: 7025 },
    { name: 'Hana Takahashi', impactScore: 4880 },
];

const CURRENT_USER_IMPACT_SCORE = 12750;

export function NetworkRanking() {
    const { auth } = usePage().props;
    const getInitials = useInitials();

    const entries: NetworkRankingEntry[] = [
        ...PEER_ENTRIES,
        {
            name: auth.user?.name ?? 'You',
            impactScore: CURRENT_USER_IMPACT_SCORE,
            avatar: auth.user?.avatar,
            isCurrentUser: true,
        },
    ].sort((a, b) => b.impactScore - a.impactScore);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Network Ranking</CardTitle>
                <CardDescription>
                    How your impact score compares across your network.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="overflow-hidden rounded-lg border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b bg-muted/40 text-left text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                <th className="w-12 px-3 py-2.5 text-center sm:px-4">
                                    #
                                </th>
                                <th className="px-3 py-2.5 sm:px-4">Member</th>
                                <th className="px-3 py-2.5 text-right sm:px-4">
                                    Impact Score
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {entries.map((entry, index) => {
                                const rank = index + 1;
                                const isTopThree = rank <= 3;

                                return (
                                    <tr
                                        key={`${entry.name}-${rank}`}
                                        className={cn(
                                            'border-b transition-colors last:border-b-0 hover:bg-muted/50',
                                            entry.isCurrentUser &&
                                                'bg-primary/5 hover:bg-primary/10',
                                        )}
                                    >
                                        <td className="px-3 py-3 text-center sm:px-4">
                                            <span
                                                className={cn(
                                                    'inline-flex h-6 min-w-6 items-center justify-center rounded-full px-1.5 text-xs font-semibold tabular-nums',
                                                    isTopThree
                                                        ? 'bg-primary text-primary-foreground'
                                                        : 'bg-muted text-muted-foreground',
                                                )}
                                            >
                                                {rank}
                                            </span>
                                        </td>
                                        <td className="px-3 py-3 sm:px-4">
                                            <div className="flex items-center gap-3">
                                                <Avatar className="h-8 w-8 rounded-full">
                                                    <AvatarImage
                                                        src={entry.avatar}
                                                        alt={entry.name}
                                                    />
                                                    <AvatarFallback className="rounded-full bg-neutral-200 text-xs text-black dark:bg-neutral-700 dark:text-white">
                                                        {getInitials(
                                                            entry.name,
                                                        )}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <span className="truncate font-medium">
                                                    {entry.name}
                                                </span>
                                                {entry.isCurrentUser && (
                                                    <span className="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                                                        You
                                                    </span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-3 py-3 text-right font-semibold tabular-nums sm:px-4">
                                            {entry.impactScore.toLocaleString()}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}
