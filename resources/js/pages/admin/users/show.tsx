import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, UserCheck, UserX } from 'lucide-react';
import { useState } from 'react';
import { index, show, update } from '@/actions/App/Http/Controllers/Admin/UserController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type RoleOption = { value: string; label: string; color: string };

type UserRole = { id: number; name: string };

type UserProfile = {
    date_of_birth: string | null;
    city: string | null;
    bio: string | null;
    position: string | null;
    country: string | null;
};

type RecentGame = {
    id: number;
    uuid: string;
    title: string;
    status: string;
    played_at: string;
    created_at: string;
};

type RecentReview = {
    id: number;
    game_title: string | null;
    game_uuid: string | null;
    status: string;
    created_at: string;
};

type UserData = {
    id: number;
    uuid: string;
    name: string;
    email: string;
    created_at: string;
    deactivated_at: string | null;
    deactivation_reason: string | null;
    deactivated_by: string | null;
    roles: UserRole[];
    profile: UserProfile | null;
    recent_games: RecentGame[];
    recent_moderation_reviews: RecentReview[];
};

type Props = {
    user: UserData;
    roles: RoleOption[];
};

function roleBadge(roleName: string | undefined, roles: RoleOption[]) {
    if (!roleName) {
        return <span className="text-xs text-muted-foreground">No role</span>;
    }
    const option = roles.find((r) => r.value === roleName);
    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white ${option?.color ?? 'bg-gray-400'}`}
        >
            {option?.label ?? roleName}
        </span>
    );
}

export default function UserShow({ user, roles }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Users', href: index().url },
        { title: user.name, href: show(user.id).url },
    ];

    const isDeactivated = user.deactivated_at != null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`User: ${user.name}`} />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={index().url}>
                                <ArrowLeft className="size-4" />
                                <span className="sr-only">Back to users</span>
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-semibold">
                                {user.name}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {user.email}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {roleBadge(user.roles[0]?.name, roles)}
                        {isDeactivated && (
                            <span className="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                Deactivated
                            </span>
                        )}
                    </div>
                </div>

                {isDeactivated && (
                    <Card className="border-red-200 dark:border-red-900/50">
                        <CardHeader>
                            <CardTitle className="text-base">
                                Deactivation details
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            <p>
                                <span className="font-medium">Date:</span>{' '}
                                {new Date(
                                    user.deactivated_at!,
                                ).toLocaleString()}
                            </p>
                            {user.deactivated_by && (
                                <p>
                                    <span className="font-medium">
                                        By:
                                    </span>{' '}
                                    {user.deactivated_by}
                                </p>
                            )}
                            {user.deactivation_reason && (
                                <p>
                                    <span className="font-medium">
                                        Reason:
                                    </span>{' '}
                                    {user.deactivation_reason}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Profile</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Joined:</span>{' '}
                            {new Date(user.created_at).toLocaleDateString()}
                        </p>
                        {user.profile && (
                            <>
                                {user.profile.country && (
                                    <p>
                                        <span className="font-medium">
                                            Country:
                                        </span>{' '}
                                        {user.profile.country}
                                    </p>
                                )}
                                {user.profile.city && (
                                    <p>
                                        <span className="font-medium">
                                            City:
                                        </span>{' '}
                                        {user.profile.city}
                                    </p>
                                )}
                                {user.profile.position && (
                                    <p>
                                        <span className="font-medium">
                                            Position:
                                        </span>{' '}
                                        {user.profile.position}
                                    </p>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Games submitted ({user.recent_games.length}
                            {user.recent_games.length >= 20 ? '+' : ''})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {user.recent_games.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No games submitted.
                            </p>
                        ) : (
                            <ul className="space-y-1 text-sm">
                                {user.recent_games.map((game) => (
                                    <li
                                        key={game.id}
                                        className="flex justify-between"
                                    >
                                        <span className="font-medium">
                                            {game.title}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {game.status} ·{' '}
                                            {new Date(
                                                game.played_at,
                                            ).toLocaleDateString()}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                {user.recent_moderation_reviews.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Moderation activity (
                                {user.recent_moderation_reviews.length}
                                {user.recent_moderation_reviews.length >= 20
                                    ? '+'
                                    : ''}
                                )
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="space-y-1 text-sm">
                                {user.recent_moderation_reviews.map(
                                    (review) => (
                                        <li
                                            key={review.id}
                                            className="flex justify-between"
                                        >
                                            <span className="font-medium">
                                                {review.game_title ?? '—'}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {review.status} ·{' '}
                                                {new Date(
                                                    review.created_at,
                                                ).toLocaleDateString()}
                                            </span>
                                        </li>
                                    ),
                                )}
                            </ul>
                        </CardContent>
                    </Card>
                )}

                <div className="flex gap-2">
                    <Form {...update.form(user.id)}>
                        <input
                            type="hidden"
                            name="name"
                            value={user.name}
                        />
                        <input
                            type="hidden"
                            name="email"
                            value={user.email}
                        />
                        <input
                            type="hidden"
                            name="role"
                            value={user.roles[0]?.name ?? 'player'}
                        />
                        <Button type="submit" variant="outline" size="sm">
                            Edit user
                        </Button>
                    </Form>
                    {isDeactivated ? (
                        <Form
                            action={`/admin/users/${user.id}/reactivate`}
                            method="patch"
                        >
                            <Button
                                type="submit"
                                variant="outline"
                                size="sm"
                                className="text-green-600"
                            >
                                <UserCheck className="mr-1 size-4" />
                                Reactivate
                            </Button>
                        </Form>
                    ) : (
                        <DeactivateButton user={user} />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

function DeactivateButton({ user }: { user: UserData }) {
    const [open, setOpen] = useState(false);
    const [reason, setReason] = useState('');
    return (
        <>
            <Button
                type="button"
                variant="outline"
                size="sm"
                className="text-red-600"
                onClick={() => setOpen(true)}
            >
                <UserX className="mr-1 size-4" />
                Deactivate
            </Button>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Deactivate user</DialogTitle>
                        <DialogDescription>
                            Deactivating will prevent this user from logging in.
                            Provide a reason (required).
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        action={`/admin/users/${user.id}/deactivate`}
                        method="patch"
                        onSubmit={() => setOpen(false)}
                        className="space-y-4"
                    >
                        <input type="hidden" name="_method" value="PATCH" />
                        <div className="grid gap-2">
                            <Label htmlFor="reason">Reason</Label>
                            <Input
                                id="reason"
                                name="reason"
                                value={reason}
                                onChange={(e) => setReason(e.target.value)}
                                placeholder="Reason for deactivation"
                                required
                            />
                        </div>
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button type="submit" variant="destructive">
                                Deactivate
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </>
    );
}

