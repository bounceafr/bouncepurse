import { Head, usePage } from '@inertiajs/react';
import { CircleAlert, CircleCheck, Clock, MapPin } from 'lucide-react';
import { ChartAreaInteractive } from '@/components/chart-area-interactive';
import { CourtActivityHeatmap } from '@/components/dashboard/court-activity-heatmap';
import { DisputeFunnelChart } from '@/components/dashboard/dispute-funnel-chart';
import { GameStatusChart } from '@/components/dashboard/game-status-chart';
import { GamesPerMonthChart } from '@/components/dashboard/games-per-month-chart';
import { PathwayEligibilityCard } from '@/components/dashboard/pathway-eligibility-card';
import { RankingsCard } from '@/components/dashboard/rankings-card';
import { RecentGamesCard } from '@/components/dashboard/recent-games-card';
import {
    type DailyGameData,
    type FunnelStage,
    type GameStats,
    type GameStatusSlice,
    type HeatmapCell,
    type PathwayEligibility,
    type PlayerRankingEntry,
    type RecentGame,
    type SparklineDay,
    type VisitorStat,
} from '@/components/dashboard/types';
import { SectionCards, type SectionCardData } from '@/components/section-cards';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

interface Props {
    stats: GameStats;
    stats_sparklines: SparklineDay[];
    recent_games: RecentGame[];
    games_per_month: DailyGameData[];
    visitor_stats: VisitorStat[];
    player_rankings: Record<string, PlayerRankingEntry>;
    pathway_eligibility: PathwayEligibility | null;
    game_status_distribution: GameStatusSlice[];
    court_heatmap: HeatmapCell[];
    dispute_funnel: FunnelStage[] | null;
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

/** Percentage change between the first and last value of a daily series. */
function percentChange(series: number[]): number | undefined {
    if (series.length < 2) {
        return undefined;
    }

    const first = series[0];
    const last = series[series.length - 1];

    if (first === 0) {
        return last === 0 ? 0 : 100;
    }

    return Math.round(((last - first) / first) * 100);
}

export default function Dashboard({
    stats,
    stats_sparklines,
    recent_games,
    games_per_month,
    visitor_stats,
    player_rankings,
    pathway_eligibility,
    game_status_distribution,
    court_heatmap,
    dispute_funnel,
}: Props) {
    const { auth } = usePage().props;

    const isAdmin =
        auth.roles.includes('administrator') ||
        auth.roles.includes('super-admin');
    const isPlayer = auth.roles.includes('player');
    const canSeeVisitorStats = visitor_stats.length > 0 && isAdmin;

    const rankingEntries = Object.values(player_rankings);

    const approvalRate =
        stats.total_games > 0
            ? Math.round((stats.approved_games / stats.total_games) * 100)
            : 0;

    const sectionCards: SectionCardData[] = [
        {
            label: 'Pending Verification',
            value: stats.pending_games,
            icon: <Clock />,
            trend: percentChange(stats_sparklines.map((day) => day.pending)),
            trendLabel: 'Awaiting review',
            description: 'Games pending verification',
            cardClassName: 'bg-gradient-to-t from-amber-500/15 to-card',
            iconClassName: 'bg-amber-500/15 text-amber-500',
        },
        {
            label: 'Verified Games',
            value: stats.approved_games,
            icon: <CircleCheck />,
            trend: percentChange(stats_sparklines.map((day) => day.approved)),
            trendLabel: `${approvalRate}% approval rate`,
            description: 'Successfully verified',
            cardClassName: 'bg-gradient-to-t from-green-500/15 to-card',
            iconClassName: 'bg-green-500/15 text-green-500',
        },
        {
            label: 'Contested Games',
            value: stats.contested_games,
            icon: <CircleAlert />,
            trendLabel:
                stats.contested_games > 0 ? 'Action required' : 'All clear',
            description: 'Disputed results',
            cardClassName: 'bg-gradient-to-t from-red-500/15 to-card',
            iconClassName: 'bg-red-500/15 text-red-500',
        },
        {
            label: 'Active Courts',
            value: stats.total_courts,
            icon: <MapPin />,
            trend: percentChange(stats_sparklines.map((day) => day.courts)),
            trendLabel: 'Across the network',
            description: 'Courts with games',
            cardClassName: 'bg-gradient-to-t from-blue-500/15 to-card',
            iconClassName: 'bg-blue-500/15 text-blue-500',
        },
    ];

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

                    <SectionCards cards={sectionCards} />

                    {isAdmin && (
                        <div className="px-4 *:data-[slot=card]:shadow-xs lg:px-6">
                            <GamesPerMonthChart data={games_per_month} />
                        </div>
                    )}

                    {isAdmin && (
                        <div className="grid gap-4 px-4 *:data-[slot=card]:shadow-xs md:grid-cols-2 lg:px-6">
                            <GameStatusChart data={game_status_distribution} />
                            {dispute_funnel && <DisputeFunnelChart data={dispute_funnel} />}
                        </div>
                    )}

                    {isAdmin && (
                        <div className="px-4 *:data-[slot=card]:shadow-xs lg:px-6">
                            <CourtActivityHeatmap data={court_heatmap} />
                        </div>
                    )}

                    {canSeeVisitorStats && (
                        <div className="px-4 *:data-[slot=card]:shadow-xs lg:px-6">
                            <ChartAreaInteractive data={visitor_stats} />
                        </div>
                    )}

                    {isPlayer && (rankingEntries.length > 0 || pathway_eligibility) && (
                        <div className="grid gap-4 px-4 *:data-[slot=card]:shadow-xs md:grid-cols-2 lg:px-6">
                            {rankingEntries.length > 0 && (
                                <RankingsCard entries={rankingEntries} />
                            )}
                            {pathway_eligibility && (
                                <PathwayEligibilityCard
                                    eligibility={pathway_eligibility}
                                />
                            )}
                        </div>
                    )}

                    <div className="px-4 *:data-[slot=card]:shadow-xs lg:px-6">
                        <RecentGamesCard games={recent_games} />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
