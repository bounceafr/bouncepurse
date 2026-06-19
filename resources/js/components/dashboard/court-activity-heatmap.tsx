import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type HeatmapCell } from './types';

const DAYS = [
    { label: 'Mon', dow: 2 },
    { label: 'Tue', dow: 3 },
    { label: 'Wed', dow: 4 },
    { label: 'Thu', dow: 5 },
    { label: 'Fri', dow: 6 },
    { label: 'Sat', dow: 7 },
    { label: 'Sun', dow: 1 },
];

const HOURS = Array.from({ length: 24 }, (_, i) => i);

function formatHour(h: number): string {
    if (h === 0) return '12a';
    if (h === 12) return '12p';
    return h < 12 ? `${h}a` : `${h - 12}p`;
}

export function CourtActivityHeatmap({ data }: { data: HeatmapCell[] }) {
    const lookup = new Map<string, number>();
    let maxCount = 1;

    for (const cell of data) {
        const key = `${cell.dow}-${cell.hour}`;
        lookup.set(key, cell.count);
        if (cell.count > maxCount) {
            maxCount = cell.count;
        }
    }

    const totalGames = data.reduce((sum, d) => sum + d.count, 0);

    return (
        <Card className="@container/card">
            <CardHeader>
                <CardTitle>Court Activity</CardTitle>
                <CardDescription>
                    Game distribution by day &amp; hour &middot; {totalGames.toLocaleString()} games tracked
                </CardDescription>
            </CardHeader>
            <CardContent className="px-4 pb-6 sm:px-6">
                <div className="overflow-x-auto">
                    <div
                        className="grid min-w-[560px] gap-1"
                        style={{ gridTemplateColumns: '2.5rem repeat(24, 1fr)' }}
                    >
                        {/* Hour header row */}
                        <div />
                        {HOURS.map((h) => (
                            <div
                                key={h}
                                className="text-center text-[10px] leading-none text-muted-foreground"
                            >
                                {h % 6 === 0 ? formatHour(h) : ''}
                            </div>
                        ))}

                        {/* Day rows */}
                        {DAYS.map(({ label, dow }) => (
                            <>
                                <div
                                    key={`label-${dow}`}
                                    className="flex items-center text-[11px] leading-none text-muted-foreground"
                                >
                                    {label}
                                </div>
                                {HOURS.map((h) => {
                                    const count = lookup.get(`${dow}-${h}`) ?? 0;
                                    const intensity = count > 0 ? 0.15 + (count / maxCount) * 0.75 : 0;

                                    return (
                                        <div
                                            key={`${dow}-${h}`}
                                            className="aspect-square rounded-[3px] transition-opacity"
                                            style={{
                                                backgroundColor:
                                                    count > 0
                                                        ? `rgba(34,197,94,${intensity})`
                                                        : 'hsl(var(--muted))',
                                                opacity: count > 0 ? 1 : 0.4,
                                            }}
                                            title={`${label} ${formatHour(h)}: ${count} game${count !== 1 ? 's' : ''}`}
                                        />
                                    );
                                })}
                            </>
                        ))}
                    </div>

                    {/* Legend */}
                    <div className="mt-3 flex items-center gap-2">
                        <span className="text-[11px] text-muted-foreground">Less</span>
                        {[0, 0.2, 0.4, 0.65, 0.9].map((o) => (
                            <div
                                key={o}
                                className="size-3 rounded-[3px]"
                                style={{
                                    backgroundColor:
                                        o === 0 ? 'hsl(var(--muted))' : `rgba(34,197,94,${o})`,
                                    opacity: o === 0 ? 0.4 : 1,
                                }}
                            />
                        ))}
                        <span className="text-[11px] text-muted-foreground">More</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
