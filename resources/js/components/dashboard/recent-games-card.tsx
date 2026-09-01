import * as React from 'react';
import {
    type ColumnDef,
    type ColumnFiltersState,
    flexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    type SortingState,
    type VisibilityState,
    useReactTable,
} from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Clock,
    History,
    MapPin,
    MoreHorizontal,
    Search,
    SlidersHorizontal,
    User,
    XCircle,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from '@/components/ui/drawer';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';
import { show } from '@/routes/admin/games';
import { type RecentGame } from './types';

const STATUS_CONFIG: Record<string, { icon: React.ReactNode; dot: string; text: string; label: string }> = {
    approved: {
        icon: <CheckCircle2 className="size-3.5" />,
        dot: 'bg-green-500',
        text: 'text-green-600 dark:text-green-400',
        label: 'Approved',
    },
    pending: {
        icon: <Clock className="size-3.5" />,
        dot: 'bg-amber-500',
        text: 'text-amber-600 dark:text-amber-400',
        label: 'Pending',
    },
    rejected: {
        icon: <XCircle className="size-3.5" />,
        dot: 'bg-red-500',
        text: 'text-red-600 dark:text-red-400',
        label: 'Rejected',
    },
    flagged: {
        icon: <AlertTriangle className="size-3.5" />,
        dot: 'bg-orange-500',
        text: 'text-orange-600 dark:text-orange-400',
        label: 'Contested',
    },
    scheduled: {
        icon: <Clock className="size-3.5" />,
        dot: 'bg-blue-500',
        text: 'text-blue-600 dark:text-blue-400',
        label: 'Scheduled',
    },
};

function StatusBadge({ status }: { status: string }) {
    const cfg = STATUS_CONFIG[status] ?? {
        icon: null,
        dot: 'bg-muted-foreground',
        text: 'text-muted-foreground',
        label: status,
    };

    return (
        <Badge variant="outline" className={cn('gap-1.5 capitalize', cfg.text)}>
            {cfg.icon}
            {cfg.label}
        </Badge>
    );
}

function GameDetailDrawer({ game }: { game: RecentGame }) {
    const isMobile = useIsMobile();
    const cfg = STATUS_CONFIG[game.status];

    const playedDate = game.played_at
        ? new Date(game.played_at).toLocaleDateString('en-US', {
              weekday: 'long',
              year: 'numeric',
              month: 'long',
              day: 'numeric',
          })
        : '—';

    return (
        <Drawer direction={isMobile ? 'bottom' : 'right'}>
            <DrawerTrigger asChild>
                <Button
                    variant="link"
                    className="h-auto w-fit p-0 text-left text-sm font-medium text-foreground"
                >
                    {game.title}
                </Button>
            </DrawerTrigger>
            <DrawerContent>
                <DrawerHeader className="gap-1">
                    <DrawerTitle>{game.title}</DrawerTitle>
                    <DrawerDescription>Game details</DrawerDescription>
                </DrawerHeader>

                <div className="flex flex-col gap-4 overflow-y-auto px-4 pb-2 text-sm">
                    <div className="flex items-center gap-2">
                        <StatusBadge status={game.status} />
                    </div>

                    <Separator />

                    <dl className="grid gap-3">
                        <div className="flex items-start gap-3">
                            <CalendarDays className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                            <div>
                                <dt className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    Date Played
                                </dt>
                                <dd className="mt-0.5 font-medium">{playedDate}</dd>
                            </div>
                        </div>

                        <div className="flex items-start gap-3">
                            <MapPin className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                            <div>
                                <dt className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    Court
                                </dt>
                                <dd className="mt-0.5 font-medium">
                                    {game.court?.name ?? '—'}
                                </dd>
                            </div>
                        </div>

                        <div className="flex items-start gap-3">
                            <User className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                            <div>
                                <dt className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    Player
                                </dt>
                                <dd className="mt-0.5 font-medium">
                                    {game.player?.name ?? '—'}
                                </dd>
                            </div>
                        </div>
                    </dl>

                    <Separator />
                </div>

                <DrawerFooter>
                    <Button asChild>
                        <Link href={show(game.uuid).url}>View Full Game</Link>
                    </Button>
                    <DrawerClose asChild>
                        <Button variant="outline">Close</Button>
                    </DrawerClose>
                </DrawerFooter>
            </DrawerContent>
        </Drawer>
    );
}

const columns: ColumnDef<RecentGame>[] = [
    {
        id: 'select',
        header: ({ table }) => (
            <div className="flex items-center justify-center">
                <Checkbox
                    checked={
                        table.getIsAllPageRowsSelected() ||
                        (table.getIsSomePageRowsSelected() && 'indeterminate')
                    }
                    onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
                    aria-label="Select all"
                />
            </div>
        ),
        cell: ({ row }) => (
            <div className="flex items-center justify-center">
                <Checkbox
                    checked={row.getIsSelected()}
                    onCheckedChange={(value) => row.toggleSelected(!!value)}
                    aria-label="Select row"
                />
            </div>
        ),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: 'title',
        header: 'Title',
        cell: ({ row }) => <GameDetailDrawer game={row.original} />,
        enableHiding: false,
    },
    {
        id: 'player',
        accessorFn: (row) => row.player?.name ?? '—',
        header: 'Player',
        cell: ({ row }) => (
            <span className="text-muted-foreground">{row.original.player?.name ?? '—'}</span>
        ),
    },
    {
        id: 'court',
        accessorFn: (row) => row.court?.name ?? '—',
        header: 'Court',
        cell: ({ row }) => (
            <span className="text-muted-foreground">{row.original.court?.name ?? '—'}</span>
        ),
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => <StatusBadge status={row.getValue('status')} />,
    },
    {
        accessorKey: 'played_at',
        header: 'Date',
        cell: ({ row }) => {
            const val = row.getValue<string>('played_at');
            return val
                ? new Date(val).toLocaleDateString('en-US', {
                      month: 'short',
                      day: 'numeric',
                      year: 'numeric',
                  })
                : '—';
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => (
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8 text-muted-foreground data-[state=open]:bg-muted"
                    >
                        <MoreHorizontal className="size-4" />
                        <span className="sr-only">Open menu</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-36">
                    <DropdownMenuItem asChild>
                        <Link href={show(row.original.uuid).url}>View game</Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        ),
        enableSorting: false,
        enableHiding: false,
    },
];

export function RecentGamesCard({ games }: { games: RecentGame[] }) {
    const [sorting, setSorting] = React.useState<SortingState>([
        { id: 'played_at', desc: true },
    ]);
    const [columnFilters, setColumnFilters] = React.useState<ColumnFiltersState>([]);
    const [columnVisibility, setColumnVisibility] = React.useState<VisibilityState>({});
    const [rowSelection, setRowSelection] = React.useState({});
    const [pagination, setPagination] = React.useState({ pageIndex: 0, pageSize: 10 });

    const table = useReactTable({
        data: games,
        columns,
        state: { sorting, columnFilters, columnVisibility, rowSelection, pagination },
        getRowId: (row) => String(row.id),
        enableRowSelection: true,
        onSortingChange: setSorting,
        onColumnFiltersChange: setColumnFilters,
        onColumnVisibilityChange: setColumnVisibility,
        onRowSelectionChange: setRowSelection,
        onPaginationChange: setPagination,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
    });

    const selectedCount = table.getFilteredSelectedRowModel().rows.length;
    const totalCount = table.getFilteredRowModel().rows.length;

    return (
        <Card className="@container/card">
            <CardHeader>
                <div className="flex items-center gap-2">
                    <span className="flex size-8 items-center justify-center rounded-lg bg-green-500/10 text-green-500 [&>svg]:size-4">
                        <History />
                    </span>
                    <div>
                        <CardTitle>Recent Games</CardTitle>
                        <CardDescription>{games.length} most recent</CardDescription>
                    </div>
                </div>
            </CardHeader>

            <CardContent className="pt-0">
                {/* Toolbar */}
                <div className="flex items-center gap-2 pb-4">
                    <div className="relative flex-1 @sm/card:max-w-xs">
                        <Search className="absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Filter by title…"
                            value={(table.getColumn('title')?.getFilterValue() as string) ?? ''}
                            onChange={(e) =>
                                table.getColumn('title')?.setFilterValue(e.target.value)
                            }
                            className="h-8 pl-8 text-sm"
                        />
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" size="sm" className="ml-auto">
                                <SlidersHorizontal className="size-3.5" />
                                <span className="hidden sm:inline">Columns</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-40">
                            {table
                                .getAllColumns()
                                .filter((col) => col.getCanHide())
                                .map((col) => (
                                    <DropdownMenuCheckboxItem
                                        key={col.id}
                                        className="capitalize"
                                        checked={col.getIsVisible()}
                                        onCheckedChange={(value) =>
                                            col.toggleVisibility(!!value)
                                        }
                                    >
                                        {col.id}
                                    </DropdownMenuCheckboxItem>
                                ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                {/* Table */}
                <div className="overflow-hidden rounded-lg border">
                    <Table>
                        <TableHeader className="bg-muted/50">
                            {table.getHeaderGroups().map((headerGroup) => (
                                <TableRow key={headerGroup.id}>
                                    {headerGroup.headers.map((header) => (
                                        <TableHead key={header.id}>
                                            {header.isPlaceholder
                                                ? null
                                                : flexRender(
                                                      header.column.columnDef.header,
                                                      header.getContext(),
                                                  )}
                                        </TableHead>
                                    ))}
                                </TableRow>
                            ))}
                        </TableHeader>
                        <TableBody>
                            {table.getRowModel().rows.length ? (
                                table.getRowModel().rows.map((row) => (
                                    <TableRow
                                        key={row.id}
                                        data-state={row.getIsSelected() && 'selected'}
                                    >
                                        {row.getVisibleCells().map((cell) => (
                                            <TableCell key={cell.id}>
                                                {flexRender(
                                                    cell.column.columnDef.cell,
                                                    cell.getContext(),
                                                )}
                                            </TableCell>
                                        ))}
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={columns.length}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No games found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                {/* Pagination */}
                <div className="mt-4 flex items-center justify-between">
                    <p className="hidden text-xs text-muted-foreground lg:block">
                        {selectedCount} of {totalCount} row(s) selected
                    </p>

                    <div className="flex w-full items-center gap-6 lg:w-auto">
                        <div className="hidden items-center gap-2 lg:flex">
                            <Label htmlFor="page-size" className="text-xs font-medium">
                                Rows per page
                            </Label>
                            <Select
                                value={String(table.getState().pagination.pageSize)}
                                onValueChange={(v) => table.setPageSize(Number(v))}
                            >
                                <SelectTrigger id="page-size" size="sm" className="w-16">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent side="top">
                                    {[5, 10, 15, 20].map((size) => (
                                        <SelectItem key={size} value={String(size)}>
                                            {size}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <p className="text-xs font-medium text-muted-foreground">
                            Page {table.getState().pagination.pageIndex + 1} of{' '}
                            {table.getPageCount()}
                        </p>

                        <div className="ml-auto flex items-center gap-1 lg:ml-0">
                            <Button
                                variant="outline"
                                size="icon"
                                className="hidden size-7 lg:flex"
                                onClick={() => table.setPageIndex(0)}
                                disabled={!table.getCanPreviousPage()}
                            >
                                <ChevronsLeft className="size-3.5" />
                                <span className="sr-only">First page</span>
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                className="size-7"
                                onClick={() => table.previousPage()}
                                disabled={!table.getCanPreviousPage()}
                            >
                                <ChevronLeft className="size-3.5" />
                                <span className="sr-only">Previous page</span>
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                className="size-7"
                                onClick={() => table.nextPage()}
                                disabled={!table.getCanNextPage()}
                            >
                                <ChevronRight className="size-3.5" />
                                <span className="sr-only">Next page</span>
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                className="hidden size-7 lg:flex"
                                onClick={() => table.setPageIndex(table.getPageCount() - 1)}
                                disabled={!table.getCanNextPage()}
                            >
                                <ChevronsRight className="size-3.5" />
                                <span className="sr-only">Last page</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
