import { Head, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { CalendarIcon, CheckCircle2, Gamepad2, PiggyBank, Route } from 'lucide-react';
import { useState } from 'react';
import LedgerController from '@/actions/App/Http/Controllers/LedgerController';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { SectionCards, type SectionCardData } from '@/components/section-cards';
import { cn } from '@/lib/utils';
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
    game_formats: { value: string; label: string }[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Ledger', href: LedgerController().url },
];

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
        cell: ({ row }) => (
            <Badge variant="secondary">{row.getValue('format')}</Badge>
        ),
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
    game_formats,
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

    const sectionCards: SectionCardData[] = [
        {
            label: 'Total Games',
            value: total_games,
            icon: <Gamepad2 />,
            trendLabel: 'All games',
            description: 'Total games recorded',
            cardClassName: 'bg-gradient-to-t from-blue-500/15 to-card',
            iconClassName: 'bg-blue-500/15 text-blue-500',
        },
        {
            label: 'Approved Games',
            value: approved_games,
            icon: <CheckCircle2 />,
            trendLabel: `${total_games > 0 ? Math.round((approved_games / total_games) * 100) : 0}% approval rate`,
            description: 'Successfully approved games',
            cardClassName: 'bg-gradient-to-t from-green-500/15 to-card',
            iconClassName: 'bg-green-500/15 text-green-500',
        },
        {
            label: 'Savings Credits',
            value: summary.savings,
            icon: <PiggyBank />,
            trendLabel: 'Your savings pool',
            description: 'Credits allocated to savings',
            valueFormatter: (value) => `$${value.toFixed(4)}`,
            cardClassName: 'bg-gradient-to-t from-amber-500/15 to-card',
            iconClassName: 'bg-amber-500/15 text-amber-500',
        },
        {
            label: 'Pathway Credits',
            value: summary.pathway,
            icon: <Route />,
            trendLabel: 'Your pathway pool',
            description: 'Credits allocated to pathway',
            valueFormatter: (value) => `$${value.toFixed(4)}`,
            cardClassName: 'bg-gradient-to-t from-purple-500/15 to-card',
            iconClassName: 'bg-purple-500/15 text-purple-500',
        },
    ];

    const [fromOpen, setFromOpen] = useState(false);
    const [toOpen, setToOpen] = useState(false);

    const toolbar = (
        <div className="flex flex-wrap items-center gap-2">
            <div className="flex items-center gap-2">
                <div className="grid gap-1.5">
                    <Label htmlFor="from" className="text-xs">
                        From
                    </Label>
                    <Popover open={fromOpen} onOpenChange={setFromOpen}>
                        <PopoverTrigger asChild>
                            <Button
                                id="from"
                                variant="outline"
                                className={cn(
                                    'w-36 justify-start text-left font-normal',
                                    !from && 'text-muted-foreground'
                                )}
                            >
                                <CalendarIcon className="mr-2 size-4" />
                                {from ? (
                                    new Date(from).toLocaleDateString(undefined, {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    })
                                ) : (
                                    <span>Pick a date</span>
                                )}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-auto p-0" align="start">
                            <Calendar
                                mode="single"
                                selected={from ? new Date(from) : undefined}
                                onSelect={(date) => {
                                    if (date) {
                                        const year = date.getFullYear();
                                        const month = String(date.getMonth() + 1).padStart(2, '0');
                                        const day = String(date.getDate()).padStart(2, '0');
                                        setFrom(`${year}-${month}-${day}`);
                                    } else {
                                        setFrom('');
                                    }
                                    setFromOpen(false);
                                }}
                            />
                        </PopoverContent>
                    </Popover>
                </div>
                <div className="grid gap-1.5">
                    <Label htmlFor="to" className="text-xs">
                        To
                    </Label>
                    <Popover open={toOpen} onOpenChange={setToOpen}>
                        <PopoverTrigger asChild>
                            <Button
                                id="to"
                                variant="outline"
                                className={cn(
                                    'w-36 justify-start text-left font-normal',
                                    !to && 'text-muted-foreground'
                                )}
                            >
                                <CalendarIcon className="mr-2 size-4" />
                                {to ? (
                                    new Date(to).toLocaleDateString(undefined, {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    })
                                ) : (
                                    <span>Pick a date</span>
                                )}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-auto p-0" align="start">
                            <Calendar
                                mode="single"
                                selected={to ? new Date(to) : undefined}
                                onSelect={(date) => {
                                    if (date) {
                                        const year = date.getFullYear();
                                        const month = String(date.getMonth() + 1).padStart(2, '0');
                                        const day = String(date.getDate()).padStart(2, '0');
                                        setTo(`${year}-${month}-${day}`);
                                    } else {
                                        setTo('');
                                    }
                                    setToOpen(false);
                                }}
                            />
                        </PopoverContent>
                    </Popover>
                </div>
                <div className="grid gap-1.5">
                    <Label htmlFor="format" className="text-xs">
                        Format
                    </Label>
                    <Select value={format} onValueChange={setFormat}>
                        <SelectTrigger id="format" className="w-32">
                            <SelectValue placeholder="Select format" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All</SelectItem>
                            {game_formats.map(({ value, label }) => (
                                <SelectItem key={value} value={value}>
                                    {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <div className="flex gap-2 pt-5">
                    <Button size="sm" onClick={applyFilters}>
                        Apply
                    </Button>
                    <Button size="sm" variant="outline" onClick={clearFilters}>
                        Clear
                    </Button>
                </div>
            </div>
        </div>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Ledger" />

            <div className="@container/main flex flex-1 flex-col gap-2">
                <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
                    <div className="px-4 lg:px-6">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            My Ledger
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            View your game credits and allocation history.
                        </p>
                    </div>

                    <SectionCards cards={sectionCards} />

                    <div className="px-4 lg:px-6">
                        <Alert>
                            <AlertDescription>
                                Credits shown are informational and do not
                                represent cash value.
                            </AlertDescription>
                        </Alert>
                    </div>

                    <div className="px-4 lg:px-6 *:data-[slot=card]:shadow-xs">
                        <DataTable
                            columns={columns}
                            data={allocations.data}
                            toolbar={toolbar}
                            pagination={
                                allocations.last_page > 1 ? (
                                    <LaravelPagination
                                        links={allocations.links}
                                    />
                                ) : undefined
                            }
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
