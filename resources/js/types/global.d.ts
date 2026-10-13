import type { Auth } from '@/types/auth';
import type { AppNotification } from '@/types/notifications';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            assets: {
                logo: string;
                logoDark: string;
            };
            sidebarOpen: boolean;
            flash: { success: string | null; error: string | null };
            notifications: {
                unread_count: number;
                recent: AppNotification[];
            } | null;
            [key: string]: unknown;
        };
    }
}
