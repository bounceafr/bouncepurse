import { Head, Link } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
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
import { index, show } from '@/routes/admin/override';
import type { BreadcrumbItem } from '@/types';

type User = { id: number; name: string };
type Court = { id: number; name: string };

type GameModeration = {
    id: number;
    status: string;
    reason: string;
    is_override: boolean;
    created_at: string;
    moderator: User | null;
};

type Game = {
    id: number;
    uuid: string;
    title: string;
    format: string;
    court_id: number | null;
    player_id: number;
    played_at: string;
    vimeo_uri: string | null;
    vimeo_status: string | null;
    status: string;
    court: Court | null;
    player: User | null;
    moderation: GameModeration[];
};

type PaginationLink = { url: string | null; label: string; active: boolean };

type PaginatedGames = {
    data: Game[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
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
        accessorFn: (row) => row.court?.name ?? '—',
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

export default function OverrideIndex({ games }: { games: PaginatedGames }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Flagged Games" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Flagged Games</h1>
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
            </div>
        </AppLayout>
    );
}
