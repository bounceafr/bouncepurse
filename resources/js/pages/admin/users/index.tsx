import { Form, Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import {
    Key,
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
    UserCircle,
    UserMinus,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import UserController, {
    deactivate,
    index,
    resetPassword,
    show,
} from '@/actions/App/Http/Controllers/Admin/UserController';
import InputError from '@/components/input-error';
import { ListPageShell } from '@/components/list-page-shell';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DataTable,
    LaravelPagination,
    selectionColumn,
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
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
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
    updated_at: string;
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

type UserCounts = {
    roles: Record<string, number>;
    removed: number;
    active: number;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: index().url }];

const userActionsMenuClassName =
    '!bg-background text-foreground shadow-lg ring-0 before:hidden border border-border';

const userActionsMenuItemClassName =
    'text-foreground [&_svg]:text-foreground focus:bg-primary/10 focus:text-primary focus:[&_svg]:text-primary data-highlighted:bg-primary/10 data-highlighted:text-primary data-highlighted:[&_svg]:text-primary focus:**:text-primary! data-highlighted:**:text-primary!';

function formatDateTime(value: string): string {
    const date = new Date(value);
    return date.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
}

function formatDate(value: string): string {
    const date = new Date(value);
    return date.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
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

function InlineRoleSelect({
    user,
    roles,
}: {
    user: User;
    roles: RoleOption[];
}) {
    const currentRole = user.roles[0]?.name ?? '';

    function handleRoleChange(newRole: string) {
        if (newRole === currentRole) {
            return;
        }

        router.patch(
            UserController.update.url(user.id),
            {
                name: user.name,
                email: user.email,
                role: newRole,
            },
            { preserveScroll: true },
        );
    }

    return (
        <Select value={currentRole} onValueChange={handleRoleChange}>
            <SelectTrigger className="h-8 w-[160px] border-border bg-background shadow-none">
                <UserCircle className="mr-1 size-3.5 text-muted-foreground" />
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {roles.map((role) => (
                    <SelectItem key={role.value} value={role.value}>
                        {role.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export default function UsersIndex({
    users,
    roles,
    counts,
    filters,
}: {
    users: PaginatedUsers;
    roles: RoleOption[];
    counts: UserCounts;
    filters: {
        search: string | null;
        role: string | null;
        status: string | null;
    };
}) {
    const getInitials = useInitials();
    const [createOpen, setCreateOpen] = useState(false);
    const [editUser, setEditUser] = useState<User | null>(null);
    const [deleteUser, setDeleteUser] = useState<User | null>(null);
    const [deactivateUser, setDeactivateUser] = useState<User | null>(null);
    const [resetUser, setResetUser] = useState<User | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const isInitialRender = useRef(true);

    const activeTab =
        filters.status === 'removed'
            ? 'removed'
            : (filters.role ?? 'all');

    useEffect(() => {
        if (isInitialRender.current) {
            isInitialRender.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                index().url,
                {
                    search: search || undefined,
                    role: filters.role || undefined,
                    status: filters.status || undefined,
                },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, filters.role, filters.status]);

    function handleTabChange(tab: string) {
        router.get(
            index().url,
            {
                search: search || undefined,
                role:
                    tab !== 'all' && tab !== 'removed' ? tab : undefined,
                status: tab === 'removed' ? 'removed' : undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    const columns: ColumnDef<User, unknown>[] = [
        selectionColumn<User>(),
        {
            id: 'index',
            header: () => (
                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    #
                </span>
            ),
            enableSorting: false,
            cell: ({ row, table }) => {
                const pageIndex = table.getState().pagination.pageIndex;
                const pageSize = table.getState().pagination.pageSize;
                return (
                    <span className="text-muted-foreground">
                        {pageIndex * pageSize + row.index + 1}
                    </span>
                );
            },
        },
        {
            id: 'name',
            header: () => (
                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Name
                </span>
            ),
            enableSorting: false,
            cell: ({ row }) => {
                const user = row.original;
                return (
                    <div className="flex items-center gap-3 py-1">
                        <Avatar className="size-10">
                            <AvatarFallback className="bg-muted text-xs font-medium text-muted-foreground">
                                {getInitials(user.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0 flex-1">
                            <Link
                                href={show(user.id).url}
                                className="font-medium text-foreground hover:underline"
                            >
                                {user.name}
                            </Link>
                            <p className="truncate text-xs text-muted-foreground">
                                {user.email}
                            </p>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="size-8 shrink-0 text-muted-foreground"
                                >
                                    <MoreHorizontal className="size-4" />
                                    <span className="sr-only">Actions</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="end"
                                className={userActionsMenuClassName}
                            >
                                <DropdownMenuItem
                                    className={userActionsMenuItemClassName}
                                    onClick={() => setEditUser(user)}
                                >
                                    <Pencil className="size-4" />
                                    Edit
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    className={userActionsMenuItemClassName}
                                    onClick={() => setResetUser(user)}
                                >
                                    <Key className="size-4" />
                                    Reset password
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                {user.deactivated_at ? (
                                    <DropdownMenuItem
                                        asChild
                                        className={userActionsMenuItemClassName}
                                    >
                                        <Link href={show(user.id).url}>
                                            View to reactivate
                                        </Link>
                                    </DropdownMenuItem>
                                ) : (
                                    <DropdownMenuItem
                                        className={userActionsMenuItemClassName}
                                        onClick={() => setDeactivateUser(user)}
                                    >
                                        <UserMinus className="size-4" />
                                        Suspend
                                    </DropdownMenuItem>
                                )}
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    className={userActionsMenuItemClassName}
                                    onClick={() => setDeleteUser(user)}
                                >
                                    <UserMinus className="size-4" />
                                    Remove
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                );
            },
        },
        {
            id: 'role',
            header: () => (
                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Role
                </span>
            ),
            enableSorting: false,
            cell: ({ row }) => (
                <InlineRoleSelect user={row.original} roles={roles} />
            ),
        },
        {
            id: 'status',
            header: () => (
                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Status
                </span>
            ),
            enableSorting: false,
            cell: ({ row }) =>
                row.original.deactivated_at ? (
                    <span className="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-600/20">
                        Suspended
                    </span>
                ) : (
                    <span className="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/20">
                        Active
                    </span>
                ),
        },
        {
            accessorKey: 'updated_at',
            header: () => (
                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Last Activity
                </span>
            ),
            enableSorting: false,
            cell: ({ row }) => (
                <span className="text-sm text-muted-foreground">
                    {formatDateTime(row.getValue('updated_at'))}
                </span>
            ),
        },
        {
            accessorKey: 'created_at',
            header: () => (
                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Joined
                </span>
            ),
            enableSorting: false,
            cell: ({ row }) => (
                <span className="text-sm text-muted-foreground">
                    {formatDate(row.getValue('created_at'))}
                </span>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />

            <ListPageShell>
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">
                            Users
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Active users: {counts.active}
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        className="border-border bg-background shadow-none"
                        onClick={() => setCreateOpen(true)}
                    >
                        <Plus className="size-4" />
                        Create user
                    </Button>
                </div>

                <Tabs value={activeTab} onValueChange={handleTabChange}>
                    <TabsList
                        variant="line"
                        className="h-auto w-full justify-start gap-0 rounded-none border-b border-border bg-transparent p-0"
                    >
                        <TabsTrigger
                            value="all"
                            className={cn(
                                'rounded-none px-4 py-2.5 text-muted-foreground data-active:text-foreground data-active:after:bg-primary',
                            )}
                        >
                            All ({counts.active})
                        </TabsTrigger>
                        {roles.map((role) => (
                            <TabsTrigger
                                key={role.value}
                                value={role.value}
                                className="rounded-none px-4 py-2.5 text-muted-foreground data-active:text-foreground data-active:after:bg-primary"
                            >
                                {role.label} (
                                {String(
                                    counts.roles[role.value] ?? 0,
                                ).padStart(2, '0')}
                                )
                            </TabsTrigger>
                        ))}
                        <TabsTrigger
                            value="removed"
                            className="rounded-none px-4 py-2.5 text-muted-foreground data-active:text-foreground data-active:after:bg-primary"
                        >
                            Removed (
                            {String(counts.removed).padStart(2, '0')})
                        </TabsTrigger>
                    </TabsList>
                </Tabs>

                <div className="flex items-center justify-between gap-4">
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Search by name or email"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="border-border bg-background pl-9 shadow-none"
                        />
                    </div>
                    <span className="shrink-0 text-sm text-muted-foreground">
                        {users.total}{' '}
                        {activeTab === 'removed' ? 'removed' : 'active'}
                    </span>
                </div>

                <DataTable
                    columns={columns}
                    data={users.data}
                    hideColumnToggle
                    pagination={
                        users.last_page > 1 ? (
                            <LaravelPagination links={users.links} />
                        ) : undefined
                    }
                />
            </ListPageShell>

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
                        <DialogTitle>Suspend User</DialogTitle>
                        <DialogDescription>
                            Suspending will prevent{' '}
                            <span className="font-medium">
                                {deactivateUser?.name}
                            </span>{' '}
                            from logging in. Provide a reason (required).
                        </DialogDescription>
                    </DialogHeader>

                    {deactivateUser && (
                        <Form
                            {...deactivate.form(deactivateUser.id)}
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
                                            placeholder="Reason for suspension"
                                            required
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
                                                Suspend
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog
                open={resetUser !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setResetUser(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reset Password</DialogTitle>
                        <DialogDescription>
                            Set a new password for{' '}
                            <span className="font-medium">
                                {resetUser?.name}
                            </span>
                            .
                        </DialogDescription>
                    </DialogHeader>

                    {resetUser && (
                        <Form
                            {...resetPassword.form(resetUser.id)}
                            onSuccess={() => setResetUser(null)}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="reset-password">
                                            New Password
                                        </Label>
                                        <Input
                                            id="reset-password"
                                            name="password"
                                            type="password"
                                            placeholder="Min 8 characters"
                                            required
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="reset-password-confirm">
                                            Confirm Password
                                        </Label>
                                        <Input
                                            id="reset-password-confirm"
                                            name="password_confirmation"
                                            type="password"
                                            placeholder="Repeat password"
                                            required
                                        />
                                    </div>

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary">
                                                Cancel
                                            </Button>
                                        </DialogClose>
                                        <Button disabled={processing} asChild>
                                            <button type="submit">
                                                Reset Password
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>

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
                        <DialogTitle>Remove User</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to remove{' '}
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
                                                Remove
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
