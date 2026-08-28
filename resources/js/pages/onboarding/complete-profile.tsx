import { Form, Head } from '@inertiajs/react';
import { format, differenceInYears, parseISO } from 'date-fns';
import { CalendarIcon, UserCircle } from 'lucide-react';
import * as React from 'react';
import OnboardingController from '@/actions/App/Http/Controllers/OnboardingController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import AuthSplitLayout from '@/layouts/auth/auth-split-layout';
import { cn } from '@/lib/utils';

type Country = {
    id: number;
    name: string;
};

type GuardianRelationship = {
    value: string;
    label: string;
};

type Props = {
    countries: Country[];
    guardianRelationships: GuardianRelationship[];
    profile: {
        date_of_birth: string | null;
        country_id: number | null;
        city: string | null;
        phone_number: string | null;
        bio: string | null;
        position: string | null;
        profile_image: string | null;
    } | null;
    guardian: {
        full_name: string | null;
        email: string | null;
        phone: string | null;
        address: string | null;
        relationship: string | null;
    } | null;
};

export default function CompleteProfile({
    countries,
    guardianRelationships,
    profile,
    guardian,
}: Props) {
    const currentYear = new Date().getFullYear();

    const initialDate = profile?.date_of_birth
        ? parseISO(profile.date_of_birth)
        : undefined;

    const [dateOfBirth, setDateOfBirth] = React.useState<Date | undefined>(
        initialDate,
    );
    const [dateOpen, setDateOpen] = React.useState(false);
    const [imagePreview, setImagePreview] = React.useState<string | null>(
        profile?.profile_image ? `/storage/${profile.profile_image}` : null,
    );

    const isMinor =
        dateOfBirth !== undefined &&
        differenceInYears(new Date(), dateOfBirth) < 18;

    function handleImageChange(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (file) {
            const reader = new FileReader();
            reader.onloadend = () => setImagePreview(reader.result as string);
            reader.readAsDataURL(file);
        }
    }

    return (
        <AuthSplitLayout
            title="Complete your profile"
            description="Tell us a bit about yourself to get started"
        >
            <Head title="Complete your profile" />

            <Form
                action={OnboardingController.store.url()}
                method="post"
                disableWhileProcessing
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        {/* Profile image */}
                        <div className="grid gap-2">
                            <Label>Profile image</Label>
                            <div className="flex items-center gap-4">
                                <div className="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-muted">
                                    {imagePreview ? (
                                        <img
                                            src={imagePreview}
                                            alt="Profile"
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <UserCircle className="size-10 text-muted-foreground" />
                                    )}
                                </div>
                                <Input
                                    type="file"
                                    name="profile_image"
                                    accept="image/*"
                                    onChange={handleImageChange}
                                    className="cursor-pointer"
                                />
                            </div>
                            <InputError message={errors.profile_image} />
                        </div>

                        {/* Date of birth */}
                        <div className="grid gap-2">
                            <Label>Date of birth</Label>
                            <input
                                type="hidden"
                                name="date_of_birth"
                                value={
                                    dateOfBirth
                                        ? format(dateOfBirth, 'yyyy-MM-dd')
                                        : ''
                                }
                                readOnly
                            />
                            <Popover open={dateOpen} onOpenChange={setDateOpen}>
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
                                            ? format(dateOfBirth, 'PPP')
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
                                            date < new Date('1900-01-01')
                                        }
                                        captionLayout="dropdown"
                                        fromYear={2001}
                                        toYear={currentYear - 5}
                                        defaultMonth={
                                            dateOfBirth ??
                                            new Date(currentYear - 18, 0)
                                        }
                                    />
                                </PopoverContent>
                            </Popover>
                            <InputError message={errors.date_of_birth} />
                        </div>

                        {/* Country */}
                        <div className="grid gap-2">
                            <Label htmlFor="country_id">Country</Label>
                            <Select
                                name="country_id"
                                defaultValue={
                                    profile?.country_id?.toString() ?? undefined
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
                            <InputError message={errors.country_id} />
                        </div>

                        {/* City */}
                        <div className="grid gap-2">
                            <Label htmlFor="city">City</Label>
                            <Input
                                id="city"
                                name="city"
                                defaultValue={profile?.city ?? ''}
                                required
                                placeholder="Your city"
                            />
                            <InputError message={errors.city} />
                        </div>

                        {/* Phone number */}
                        <div className="grid gap-2">
                            <Label htmlFor="phone_number">Phone number</Label>
                            <Input
                                id="phone_number"
                                name="phone_number"
                                defaultValue={profile?.phone_number ?? ''}
                                required
                                placeholder="+1 234 567 8900"
                            />
                            <InputError message={errors.phone_number} />
                        </div>

                        {/* Position */}
                        <div className="grid gap-2">
                            <Label htmlFor="position">Position</Label>
                            <Input
                                id="position"
                                name="position"
                                defaultValue={profile?.position ?? ''}
                                required
                                placeholder="e.g. Point Guard"
                            />
                            <InputError message={errors.position} />
                        </div>

                        {/* Bio */}
                        <div className="grid gap-2">
                            <Label htmlFor="bio">Bio</Label>
                            <Textarea
                                id="bio"
                                name="bio"
                                defaultValue={profile?.bio ?? ''}
                                required
                                rows={3}
                                placeholder="Tell us about yourself"
                            />
                            <InputError message={errors.bio} />
                        </div>

                        {/* Guardian section — shown when DOB indicates minor */}
                        {isMinor && (
                            <div className="space-y-4 rounded-lg border border-border bg-muted/30 p-4">
                                <div>
                                    <h3 className="text-sm font-medium">
                                        Guardian details
                                    </h3>
                                    <p className="text-xs text-muted-foreground">
                                        Since you are under 18, a guardian must
                                        verify your account.
                                    </p>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="guardian_full_name">
                                        Full name
                                    </Label>
                                    <Input
                                        id="guardian_full_name"
                                        name="guardian[full_name]"
                                        defaultValue={guardian?.full_name ?? ''}
                                        required
                                        placeholder="Guardian's full name"
                                    />
                                    <InputError
                                        message={errors['guardian.full_name']}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="guardian_email">
                                        Email
                                    </Label>
                                    <Input
                                        id="guardian_email"
                                        name="guardian[email]"
                                        type="email"
                                        defaultValue={guardian?.email ?? ''}
                                        required
                                        placeholder="guardian@example.com"
                                    />
                                    <InputError
                                        message={errors['guardian.email']}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="guardian_phone">
                                        Phone
                                    </Label>
                                    <Input
                                        id="guardian_phone"
                                        name="guardian[phone]"
                                        defaultValue={guardian?.phone ?? ''}
                                        required
                                        placeholder="+1 234 567 8900"
                                    />
                                    <InputError
                                        message={errors['guardian.phone']}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="guardian_address">
                                        Address
                                    </Label>
                                    <Input
                                        id="guardian_address"
                                        name="guardian[address]"
                                        defaultValue={guardian?.address ?? ''}
                                        required
                                        placeholder="Full address"
                                    />
                                    <InputError
                                        message={errors['guardian.address']}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="guardian_relationship">
                                        Relationship
                                    </Label>
                                    <Select
                                        name="guardian[relationship]"
                                        defaultValue={
                                            guardian?.relationship ?? undefined
                                        }
                                    >
                                        <SelectTrigger id="guardian_relationship">
                                            <SelectValue placeholder="Select relationship" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {guardianRelationships.map(
                                                (rel) => (
                                                    <SelectItem
                                                        key={rel.value}
                                                        value={rel.value}
                                                    >
                                                        {rel.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={
                                            errors['guardian.relationship']
                                        }
                                    />
                                </div>
                            </div>
                        )}

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            {isMinor
                                ? 'Continue & send guardian verification'
                                : 'Complete profile'}
                        </Button>
                    </>
                )}
            </Form>
        </AuthSplitLayout>
    );
}
