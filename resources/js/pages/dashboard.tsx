import { Head, usePage } from '@inertiajs/react';
import { ChartAreaInteractive } from '@/components/chart-area-interactive';
import { GamesPerMonthChart } from '@/components/dashboard/games-per-month-chart';
import { PathwayEligibilityCard } from '@/components/dashboard/pathway-eligibility-card';
import { RankingsCard } from '@/components/dashboard/rankings-card';
import { RecentGamesCard } from '@/components/dashboard/recent-games-card';
import { StatsGrid } from '@/components/dashboard/stats-grid';
import {
    type GameStats,
    type MonthlyData,
    type PathwayEligibility,
    type PlayerRankingEntry,
    type RecentGame,
    type SparklineDay,
    type VisitorStat,
} from '@/components/dashboard/types';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

interface Props {
    stats: GameStats;
    stats_sparklines: SparklineDay[];
    recent_games: RecentGame[];
    games_per_month: MonthlyData[];
    visitor_stats: VisitorStat[];
    player_rankings: Record<string, PlayerRankingEntry>;
    pathway_eligibility: PathwayEligibility | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

function getGreeting(): string {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
}

function getFormattedDate(): string {
    return new Date().toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
    });
}

export default function Dashboard({
    stats,
    stats_sparklines,
    recent_games,
    games_per_month,
    visitor_stats,
    player_rankings,
    pathway_eligibility,
}: Props) {
    const { auth } = usePage().props;

    const isAdmin =
        auth.roles.includes('Administrator') ||
        auth.roles.includes('SuperAdmin');
    const canSeeVisitorStats = visitor_stats.length > 0 && isAdmin;

    const rankingEntries = Object.values(player_rankings);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="@container/main flex flex-1 flex-col gap-2">
                <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
                    <div className="px-4 lg:px-6">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {getGreeting()}, {auth.user.name}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {getFormattedDate()} &middot; Here&rsquo;s
                            what&rsquo;s happening across your games.
                        </p>
                    </div>

                    <StatsGrid stats={stats} sparklines={stats_sparklines} />

                    <div className="px-4 *:data-[slot=card]:shadow-xs lg:px-6">
                        <GamesPerMonthChart data={games_per_month} />
                    </div>

                    <div className="grid gap-4 px-4 *:data-[slot=card]:shadow-xs md:grid-cols-2 lg:px-6">
                        <RecentGamesCard games={recent_games} />
                        {rankingEntries.length > 0 && (
                            <RankingsCard entries={rankingEntries} />
                        )}
                        {pathway_eligibility && (
                            <PathwayEligibilityCard
                                eligibility={pathway_eligibility}
                            />
                        )}
                    </div>

                    {canSeeVisitorStats && (
                        <ChartAreaInteractive data={visitor_stats} />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
