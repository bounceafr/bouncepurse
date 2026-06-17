import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type UseMyLocationButtonProps = {
    latRef: React.RefObject<HTMLInputElement | null>;
    lngRef: React.RefObject<HTMLInputElement | null>;
};

function setNativeValue(input: HTMLInputElement, value: string) {
    const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
        window.HTMLInputElement.prototype,
        'value',
    )?.set;
    nativeInputValueSetter?.call(input, value);
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

export default function UseMyLocationButton({
    latRef,
    lngRef,
}: UseMyLocationButtonProps) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [permissionDeniedOpen, setPermissionDeniedOpen] = useState(false);

    async function handleClick() {
        if (!navigator.geolocation) {
            setError('Geolocation is not supported by your browser.');
            return;
        }

        if (!window.isSecureContext) {
            setError(
                'Location requires a secure context (HTTPS or localhost). Open this page on HTTPS and try again.',
            );
            return;
        }

        setLoading(true);
        setError(null);
        setPermissionDeniedOpen(false);

        if ('permissions' in navigator) {
            try {
                const permissionStatus = await navigator.permissions.query({
                    name: 'geolocation',
                });
                if (permissionStatus.state === 'denied') {
                    setLoading(false);
                    setPermissionDeniedOpen(true);
                    return;
                }
            } catch {
                // Some browsers throw for permissions query; continue to geolocation request.
            }
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                if (latRef.current) {
                    setNativeValue(
                        latRef.current,
                        String(position.coords.latitude),
                    );
                }
                if (lngRef.current) {
                    setNativeValue(
                        lngRef.current,
                        String(position.coords.longitude),
                    );
                }
                setLoading(false);
            },
            (err) => {
                setLoading(false);
                if (err.code === err.PERMISSION_DENIED) {
                    setPermissionDeniedOpen(true);
                    return;
                }
                if (err.code === err.POSITION_UNAVAILABLE) {
                    setError(
                        'Location is currently unavailable from your device. You can enter latitude and longitude manually.',
                    );
                    return;
                }
                if (err.code === err.TIMEOUT) {
                    setError(
                        'Location request timed out. Check your signal and try again, or enter coordinates manually.',
                    );
                    return;
                }
                setError(
                    'Unable to retrieve your location. You can enter latitude and longitude manually.',
                );
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000,
            },
        );
    }

    return (
        <div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={loading}
                onClick={handleClick}
            >
                {loading ? 'Getting location…' : 'Use My Location'}
            </Button>
            {error && (
                <p className="mt-1 flex items-center justify-between gap-2 text-sm text-amber-600 dark:text-amber-500">
                    <span>{error}</span>
                    <button
                        type="button"
                        onClick={() => setError(null)}
                        className="shrink-0 font-medium underline hover:no-underline"
                    >
                        Dismiss
                    </button>
                </p>
            )}

            <Dialog
                open={permissionDeniedOpen}
                onOpenChange={setPermissionDeniedOpen}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Enable location access</DialogTitle>
                        <DialogDescription>
                            To use your current location, this site needs
                            permission to access your device location. In your
                            browser, click the lock or info icon in the address
                            bar, open site settings, and set Location to
                            &quot;Allow&quot;. Then try &quot;Use My
                            Location&quot; again. You can also enter latitude
                            and longitude manually.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            onClick={() => setPermissionDeniedOpen(false)}
                        >
                            OK
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
