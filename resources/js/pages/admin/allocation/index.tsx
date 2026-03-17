import { Head, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { CalendarIcon, DownloadIcon } from 'lucide-react';
import { useState } from 'react';
import {
    exportMethod,
    index,
} from '@/actions/App/Http/Controllers/Admin/AllocationController';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DataTable,
    LaravelPagination,
    selectionColumn,
    sortableHeader,
} from '@/components/ui/data-table';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type Summary = {
    total: number;
    insurance: number;
    savings: number;
    pathway: number;
    administration: number;
    court_fees: number;
    count: number;
};

type Allocation = {
    id: number;
    game_id: number;
    player: { id: number; name: string };
    game: { format: string };
    total_amount: number;
    insurance_amount: number;
    savings_amount: number;
    pathway_amount: number;
    administration_amount: number;
    court_fees_amount: number;
    created_at: string;
};

type PaginatedAllocations = {
    data: Allocation[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
};

type Filters = {
    from?: string;
    to?: string;
    format?: string;
    player_id?: number;
};

type Props = {
    summary: Summary;
    allocations: PaginatedAllocations;
    filters: Filters;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Allocation Summary', href: index().url },
];

function StatCard({
    title,
    value,
    subtitle,
}: {
    title: string;
    value: string;
    subtitle?: string;
}) {
    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {title}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-2xl font-bold">{value}</p>
                {subtitle && (
                    <p className="text-xs text-muted-foreground">{subtitle}</p>
                )}
            </CardContent>
        </Card>
    );
}

const columns: ColumnDef<Allocation, unknown>[] = [
    selectionColumn<Allocation>(),
    {
        id: 'player',
        accessorFn: (row) => row.player.name,
        header: sortableHeader('Player'),
        cell: ({ row }) => (
            <span className="font-medium">{row.original.player.name}</span>
        ),
    },
    {
        id: 'format',
        accessorFn: (row) => row.game.format,
        header: sortableHeader('Format'),
    },
    {
        accessorKey: 'total_amount',
        header: sortableHeader('Total'),
        cell: ({ row }) =>
            `$${(row.getValue('total_amount') as number).toFixed(2)}`,
    },
    {
        accessorKey: 'insurance_amount',
        header: sortableHeader('Insurance'),
        cell: ({ row }) =>
            `$${(row.getValue('insurance_amount') as number).toFixed(4)}`,
    },
    {
        accessorKey: 'savings_amount',
        header: sortableHeader('Savings'),
        cell: ({ row }) =>
            `$${(row.getValue('savings_amount') as number).toFixed(4)}`,
    },
    {
        accessorKey: 'pathway_amount',
        header: sortableHeader('Pathway'),
        cell: ({ row }) =>
            `$${(row.getValue('pathway_amount') as number).toFixed(4)}`,
    },
    {
        accessorKey: 'administration_amount',
        header: sortableHeader('Administration'),
        cell: ({ row }) =>
            `$${(row.getValue('administration_amount') as number).toFixed(4)}`,
    },
    {
        accessorKey: 'court_fees_amount',
        header: sortableHeader('Court Fees'),
        cell: ({ row }) =>
            `$${(row.getValue('court_fees_amount') as number).toFixed(4)}`,
    },
    {
        accessorKey: 'created_at',
        header: sortableHeader('Date'),
        cell: ({ row }) =>
            new Date(row.getValue('created_at')).toLocaleDateString(),
    },
];

export default function AllocationIndex({
    summary,
    allocations,
    filters,
}: Props) {
    const [fromDate, setFromDate] = useState<Date | undefined>(
        filters.from ? new Date(filters.from + 'T00:00:00') : undefined,
    );
    const [toDate, setToDate] = useState<Date | undefined>(
        filters.to ? new Date(filters.to + 'T00:00:00') : undefined,
    );
    const [fromCalendarOpen, setFromCalendarOpen] = useState(false);
    const [toCalendarOpen, setToCalendarOpen] = useState(false);
    const [format, setFormat] = useState(filters.format ?? '');
    const [playerId, setPlayerId] = useState(
        filters.player_id ? String(filters.player_id) : '',
    );

    function formatDateParam(date: Date): string {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }

    function applyFilters() {
        const params: Record<string, string> = {};
        if (fromDate) {
            params.from = formatDateParam(fromDate);
        }
        if (toDate) {
            params.to = formatDateParam(toDate);
        }
        if (format) {
            params.format = format;
        }
        if (playerId) {
            params.player_id = playerId;
        }

        router.get(index().url, params, { preserveState: true });
    }

    function clearFilters() {
        setFromDate(undefined);
        setToDate(undefined);
        setFormat('');
        setPlayerId('');
        router.get(index().url, {}, { preserveState: true });
    }

    const exportUrl = (() => {
        const params = new URLSearchParams();
        if (filters.from) {
            params.set('from', filters.from);
        }
        if (filters.to) {
            params.set('to', filters.to);
        }
        if (filters.format) {
            params.set('format', filters.format);
        }
        if (filters.player_id) {
            params.set('player_id', String(filters.player_id));
        }
        const qs = params.toString();
        return exportMethod.url() + (qs ? `?${qs}` : '');
    })();

    const toolbar = (
        <Button asChild variant="outline" size="sm">
            <a href={exportUrl}>
                <DownloadIcon className="mr-2 h-4 w-4" />
                Export CSV
            </a>
        </Button>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Allocation Summary" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Allocation Summary
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        $1 per approved game, split across four categories.
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    <StatCard
                        title="Total Allocated"
                        value={`$${summary.total.toFixed(2)}`}
                        subtitle={`${summary.count} games`}
                    />
                    <StatCard
                        title="Insurance"
                        value={`$${summary.insurance.toFixed(4)}`}
                    />
                    <StatCard
                        title="Savings"
                        value={`$${summary.savings.toFixed(4)}`}
                    />
                    <StatCard
                        title="Pathway"
                        value={`$${summary.pathway.toFixed(4)}`}
                    />
                    <StatCard
                        title="Administration"
                        value={`$${summary.administration.toFixed(4)}`}
                    />
                    <StatCard
                        title="Court Fees"
                        value={`$${summary.court_fees.toFixed(4)}`}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div className="grid gap-1.5">
                                <Label>From</Label>
                                <Popover
                                    open={fromCalendarOpen}
                                    onOpenChange={setFromCalendarOpen}
                                >
                                    <PopoverTrigger asChild>
                                        <Button
                                            variant="outline"
                                            className={cn(
                                                'justify-start font-normal',
                                                !fromDate &&
                                                    'text-muted-foreground',
                                            )}
                                        >
                                            <CalendarIcon className="mr-2 size-4" />
                                            {fromDate
                                                ? fromDate.toLocaleDateString(
                                                      'default',
                                                      {
                                                          year: 'numeric',
                                                          month: 'long',
                                                          day: 'numeric',
                                                      },
                                                  )
                                                : 'Pick a date'}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent align="start">
                                        <Calendar
                                            mode="single"
                                            selected={fromDate}
                                            onSelect={(d) => {
                                                setFromDate(d);
                                                setFromCalendarOpen(false);
                                            }}
                                        />
                                    </PopoverContent>
                                </Popover>
                            </div>
                            <div className="grid gap-1.5">
                                <Label>To</Label>
                                <Popover
                                    open={toCalendarOpen}
                                    onOpenChange={setToCalendarOpen}
                                >
                                    <PopoverTrigger asChild>
                                        <Button
                                            variant="outline"
                                            className={cn(
                                                'justify-start font-normal',
                                                !toDate &&
                                                    'text-muted-foreground',
                                            )}
                                        >
                                            <CalendarIcon className="mr-2 size-4" />
                                            {toDate
                                                ? toDate.toLocaleDateString(
                                                      'default',
                                                      {
                                                          year: 'numeric',
                                                          month: 'long',
                                                          day: 'numeric',
                                                      },
                                                  )
                                                : 'Pick a date'}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent align="start">
                                        <Calendar
                                            mode="single"
                                            selected={toDate}
                                            onSelect={(d) => {
                                                setToDate(d);
                                                setToCalendarOpen(false);
                                            }}
                                        />
                                    </PopoverContent>
                                </Popover>
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="format">Format</Label>
                                <Input
                                    id="format"
                                    placeholder="e.g. singles"
                                    value={format}
                                    onChange={(e) => setFormat(e.target.value)}
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="player_id">Player ID</Label>
                                <Input
                                    id="player_id"
                                    type="number"
                                    placeholder="Player ID"
                                    value={playerId}
                                    onChange={(e) =>
                                        setPlayerId(e.target.value)
                                    }
                                />
                            </div>
                        </div>
                        <div className="mt-4 flex gap-2">
                            <Button size="sm" onClick={applyFilters}>
                                Apply
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={clearFilters}
                            >
                                Clear
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <DataTable
                    columns={columns}
                    data={allocations.data}
                    toolbar={toolbar}
                    pagination={
                        allocations.last_page > 1 ? (
                            <LaravelPagination links={allocations.links} />
                        ) : undefined
                    }
                />
            </div>
        </AppLayout>
    );
}
