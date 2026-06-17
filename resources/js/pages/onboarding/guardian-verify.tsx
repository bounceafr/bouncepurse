import { Form, Head, usePage } from '@inertiajs/react';
import { CheckCircle2, ShieldCheckIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitLayout from '@/layouts/auth/auth-split-layout';

type Guardian = {
    uuid: string;
    full_name: string;
    relationship: string;
    player_name: string;
    is_verified: boolean;
};

export default function GuardianVerify({ guardian }: { guardian: Guardian }) {
    const { props } = usePage();
    const status = (props as Record<string, unknown>).status as
        | string
        | undefined;

    const isVerified = guardian.is_verified || status === 'guardian-verified';

    return (
        <AuthSplitLayout
            title="Guardian verification"
            description={
                isVerified
                    ? 'Verification complete'
                    : `Confirm guardianship for ${guardian.player_name}`
            }
        >
            <Head title="Guardian verification" />

            {isVerified ? (
                <div className="flex flex-col items-center gap-4 text-center">
                    <div className="flex size-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <CheckCircle2 className="size-6 text-green-600 dark:text-green-400" />
                    </div>
                    <h2 className="text-lg font-medium">Thank you!</h2>
                    <p className="text-sm text-muted-foreground">
                        You have verified guardianship for{' '}
                        <span className="font-medium text-foreground">
                            {guardian.player_name}
                        </span>
                        . They can now access the platform.
                    </p>
                </div>
            ) : (
                <div className="flex flex-col items-center gap-4 text-center">
                    <div className="flex size-12 items-center justify-center rounded-full bg-muted">
                        <ShieldCheckIcon className="size-6 text-muted-foreground" />
                    </div>

                    <div className="space-y-1 text-sm text-muted-foreground">
                        <p>
                            <span className="font-medium text-foreground">
                                {guardian.player_name}
                            </span>{' '}
                            has listed you as their{' '}
                            <span className="font-medium text-foreground">
                                {guardian.relationship}
                            </span>
                            .
                        </p>
                        <p>
                            By confirming, you consent to their use of the
                            platform.
                        </p>
                    </div>

                    <Form
                        action={window.location.href}
                        method="post"
                        className="w-full"
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing && <Spinner />}I confirm
                                guardianship
                            </Button>
                        )}
                    </Form>
                </div>
            )}
        </AuthSplitLayout>
    );
}
