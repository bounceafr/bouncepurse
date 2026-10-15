import { Head, Link } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import GameVideoController from '@/actions/App/Http/Controllers/Admin/GameVideoController';
import {
    index,
    show,
} from '@/actions/App/Http/Controllers/Admin/ModerationController';
import { GameStatusBadge } from '@/components/game-status-badge';
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
import { type GameSummary, type GameVideo } from '@/lib/game';
import type { BreadcrumbItem, Paginated } from '@/types';

type Game = GameSummary & GameVideo;

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
        accessorFn: (row) => formatCourtName(row.court),
        header: sortableHeader('Court'),
    },
    {
        accessorKey: 'played_at',
        header: sortableHeader('Played At'),
        cell: ({ row }) =>
            new Date(row.getValue('played_at')).toLocaleDateString(),
    },
    {
        accessorKey: 'status',
        header: sortableHeader('Status'),
        cell: ({ row }) => <GameStatusBadge status={row.original.status} />,
    },
    {
        id: 'video',
        header: 'Video',
        enableSorting: false,
        cell: ({ row }) => {
            const game = row.original;
            const href = game.video_path
                ? GameVideoController.show(game.uuid).url
                : game.vimeo_uri && `https://vimeo.com${game.vimeo_uri}`;

            return href ? (
                <a
                    href={href}
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

export default function ModerationIndex({ games }: { games: Paginated<Game> }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Moderation Queue" />

            <ListPageShell>
                <div>
                    <h1 className="text-2xl font-semibold text-foreground">
                        Moderation Queue
                    </h1>
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
            </ListPageShell>
        </AppLayout>
    );
}
