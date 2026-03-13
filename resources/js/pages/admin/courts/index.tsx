import { Form, Head, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import CourtController, {
    index,
} from '@/actions/App/Http/Controllers/Admin/CourtController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    DataTable,
    LaravelPagination,
    selectionColumn,
    sortableHeader,
} from '@/components/ui/data-table';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
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
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type CountryOption = {
    id: number;
    name: string;
    iso_alpha2: string;
};

type StatusOption = {
    value: string;
    label: string;
    color: string;
};

type Court = {
    id: number;
    uuid: string;
    court_code: string;
    name: string;
    country_id: number;
    country: CountryOption;
    city: string;
    host_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    latitude: number | null;
    longitude: number | null;
    status: string;
    created_at: string;
    updated_at: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedCourts = {
    data: Court[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Courts',
        href: index().url,
    },
];

function CourtFormFields({
    court,
    countries,
    statuses,
    errors,
}: {
    court?: Court;
    countries: CountryOption[];
    statuses: StatusOption[];
    errors: Record<string, string>;
}) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    defaultValue={court?.name}
                    placeholder="Court name"
                    required
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="country_id">Country</Label>
                <Select
                    name="country_id"
                    defaultValue={
                        court?.country_id
                            ? String(court.country_id)
                            : undefined
                    }
                    required
                >
                    <SelectTrigger id="country_id">
                        <SelectValue placeholder="Select a country" />
                    </SelectTrigger>
                    <SelectContent>
                        {countries.map((country) => (
                            <SelectItem
                                key={country.id}
                                value={String(country.id)}
                            >
                                {country.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.country_id} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="city">City</Label>
                <Input
                    id="city"
                    name="city"
                    defaultValue={court?.city}
                    placeholder="City"
                    required
                />
                <InputError message={errors.city} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="host_name">Host Name</Label>
                <Input
                    id="host_name"
                    name="host_name"
                    defaultValue={court?.host_name ?? ''}
                    placeholder="Owner or manager name"
                />
                <InputError message={errors.host_name} />
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="contact_email">Contact Email</Label>
                    <Input
                        id="contact_email"
                        name="contact_email"
                        type="email"
                        defaultValue={court?.contact_email ?? ''}
                        placeholder="email@example.com"
                    />
                    <InputError message={errors.contact_email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="contact_phone">Contact Phone</Label>
                    <Input
                        id="contact_phone"
                        name="contact_phone"
                        type="tel"
                        defaultValue={court?.contact_phone ?? ''}
                        placeholder="+1 234 567 890"
                    />
                    <InputError message={errors.contact_phone} />
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="latitude">Latitude</Label>
                    <Input
                        id="latitude"
                        name="latitude"
                        type="number"
                        step="any"
                        defaultValue={court?.latitude ?? ''}
                        placeholder="e.g. 51.5074"
                    />
                    <InputError message={errors.latitude} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="longitude">Longitude</Label>
                    <Input
                        id="longitude"
                        name="longitude"
                        type="number"
                        step="any"
                        defaultValue={court?.longitude ?? ''}
                        placeholder="e.g. -0.1278"
                    />
                    <InputError message={errors.longitude} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="status">Status</Label>
                <Select name="status" defaultValue={court?.status} required>
                    <SelectTrigger id="status">
                        <SelectValue placeholder="Select a status" />
                    </SelectTrigger>
                    <SelectContent>
                        {statuses.map((status) => (
                            <SelectItem key={status.value} value={status.value}>
                                {status.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.status} />
            </div>
        </>
    );
}

export default function CourtsIndex({
    courts,
    countries,
    statuses,
    filters,
}: {
    courts: PaginatedCourts;
    countries: CountryOption[];
    statuses: StatusOption[];
    filters: { search: string | null };
}) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editCourt, setEditCourt] = useState<Court | null>(null);
    const [deleteCourt, setDeleteCourt] = useState<Court | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const isInitialMount = useRef(true);

    const statusMap = Object.fromEntries(statuses.map((s) => [s.value, s]));

    useEffect(() => {
        if (isInitialMount.current) {
            isInitialMount.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                index().url,
                { search: search || undefined },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search]);

    const columns: ColumnDef<Court, unknown>[] = [
        selectionColumn<Court>(),
        {
            accessorKey: 'court_code',
            header: sortableHeader('Code'),
            cell: ({ row }) => (
                <span className="font-mono text-sm">
                    {row.getValue('court_code')}
                </span>
            ),
        },
        {
            accessorKey: 'name',
            header: sortableHeader('Name'),
            cell: ({ row }) => (
                <span className="font-medium">{row.getValue('name')}</span>
            ),
        },
        {
            id: 'country',
            header: sortableHeader('Country'),
            cell: ({ row }) => row.original.country?.name ?? '—',
        },
        {
            accessorKey: 'city',
            header: sortableHeader('City'),
        },
        {
            accessorKey: 'status',
            header: sortableHeader('Status'),
            cell: ({ row }) => {
                const status = statusMap[row.getValue('status') as string];
                return status ? (
                    <span
                        className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white ${status.color}`}
                    >
                        {status.label}
                    </span>
                ) : null;
            },
        },
        {
            accessorKey: 'host_name',
            header: sortableHeader('Host'),
            cell: ({ row }) => row.getValue('host_name') || '—',
        },
        {
            id: 'actions',
            enableHiding: false,
            enableSorting: false,
            cell: ({ row }) => {
                const court = row.original;
                return (
                    <div className="text-right">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon">
                                    <MoreHorizontal className="size-4" />
                                    <span className="sr-only">Actions</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                    onClick={() => setEditCourt(court)}
                                >
                                    Edit
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    variant="destructive"
                                    onClick={() => setDeleteCourt(court)}
                                >
                                    Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                );
            },
        },
    ];

    const toolbar = (
        <>
            <Input
                placeholder="Search courts..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-64"
            />
            <Button onClick={() => setCreateOpen(true)}>Add Court</Button>
        </>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Courts" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Courts</h1>
                    <p className="text-sm text-muted-foreground">
                        Manage all courts ({courts.total} total)
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    data={courts.data}
                    toolbar={toolbar}
                    pagination={
                        courts.last_page > 1 ? (
                            <LaravelPagination links={courts.links} />
                        ) : undefined
                    }
                />
            </div>

            {/* Create Court Modal */}
            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Create Court</DialogTitle>
                        <DialogDescription>
                            Add a new court to the system.
                        </DialogDescription>
                    </DialogHeader>

                    <Form
                        {...CourtController.store.form()}
                        key={createOpen ? 'open' : 'closed'}
                        resetOnSuccess
                        onSuccess={() => setCreateOpen(false)}
                        className="space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <CourtFormFields
                                    countries={countries}
                                    statuses={statuses}
                                    errors={errors}
                                />

                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button variant="secondary">
                                            Cancel
                                        </Button>
                                    </DialogClose>

                                    <Button disabled={processing} asChild>
                                        <button type="submit">
                                            Create Court
                                        </button>
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>

            {/* Edit Court Modal */}
            <Dialog
                open={editCourt !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditCourt(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Court</DialogTitle>
                        <DialogDescription>
                            Update court details.
                        </DialogDescription>
                    </DialogHeader>

                    {editCourt && (
                        <Form
                            {...CourtController.update.form(editCourt.uuid)}
                            key={editCourt.id}
                            onSuccess={() => setEditCourt(null)}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <CourtFormFields
                                        court={editCourt}
                                        countries={countries}
                                        statuses={statuses}
                                        errors={errors}
                                    />

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary">
                                                Cancel
                                            </Button>
                                        </DialogClose>

                                        <Button disabled={processing} asChild>
                                            <button type="submit">
                                                Update Court
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>

            {/* Delete Court Modal */}
            <Dialog
                open={deleteCourt !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleteCourt(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Court</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete{' '}
                            <span className="font-medium">
                                {deleteCourt?.name}
                            </span>
                            ? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>

                    {deleteCourt && (
                        <Form
                            {...CourtController.destroy.form(deleteCourt.uuid)}
                            onSuccess={() => setDeleteCourt(null)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <InputError message={errors.court} />

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary">
                                                Cancel
                                            </Button>
                                        </DialogClose>

                                        <Button
                                            variant="destructive"
                                            disabled={processing}
                                            asChild
                                        >
                                            <button type="submit">
                                                Delete
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
