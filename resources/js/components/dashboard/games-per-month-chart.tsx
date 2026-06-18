import { Activity } from 'lucide-react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
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
import { type MonthlyData } from './types';

const chartConfig = {
    games: {
        label: 'Games',
        color: 'var(--color-blue-500)',
    },
} satisfies ChartConfig;

function formatMonth(yearMonth: string): string {
    const [year, month] = yearMonth.split('-');
    const date = new Date(Number(year), Number(month) - 1, 1);
    return date.toLocaleString('default', { month: 'short' });
}

export function GamesPerMonthChart({ data }: { data: MonthlyData[] }) {
    const chartData = data.map((item) => ({
        month: formatMonth(item.month),
        games: item.games,
    }));
    const total = chartData.reduce((sum, item) => sum + item.games, 0);

    return (
        <Card className="@container/card">
            <CardHeader>
                <div className="flex items-center gap-2">
                    <span className="flex size-8 items-center justify-center rounded-lg bg-blue-500/10 text-blue-500 [&>svg]:size-4">
                        <Activity />
                    </span>
                    <CardTitle>Games per Month</CardTitle>
                </div>
                <CardDescription>{total} games this period</CardDescription>
            </CardHeader>
            <CardContent className="pt-0">
                <ChartContainer
                    config={chartConfig}
                    className="aspect-auto h-64 w-full"
                >
                    <BarChart data={chartData}>
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
                        <ChartTooltip content={<ChartTooltipContent />} />
                        <Bar
                            dataKey="games"
                            fill="var(--color-games)"
                            radius={4}
                        />
                    </BarChart>
                </ChartContainer>
            </CardContent>
        </Card>
    );
}
