import { type ColumnDef } from '@tanstack/react-table';
import { History } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DataTable,
    selectionColumn,
    sortableHeader,
} from '@/components/ui/data-table';
import { cn } from '@/lib/utils';
import { type RecentGame } from './types';

/** Literal class strings per status so Tailwind's JIT detects them. */
const statusStyles: Record<string, { dot: string; text: string }> = {
    approved: {
        dot: 'bg-green-500',
        text: 'text-green-600 dark:text-green-400',
    },
    pending: {
        dot: 'bg-amber-500',
        text: 'text-amber-600 dark:text-amber-400',
    },
    rejected: { dot: 'bg-red-500', text: 'text-red-600 dark:text-red-400' },
    flagged: {
        dot: 'bg-orange-500',
        text: 'text-orange-600 dark:text-orange-400',
    },
    scheduled: { dot: 'bg-blue-500', text: 'text-blue-600 dark:text-blue-400' },
};

function StatusBadge({ status }: { status: string }) {
    const style = statusStyles[status] ?? {
        dot: 'bg-muted-foreground',
        text: 'text-muted-foreground',
    };

    return (
        <Badge
            variant="outline"
            className={cn('gap-1.5 capitalize', style.text)}
        >
            <span className={cn('size-1.5 rounded-full', style.dot)} />
            {status}
        </Badge>
    );
}

const columns: ColumnDef<RecentGame>[] = [
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
        cell: ({ row }) => <StatusBadge status={row.getValue('status')} />,
    },
    {
        accessorKey: 'played_at',
        header: sortableHeader('Date'),
        cell: ({ row }) =>
            new Date(row.getValue('played_at')).toLocaleDateString(),
    },
];

export function RecentGamesCard({ games }: { games: RecentGame[] }) {
    return (
        <Card className="@container/card">
            <CardHeader>
                <div className="flex items-center gap-2">
                    <span className="flex size-8 items-center justify-center rounded-lg bg-green-500/10 text-green-500 [&>svg]:size-4">
                        <History />
                    </span>
                    <CardTitle>Recent Games</CardTitle>
                </div>
                <CardDescription>{games.length} most recent</CardDescription>
            </CardHeader>
            <CardContent className="pt-0">
                {games.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No games have been recorded yet.
                    </p>
                ) : (
                    <DataTable columns={columns} data={games} />
                )}
            </CardContent>
        </Card>
    );
}
