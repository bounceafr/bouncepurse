import { Area, AreaChart, CartesianGrid, XAxis } from 'recharts';

import {
    Card,
    CardContent,
    CardDescription,
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

interface GamesCourtsData {
    month: string;
    games: number;
    courts: number;
}

const chartConfig = {
    games: {
        label: 'Games',
        color: 'var(--chart-1)',
    },
    courts: {
        label: 'Courts',
        color: 'var(--chart-2)',
    },
} satisfies ChartConfig;

function formatMonth(yearMonth: string): string {
    const [year, month] = yearMonth.split('-');
    const date = new Date(Number(year), Number(month) - 1, 1);
    return date.toLocaleString('default', { month: 'short' });
}

export function GamesCourtsChart({ data }: { data: GamesCourtsData[] }) {
    const totalGames = data.reduce((sum, d) => sum + d.games, 0);
    const totalCourts = data.reduce((sum, d) => sum + d.courts, 0);

    return (
        <Card className="@container/card w-full">
            <CardHeader>
                <CardTitle>Games & Courts per Month</CardTitle>
                <CardDescription>
                    {totalGames} games across {totalCourts} courts this year
                </CardDescription>
            </CardHeader>
            <CardContent className="px-2 pt-4 sm:px-6 sm:pt-6">
                <ChartContainer
                    config={chartConfig}
                    className="aspect-auto h-[250px] w-full"
                >
                    <AreaChart data={data}>
                        <defs>
                            <linearGradient
                                id="fillGames"
                                x1="0"
                                y1="0"
                                x2="0"
                                y2="1"
                            >
                                <stop
                                    offset="5%"
                                    stopColor="var(--color-games)"
                                    stopOpacity={0.8}
                                />
                                <stop
                                    offset="95%"
                                    stopColor="var(--color-games)"
                                    stopOpacity={0.1}
                                />
                            </linearGradient>
                            <linearGradient
                                id="fillCourts"
                                x1="0"
                                y1="0"
                                x2="0"
                                y2="1"
                            >
                                <stop
                                    offset="5%"
                                    stopColor="var(--color-courts)"
                                    stopOpacity={0.8}
                                />
                                <stop
                                    offset="95%"
                                    stopColor="var(--color-courts)"
                                    stopOpacity={0.1}
                                />
                            </linearGradient>
                        </defs>
                        <CartesianGrid vertical={false} />
                        <XAxis
                            dataKey="month"
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            minTickGap={32}
                            tickFormatter={(value) => formatMonth(value)}
                        />
                        <ChartTooltip
                            cursor={false}
                            content={
                                <ChartTooltipContent
                                    labelFormatter={(value) => {
                                        if (typeof value !== 'string') {
                                            return '';
                                        }

                                        const [year, month] = value.split('-');
                                        const date = new Date(
                                            Number(year),
                                            Number(month) - 1,
                                            1,
                                        );
                                        return date.toLocaleDateString(
                                            'en-US',
                                            {
                                                year: 'numeric',
                                                month: 'long',
                                            },
                                        );
                                    }}
                                    indicator="dot"
                                />
                            }
                        />
                        <Area
                            dataKey="courts"
                            type="natural"
                            fill="url(#fillCourts)"
                            stroke="var(--color-courts)"
                            stackId="a"
                        />
                        <Area
                            dataKey="games"
                            type="natural"
                            fill="url(#fillGames)"
                            stroke="var(--color-games)"
                            stackId="a"
                        />
                        <ChartLegend content={<ChartLegendContent />} />
                    </AreaChart>
                </ChartContainer>
            </CardContent>
        </Card>
    );
}
