import { Head } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import {
    CheckCircle2,
    CircleCheck,
    CircleX,
    Clock,
    Gamepad2,
    MapPin,
    Route,
    Trophy,
    TrendingDown,
    TrendingUp,
} from 'lucide-react';
import React, { useState } from 'react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    XAxis,
    YAxis,
} from 'recharts';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import {
    DataTable,
    selectionColumn,
    sortableHeader,
} from '@/components/ui/data-table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
        color: 'hsl(var(--chart-1))',
    },
};

const visitorsChartConfig = {
    visitors: {
        label: 'Visitors',
    },
    desktop: {
        label: 'Desktop',
        color: 'var(--chart-1)',
    },
    mobile: {
        label: 'Mobile',
        color: 'var(--chart-2)',
    },
} satisfies ChartConfig;

function statusBadgeClass(status: string): string {
    switch (status) {
        case 'approved':
            return 'border-transparent bg-green-500 capitalize text-white';
        case 'pending':
            return 'border-transparent bg-yellow-500 capitalize text-white';
        case 'rejected':
            return 'border-transparent bg-red-500 capitalize text-white';
        case 'flagged':
            return 'border-transparent bg-orange-500 capitalize text-white';
        default:
            return '';
    }
}

function formatMonth(yearMonth: string): string {
    const [year, month] = yearMonth.split('-');
    const date = new Date(Number(year), Number(month) - 1, 1);
    return date.toLocaleString('default', { month: 'short' });
}

function computeTrend(data: { v: number }[]): number | undefined {
    if (data.length < 2) {
        return undefined;
    }
    const first = data[0].v;
    const last = data[data.length - 1].v;
    if (first === 0) {
        return undefined;
    }
    return Math.round(((last - first) / first) * 100);
}

interface StatCardProps {
    label: string;
    value: number;
    icon: React.ReactNode;
    sparklineData: { v: number }[];
    trend?: number;
}

function StatCard({ label, value, icon, sparklineData, trend }: StatCardProps) {
    return (
        <Card className="border bg-card shadow-sm">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardDescription className="text-sm">{label}</CardDescription>
                <div className="[&>svg]:size-4 [&>svg]:text-muted-foreground">
                    {icon}
                </div>
            </CardHeader>
            <CardContent className="pb-2">
                <div className="flex items-center gap-2">
                    <span className="text-3xl font-bold">
                        {value.toLocaleString()}
                    </span>
                    {trend !== undefined && (
                        <span
                            className={`flex items-center gap-0.5 text-xs font-medium ${trend >= 0 ? 'text-green-600' : 'text-red-500'}`}
                        >
                            {trend >= 0 ? (
                                <TrendingUp className="size-3" />
                            ) : (
                                <TrendingDown className="size-3" />
                            )}
                            {Math.abs(trend)}%
                        </span>
                    )}
                </div>
            </CardContent>
            <CardFooter className="pt-0">
                <div className="h-12 w-full">
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={sparklineData}>
                            <Line
                                type="monotone"
                                dataKey="v"
                                stroke="var(--chart-1)"
                                strokeWidth={1.5}
                                dot={false}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            </CardFooter>
        </Card>
    );
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
            <Badge className={statusBadgeClass(row.getValue('status'))}>
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
    stats_sparklines,
    recent_games,
    games_per_month,
    visitor_stats,
    player_rankings,
    pathway_eligibility,
}: Props) {
    const [timeRange, setTimeRange] = useState('90d');

    const gamesChartData = games_per_month.map((item) => ({
        month: formatMonth(item.month),
        games: item.count,
    }));

    const filteredVisitors = visitor_stats.filter((item) => {
        const date = new Date(item.date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        let daysToSubtract = 90;
        if (timeRange === '30d') {
            daysToSubtract = 30;
        } else if (timeRange === '7d') {
            daysToSubtract = 7;
        }
        const startDate = new Date(today);
        startDate.setDate(startDate.getDate() - daysToSubtract + 1);
        return date >= startDate;
    });

    const totalVisitors = filteredVisitors.reduce(
        (sum, d) => sum + d.desktop + d.mobile,
        0,
    );

    const rankingEntries = Object.values(player_rankings);

    const gamesSparkline = stats_sparklines.map((d) => ({ v: d.games }));
    const courtsSparkline = stats_sparklines.map((d) => ({ v: d.courts }));
    const pendingSparkline = stats_sparklines.map((d) => ({ v: d.pending }));
    const approvedSparkline = stats_sparklines.map((d) => ({ v: d.approved }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
                {/* Stat Cards */}
                <div className="grid gap-6 md:grid-cols-4">
                    <StatCard
                        label="Total Games"
                        value={stats.total_games}
                        icon={<Gamepad2 />}
                        sparklineData={gamesSparkline}
                        trend={computeTrend(gamesSparkline)}
                    />
                    <StatCard
                        label="Total Courts"
                        value={stats.total_courts}
                        icon={<MapPin />}
                        sparklineData={courtsSparkline}
                        trend={computeTrend(courtsSparkline)}
                    />
                    <StatCard
                        label="Pending Review"
                        value={stats.pending_games}
                        icon={<Clock />}
                        sparklineData={pendingSparkline}
                        trend={computeTrend(pendingSparkline)}
                    />
                    <StatCard
                        label="Approved Games"
                        value={stats.approved_games}
                        icon={<CheckCircle2 />}
                        sparklineData={approvedSparkline}
                        trend={computeTrend(approvedSparkline)}
                    />
                </div>

                {/* Visitors Chart — full width */}
                <Card className="pt-0">
                    <CardHeader className="flex items-center gap-2 space-y-0 border-b py-5 sm:flex-row">
                        <div className="grid flex-1 gap-1">
                            <CardTitle>Total Visitors</CardTitle>
                            <CardDescription>
                                {totalVisitors.toLocaleString()} sessions
                                &mdash; by device type
                            </CardDescription>
                        </div>
                        <Select value={timeRange} onValueChange={setTimeRange}>
                            <SelectTrigger
                                className="hidden w-40 rounded-lg sm:ml-auto sm:flex"
                                aria-label="Select a value"
                            >
                                <SelectValue placeholder="Last 3 months" />
                            </SelectTrigger>
                            <SelectContent className="rounded-xl">
                                <SelectItem value="90d" className="rounded-lg">
                                    Last 3 months
                                </SelectItem>
                                <SelectItem value="30d" className="rounded-lg">
                                    Last 30 days
                                </SelectItem>
                                <SelectItem value="7d" className="rounded-lg">
                                    Last 7 days
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </CardHeader>
                    <CardContent className="px-2 pt-4 sm:px-6 sm:pt-6">
                        <ChartContainer
                            config={visitorsChartConfig}
                            className="aspect-auto h-62.5 w-full"
                        >
                            <AreaChart data={filteredVisitors}>
                                <defs>
                                    <linearGradient
                                        id="fillDesktop"
                                        x1="0"
                                        y1="0"
                                        x2="0"
                                        y2="1"
                                    >
                                        <stop
                                            offset="5%"
                                            stopColor="var(--color-desktop)"
                                            stopOpacity={0.8}
                                        />
                                        <stop
                                            offset="95%"
                                            stopColor="var(--color-desktop)"
                                            stopOpacity={0.1}
                                        />
                                    </linearGradient>
                                    <linearGradient
                                        id="fillMobile"
                                        x1="0"
                                        y1="0"
                                        x2="0"
                                        y2="1"
                                    >
                                        <stop
                                            offset="5%"
                                            stopColor="var(--color-mobile)"
                                            stopOpacity={0.8}
                                        />
                                        <stop
                                            offset="95%"
                                            stopColor="var(--color-mobile)"
                                            stopOpacity={0.1}
                                        />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid vertical={false} />
                                <XAxis
                                    dataKey="date"
                                    tickLine={false}
                                    axisLine={false}
                                    tickMargin={8}
                                    minTickGap={32}
                                    tickFormatter={(value) => {
                                        const date = new Date(value);
                                        return date.toLocaleDateString(
                                            'en-US',
                                            {
                                                month: 'short',
                                                day: 'numeric',
                                            },
                                        );
                                    }}
                                />
                                <ChartTooltip
                                    cursor={false}
                                    content={
                                        <ChartTooltipContent
                                            labelFormatter={(value) => {
                                                return new Date(
                                                    value as string,
                                                ).toLocaleDateString('en-US', {
                                                    month: 'short',
                                                    day: 'numeric',
                                                });
                                            }}
                                            indicator="dot"
                                        />
                                    }
                                />
                                <Area
                                    dataKey="mobile"
                                    type="natural"
                                    fill="url(#fillMobile)"
                                    stroke="var(--color-mobile)"
                                    stackId="a"
                                />
                                <Area
                                    dataKey="desktop"
                                    type="natural"
                                    fill="url(#fillDesktop)"
                                    stroke="var(--color-desktop)"
                                    stackId="a"
                                />
                                <ChartLegend content={<ChartLegendContent />} />
                            </AreaChart>
                        </ChartContainer>
                    </CardContent>
                </Card>

                {/* My Rankings */}
                {rankingEntries.length > 0 && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Trophy className="size-4 text-muted-foreground" />
                                <CardTitle>My Rankings</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="flex gap-6 overflow-x-auto">
                                {rankingEntries.map((entry, index) => (
                                    <React.Fragment key={entry.format}>
                                        {index > 0 && (
                                            <Separator
                                                orientation="vertical"
                                                className="h-auto"
                                            />
                                        )}
                                        <div className="flex min-w-20 flex-col gap-1">
                                            <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                {entry.format}
                                            </span>
                                            <span className="text-3xl font-bold">
                                                #{entry.rank}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                <span className="text-green-600">
                                                    {entry.wins}W
                                                </span>
                                                {' / '}
                                                <span className="text-red-500">
                                                    {entry.losses}L
                                                </span>
                                            </span>
                                        </div>
                                    </React.Fragment>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Pathway Eligibility */}
                {pathway_eligibility && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Route className="size-4 text-muted-foreground" />
                                <CardTitle>Pathway Eligibility</CardTitle>
                                <Badge
                                    className={
                                        pathway_eligibility.is_eligible
                                            ? 'border-transparent bg-green-500 text-white'
                                            : 'border-transparent bg-gray-400 text-white'
                                    }
                                >
                                    {pathway_eligibility.is_eligible
                                        ? 'Pathway Candidate'
                                        : 'Not Yet Eligible'}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-col gap-3">
                                <div className="flex items-center gap-2">
                                    {pathway_eligibility.criteria.approved_games
                                        .met ? (
                                        <CircleCheck className="size-4 text-green-600" />
                                    ) : (
                                        <CircleX className="size-4 text-red-500" />
                                    )}
                                    <span className="text-sm">
                                        Approved Games:{' '}
                                        {
                                            pathway_eligibility.criteria
                                                .approved_games.current
                                        }{' '}
                                        /{' '}
                                        {
                                            pathway_eligibility.criteria
                                                .approved_games.required
                                        }{' '}
                                        required
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    {pathway_eligibility.criteria.rank.met ? (
                                        <CircleCheck className="size-4 text-green-600" />
                                    ) : (
                                        <CircleX className="size-4 text-red-500" />
                                    )}
                                    <span className="text-sm">
                                        Best Rank:{' '}
                                        {pathway_eligibility.criteria.rank
                                            .current !== null
                                            ? `#${pathway_eligibility.criteria.rank.current}`
                                            : 'N/A'}{' '}
                                        / top{' '}
                                        {
                                            pathway_eligibility.criteria.rank
                                                .required
                                        }{' '}
                                        required
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    {pathway_eligibility.criteria.conduct_flags
                                        .met ? (
                                        <CircleCheck className="size-4 text-green-600" />
                                    ) : (
                                        <CircleX className="size-4 text-red-500" />
                                    )}
                                    <span className="text-sm">
                                        Conduct Flags:{' '}
                                        {
                                            pathway_eligibility.criteria
                                                .conduct_flags.current
                                        }{' '}
                                        /{' '}
                                        {
                                            pathway_eligibility.criteria
                                                .conduct_flags.limit
                                        }{' '}
                                        max allowed
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Chart + Recent Games */}
                <div className="grid gap-6 lg:grid-cols-[2fr_3fr]">
                    {/* Games per Month Chart */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Games per Month</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer
                                config={gamesChartConfig}
                                className="aspect-auto h-62.5 w-full"
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

                    {/* Recent Games */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Games</CardTitle>
                            <CardDescription>
                                {recent_games.length} most recent
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
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
            </div>
        </AppLayout>
    );
}
