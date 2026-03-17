import { Head, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { CheckCircle2, Gamepad2, PiggyBank, Route } from 'lucide-react';
import { useState } from 'react';
import LedgerController from '@/actions/App/Http/Controllers/LedgerController';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DataTable,
    LaravelPagination,
    selectionColumn,
    sortableHeader,
} from '@/components/ui/data-table';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Summary = {
    total: number;
    insurance: number;
    savings: number;
    pathway: number;
    administration: number;
    count: number;
};

type Allocation = {
    id: number;
    game_id: number;
    player: { id: number; name: string };
    game: { format: string };
    total_amount: number;
    savings_amount: number;
    pathway_amount: number;
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
};

type Props = {
    summary: Summary;
    allocations: PaginatedAllocations;
    filters: Filters;
    total_games: number;
    approved_games: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Ledger', href: LedgerController().url },
];

function StatCard({
    title,
    value,
    subtitle,
    icon,
}: {
    title: string;
    value: string;
    subtitle?: string;
    icon: React.ReactNode;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {title}
                </CardTitle>
                <div className="[&>svg]:size-4 [&>svg]:text-muted-foreground">
                    {icon}
                </div>
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
        accessorKey: 'created_at',
        header: sortableHeader('Date'),
        cell: ({ row }) =>
            new Date(row.getValue('created_at')).toLocaleDateString(),
    },
    {
        id: 'format',
        accessorFn: (row) => row.game.format,
        header: sortableHeader('Game Format'),
    },
    {
        accessorKey: 'total_amount',
        header: sortableHeader('Total'),
        cell: ({ row }) =>
            `$${(row.getValue('total_amount') as number).toFixed(2)}`,
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
];

export default function LedgerIndex({
    summary,
    allocations,
    filters,
    total_games,
    approved_games,
}: Props) {
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');
    const [format, setFormat] = useState(filters.format ?? '');

    function applyFilters() {
        const params: Record<string, string> = {};
        if (from) {
            params.from = from;
        }
        if (to) {
            params.to = to;
        }
        if (format) {
            params.format = format;
        }

        router.get(LedgerController().url, params, { preserveState: true });
    }

    function clearFilters() {
        setFrom('');
        setTo('');
        setFormat('');
        router.get(LedgerController().url, {}, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Ledger" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">My Ledger</h1>
                    <p className="text-sm text-muted-foreground">
                        View your game credits and allocation history.
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatCard
                        title="Total Games"
                        value={total_games.toLocaleString()}
                        icon={<Gamepad2 />}
                    />
                    <StatCard
                        title="Approved Games"
                        value={approved_games.toLocaleString()}
                        icon={<CheckCircle2 />}
                    />
                    <StatCard
                        title="Savings Credits"
                        value={`$${summary.savings.toFixed(4)}`}
                        icon={<PiggyBank />}
                    />
                    <StatCard
                        title="Pathway Credits"
                        value={`$${summary.pathway.toFixed(4)}`}
                        icon={<Route />}
                    />
                </div>

                <Alert>
                    <AlertDescription>
                        Credits shown are informational and do not represent
                        cash value.
                    </AlertDescription>
                </Alert>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <div className="grid gap-1.5">
                                <Label htmlFor="from">From</Label>
                                <Input
                                    id="from"
                                    type="date"
                                    value={from}
                                    onChange={(e) => setFrom(e.target.value)}
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="to">To</Label>
                                <Input
                                    id="to"
                                    type="date"
                                    value={to}
                                    onChange={(e) => setTo(e.target.value)}
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="format">Format</Label>
                                <Input
                                    id="format"
                                    placeholder="e.g. 1v1"
                                    value={format}
                                    onChange={(e) => setFormat(e.target.value)}
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
