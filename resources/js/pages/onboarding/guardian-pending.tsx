import { Form, Head } from '@inertiajs/react';
import { MailIcon } from 'lucide-react';
import OnboardingController from '@/actions/App/Http/Controllers/OnboardingController';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitLayout from '@/layouts/auth/auth-split-layout';
import { logout } from '@/routes';

export default function GuardianPending({
    guardianEmail,
    status,
}: {
    guardianEmail: string;
    status?: string;
}) {
    return (
        <AuthSplitLayout
            title="Waiting for guardian"
            description="Your guardian needs to verify your account before you can continue."
        >
            <Head title="Guardian verification pending" />

            <div className="flex flex-col items-center gap-4 text-center">
                <div className="flex size-12 items-center justify-center rounded-full bg-muted">
                    <MailIcon className="size-6 text-muted-foreground" />
                </div>

                <p className="text-sm text-muted-foreground">
                    We sent a verification email to{' '}
                    <span className="font-medium text-foreground">
                        {guardianEmail}
                    </span>
                    . Once your guardian confirms, you'll be able to access the
                    platform.
                </p>

                {status === 'verification-link-sent' && (
                    <div className="text-sm font-medium text-green-600">
                        A new verification link has been sent.
                    </div>
                )}

                <Form
                    action={OnboardingController.resendGuardianVerification.url()}
                    method="post"
                    className="w-full"
                >
                    {({ processing }) => (
                        <Button
                            type="submit"
                            variant="outline"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            Resend verification email
                        </Button>
                    )}
                </Form>

                <TextLink href={logout()} className="text-sm">
                    Log out
                </TextLink>
            </div>
        </AuthSplitLayout>
    );
}
