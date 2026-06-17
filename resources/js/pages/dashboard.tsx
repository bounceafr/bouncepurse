import { Head, usePage } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import {
    Activity,
    CheckCircle2,
    CircleCheck,
    CircleX,
    Clock,
    Gamepad2,
    MapPin,
    Route,
    Trophy,
} from 'lucide-react';
import { Fragment } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    XAxis,
    YAxis,
} from 'recharts';
import { ChartAreaInteractive } from '@/components/chart-area-interactive';
import { SectionCards } from '@/components/section-cards';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import {
    DataTable,
    selectionColumn,
    sortableHeader,
} from '@/components/ui/data-table';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

interface GameStats {
    total_games: number;
    total_courts: number;
    pending_games: number;
    approved_games: number;
}

interface RecentGame {
    id: number;
    uuid: string;
    title: string;
    status: string;
    played_at: string;
    court: { name: string } | null;
    player: { name: string };
}

interface MonthlyData {
    month: string;
    count: number;
}

interface SparklineDay {
    date: string;
    games: number;
    approved: number;
    pending: number;
    courts: number;
}

interface VisitorStat {
    date: string;
    desktop: number;
    mobile: number;
}

interface PlayerRankingEntry {
    format: string;
    rank: number;
    score: number;
    wins: number;
    losses: number;
}

interface CriterionDetail {
    required?: number;
    current: number | null;
    met: boolean;
    limit?: number;
}

interface PathwayEligibility {
    is_eligible: boolean;
    criteria: {
        approved_games: CriterionDetail;
        rank: CriterionDetail;
        conduct_flags: CriterionDetail;
    };
}

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

const gamesChartConfig = {
    games: {
        label: 'Games',
        color: 'var(--chart-1)',
    },
} satisfies ChartConfig;

function statusBadgeVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'approved':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'rejected':
            return 'destructive';
        default:
            return 'outline';
    }
}

function formatMonth(yearMonth: string): string {
    const [year, month] = yearMonth.split('-');
    const date = new Date(Number(year), Number(month) - 1, 1);
    return date.toLocaleString('default', { month: 'short' });
}

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

const recentGamesColumns: ColumnDef<RecentGame>[] = [
    selectionColumn<RecentGame>(),
    {
        accessorKey: 'title',
        header: sortableHeader('Title'),
        cell: ({ row }) => (
            <span className="font-medium">{row.getValue('title')}</span>
        ),
    },
    {
        id: 'court',
        accessorFn: (row) => row.court?.name ?? '—',
        header: sortableHeader('Court'),
    },
    {
        accessorKey: 'status',
        header: sortableHeader('Status'),
        cell: ({ row }) => (
            <Badge variant={statusBadgeVariant(row.getValue('status'))}>
                {row.getValue('status')}
            </Badge>
        ),
    },
    {
        accessorKey: 'played_at',
        header: sortableHeader('Date'),
        cell: ({ row }) =>
            new Date(row.getValue('played_at')).toLocaleDateString(),
    },
];

export default function Dashboard({
    stats,
    recent_games,
    games_per_month,
    visitor_stats,
    player_rankings,
    pathway_eligibility,
}: Props) {
    const { auth } = usePage().props;

    const isAdmin = auth.roles.includes('Administrator') || auth.roles.includes('SuperAdmin');
    const canSeeVisitorStats = visitor_stats.length > 0 && isAdmin;

    const gamesChartData = games_per_month.map((item) => ({
        month: formatMonth(item.month),
        games: item.count,
    }));

    const rankingEntries = Object.values(player_rankings);

    const sectionCards = [
        {
            label: 'Total Games',
            value: stats.total_games,
            icon: <Gamepad2 />,
            trend: stats.total_games > 0 ? 12 : undefined,
            trendLabel: `${stats.total_games > 0 ? 'Active' : 'No'} games recorded`,
            description: 'All games across all courts',
        },
        {
            label: 'Total Courts',
            value: stats.total_courts,
            icon: <MapPin />,
            trendLabel: `${stats.total_courts > 0 ? 'Available' : 'No'} courts registered`,
            description: 'Courts available for play',
        },
        {
            label: 'Pending Review',
            value: stats.pending_games,
            icon: <Clock />,
            trendLabel: `${stats.pending_games > 0 ? 'Awaiting' : 'No'} pending reviews`,
            description: 'Games awaiting approval',
        },
        {
            label: 'Approved Games',
            value: stats.approved_games,
            icon: <CheckCircle2 />,
            trendLabel: `${stats.approved_games > 0 ? 'Successfully' : 'No'} approved games`,
            description: 'Games that passed review',
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
                            {getFormattedDate()} &middot; Here&rsquo;s what&rsquo;s happening across your games.
                        </p>
                    </div>

                    <SectionCards cards={sectionCards} />

                    <div className="grid gap-4 px-4 lg:px-6 lg:grid-cols-2 *:data-[slot=card]:bg-gradient-to-t *:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card *:data-[slot=card]:shadow-xs">
                        <Card className="@container/card">
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <Activity className="size-4" />
                                    <CardTitle>Games per Month</CardTitle>
                                </div>
                                <CardDescription>
                                    {gamesChartData.reduce((s, d) => s + d.games, 0)} games this period
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="pt-0">
                                <ChartContainer
                                    config={gamesChartConfig}
                                    className="aspect-auto h-64 w-full"
                                >
                                    <BarChart data={gamesChartData}>
                                        <CartesianGrid vertical={false} />
                                        <XAxis
                                            dataKey="month"
                                            tickLine={false}
                                            axisLine={false}
                                        />
                                        <YAxis
                                            tickLine={false}
                                            axisLine={false}
                                            allowDecimals={false}
                                        />
                                        <ChartTooltip
                                            content={<ChartTooltipContent />}
                                        />
                                        <Bar
                                            dataKey="games"
                                            fill="var(--color-games)"
                                            radius={4}
                                        />
                                    </BarChart>
                                </ChartContainer>
                            </CardContent>
                        </Card>

                        <Card className="@container/card">
                            <CardHeader>
                                <CardTitle>Recent Games</CardTitle>
                                <CardDescription>
                                    {recent_games.length} most recent
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="pt-0">
                                {recent_games.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No games have been recorded yet.
                                    </p>
                                ) : (
                                    <DataTable
                                        columns={recentGamesColumns}
                                        data={recent_games}
                                    />
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {(rankingEntries.length > 0 || pathway_eligibility) && (
                        <div className="grid gap-4 px-4 lg:px-6 md:grid-cols-2 *:data-[slot=card]:bg-gradient-to-t *:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card *:data-[slot=card]:shadow-xs">
                            {rankingEntries.length > 0 && (
                                <Card className="@container/card">
                                    <CardHeader>
                                        <div className="flex items-center gap-2">
                                            <Trophy className="size-4" />
                                            <CardTitle>My Rankings</CardTitle>
                                        </div>
                                        <CardDescription>
                                            {rankingEntries.reduce((s, e) => s + e.wins + e.losses, 0)} games played
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="pt-0">
                                        <div className="flex gap-4 overflow-x-auto">
                                            {rankingEntries.map((entry, index) => (
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
                                                            <span className="text-chart-1">
                                                                {entry.wins}W
                                                            </span>
                                                            {' / '}
                                                            <span className="text-destructive">
                                                                {entry.losses}L
                                                            </span>
                                                        </span>
                                                    </div>
                                                </Fragment>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {pathway_eligibility && (
                                <Card className="@container/card">
                                    <CardHeader>
                                        <div className="flex items-center gap-2">
                                            <Route className="size-4" />
                                            <CardTitle>Pathway Eligibility</CardTitle>
                                            <Badge
                                                variant={
                                                    pathway_eligibility.is_eligible
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {pathway_eligibility.is_eligible
                                                    ? 'Pathway Candidate'
                                                    : 'Not Yet Eligible'}
                                            </Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="pt-0">
                                        <div className="flex flex-col gap-2">
                                            <div className="flex items-center gap-2">
                                                {pathway_eligibility.criteria
                                                    .approved_games.met ? (
                                                    <CircleCheck className="size-4 text-chart-1" />
                                                ) : (
                                                    <CircleX className="size-4 text-destructive" />
                                                )}
                                                <span className="text-sm">
                                                    Approved Games:{' '}
                                                    {pathway_eligibility.criteria
                                                        .approved_games.current}{' '}
                                                    /{' '}
                                                    {pathway_eligibility.criteria
                                                        .approved_games.required}{' '}
                                                    required
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                {pathway_eligibility.criteria.rank
                                                    .met ? (
                                                    <CircleCheck className="size-4 text-chart-1" />
                                                ) : (
                                                    <CircleX className="size-4 text-destructive" />
                                                )}
                                                <span className="text-sm">
                                                    Best Rank:{' '}
                                                    {pathway_eligibility.criteria
                                                        .rank.current !== null
                                                        ? `#${pathway_eligibility.criteria.rank.current}`
                                                        : 'N/A'}{' '}
                                                    / top{' '}
                                                    {pathway_eligibility.criteria
                                                        .rank.required}{' '}
                                                    required
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                {pathway_eligibility.criteria
                                                    .conduct_flags.met ? (
                                                    <CircleCheck className="size-4 text-chart-1" />
                                                ) : (
                                                    <CircleX className="size-4 text-destructive" />
                                                )}
                                                <span className="text-sm">
                                                    Conduct Flags:{' '}
                                                    {pathway_eligibility.criteria
                                                        .conduct_flags.current}{' '}
                                                    /{' '}
                                                    {pathway_eligibility.criteria
                                                        .conduct_flags.limit}{' '}
                                                    max allowed
                                                </span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </div>
                    )}

                    {canSeeVisitorStats && (
                        <ChartAreaInteractive data={visitor_stats} />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
