import { Head, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Trophy } from 'lucide-react';
import {
    DataTable,
    selectionColumn,
    sortableHeader,
} from '@/components/ui/data-table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { leaderboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type LeaderboardEntry = {
    rank: number;
    player_id: number;
    player_name: string;
    wins: number;
    losses: number;
    total_games: number;
    score: number;
};

type Filters = {
    format: string;
    geo: string;
};

type Props = {
    entries: LeaderboardEntry[];
    filters: Filters;
    formats: string[];
};

const geoOptions = [
    { value: 'local', label: 'Local' },
    { value: 'national', label: 'National' },
    { value: 'continental', label: 'Continental' },
];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Leaderboard', href: leaderboard().url },
];

const columns: ColumnDef<LeaderboardEntry, unknown>[] = [
    selectionColumn<LeaderboardEntry>(),
    {
        accessorKey: 'rank',
        header: sortableHeader('Rank'),
        cell: ({ row }) => (
            <span className="font-bold">#{row.getValue('rank')}</span>
        ),
    },
    {
        accessorKey: 'player_name',
        header: sortableHeader('Player'),
        cell: ({ row }) => (
            <span className="font-medium">{row.getValue('player_name')}</span>
        ),
    },
    {
        accessorKey: 'wins',
        header: sortableHeader('W'),
        cell: ({ row }) => (
            <span className="block text-right text-green-600">
                {row.getValue('wins')}
            </span>
        ),
    },
    {
        accessorKey: 'losses',
        header: sortableHeader('L'),
        cell: ({ row }) => (
            <span className="block text-right text-red-500">
                {row.getValue('losses')}
            </span>
        ),
    },
    {
        accessorKey: 'total_games',
        header: sortableHeader('Games'),
        cell: ({ row }) => (
            <span className="block text-right">
                {row.getValue('total_games')}
            </span>
        ),
    },
    {
        accessorKey: 'score',
        header: sortableHeader('Score'),
        cell: ({ row }) => {
            const score = row.getValue('score') as number;
            return (
                <span className="block text-right font-mono">
                    {score.toFixed(2)}
                </span>
            );
        },
    },
];

export default function LeaderboardIndex({ entries, filters, formats }: Props) {
    function handleFilterChange(key: string, value: string) {
        router.get(
            leaderboard().url,
            { ...filters, [key]: value },
            { preserveState: true },
        );
    }

    const toolbar = (
        <>
            <div className="w-40">
                <Select
                    value={filters.format}
                    onValueChange={(val) => handleFilterChange('format', val)}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Format" />
                    </SelectTrigger>
                    <SelectContent>
                        {formats.map((fmt) => (
                            <SelectItem key={fmt} value={fmt}>
                                {fmt}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="w-44">
                <Select
                    value={filters.geo}
                    onValueChange={(val) => handleFilterChange('geo', val)}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Region" />
                    </SelectTrigger>
                    <SelectContent>
                        {geoOptions.map((opt) => (
                            <SelectItem key={opt.value} value={opt.value}>
                                {opt.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        </>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Leaderboard" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center gap-3">
                    <Trophy className="h-6 w-6 text-yellow-500" />
                    <div>
                        <h1 className="text-2xl font-semibold">Leaderboard</h1>
                        <p className="text-sm text-muted-foreground">
                            Rankings based on approved games
                        </p>
                    </div>
                </div>

                <DataTable columns={columns} data={entries} toolbar={toolbar} />
            </div>
        </AppLayout>
    );
}
