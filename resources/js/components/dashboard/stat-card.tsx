import { type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { type StatAccent } from './types';

interface StatCardProps {
    label: string;
    value: number;
    icon: LucideIcon;
    accent: StatAccent;
    /** Optional 7-day trend rendered as a tiny sparkline. */
    trend?: number[];
    footer?: ReactNode;
}

/** Minimal inline-SVG sparkline. Solid stroke (currentColor), no gradient. */
function Sparkline({
    data,
    className,
}: {
    data: number[];
    className?: string;
}) {
    if (data.length < 2) {
        return null;
    }

    const width = 100;
    const height = 28;
    const max = Math.max(...data);
    const min = Math.min(...data);
    const range = max - min || 1;
    const step = width / (data.length - 1);
    const points = data
        .map((value, index) => {
            const x = index * step;
            const y = height - ((value - min) / range) * height;
            return `${x.toFixed(2)},${y.toFixed(2)}`;
        })
        .join(' ');

    return (
        <svg
            viewBox={`0 0 ${width} ${height}`}
            preserveAspectRatio="none"
            className={cn('h-7 w-full', className)}
            aria-hidden="true"
        >
            <polyline
                points={points}
                fill="none"
                stroke="currentColor"
                strokeWidth={2}
                strokeLinecap="round"
                strokeLinejoin="round"
                vectorEffect="non-scaling-stroke"
            />
        </svg>
    );
}

export function StatCard({
    label,
    value,
    icon: Icon,
    accent,
    trend,
    footer,
}: StatCardProps) {
    return (
        <Card className="@container/card">
            <CardHeader>
                <div className="flex items-center justify-between gap-2">
                    <CardDescription className="text-xs font-medium tracking-wider uppercase">
                        {label}
                    </CardDescription>
                    <span
                        className={cn(
                            'flex size-9 items-center justify-center rounded-lg [&>svg]:size-4.5',
                            accent.tint,
                            accent.text,
                        )}
                    >
                        <Icon />
                    </span>
                </div>
                <CardTitle
                    className={cn(
                        'mt-1 text-3xl font-bold tabular-nums @[250px]/card:text-4xl',
                        accent.text,
                    )}
                >
                    {value.toLocaleString()}
                </CardTitle>
            </CardHeader>
            <CardContent className="pt-0">
                {trend && <Sparkline data={trend} className={accent.text} />}
                {footer}
            </CardContent>
        </Card>
    );
}
