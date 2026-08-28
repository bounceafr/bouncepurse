import { Transition } from '@headlessui/react';
import { Form, Head, router } from '@inertiajs/react';
import { Trash2, UserPlus } from 'lucide-react';
import {
    show as teamShow,
    update as teamUpdate,
} from '@/actions/App/Http/Controllers/Team/TeamController';
import {
    store as invitationStore,
    destroy as invitationDestroy,
} from '@/actions/App/Http/Controllers/Team/TeamInvitationController';
import { destroy as memberDestroy } from '@/actions/App/Http/Controllers/Team/TeamMemberController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Team = {
    id: number;
    uuid: string;
    name: string;
    website: string | null;
    city: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    status: string;
    user_id: number;
    country_id: number | null;
};

type Member = {
    id: number;
    uuid: string;
    name: string;
    email: string;
    pivot: {
        joined_at: string;
    };
};

type Invitation = {
    id: number;
    uuid: string;
    email: string;
    status: string;
    created_at: string;
    invited_by: {
        id: number;
        name: string;
    };
};

type Country = {
    id: number;
    name: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'My Team',
        href: teamShow().url,
    },
];

export default function Show({
    team,
    members,
    invitations,
    countries,
    isOwner,
}: {
    team: Team | null;
    members: Member[] | null;
    invitations: Invitation[] | null;
    countries: Country[];
    isOwner: boolean;
}) {
    if (!team) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="My Team" />

                <div className="flex flex-col gap-6 p-6">
                    <Heading
                        title="My Team"
                        description="Manage your team details, members, and invitations."
                    />

                    <Card>
                        <CardHeader>
                            <CardTitle>No Team</CardTitle>
                            <CardDescription>
                                You are not currently part of a team. Join an
                                existing team through an invitation to get
                                started.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Team" />

            <div className="flex flex-col gap-6 p-6">
                <Heading
                    title="My Team"
                    description="Manage your team details, members, and invitations."
                />

                <div
                    className={`grid gap-6 ${isOwner ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1'}`}
                >
                    {isOwner && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Team Details</CardTitle>
                                <CardDescription>
                                    Update your team information.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    {...teamUpdate.form()}
                                    options={{ preserveScroll: true }}
                                    className="space-y-4"
                                >
                                    {({
                                        processing,
                                        recentlySuccessful,
                                        errors,
                                    }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="name">
                                                    Team Name
                                                </Label>
                                                <Input
                                                    id="name"
                                                    name="name"
                                                    defaultValue={team.name}
                                                    required
                                                />
                                                <InputError
                                                    message={errors.name}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="email">
                                                    Email
                                                </Label>
                                                <Input
                                                    id="email"
                                                    name="email"
                                                    type="email"
                                                    defaultValue={
                                                        team.email ?? ''
                                                    }
                                                />
                                                <InputError
                                                    message={errors.email}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="phone">
                                                    Phone
                                                </Label>
                                                <Input
                                                    id="phone"
                                                    name="phone"
                                                    defaultValue={
                                                        team.phone ?? ''
                                                    }
                                                />
                                                <InputError
                                                    message={errors.phone}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="website">
                                                    Website
                                                </Label>
                                                <Input
                                                    id="website"
                                                    name="website"
                                                    defaultValue={
                                                        team.website ?? ''
                                                    }
                                                    placeholder="https://"
                                                />
                                                <InputError
                                                    message={errors.website}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="city">
                                                    City
                                                </Label>
                                                <Input
                                                    id="city"
                                                    name="city"
                                                    defaultValue={
                                                        team.city ?? ''
                                                    }
                                                />
                                                <InputError
                                                    message={errors.city}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="address">
                                                    Address
                                                </Label>
                                                <Input
                                                    id="address"
                                                    name="address"
                                                    defaultValue={
                                                        team.address ?? ''
                                                    }
                                                />
                                                <InputError
                                                    message={errors.address}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="country_id">
                                                    Country
                                                </Label>
                                                <Select
                                                    name="country_id"
                                                    defaultValue={
                                                        team.country_id?.toString() ??
                                                        undefined
                                                    }
                                                >
                                                    <SelectTrigger id="country_id">
                                                        <SelectValue placeholder="Select a country" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {countries.map(
                                                            (country) => (
                                                                <SelectItem
                                                                    key={
                                                                        country.id
                                                                    }
                                                                    value={country.id.toString()}
                                                                >
                                                                    {
                                                                        country.name
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    message={errors.country_id}
                                                />
                                            </div>

                                            <div className="flex items-center gap-4">
                                                <Button disabled={processing}>
                                                    Save
                                                </Button>
                                                <Transition
                                                    show={recentlySuccessful}
                                                    enter="transition ease-in-out"
                                                    enterFrom="opacity-0"
                                                    leave="transition ease-in-out"
                                                    leaveTo="opacity-0"
                                                >
                                                    <p className="text-sm text-neutral-600">
                                                        Saved
                                                    </p>
                                                </Transition>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Members</CardTitle>
                                    <CardDescription>
                                        Team members and their roles.
                                    </CardDescription>
                                </div>
                                <Badge variant="secondary">
                                    {(members ?? []).length}/10
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Joined</TableHead>
                                        {isOwner && (
                                            <TableHead className="w-[80px]" />
                                        )}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {(members ?? []).map((member) => (
                                        <TableRow key={member.id}>
                                            <TableCell className="font-medium">
                                                {member.name}
                                                {member.id === team.user_id && (
                                                    <Badge
                                                        variant="outline"
                                                        className="ml-2"
                                                    >
                                                        Owner
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {member.email}
                                            </TableCell>
                                            <TableCell>
                                                {new Date(
                                                    member.pivot.joined_at,
                                                ).toLocaleDateString()}
                                            </TableCell>
                                            {isOwner && (
                                                <TableCell>
                                                    {member.id !==
                                                        team.user_id && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                router.delete(
                                                                    memberDestroy(
                                                                        member,
                                                                    ).url,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="size-4 text-destructive" />
                                                        </Button>
                                                    )}
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                {isOwner && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Invitations</CardTitle>
                            <CardDescription>
                                Invite new members to your team.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <Form
                                {...invitationStore.form()}
                                options={{ preserveScroll: true }}
                                className="flex max-w-lg items-end gap-3"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid flex-1 gap-2">
                                            <Label htmlFor="invite-email">
                                                Email address
                                            </Label>
                                            <Input
                                                id="invite-email"
                                                name="email"
                                                type="email"
                                                placeholder="teammate@example.com"
                                                required
                                            />
                                            <InputError
                                                message={errors.email}
                                            />
                                        </div>
                                        <Button disabled={processing}>
                                            <UserPlus className="mr-2 size-4" />
                                            Invite
                                        </Button>
                                    </>
                                )}
                            </Form>

                            {(invitations ?? []).length > 0 && (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Email</TableHead>
                                            <TableHead>Invited By</TableHead>
                                            <TableHead>Sent</TableHead>
                                            <TableHead className="w-[80px]" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {(invitations ?? []).map((invitation) => (
                                            <TableRow key={invitation.id}>
                                                <TableCell>
                                                    {invitation.email}
                                                </TableCell>
                                                <TableCell>
                                                    {invitation.invited_by.name}
                                                </TableCell>
                                                <TableCell>
                                                    {new Date(
                                                        invitation.created_at,
                                                    ).toLocaleDateString()}
                                                </TableCell>
                                                <TableCell>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            router.delete(
                                                                invitationDestroy(
                                                                    invitation,
                                                                ).url,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-4 text-destructive" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
