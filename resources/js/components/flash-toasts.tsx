import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { Toaster } from '@/components/ui/sonner';

/** Shows the server's flash messages (`->with('success', …)`) as toasts. */
export function FlashToasts() {
    const { flash } = usePage().props;

    // The flash object is new on every response, so a repeated message still shows.
    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }

        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    return <Toaster position="top-right" richColors />;
}
