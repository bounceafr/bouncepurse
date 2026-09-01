import { Trophy } from 'lucide-react';
import { Fragment } from 'react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { type PlayerRankingEntry } from './types';

export function RankingsCard({ entries }: { entries: PlayerRankingEntry[] }) {
    const gamesPlayed = entries.reduce(
        (sum, entry) => sum + entry.wins + entry.losses,
        0,
    );

    return (
        <Card className="@container/card">
            <CardHeader>
                <div className="flex items-center gap-2">
                    <span className="flex size-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-500 [&>svg]:size-4">
                        <Trophy />
                    </span>
                    <CardTitle>My Rankings</CardTitle>
                </div>
                <CardDescription>{gamesPlayed} games played</CardDescription>
            </CardHeader>
            <CardContent className="pt-0">
                <div className="flex gap-4 overflow-x-auto">
                    {entries.map((entry, index) => (
                        <Fragment key={entry.format}>
                            {index > 0 && (
                                <Separator
                                    orientation="vertical"
                                    className="h-auto"
                                />
                            )}
                            <div className="flex min-w-16 flex-col gap-0.5">
                                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    {entry.format}
                                </span>
                                <span className="text-2xl font-bold tabular-nums">
                                    #{entry.rank}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    <span className="text-green-500">
                                        {entry.wins}W
                                    </span>
                                    {' / '}
                                    <span className="text-red-500">
                                        {entry.losses}L
                                    </span>
                                </span>
                            </div>
                        </Fragment>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
