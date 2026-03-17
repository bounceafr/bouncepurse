import { Form, Head } from '@inertiajs/react';
import CourtController, {
    index,
} from '@/actions/App/Http/Controllers/Admin/CourtController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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

type Court = {
    id: number;
    uuid: string;
    name: string;
    country_id: number;
    city: string;
    host_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    latitude: number | null;
    longitude: number | null;
    status: string;
};

type StatusOption = {
    value: string;
    label: string;
    color: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Courts',
        href: index().url,
    },
    {
        title: 'Edit Court',
        href: '#',
    },
];

export default function EditCourt({
    court,
    countries,
    statuses,
}: {
    court: Court;
    countries: CountryOption[];
    statuses: StatusOption[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Court" />

            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Edit Court</h1>
                    <p className="text-sm text-muted-foreground">
                        Update the court details.
                    </p>
                </div>

                <Form
                    {...CourtController.update.patch(court)}
                    className="max-w-lg space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={court.name}
                                    placeholder="Court name"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="country_id">Country</Label>
                                <Select
                                    name="country_id"
                                    defaultValue={String(court.country_id)}
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
                                    defaultValue={court.city}
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
                                    defaultValue={court.host_name ?? ''}
                                    placeholder="Owner or manager name"
                                />
                                <InputError message={errors.host_name} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="contact_email">
                                        Contact Email
                                    </Label>
                                    <Input
                                        id="contact_email"
                                        name="contact_email"
                                        type="email"
                                        defaultValue={court.contact_email ?? ''}
                                        placeholder="email@example.com"
                                    />
                                    <InputError
                                        message={errors.contact_email}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="contact_phone">
                                        Contact Phone
                                    </Label>
                                    <Input
                                        id="contact_phone"
                                        name="contact_phone"
                                        type="tel"
                                        defaultValue={court.contact_phone ?? ''}
                                        placeholder="+1 234 567 890"
                                    />
                                    <InputError
                                        message={errors.contact_phone}
                                    />
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
                                        defaultValue={court.latitude ?? ''}
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
                                        defaultValue={court.longitude ?? ''}
                                        placeholder="e.g. -0.1278"
                                    />
                                    <InputError message={errors.longitude} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Select
                                    name="status"
                                    defaultValue={court.status}
                                    required
                                >
                                    <SelectTrigger id="status">
                                        <SelectValue placeholder="Select a status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((status) => (
                                            <SelectItem
                                                key={status.value}
                                                value={status.value}
                                            >
                                                {status.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => window.history.back()}
                                >
                                    Cancel
                                </Button>
                                <Button disabled={processing} asChild>
                                    <button type="submit">Update Court</button>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
