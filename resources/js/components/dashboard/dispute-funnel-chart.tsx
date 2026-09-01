import { Funnel, FunnelChart, LabelList, Tooltip } from 'recharts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ChartContainer, type ChartConfig } from '@/components/ui/chart';
import { type FunnelStage } from './types';

const STAGE_COLORS: Record<string, string> = {
    Contested: 'hsl(0, 84%, 60%)',
    Disputed: 'hsl(38, 92%, 50%)',
    Resolved: 'hsl(142, 71%, 45%)',
    Dismissed: 'hsl(220, 9%, 46%)',
};

const chartConfig = {
    count: { label: 'Games' },
} satisfies ChartConfig;

export function DisputeFunnelChart({ data }: { data: FunnelStage[] }) {
    const total = data[0]?.count ?? 0;

    const funnelData = data.map((d) => ({
        ...d,
        value: d.count,
        fill: STAGE_COLORS[d.stage] ?? 'hsl(var(--muted))',
    }));

    const hasDisputes = data.slice(1).some((d) => d.count > 0);

    return (
        <Card className="@container/card">
            <CardHeader>
                <CardTitle>Dispute Resolution</CardTitle>
                <CardDescription>
                    {total > 0
                        ? `${total} contested game${total !== 1 ? 's' : ''} in the pipeline`
                        : 'No contested games'}
                </CardDescription>
            </CardHeader>
            <CardContent className="pb-6">
                {total === 0 ? (
                    <div className="flex h-[220px] items-center justify-center text-sm text-muted-foreground">
                        No disputed games to display
                    </div>
                ) : (
                    <ChartContainer config={chartConfig} className="h-[220px] w-full">
                        <FunnelChart>
                            <Tooltip
                                content={({ active, payload }) => {
                                    if (!active || !payload?.length) {
                                        return null;
                                    }
                                    const item = payload[0].payload as (typeof funnelData)[number];
                                    const pct =
                                        total > 0 ? Math.round((item.count / total) * 100) : 0;
                                    return (
                                        <div className="rounded-lg border bg-background px-3 py-2 text-sm shadow-md">
                                            <p className="font-medium">{item.stage}</p>
                                            <p className="text-muted-foreground">
                                                {item.count.toLocaleString()} games
                                                {hasDisputes && ` · ${pct}%`}
                                            </p>
                                        </div>
                                    );
                                }}
                            />
                            <Funnel dataKey="value" data={funnelData} isAnimationActive>
                                <LabelList
                                    position="insideTop"
                                    fill="white"
                                    stroke="none"
                                    fontSize={12}
                                    fontWeight={600}
                                    formatter={(value: number) =>
                                        value > 0 ? value.toLocaleString() : ''
                                    }
                                />
                            </Funnel>
                        </FunnelChart>
                    </ChartContainer>
                )}

                {/* Stage legend */}
                <div className="mt-3 grid grid-cols-2 gap-x-6 gap-y-1.5">
                    {data.map((d) => {
                        const pct = total > 0 ? Math.round((d.count / total) * 100) : 0;
                        return (
                            <div key={d.stage} className="flex items-center gap-2">
                                <span
                                    className="size-2.5 shrink-0 rounded-full"
                                    style={{ backgroundColor: STAGE_COLORS[d.stage] }}
                                />
                                <span className="min-w-0 flex-1 truncate text-xs text-muted-foreground">
                                    {d.stage}
                                </span>
                                <span className="text-xs font-semibold tabular-nums">
                                    {d.count}
                                </span>
                                {hasDisputes && (
                                    <span className="text-xs text-muted-foreground">
                                        {pct}%
                                    </span>
                                )}
                            </div>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}
