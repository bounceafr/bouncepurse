import { CircleAlert, Clock, MapPin, TrendingUp } from 'lucide-react';
import { cn } from '@/lib/utils';
import { StatCard } from './stat-card';
import { type GameStats, type SparklineDay, statAccents } from './types';

interface StatsGridProps {
    stats: GameStats;
    sparklines: SparklineDay[];
}

export function StatsGrid({ stats, sparklines }: StatsGridProps) {
    const pendingPercent =
        stats.total_games > 0
            ? (stats.pending_games / stats.total_games) * 100
            : 0;
    const approvedPercent =
        stats.total_games > 0
            ? (stats.approved_games / stats.total_games) * 100
            : 0;

    const totalDots = 5;
    const activeDots = Math.min(stats.total_courts, totalDots);

    return (
        <div className="grid grid-cols-1 gap-4 px-4 lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4">
            <StatCard
                label="Pending Verification"
                value={stats.pending_games}
                icon={Clock}
                accent={statAccents.amber}
                trend={sparklines.map((day) => day.pending)}
                footer={
                    <div className="mt-2 h-1 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            className={cn(
                                'h-full rounded-full transition-all',
                                statAccents.amber.fill,
                            )}
                            style={{ width: `${pendingPercent}%` }}
                        />
                    </div>
                }
            />

            <StatCard
                label="Verified This Week"
                value={stats.approved_games}
                icon={TrendingUp}
                accent={statAccents.green}
                trend={sparklines.map((day) => day.approved)}
                footer={
                    <p
                        className={cn(
                            'mt-1 flex items-center gap-1 text-xs',
                            statAccents.green.text,
                        )}
                    >
                        <TrendingUp className="size-3.5" />
                        {approvedPercent.toFixed(0)}% approval rate
                    </p>
                }
            />

            <StatCard
                label="Contested Games"
                value={stats.contested_games}
                icon={CircleAlert}
                accent={statAccents.red}
                footer={
                    <p
                        className={cn(
                            'mt-1 text-xs font-medium',
                            statAccents.red.text,
                        )}
                    >
                        {stats.contested_games > 0
                            ? 'Action required'
                            : 'No contested games'}
                    </p>
                }
            />

            <StatCard
                label="Active Courts"
                value={stats.total_courts}
                icon={MapPin}
                accent={statAccents.blue}
                trend={sparklines.map((day) => day.courts)}
                footer={
                    <div className="mt-2 flex gap-1">
                        {Array.from({ length: totalDots }).map((_, index) => (
                            <div
                                key={index}
                                className={cn(
                                    'size-2 rounded-full',
                                    index < activeDots
                                        ? statAccents.blue.fill
                                        : 'bg-muted',
                                )}
                            />
                        ))}
                    </div>
                }
            />
        </div>
    );
}
