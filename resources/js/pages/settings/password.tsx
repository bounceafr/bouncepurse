import { Transition } from '@headlessui/react';
import { Form, Head } from '@inertiajs/react';
import { useRef } from 'react';
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Field,
    FieldContent,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/user-password';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Password settings',
        href: edit().url,
    },
];

export default function Password() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Password settings" />

            <h1 className="sr-only">Password Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Update password"
                        description="Ensure your account is using a long, random password to stay secure"
                    />

                    <Form
                        {...PasswordController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        resetOnError={[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]}
                        resetOnSuccess
                        onError={(errors) => {
                            if (errors.password) {
                                passwordInput.current?.focus();
                            }

                            if (errors.current_password) {
                                currentPasswordInput.current?.focus();
                            }
                        }}
                    >
                        {({ errors, processing, recentlySuccessful }) => (
                            <FieldGroup className="space-y-6">
                                <Field data-invalid={!!errors.current_password}>
                                    <FieldLabel htmlFor="current_password">
                                        Current password
                                    </FieldLabel>
                                    <FieldContent>
                                        <Input
                                            id="current_password"
                                            ref={currentPasswordInput}
                                            name="current_password"
                                            type="password"
                                            autoComplete="current-password"
                                            placeholder="Current password"
                                            aria-invalid={!!errors.current_password}
                                        />
                                        <FieldError errors={errors.current_password ? [{ message: errors.current_password }] : undefined} />
                                    </FieldContent>
                                </Field>

                                <Field data-invalid={!!errors.password}>
                                    <FieldLabel htmlFor="password">
                                        New password
                                    </FieldLabel>
                                    <FieldContent>
                                        <Input
                                            id="password"
                                            ref={passwordInput}
                                            name="password"
                                            type="password"
                                            autoComplete="new-password"
                                            placeholder="New password"
                                            aria-invalid={!!errors.password}
                                        />
                                        <FieldError errors={errors.password ? [{ message: errors.password }] : undefined} />
                                    </FieldContent>
                                </Field>

                                <Field data-invalid={!!errors.password_confirmation}>
                                    <FieldLabel htmlFor="password_confirmation">
                                        Confirm password
                                    </FieldLabel>
                                    <FieldContent>
                                        <Input
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            type="password"
                                            autoComplete="new-password"
                                            placeholder="Confirm password"
                                            aria-invalid={!!errors.password_confirmation}
                                        />
                                        <FieldError errors={errors.password_confirmation ? [{ message: errors.password_confirmation }] : undefined} />
                                    </FieldContent>
                                </Field>

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-password-button"
                                    >
                                        Save password
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
            </SettingsLayout>
        </AppLayout>
    );
}
