import { Form, Head, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { UserMinus } from 'lucide-react';
import { useState } from 'react';
import { update as userUpdate } from '@/actions/App/Http/Controllers/Admin/UserController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { usePage } from '@inertiajs/react';
import type { BreadcrumbItem } from '@/types';

type ModeratorRow = {
    user_id: number;
    name: string;
    email: string;
    total_reviews: number;
    approval_rate: number;
    flag_rate: number;
};

type Props = {
    moderators: ModeratorRow[];
    filters: { from: string | null; to: string | null };
};

const moderatorsIndexUrl = '/admin/moderators';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Moderator performance', href: moderatorsIndexUrl },
];

export default function ModeratorsIndex({ moderators, filters }: Props) {
    const { auth } = usePage().props as { auth: { permissions: string[] } };
    const canManageUsers = auth.permissions.includes('manage-users');

    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');

    function applyFilters() {
        const params: Record<string, string> = {};
        if (from) params.from = from;
        if (to) params.to = to;
        router.get(moderatorsIndexUrl, params, { preserveState: true });
    }

    function clearFilters() {
        setFrom('');
        setTo('');
        router.get(moderatorsIndexUrl, {}, { preserveState: true });
    }

    const columns: ColumnDef<ModeratorRow, unknown>[] = [
        {
            accessorKey: 'name',
            header: 'Moderator',
            cell: ({ row }) => (
                <div>
                    <p className="font-medium">{row.original.name}</p>
                    <p className="text-xs text-muted-foreground">
                        {row.original.email}
                    </p>
                </div>
            ),
        },
        {
            accessorKey: 'total_reviews',
            header: 'Total reviews',
            cell: ({ row }) =>
                row.original.total_reviews.toLocaleString(),
        },
        {
            accessorKey: 'approval_rate',
            header: 'Approval rate',
            cell: ({ row }) => `${row.original.approval_rate}%`,
        },
        {
            accessorKey: 'flag_rate',
            header: 'Flag / reject rate',
            cell: ({ row }) => `${row.original.flag_rate}%`,
        },
        ...(canManageUsers
            ? [
                  {
                      id: 'actions',
                      header: '',
                      enableSorting: false,
                      cell: ({ row }: { row: { original: ModeratorRow } }) => (
                          <Form
                              {...userUpdate.form(row.original.user_id)}
                              className="inline"
                          >
                              <input
                                  type="hidden"
                                  name="name"
                                  value={row.original.name}
                              />
                              <input
                                  type="hidden"
                                  name="email"
                                  value={row.original.email}
                              />
                              <input
                                  type="hidden"
                                  name="role"
                                  value="player"
                              />
                              <Button
                                  type="submit"
                                  variant="outline"
                                  size="sm"
                                  className="text-orange-600"
                              >
                                  <UserMinus className="mr-1 size-4" />
                                  Revoke
                              </Button>
                          </Form>
                      ),
                  } as ColumnDef<ModeratorRow, unknown>,
              ]
            : []),
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Moderator performance" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Moderator performance
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Review counts, approval rate, and flag/reject rate per
                        moderator. Filter by date range.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Date range filter
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
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
                    data={moderators}
                />
            </div>
        </AppLayout>
    );
}

