import { Head, Link } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import { ListPageShell } from '@/components/list-page-shell';
import { Button } from '@/components/ui/button';
import {
    type DataTableColumnDef as ColumnDef,
    DataTable,
    LaravelPagination,
    selectionColumn,
    sortableHeader,
} from '@/components/ui/data-table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { formatCourtName } from '@/lib/court';
import type { GameModeration, GameSummary, VideoStatus } from '@/lib/game';
import { index, show } from '@/routes/admin/override';
import type { BreadcrumbItem, Paginated } from '@/types';

type Game = GameSummary & {
    vimeo_uri: string | null;
    vimeo_status: VideoStatus | null;
    moderation: GameModeration[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Flagged Games', href: index().url },
];

const columns: ColumnDef<Game, unknown>[] = [
    selectionColumn<Game>(),
    {
        accessorKey: 'title',
        header: sortableHeader('Title'),
        cell: ({ row }) => (
            <span className="font-medium">{row.getValue('title')}</span>
        ),
    },
    {
        id: 'player',
        accessorFn: (row) => row.player?.name ?? '—',
        header: sortableHeader('Player'),
    },
    {
        accessorKey: 'format',
        header: sortableHeader('Format'),
    },
    {
        id: 'court',
        accessorFn: (row) => formatCourtName(row.court),
        header: sortableHeader('Court'),
    },
    {
        id: 'flagged_by',
        accessorFn: (row) => {
            const latest = row.moderation[row.moderation.length - 1] ?? null;
            return latest?.moderator?.name ?? '—';
        },
        header: sortableHeader('Flagged By'),
    },
    {
        id: 'flag_reason',
        header: 'Flag Reason',
        enableSorting: false,
        cell: ({ row }) => {
            const latest =
                row.original.moderation[row.original.moderation.length - 1] ??
                null;
            return (
                <span className="block max-w-xs truncate">
                    {latest?.reason ?? '—'}
                </span>
            );
        },
    },
    {
        id: 'actions',
        enableHiding: false,
        enableSorting: false,
        cell: ({ row }) => {
            const game = row.original;
            return (
                <div className="text-right">
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon">
                                <MoreHorizontal className="size-4" />
                                <span className="sr-only">Actions</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <Link href={show(game.uuid).url}>Review</Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            );
        },
    },
];

export default function OverrideIndex({ games }: { games: Paginated<Game> }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Flagged Games" />

            <ListPageShell>
                <div>
                    <h1 className="text-2xl font-semibold text-foreground">
                        Flagged Games
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {games.total} game{games.total !== 1 ? 's' : ''} flagged
                        for review
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    data={games.data}
                    pagination={
                        games.last_page > 1 ? (
                            <LaravelPagination links={games.links} />
                        ) : undefined
                    }
                />
            </ListPageShell>
        </AppLayout>
    );
}
