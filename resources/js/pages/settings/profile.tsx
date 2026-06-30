import { Transition } from '@headlessui/react';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import {
    AlertCircleIcon,
    CalendarIcon,
    CheckCircle2,
    UserCircle,
    XIcon,
} from 'lucide-react';
import * as React from 'react';
import PlayerProfileController from '@/actions/App/Http/Controllers/Settings/PlayerProfileController';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { cn } from '@/lib/utils';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

type Country = {
    id: number;
    name: string;
};

type PlayerProfile = {
    date_of_birth: string | null;
    country_id: number | null;
    city: string | null;
    phone_number: string | null;
    bio: string | null;
    position: string | null;
    profile_image: string | null;
};

type Toast = {
    type: 'success' | 'error';
    message: string;
};

export default function Profile({
    mustVerifyEmail,
    status,
    countries,
    playerProfile,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    countries: Country[];
    playerProfile: PlayerProfile | null;
}) {
    const { auth } = usePage().props;
    const isPlayer = auth.roles.includes('player');

    const initialDate = playerProfile?.date_of_birth
        ? parseISO(playerProfile.date_of_birth)
        : undefined;

    const [dateOfBirth, setDateOfBirth] = React.useState<Date | undefined>(
        initialDate,
    );
    const [dateOpen, setDateOpen] = React.useState(false);
    const [imagePreview, setImagePreview] = React.useState<string | null>(
        playerProfile?.profile_image
            ? `/storage/${playerProfile.profile_image}`
            : null,
    );
    const [showToast, setShowToast] = React.useState(
        status === 'player-profile-updated',
    );

    const toast: Toast | null =
        showToast && status === 'player-profile-updated'
            ? {
                  type: 'success',
                  message: 'Your player profile has been saved successfully.',
              }
            : null;

    React.useEffect(() => {
        if (!showToast) return;
        const timer = setTimeout(() => setShowToast(false), 4000);
        return () => clearTimeout(timer);
    }, [showToast]);

    function handleImageChange(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (file) {
            const reader = new FileReader();
            reader.onloadend = () => setImagePreview(reader.result as string);
            reader.readAsDataURL(file);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile Settings</h1>

            {/* Fixed toast — top-right corner */}
            {toast && (
                <div className="fixed top-4 right-4 z-50 w-full max-w-sm animate-in fade-in slide-in-from-top-2">
                    <Alert
                        className={cn(
                            'shadow-lg',
                            toast.type === 'success'
                                ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-300'
                                : 'border-destructive/50 bg-destructive/10 text-destructive dark:border-destructive dark:text-destructive-foreground',
                        )}
                    >
                        {toast.type === 'success' ? (
                            <CheckCircle2 className="size-4" />
                        ) : (
                            <AlertCircleIcon className="size-4" />
                        )}
                        <AlertTitle>
                            {toast.type === 'success'
                                ? 'Profile updated'
                                : 'Error'}
                        </AlertTitle>
                        <AlertDescription>{toast.message}</AlertDescription>
                        <button
                            onClick={() => setShowToast(false)}
                            className="absolute top-3 right-3 rounded-sm opacity-70 transition-opacity hover:opacity-100 focus:outline-none"
                            aria-label="Dismiss"
                        >
                            <XIcon className="size-4" />
                        </button>
                    </Alert>
                </div>
            )}

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Profile information"
                        description="Update your name and email address"
                    />

                    <Form
                        {...ProfileController.update.form()}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <FieldGroup className="space-y-6">
                                <Field data-invalid={!!errors.name}>
                                    <FieldLabel htmlFor="name">Name</FieldLabel>
                                    <FieldContent>
                                        <Input
                                            id="name"
                                            defaultValue={auth.user.name}
                                            name="name"
                                            required
                                            autoComplete="name"
                                            placeholder="Full name"
                                            aria-invalid={!!errors.name}
                                        />
                                        <FieldError errors={errors.name ? [{ message: errors.name }] : undefined} />
                                    </FieldContent>
                                </Field>

                                <Field data-invalid={!!errors.email}>
                                    <FieldLabel htmlFor="email">Email address</FieldLabel>
                                    <FieldContent>
                                        <Input
                                            id="email"
                                            type="email"
                                            defaultValue={auth.user.email}
                                            name="email"
                                            required
                                            autoComplete="username"
                                            placeholder="Email address"
                                            aria-invalid={!!errors.email}
                                        />
                                        <FieldError errors={errors.email ? [{ message: errors.email }] : undefined} />
                                    </FieldContent>
                                </Field>

                                {mustVerifyEmail &&
                                    auth.user.email_verified_at === null && (
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                Your email address is
                                                unverified.{' '}
                                                <Link
                                                    href={send()}
                                                    as="button"
                                                    className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                                >
                                                    Click here to resend the
                                                    verification email.
                                                </Link>
                                            </p>

                                            {status ===
                                                'verification-link-sent' && (
                                                <div className="mt-2 text-sm font-medium text-green-600">
                                                    A new verification link has
                                                    been sent to your email
                                                    address.
                                                </div>
                                            )}
                                        </div>
                                    )}

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-profile-button"
                                    >
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
                            </FieldGroup>
                        )}
                    </Form>
                </div>

                {isPlayer && (
                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title="Player profile"
                            description="Complete your player profile to access all features"
                        />

                        <Form
                            action={PlayerProfileController.update.url()}
                            method="post"
                            options={{ preserveScroll: true }}
                            onError={() =>
                                setToast({
                                    type: 'error',
                                    message:
                                        'Failed to save player profile. Please fix the errors below.',
                                })
                            }
                        >
                            {({ processing, errors }) => (
                                <FieldGroup className="space-y-6">
                                    {/* Method spoofing for file upload via PATCH */}
                                    <input
                                        type="hidden"
                                        name="_method"
                                        value="patch"
                                    />

                                    {/* Profile image */}
                                    <Field data-invalid={!!errors.profile_image}>
                                        <FieldLabel>Profile image</FieldLabel>
                                        <FieldContent>
                                            <div className="flex items-center gap-4">
                                                <div className="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-muted">
                                                    {imagePreview ? (
                                                        <img
                                                            src={imagePreview}
                                                            alt="Profile"
                                                            className="size-full object-cover"
                                                        />
                                                    ) : (
                                                        <UserCircle className="size-12 text-muted-foreground" />
                                                    )}
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Input
                                                        type="file"
                                                        name="profile_image"
                                                        accept="image/*"
                                                        onChange={handleImageChange}
                                                        className="cursor-pointer"
                                                    />
                                                    <FieldDescription>
                                                        JPG, PNG or WebP. Max 2 MB.
                                                    </FieldDescription>
                                                </div>
                                            </div>
                                            <FieldError errors={errors.profile_image ? [{ message: errors.profile_image }] : undefined} />
                                        </FieldContent>
                                    </Field>

                                    {/* Date of birth */}
                                    <Field data-invalid={!!errors.date_of_birth}>
                                        <FieldLabel>Date of birth</FieldLabel>
                                        <FieldContent>
                                            <input
                                                type="hidden"
                                                name="date_of_birth"
                                                value={
                                                    dateOfBirth
                                                        ? format(
                                                              dateOfBirth,
                                                              'yyyy-MM-dd',
                                                          )
                                                        : ''
                                                }
                                                readOnly
                                            />
                                            <Popover
                                                open={dateOpen}
                                                onOpenChange={setDateOpen}
                                            >
                                                <PopoverTrigger asChild>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        className={cn(
                                                            'w-full justify-start text-left font-normal',
                                                            !dateOfBirth &&
                                                                'text-muted-foreground',
                                                        )}
                                                    >
                                                        <CalendarIcon className="mr-2 size-4" />
                                                        {dateOfBirth
                                                            ? format(
                                                                  dateOfBirth,
                                                                  'PPP',
                                                              )
                                                            : 'Pick a date'}
                                                    </Button>
                                                </PopoverTrigger>
                                                <PopoverContent
                                                    className="w-auto p-0"
                                                    align="start"
                                                >
                                                    <Calendar
                                                        mode="single"
                                                        selected={dateOfBirth}
                                                        onSelect={(date) => {
                                                            setDateOfBirth(date);
                                                            setDateOpen(false);
                                                        }}
                                                        disabled={(date) =>
                                                            date >= new Date() ||
                                                            date <
                                                                new Date(
                                                                    '1900-01-01',
                                                                )
                                                        }
                                                        captionLayout="dropdown"
                                                        fromYear={1930}
                                                        toYear={
                                                            new Date().getFullYear() -
                                                            5
                                                        }
                                                        defaultMonth={
                                                            dateOfBirth ??
                                                            new Date(
                                                                new Date().getFullYear() -
                                                                    25,
                                                                0,
                                                            )
                                                        }
                                                    />
                                                </PopoverContent>
                                            </Popover>
                                            <FieldError errors={errors.date_of_birth ? [{ message: errors.date_of_birth }] : undefined} />
                                        </FieldContent>
                                    </Field>

                                    {/* Country */}
                                    <Field data-invalid={!!errors.country_id}>
                                        <FieldLabel htmlFor="country_id">
                                            Country
                                        </FieldLabel>
                                        <FieldContent>
                                            <Select
                                                name="country_id"
                                                defaultValue={
                                                    playerProfile?.country_id?.toString() ??
                                                    undefined
                                                }
                                            >
                                                <SelectTrigger id="country_id">
                                                    <SelectValue placeholder="Select a country" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {countries.map((country) => (
                                                        <SelectItem
                                                            key={country.id}
                                                            value={country.id.toString()}
                                                        >
                                                            {country.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <FieldError errors={errors.country_id ? [{ message: errors.country_id }] : undefined} />
                                        </FieldContent>
                                    </Field>

                                    {/* City */}
                                    <Field data-invalid={!!errors.city}>
                                        <FieldLabel htmlFor="city">City</FieldLabel>
                                        <FieldContent>
                                            <Input
                                                id="city"
                                                name="city"
                                                defaultValue={
                                                    playerProfile?.city ?? ''
                                                }
                                                required
                                                placeholder="Your city"
                                                aria-invalid={!!errors.city}
                                            />
                                            <FieldError errors={errors.city ? [{ message: errors.city }] : undefined} />
                                        </FieldContent>
                                    </Field>

                                    {/* Phone number */}
                                    <Field data-invalid={!!errors.phone_number}>
                                        <FieldLabel htmlFor="phone_number">
                                            Phone number
                                        </FieldLabel>
                                        <FieldContent>
                                            <Input
                                                id="phone_number"
                                                name="phone_number"
                                                defaultValue={
                                                    playerProfile?.phone_number ??
                                                    ''
                                                }
                                                required
                                                placeholder="+1 234 567 8900"
                                                aria-invalid={!!errors.phone_number}
                                            />
                                            <FieldError errors={errors.phone_number ? [{ message: errors.phone_number }] : undefined} />
                                        </FieldContent>
                                    </Field>

                                    {/* Position */}
                                    <Field data-invalid={!!errors.position}>
                                        <FieldLabel htmlFor="position">
                                            Position
                                        </FieldLabel>
                                        <FieldContent>
                                            <Input
                                                id="position"
                                                name="position"
                                                defaultValue={
                                                    playerProfile?.position ?? ''
                                                }
                                                required
                                                placeholder="e.g. Point Guard"
                                                aria-invalid={!!errors.position}
                                            />
                                            <FieldError errors={errors.position ? [{ message: errors.position }] : undefined} />
                                        </FieldContent>
                                    </Field>

                                    {/* Bio */}
                                    <Field data-invalid={!!errors.bio}>
                                        <FieldLabel htmlFor="bio">Bio</FieldLabel>
                                        <FieldContent>
                                            <Textarea
                                                id="bio"
                                                name="bio"
                                                defaultValue={
                                                    playerProfile?.bio ?? ''
                                                }
                                                required
                                                rows={4}
                                                placeholder="Tell us about yourself"
                                                aria-invalid={!!errors.bio}
                                            />
                                            <FieldError errors={errors.bio ? [{ message: errors.bio }] : undefined} />
                                        </FieldContent>
                                    </Field>

                                    <Button
                                        disabled={processing}
                                        data-test="update-player-profile-button"
                                    >
                                        Save player profile
                                    </Button>
                                </FieldGroup>
                            )}
                        </Form>
                    </div>
                )}

            </SettingsLayout>
        </AppLayout>
    );
}
