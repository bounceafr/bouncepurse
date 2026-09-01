import type { Auth } from '@/types/auth';

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
            [key: string]: unknown;
        };
    }
}
