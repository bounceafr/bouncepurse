import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type AllocationTotals = {
    total: number;
    insurance: number;
    savings: number;
    pathway: number;
    administration: number;
    count: number;
};

type Metrics = {
    total_users: number;
    active_players: number;
    games_submitted: number;
    games_approved: number;
    moderation_queue_size: number;
    average_review_time_hours: number | null;
    allocation_totals: AllocationTotals;
    pathway_candidate_count: number;
};

type Props = {
    metrics: Metrics;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin/dashboard' },
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
                {subtitle != null && (
                    <p className="text-xs text-muted-foreground">{subtitle}</p>
                )}
            </CardContent>
        </Card>
    );
}

export default function AdminDashboard({ metrics }: Props) {
    const { allocation_totals: alloc } = metrics;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Admin Dashboard</h1>
                    <p className="text-sm text-muted-foreground">
                        Platform health and key metrics at a glance.
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <StatCard
                        title="Total Users"
                        value={metrics.total_users.toLocaleString()}
                    />
                    <StatCard
                        title="Active Players"
                        value={metrics.active_players.toLocaleString()}
                        subtitle="Players who have submitted at least one game"
                    />
                    <StatCard
                        title="Games Submitted"
                        value={metrics.games_submitted.toLocaleString()}
                    />
                    <StatCard
                        title="Games Approved"
                        value={metrics.games_approved.toLocaleString()}
                    />
                </div>

                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <StatCard
                        title="Moderation Queue"
                        value={metrics.moderation_queue_size.toLocaleString()}
                        subtitle="Games pending review"
                    />
                    <StatCard
                        title="Avg. Review Time"
                        value={
                            metrics.average_review_time_hours != null
                                ? `${metrics.average_review_time_hours}h`
                                : '—'
                        }
                        subtitle="Hours from submit to first review"
                    />
                    <StatCard
                        title="Pathway Candidates"
                        value={metrics.pathway_candidate_count.toLocaleString()}
                    />
                </div>

                <div>
                    <h2 className="mb-3 text-lg font-medium">
                        Allocation Totals
                    </h2>
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                        <StatCard
                            title="Total"
                            value={`$${alloc.total.toFixed(2)}`}
                            subtitle={`${alloc.count} games`}
                        />
                        <StatCard
                            title="Insurance"
                            value={`$${alloc.insurance.toFixed(4)}`}
                        />
                        <StatCard
                            title="Savings"
                            value={`$${alloc.savings.toFixed(4)}`}
                        />
                        <StatCard
                            title="Pathway"
                            value={`$${alloc.pathway.toFixed(4)}`}
                        />
                        <StatCard
                            title="Administration"
                            value={`$${alloc.administration.toFixed(4)}`}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
