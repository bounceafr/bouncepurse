import { Head, Link } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';
import {
    index,
    show,
} from '@/actions/App/Http/Controllers/Admin/ModerationController';
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
import type { BreadcrumbItem } from '@/types';

type User = { id: number; name: string };
type Court = { id: number; name: string };

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
    { title: 'Moderation Queue', href: index().url },
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
        accessorKey: 'played_at',
        header: sortableHeader('Played At'),
        cell: ({ row }) =>
            new Date(row.getValue('played_at')).toLocaleDateString(),
    },
    {
        id: 'video',
        header: 'Video',
        enableSorting: false,
        cell: ({ row }) => {
            const game = row.original;
            return game.vimeo_uri ? (
                <a
                    href={`https://vimeo.com${game.vimeo_uri}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-sm text-blue-600 underline hover:text-blue-800"
                >
                    Watch
                </a>
            ) : (
                <span className="text-xs text-muted-foreground">No video</span>
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

export default function ModerationIndex({ games }: { games: PaginatedGames }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Moderation Queue" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Moderation Queue</h1>
                    <p className="text-sm text-muted-foreground">
                        {games.total} game{games.total !== 1 ? 's' : ''} pending
                        review
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
