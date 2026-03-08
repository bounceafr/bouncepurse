import { Head, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { DownloadIcon } from 'lucide-react';
import { useState } from 'react';
import {
    exportMethod,
    index,
} from '@/actions/App/Http/Controllers/Admin/PathwayEligiblePlayersController';
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

type Candidate = {
    id: number;
    name: string;
    profile: {
        country: { name: string } | null;
        is_pathway_candidate: boolean;
    } | null;
    rankings: { format: string; rank: number }[];
};

type PaginatedCandidates = {
    data: Candidate[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
};

type Filters = {
    search?: string;
};

type Props = {
    candidates: PaginatedCandidates;
    filters: Filters;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pathway Candidates', href: index().url },
];

function getBestRank(rankings: { rank: number }[]): number | null {
    if (rankings.length === 0) {
        return null;
    }
    return Math.min(...rankings.map((r) => r.rank));
}

const columns: ColumnDef<Candidate, unknown>[] = [
    selectionColumn<Candidate>(),
    {
        accessorKey: 'name',
        header: sortableHeader('Player Name'),
        cell: ({ row }) => (
            <span className="font-medium">{row.getValue('name')}</span>
        ),
    },
    {
        id: 'country',
        accessorFn: (row) => row.profile?.country?.name ?? '—',
        header: sortableHeader('Country'),
    },
    {
        id: 'best_rank',
        accessorFn: (row) => getBestRank(row.rankings),
        header: sortableHeader('Best Rank'),
        cell: ({ row }) => {
            const rank = getBestRank(row.original.rankings);
            return rank !== null ? `#${rank}` : '—';
        },
    },
];

export default function EligiblePlayers({ candidates, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    function applyFilters() {
        const params: Record<string, string> = {};
        if (search) {
            params.search = search;
        }
        router.get(index().url, params, { preserveState: true });
    }

    function clearFilters() {
        setSearch('');
        router.get(index().url, {}, { preserveState: true });
    }

    const exportUrl = (() => {
        const params = new URLSearchParams();
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
            <Head title="Pathway Candidates" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Pathway Candidates
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Players who meet all pathway eligibility criteria.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Search</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid max-w-sm gap-1.5">
                            <Label htmlFor="search">Player Name</Label>
                            <Input
                                id="search"
                                placeholder="Search by name..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                        <div className="mt-4 flex gap-2">
                            <Button size="sm" onClick={applyFilters}>
                                Search
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
                    data={candidates.data}
                    toolbar={toolbar}
                    pagination={
                        candidates.last_page > 1 ? (
                            <LaravelPagination links={candidates.links} />
                        ) : undefined
                    }
                />
            </div>
        </AppLayout>
    );
}
