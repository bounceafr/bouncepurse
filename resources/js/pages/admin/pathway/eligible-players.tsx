import { Head, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { DownloadIcon, SearchIcon, XIcon } from 'lucide-react';
import { type FormEvent } from 'react';
import { useState } from 'react';
import {
    exportMethod,
    index,
} from '@/actions/App/Http/Controllers/Admin/PathwayEligiblePlayersController';
import { Button } from '@/components/ui/button';
import {
    DataTable,
    LaravelPagination,
    selectionColumn,
    sortableHeader,
} from '@/components/ui/data-table';
import { Input } from '@/components/ui/input';
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

    function applyFilters(event?: FormEvent<HTMLFormElement>) {
        event?.preventDefault();

        const params: Record<string, string> = {};
        const searchTerm = search.trim();

        if (searchTerm) {
            params.search = searchTerm;
        }

        router.get(index().url, params, { preserveState: true, replace: true });
    }

    function clearFilters() {
        setSearch('');
        router.get(index().url, {}, { preserveState: true, replace: true });
    }

    const exportUrl = (() => {
        const params = new URLSearchParams();
        const searchTerm = search.trim();

        if (searchTerm) {
            params.set('search', searchTerm);
        }

        const qs = params.toString();
        return exportMethod.url() + (qs ? `?${qs}` : '');
    })();

    const toolbar = (
        <>
            <form
                className="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center"
                onSubmit={applyFilters}
            >
                <div className="relative w-full sm:w-80">
                    <SearchIcon className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="search"
                        type="search"
                        className="pl-8"
                        placeholder="Search players..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
                <div className="flex gap-2">
                    <Button size="sm" type="submit">
                        Search
                    </Button>
                    {filters.search ? (
                        <Button
                            size="sm"
                            type="button"
                            variant="outline"
                            onClick={clearFilters}
                        >
                            <XIcon className="mr-2 size-4" />
                            Clear
                        </Button>
                    ) : null}
                </div>
            </form>
            <Button asChild variant="outline" size="sm">
                <a href={exportUrl}>
                    <DownloadIcon className="mr-2 size-4" />
                    Export CSV
                </a>
            </Button>
        </>
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
