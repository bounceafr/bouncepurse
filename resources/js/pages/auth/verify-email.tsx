import { Form, Head } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useState } from 'react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import AuthSplitLayout from '@/layouts/auth/auth-split-layout';
import { logout } from '@/routes';
import { resend, verify } from '@/routes/verification/code';
import { send } from '@/routes/verification';

type Props = {
    status?: string;
    verificationMethod?: 'code' | 'link';
};

export default function VerifyEmail({
    status,
    verificationMethod = 'link',
}: Props) {
    const [code, setCode] = useState('');

    if (verificationMethod === 'code') {
        return (
            <AuthSplitLayout
                title="Verify email"
                description="Enter the 6-digit code we sent to your email address."
            >
                <Head title="Email verification" />

                {status === 'verification-code-sent' && (
                    <div className="mb-4 text-center text-sm font-medium text-green-600">
                        A new verification code has been sent to your email
                        address.
                    </div>
                )}

                <div className="space-y-6">
                    <Form
                        {...verify.form()}
                        className="space-y-4"
                        resetOnError
                        resetOnSuccess
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="flex flex-col items-center justify-center space-y-3 text-center">
                                    <div className="flex w-full items-center justify-center">
                                        <InputOTP
                                            name="code"
                                            maxLength={OTP_MAX_LENGTH}
                                            value={code}
                                            onChange={(value) => setCode(value)}
                                            disabled={processing}
                                            pattern={REGEXP_ONLY_DIGITS}
                                        >
                                            <InputOTPGroup>
                                                {Array.from(
                                                    { length: OTP_MAX_LENGTH },
                                                    (_, index) => (
                                                        <InputOTPSlot
                                                            key={index}
                                                            index={index}
                                                        />
                                                    ),
                                                )}
                                            </InputOTPGroup>
                                        </InputOTP>
                                    </div>
                                    <InputError message={errors.code} />
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={
                                        processing ||
                                        code.length !== OTP_MAX_LENGTH
                                    }
                                >
                                    {processing && <Spinner />}
                                    Verify email
                                </Button>
                            </>
                        )}
                    </Form>

                    <Form {...resend.form()} className="text-center">
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={processing}
                                className="w-full"
                            >
                                {processing && <Spinner />}
                                Resend verification code
                            </Button>
                        )}
                    </Form>

                    <TextLink
                        href={logout()}
                        className="mx-auto block text-center text-sm"
                    >
                        Log out
                    </TextLink>
                </div>
            </AuthSplitLayout>
        );
    }

    return (
        <AuthSplitLayout
            title="Verify email"
            description="Please verify your email address by clicking on the link we just emailed to you."
        >
            <Head title="Email verification" />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    A new verification link has been sent to the email address
                    you provided during registration.
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing}>
                            {processing && <Spinner />}
                            Resend verification email
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            Log out
                        </TextLink>
                    </>
                )}
            </Form>
        </AuthSplitLayout>
    );
}
