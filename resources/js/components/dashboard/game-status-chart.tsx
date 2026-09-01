import { Cell, Pie, PieChart, Tooltip } from 'recharts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ChartContainer, type ChartConfig } from '@/components/ui/chart';
import { type GameStatusSlice } from './types';

const STATUS_COLORS: Record<string, string> = {
    pending: 'hsl(38, 92%, 50%)',
    approved: 'hsl(142, 71%, 45%)',
    rejected: 'hsl(0, 84%, 60%)',
    flagged: 'hsl(25, 95%, 53%)',
};

const chartConfig = {
    count: { label: 'Games' },
    pending: { label: 'Pending', color: STATUS_COLORS.pending },
    approved: { label: 'Approved', color: STATUS_COLORS.approved },
    rejected: { label: 'Rejected', color: STATUS_COLORS.rejected },
    flagged: { label: 'Contested', color: STATUS_COLORS.flagged },
} satisfies ChartConfig;

export function GameStatusChart({ data }: { data: GameStatusSlice[] }) {
    const total = data.reduce((sum, d) => sum + d.count, 0);

    return (
        <Card className="@container/card">
            <CardHeader>
                <CardTitle>Game Status</CardTitle>
                <CardDescription>{total.toLocaleString()} total games</CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col items-center gap-4 pb-6">
                <ChartContainer config={chartConfig} className="h-[220px] w-full max-w-[280px]">
                    <PieChart>
                        <Tooltip
                            content={({ active, payload }) => {
                                if (!active || !payload?.length) {
                                    return null;
                                }
                                const item = payload[0];
                                return (
                                    <div className="rounded-lg border bg-background px-3 py-2 text-sm shadow-md">
                                        <p className="font-medium">{item.name}</p>
                                        <p className="text-muted-foreground">
                                            {(item.value as number).toLocaleString()} games
                                        </p>
                                    </div>
                                );
                            }}
                        />
                        <Pie
                            data={data}
                            dataKey="count"
                            nameKey="label"
                            innerRadius="55%"
                            outerRadius="80%"
                            paddingAngle={2}
                            strokeWidth={0}
                        >
                            {data.map((entry) => (
                                <Cell
                                    key={entry.status}
                                    fill={STATUS_COLORS[entry.status] ?? 'hsl(var(--muted))'}
                                />
                            ))}
                        </Pie>
                    </PieChart>
                </ChartContainer>

                <div className="grid w-full grid-cols-2 gap-x-6 gap-y-2">
                    {data.map((entry) => (
                        <div key={entry.status} className="flex items-center gap-2">
                            <span
                                className="size-2.5 shrink-0 rounded-full"
                                style={{ backgroundColor: STATUS_COLORS[entry.status] }}
                            />
                            <span className="min-w-0 flex-1 truncate text-xs text-muted-foreground">
                                {entry.label}
                            </span>
                            <span className="text-xs font-semibold tabular-nums">
                                {entry.count.toLocaleString()}
                            </span>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
