import { Form, router } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { Check, Copy, ScanLine } from 'lucide-react';
import {
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
    type FormEvent,
} from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { useAppearance } from '@/hooks/use-appearance';
import { useClipboard } from '@/hooks/use-clipboard';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { store as confirmPassword } from '@/routes/password/confirm';
import { confirm, enable } from '@/routes/two-factor';
import AlertError from './alert-error';
import { Spinner } from './ui/spinner';

function getXsrfToken(): string {
    return decodeURIComponent(
        document.cookie
            .split('; ')
            .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );
}

function GridScanIcon() {
    return (
        <div className="mb-3 rounded-full border border-border bg-card p-0.5 shadow-sm">
            <div className="relative overflow-hidden rounded-full border border-border bg-muted p-2.5">
                <div className="absolute inset-0 grid grid-cols-5 opacity-50">
                    {Array.from({ length: 5 }, (_, i) => (
                        <div
                            key={`col-${i + 1}`}
                            className="border-r border-border last:border-r-0"
                        />
                    ))}
                </div>
                <div className="absolute inset-0 grid grid-rows-5 opacity-50">
                    {Array.from({ length: 5 }, (_, i) => (
                        <div
                            key={`row-${i + 1}`}
                            className="border-b border-border last:border-b-0"
                        />
                    ))}
                </div>
                <ScanLine className="relative z-20 size-6 text-foreground" />
            </div>
        </div>
    );
}

function TwoFactorPasswordStep({
    onConfirmed,
    continueSetup,
}: {
    onConfirmed: () => void;
    continueSetup: boolean;
}) {
    const [password, setPassword] = useState('');
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);

    const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setProcessing(true);
        setError(undefined);

        try {
            const response = await fetch(confirmPassword.url(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                body: JSON.stringify({ password }),
            });

            if (response.status === 201) {
                onConfirmed();
                return;
            }

            if (response.status === 422) {
                const data = (await response.json()) as {
                    errors?: { password?: string[] };
                    message?: string;
                };
                setError(
                    data.errors?.password?.[0] ??
                        data.message ??
                        'The provided password was incorrect.',
                );
                return;
            }

            setError('Unable to confirm your password. Please try again.');
        } catch {
            setError('Unable to confirm your password. Please try again.');
        } finally {
            setProcessing(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="flex w-full flex-col gap-6">
            <div className="grid gap-2">
                <Label htmlFor="confirm-password">Password</Label>
                <Input
                    id="confirm-password"
                    type="password"
                    name="password"
                    value={password}
                    onChange={(event) => setPassword(event.target.value)}
                    placeholder="Password"
                    autoComplete="current-password"
                    autoFocus
                    disabled={processing}
                />
                <InputError message={error} />
            </div>

            <Button
                type="submit"
                className="w-full"
                disabled={processing || password.length === 0}
                data-test="confirm-password-button"
            >
                {processing && <Spinner />}
                {continueSetup ? 'Confirm & continue' : 'Confirm password'}
            </Button>
        </form>
    );
}

function TwoFactorSetupStep({
    qrCodeSvg,
    manualSetupKey,
    buttonText,
    onNextStep,
    errors,
}: {
    qrCodeSvg: string | null;
    manualSetupKey: string | null;
    buttonText: string;
    onNextStep: () => void;
    errors: string[];
}) {
    const { resolvedAppearance } = useAppearance();
    const [copiedText, copy] = useClipboard();
    const IconComponent = copiedText === manualSetupKey ? Check : Copy;

    return (
        <>
            {errors?.length ? (
                <AlertError errors={errors} />
            ) : (
                <>
                    <div className="mx-auto flex max-w-md overflow-hidden">
                        <div className="mx-auto aspect-square w-64 rounded-lg border border-border">
                            <div className="z-10 flex size-full items-center justify-center p-5">
                                {qrCodeSvg ? (
                                    <div
                                        className="aspect-square w-full rounded-lg bg-background p-2 [&_svg]:size-full"
                                        dangerouslySetInnerHTML={{
                                            __html: qrCodeSvg,
                                        }}
                                        style={{
                                            filter:
                                                resolvedAppearance === 'dark'
                                                    ? 'invert(1) brightness(1.5)'
                                                    : undefined,
                                        }}
                                    />
                                ) : (
                                    <Spinner />
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex w-full">
                        <Button className="w-full" onClick={onNextStep}>
                            {buttonText}
                        </Button>
                    </div>

                    <div className="relative flex w-full items-center justify-center">
                        <div className="absolute inset-0 top-1/2 h-px w-full bg-border" />
                        <span className="relative bg-card px-2 py-1">
                            or, enter the code manually
                        </span>
                    </div>

                    <div className="flex w-full">
                        <div className="flex w-full items-stretch overflow-hidden rounded-xl border border-border">
                            {!manualSetupKey ? (
                                <div className="flex size-full items-center justify-center bg-muted p-3">
                                    <Spinner />
                                </div>
                            ) : (
                                <>
                                    <input
                                        type="text"
                                        readOnly
                                        value={manualSetupKey}
                                        className="h-full w-full bg-background p-3 text-foreground outline-none"
                                    />
                                    <button
                                        onClick={() => copy(manualSetupKey)}
                                        className="border-l border-border px-3 hover:bg-muted"
                                    >
                                        <IconComponent className="w-4" />
                                    </button>
                                </>
                            )}
                        </div>
                    </div>
                </>
            )}
        </>
    );
}

function TwoFactorVerificationStep({
    onClose,
    onBack,
}: {
    onClose: () => void;
    onBack: () => void;
}) {
    const [code, setCode] = useState<string>('');
    const pinInputContainerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        setTimeout(() => {
            pinInputContainerRef.current?.querySelector('input')?.focus();
        }, 0);
    }, []);

    return (
        <Form
            {...confirm.form()}
            onSuccess={() => onClose()}
            resetOnError
            resetOnSuccess
        >
            {({
                processing,
                errors,
            }: {
                processing: boolean;
                errors?: { confirmTwoFactorAuthentication?: { code?: string } };
            }) => (
                <>
                    <div
                        ref={pinInputContainerRef}
                        className="relative flex w-full flex-col gap-3"
                    >
                        <div className="flex w-full flex-col items-center gap-3 py-2">
                            <InputOTP
                                id="otp"
                                name="code"
                                maxLength={OTP_MAX_LENGTH}
                                onChange={setCode}
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
                            <InputError
                                message={
                                    errors?.confirmTwoFactorAuthentication?.code
                                }
                            />
                        </div>

                        <div className="flex w-full gap-5">
                            <Button
                                type="button"
                                variant="outline"
                                className="flex-1"
                                onClick={onBack}
                                disabled={processing}
                            >
                                Back
                            </Button>
                            <Button
                                type="submit"
                                className="flex-1"
                                disabled={
                                    processing || code.length < OTP_MAX_LENGTH
                                }
                            >
                                Confirm
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </Form>
    );
}

type Props = {
    isOpen: boolean;
    onClose: () => void;
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
    confirmPassword: boolean;
    passwordConfirmed: boolean;
    onPasswordConfirmed: () => void;
    qrCodeSvg: string | null;
    manualSetupKey: string | null;
    clearSetupData: () => void;
    fetchSetupData: () => Promise<void>;
    errors: string[];
};

export default function TwoFactorSetupModal({
    isOpen,
    onClose,
    requiresConfirmation,
    twoFactorEnabled,
    confirmPassword,
    passwordConfirmed,
    onPasswordConfirmed,
    qrCodeSvg,
    manualSetupKey,
    clearSetupData,
    fetchSetupData,
    errors,
}: Props) {
    const [showPasswordStep, setShowPasswordStep] = useState(
        confirmPassword && !passwordConfirmed,
    );
    const [showVerificationStep, setShowVerificationStep] =
        useState<boolean>(false);
    const [enabling, setEnabling] = useState(false);
    const [enableError, setEnableError] = useState<string | undefined>();

    const wasOpen = useRef(false);

    useEffect(() => {
        if (isOpen && !wasOpen.current) {
            setShowPasswordStep(confirmPassword && !passwordConfirmed);
            setShowVerificationStep(false);
            setEnableError(undefined);
            setEnabling(false);
        }

        wasOpen.current = isOpen;
    }, [isOpen, confirmPassword, passwordConfirmed]);

    const modalConfig = useMemo<{
        title: string;
        description: string;
        buttonText: string;
    }>(() => {
        if (showPasswordStep) {
            return {
                title: 'Confirm your password',
                description:
                    'This is a secure area of the application. Please confirm your password before continuing.',
                buttonText: 'Confirm password',
            };
        }

        if (twoFactorEnabled) {
            return {
                title: 'Two-Factor Authentication Enabled',
                description:
                    'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
                buttonText: 'Close',
            };
        }

        if (showVerificationStep) {
            return {
                title: 'Verify Authentication Code',
                description:
                    'Enter the 6-digit code from your authenticator app',
                buttonText: 'Continue',
            };
        }

        return {
            title: 'Enable Two-Factor Authentication',
            description:
                'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app',
            buttonText: 'Continue',
        };
    }, [twoFactorEnabled, showVerificationStep, showPasswordStep]);

    const startSetup = useCallback(() => {
        setEnabling(true);
        setEnableError(undefined);

        router.post(
            enable.url(),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: async () => {
                    await fetchSetupData();
                    setShowPasswordStep(false);
                    setEnabling(false);
                },
                onError: () => {
                    setEnableError(
                        'Unable to start two-factor setup. Please try again.',
                    );
                    setEnabling(false);
                },
                onCancel: () => {
                    setEnabling(false);
                },
            },
        );
    }, [fetchSetupData]);

    const handlePasswordConfirmed = useCallback(() => {
        onPasswordConfirmed();

        if (twoFactorEnabled) {
            setShowPasswordStep(false);
            onClose();
            return;
        }

        startSetup();
    }, [onPasswordConfirmed, twoFactorEnabled, onClose, startSetup]);

    const handleModalNextStep = useCallback(() => {
        if (requiresConfirmation) {
            setShowVerificationStep(true);
            return;
        }

        clearSetupData();
        onClose();
    }, [requiresConfirmation, clearSetupData, onClose]);

    const resetModalState = useCallback(() => {
        setShowVerificationStep(false);
        setShowPasswordStep(confirmPassword && !passwordConfirmed);
        setEnableError(undefined);

        if (twoFactorEnabled) {
            clearSetupData();
        }
    }, [twoFactorEnabled, clearSetupData, confirmPassword, passwordConfirmed]);

    useEffect(() => {
        if (!isOpen || showPasswordStep || twoFactorEnabled || enabling) {
            return;
        }

        if (qrCodeSvg && manualSetupKey) {
            return;
        }

        // Password already confirmed: start (or resume) setup when the modal opens.
        const setupTask = window.setTimeout(startSetup, 0);

        return () => window.clearTimeout(setupTask);
    }, [
        isOpen,
        showPasswordStep,
        twoFactorEnabled,
        enabling,
        qrCodeSvg,
        manualSetupKey,
        startSetup,
    ]);

    const handleClose = useCallback(() => {
        resetModalState();
        onClose();
    }, [onClose, resetModalState]);

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && handleClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader className="flex items-center justify-center">
                    <GridScanIcon />
                    <DialogTitle>{modalConfig.title}</DialogTitle>
                    <DialogDescription className="text-center">
                        {modalConfig.description}
                    </DialogDescription>
                </DialogHeader>

                <div className="flex flex-col items-center gap-5">
                    {showPasswordStep ? (
                        enabling ? (
                            <div className="flex flex-col items-center gap-3 py-6">
                                <Spinner />
                                <p className="text-sm text-muted-foreground">
                                    Starting two-factor setup…
                                </p>
                            </div>
                        ) : (
                            <>
                                {enableError && (
                                    <AlertError errors={[enableError]} />
                                )}
                                <TwoFactorPasswordStep
                                    onConfirmed={handlePasswordConfirmed}
                                    continueSetup={!twoFactorEnabled}
                                />
                            </>
                        )
                    ) : showVerificationStep ? (
                        <TwoFactorVerificationStep
                            onClose={onClose}
                            onBack={() => setShowVerificationStep(false)}
                        />
                    ) : (
                        <TwoFactorSetupStep
                            qrCodeSvg={qrCodeSvg}
                            manualSetupKey={manualSetupKey}
                            buttonText={modalConfig.buttonText}
                            onNextStep={handleModalNextStep}
                            errors={errors}
                        />
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
