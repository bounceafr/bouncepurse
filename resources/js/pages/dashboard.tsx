import { Head } from '@inertiajs/react';
import { CheckCircle2, Clock, Gamepad2, MapPin, Trophy } from 'lucide-react';
import { useState } from 'react';
import { Area, AreaChart, Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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

interface Props {
    stats: GameStats;
    recent_games: RecentGame[];
    games_per_month: MonthlyData[];
    visitor_stats: VisitorStat[];
    player_rankings: Record<string, PlayerRankingEntry>;
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

export default function Dashboard({
    stats,
    recent_games,
    games_per_month,
    visitor_stats,
    player_rankings,
}: Props) {
    const [timeRange, setTimeRange] = useState('90d');

    const chartData = games_per_month.map((item) => ({
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

    const rankingEntries = Object.values(player_rankings);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                {/* Stat Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Games
                            </CardTitle>
                            <Gamepad2 className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.total_games}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Courts
                            </CardTitle>
                            <MapPin className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.total_courts}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Pending Review
                            </CardTitle>
                            <Clock className="h-4 w-4 text-yellow-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.pending_games}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Approved Games
                            </CardTitle>
                            <CheckCircle2 className="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.approved_games}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Visitors Chart — full width */}
                <Card className="pt-0">
                    <CardHeader className="flex items-center gap-2 space-y-0 border-b py-5 sm:flex-row">
                        <div className="grid flex-1 gap-1">
                            <CardTitle>Total Visitors</CardTitle>
                            <CardDescription>
                                Sessions by device type
                            </CardDescription>
                        </div>
                        <Select value={timeRange} onValueChange={setTimeRange}>
                            <SelectTrigger
                                className="hidden w-[160px] rounded-lg sm:ml-auto sm:flex"
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
                            className="aspect-auto h-[250px] w-full"
                        >
                            <AreaChart data={filteredVisitors}>
                                <defs>
                                    <linearGradient id="fillDesktop" x1="0" y1="0" x2="0" y2="1">
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
                                    <linearGradient id="fillMobile" x1="0" y1="0" x2="0" y2="1">
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
                                        return date.toLocaleDateString('en-US', {
                                            month: 'short',
                                            day: 'numeric',
                                        });
                                    }}
                                />
                                <ChartTooltip
                                    cursor={false}
                                    content={
                                        <ChartTooltipContent
                                            labelFormatter={(value) => {
                                                return new Date(value).toLocaleDateString('en-US', {
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
                    <div className="flex flex-col gap-2">
                        <h2 className="flex items-center gap-2 text-lg font-semibold">
                            <Trophy className="h-5 w-5 text-yellow-500" />
                            My Rankings
                        </h2>
                        <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                            {rankingEntries.map((entry) => (
                                <Card key={entry.format}>
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-sm font-medium text-muted-foreground uppercase">
                                            {entry.format}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="flex flex-col gap-1">
                                        <div className="text-3xl font-bold">
                                            #{entry.rank}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            Score:{' '}
                                            <span className="font-mono">
                                                {entry.score.toFixed(2)}
                                            </span>
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            <span className="text-green-600">
                                                {entry.wins}W
                                            </span>{' '}
                                            /{' '}
                                            <span className="text-red-500">
                                                {entry.losses}L
                                            </span>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                )}

                {/* Chart + Recent Games */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Games per Month Chart */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Games per Month</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer
                                config={gamesChartConfig}
                                className="w-full min-h-115"
                            >
                                <BarChart data={chartData}>
                                    <CartesianGrid vertical={true} />
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
                        </CardHeader>
                        <CardContent>
                            {recent_games.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No games have been recorded yet.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Title</TableHead>
                                            <TableHead>Court</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Date</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recent_games.map((game) => (
                                            <TableRow key={game.id}>
                                                <TableCell className="font-medium">
                                                    {game.title}
                                                </TableCell>
                                                <TableCell>
                                                    {game.court?.name ?? '—'}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        className={statusBadgeClass(
                                                            game.status,
                                                        )}
                                                    >
                                                        {game.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {new Date(
                                                        game.played_at,
                                                    ).toLocaleDateString()}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
