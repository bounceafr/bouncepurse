import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { BasketballSurface } from '@/components/basketball-surface';
import { PageEnter } from '@/components/motion';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                className="overflow-x-hidden !bg-transparent"
            >
                <BasketballSurface>
                    <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    <PageEnter className="flex flex-1 flex-col">
                        {children}
                    </PageEnter>
                </BasketballSurface>
            </AppContent>
        </AppShell>
    );
}
