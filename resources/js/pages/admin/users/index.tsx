import { Form, Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';
import { useEffect, useState } from 'react';
import UserController, {
    index,
    show,
} from '@/actions/App/Http/Controllers/Admin/UserController';
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

type RoleOption = {
    value: string;
    label: string;
    color: string;
};

type UserRole = {
    id: number;
    name: string;
};

type User = {
    id: number;
    name: string;
    email: string;
    created_at: string;
    deactivated_at: string | null;
    roles: UserRole[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedUsers = {
    data: User[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: index().url }];

function roleBadge(role: string | undefined, roles: RoleOption[]) {
    if (!role) {
        return <span className="text-xs text-muted-foreground">No role</span>;
    }
    const option = roles.find((r) => r.value === role);
    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white ${option?.color ?? 'bg-gray-400'}`}
        >
            {option?.label ?? role}
        </span>
    );
}

function UserFormFields({
    user,
    roles,
    errors,
    includePassword,
}: {
    user?: User;
    roles: RoleOption[];
    errors: Record<string, string>;
    includePassword?: boolean;
}) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    defaultValue={user?.name}
                    placeholder="Full name"
                    required
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="email">Email</Label>
                <Input
                    id="email"
                    name="email"
                    type="email"
                    defaultValue={user?.email}
                    placeholder="email@example.com"
                    required
                />
                <InputError message={errors.email} />
            </div>

            {includePassword && (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Min 8 characters"
                            required
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">
                            Confirm Password
                        </Label>
                        <Input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            placeholder="Repeat password"
                            required
                        />
                    </div>
                </>
            )}

            <div className="grid gap-2">
                <Label htmlFor="role">Role</Label>
                <Select
                    name="role"
                    defaultValue={user?.roles[0]?.name ?? ''}
                    required
                >
                    <SelectTrigger id="role">
                        <SelectValue placeholder="Select a role" />
                    </SelectTrigger>
                    <SelectContent>
                        {roles.map((role) => (
                            <SelectItem key={role.value} value={role.value}>
                                {role.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.role} />
            </div>
        </>
    );
}

export default function UsersIndex({
    users,
    roles,
    filters,
}: {
    users: PaginatedUsers;
    roles: RoleOption[];
    filters: { search: string | null; role: string | null };
}) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editUser, setEditUser] = useState<User | null>(null);
    const [deleteUser, setDeleteUser] = useState<User | null>(null);
    const [deactivateUser, setDeactivateUser] = useState<User | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const [roleFilter, setRoleFilter] = useState(filters.role ?? '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(index().url, {
                search: search || undefined,
                role: roleFilter || undefined,
            }, { preserveState: true, replace: true });
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, roleFilter]);

    const columns: ColumnDef<User, unknown>[] = [
        selectionColumn<User>(),
        {
            accessorKey: 'name',
            header: sortableHeader('Name'),
            cell: ({ row }) => {
                const user = row.original;
                return (
                    <Link
                        href={show(user.id).url}
                        className="font-medium hover:underline"
                    >
                        {user.name}
                    </Link>
                );
            },
        },
        {
            accessorKey: 'email',
            header: sortableHeader('Email'),
        },
        {
            id: 'role',
            header: 'Role',
            enableSorting: false,
            cell: ({ row }) => (
                <div className="flex items-center gap-1.5">
                    {roleBadge(row.original.roles[0]?.name, roles)}
                    {row.original.deactivated_at && (
                        <span className="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-400">
                            Deactivated
                        </span>
                    )}
                </div>
            ),
        },
        {
            accessorKey: 'created_at',
            header: sortableHeader('Created At'),
            cell: ({ row }) =>
                new Date(row.getValue('created_at')).toLocaleDateString(),
        },
        {
            id: 'actions',
            enableHiding: false,
            enableSorting: false,
            cell: ({ row }) => {
                const user = row.original;
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
                                <DropdownMenuItem asChild>
                                    <Link href={show(user.id).url}>
                                        View
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() => setEditUser(user)}
                                >
                                    Edit
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                {user.deactivated_at ? (
                                    <DropdownMenuItem asChild>
                                        <Link href={show(user.id).url}>
                                            View to reactivate
                                        </Link>
                                    </DropdownMenuItem>
                                ) : (
                                    <DropdownMenuItem
                                        className="text-orange-600"
                                        onClick={() => setDeactivateUser(user)}
                                    >
                                        Deactivate
                                    </DropdownMenuItem>
                                )}
                                <DropdownMenuItem
                                    variant="destructive"
                                    onClick={() => setDeleteUser(user)}
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
                placeholder="Search users..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-64"
            />
            <Select
                value={roleFilter}
                onValueChange={setRoleFilter}
            >
                <SelectTrigger className="w-40">
                    <SelectValue placeholder="All roles" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">All roles</SelectItem>
                    {roles.map((r) => (
                        <SelectItem key={r.value} value={r.value}>
                            {r.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <Button variant="default" onClick={() => setCreateOpen(true)}>
                Add User
            </Button>
        </>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Users</h1>
                    <p className="text-sm text-muted-foreground">
                        Manage all users ({users.total} total)
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    data={users.data}
                    toolbar={toolbar}
                    pagination={
                        users.last_page > 1 ? (
                            <LaravelPagination links={users.links} />
                        ) : undefined
                    }
                />
            </div>

            {/* Create User Modal */}
            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Create User</DialogTitle>
                        <DialogDescription>
                            Add a new user to the system.
                        </DialogDescription>
                    </DialogHeader>

                    <Form
                        {...UserController.store.form()}
                        key={createOpen ? 'open' : 'closed'}
                        resetOnSuccess
                        onSuccess={() => setCreateOpen(false)}
                        className="space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <UserFormFields
                                    roles={roles}
                                    errors={errors}
                                    includePassword
                                />

                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button variant="secondary">
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button disabled={processing} asChild>
                                        <button type="submit">
                                            Create User
                                        </button>
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>

            {/* Edit User Modal */}
            <Dialog
                open={editUser !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditUser(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit User</DialogTitle>
                        <DialogDescription>
                            Update user details.
                        </DialogDescription>
                    </DialogHeader>

                    {editUser && (
                        <Form
                            {...UserController.update.form(editUser.id)}
                            key={editUser.id}
                            onSuccess={() => setEditUser(null)}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <UserFormFields
                                        user={editUser}
                                        roles={roles}
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
                                                Update User
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>

            {/* Deactivate User Modal */}
            <Dialog
                open={deactivateUser !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeactivateUser(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Deactivate User</DialogTitle>
                        <DialogDescription>
                            Deactivating will prevent{' '}
                            <span className="font-medium">
                                {deactivateUser?.name}
                            </span>{' '}
                            from logging in. Provide a reason (required).
                        </DialogDescription>
                    </DialogHeader>

                    {deactivateUser && (
                        <Form
                            action={`/admin/users/${deactivateUser.id}/deactivate`}
                            method="patch"
                            onSuccess={() => setDeactivateUser(null)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2 py-2">
                                        <Label htmlFor="deactivate-reason">
                                            Reason
                                        </Label>
                                        <Input
                                            id="deactivate-reason"
                                            name="reason"
                                            placeholder="Reason for deactivation"
                                            required
                                        />
                                        <input
                                            type="hidden"
                                            name="_method"
                                            value="PATCH"
                                        />
                                        <InputError message={errors.reason} />
                                        <InputError message={errors.user} />
                                    </div>

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
                                                Deactivate
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>

            {/* Delete User Modal */}
            <Dialog
                open={deleteUser !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleteUser(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete User</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete{' '}
                            <span className="font-medium">
                                {deleteUser?.name}
                            </span>
                            ? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>

                    {deleteUser && (
                        <Form
                            {...UserController.destroy.form(deleteUser.id)}
                            onSuccess={() => setDeleteUser(null)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <InputError message={errors.user} />

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
