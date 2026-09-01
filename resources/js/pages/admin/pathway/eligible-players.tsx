import { Head, router } from '@inertiajs/react';
import { DownloadIcon, SearchIcon, XIcon } from 'lucide-react';
import { type FormEvent } from 'react';
import { useState } from 'react';
import {
    exportMethod,
    index,
} from '@/actions/App/Http/Controllers/Admin/PathwayEligiblePlayersController';
import { ListPageShell } from '@/components/list-page-shell';
import { Button } from '@/components/ui/button';
import {
    type DataTableColumnDef as ColumnDef,
    DataTable,
    LaravelPagination,
    selectionColumn,
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
    total: number;
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

function ColumnHeader({ label }: { label: string }) {
    return (
        <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
            {label}
        </span>
    );
}

const columns: ColumnDef<Candidate, unknown>[] = [
    selectionColumn<Candidate>(),
    {
        accessorKey: 'name',
        header: () => <ColumnHeader label="Player Name" />,
        enableSorting: false,
        cell: ({ row }) => (
            <span className="font-medium text-foreground">
                {row.getValue('name')}
            </span>
        ),
    },
    {
        id: 'country',
        accessorFn: (row) => row.profile?.country?.name ?? '—',
        header: () => <ColumnHeader label="Country" />,
        enableSorting: false,
        cell: ({ getValue }) => (
            <span className="text-muted-foreground">{String(getValue())}</span>
        ),
    },
    {
        id: 'best_rank',
        accessorFn: (row) => getBestRank(row.rankings),
        header: () => <ColumnHeader label="Best Rank" />,
        enableSorting: false,
        cell: ({ row }) => {
            const rank = getBestRank(row.original.rankings);
            return (
                <span className="text-muted-foreground">
                    {rank !== null ? `#${rank}` : '—'}
                </span>
            );
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pathway Candidates" />

            <ListPageShell>
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">
                            Pathway Candidates
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Players who meet all pathway eligibility criteria.
                        </p>
                    </div>
                    <Button
                        asChild
                        variant="outline"
                        className="border-border bg-background shadow-none"
                    >
                        <a href={exportUrl}>
                            <DownloadIcon className="size-4" />
                            Export CSV
                        </a>
                    </Button>
                </div>

                <form
                    className="flex items-center justify-between gap-4"
                    onSubmit={applyFilters}
                >
                    <div className="relative flex-1">
                        <SearchIcon className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder="Search players..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="border-border bg-background pl-9 shadow-none"
                        />
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        <Button
                            type="submit"
                            size="sm"
                            className="shadow-none"
                        >
                            Search
                        </Button>
                        {filters.search ? (
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                className="border-border bg-background shadow-none"
                                onClick={clearFilters}
                            >
                                <XIcon className="size-4" />
                                Clear
                            </Button>
                        ) : null}
                        <span className="text-sm text-muted-foreground">
                            {candidates.total}{' '}
                            {candidates.total === 1
                                ? 'candidate'
                                : 'candidates'}
                        </span>
                    </div>
                </form>

                <DataTable
                    columns={columns}
                    data={candidates.data}
                    hideColumnToggle
                    pagination={
                        candidates.last_page > 1 ? (
                            <LaravelPagination links={candidates.links} />
                        ) : undefined
                    }
                />
            </ListPageShell>
        </AppLayout>
    );
}
