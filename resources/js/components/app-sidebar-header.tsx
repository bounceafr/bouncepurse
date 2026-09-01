import { Bell, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { NavUser } from '@/components/nav-user';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const [searchOpen, setSearchOpen] = useState(false);
    const searchInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (searchOpen) {
            searchInputRef.current?.focus();
        }
    }, [searchOpen]);

    return (
        <header className="flex h-16 w-full shrink-0 items-center justify-between gap-2 border-b border-border/80 bg-background/70 px-6 backdrop-blur-sm transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1 hover:bg-primary/10 hover:text-primary" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="ml-auto flex items-center gap-1">
                <DropdownMenu open={searchOpen} onOpenChange={setSearchOpen}>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-9 w-9 hover:bg-primary/10 hover:text-primary"
                            aria-label="Search"
                        >
                            <Search className="size-5 opacity-80" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="end"
                        className="w-72 p-2"
                        onCloseAutoFocus={(e) => e.preventDefault()}
                    >
                        <Input
                            ref={searchInputRef}
                            type="search"
                            placeholder="Search…"
                            className="h-9"
                            onKeyDown={(e) => {
                                if (e.key === 'Escape') {
                                    setSearchOpen(false);
                                }
                            }}
                        />
                    </DropdownMenuContent>
                </DropdownMenu>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-9 w-9 hover:bg-primary/10 hover:text-primary"
                            aria-label="Notifications"
                        >
                            <Bell className="size-5 opacity-80" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-72">
                        <div className="p-4 text-center text-sm text-muted-foreground">
                            No new notifications
                        </div>
                    </DropdownMenuContent>
                </DropdownMenu>
                <NavUser />
            </div>
        </header>
    );
}
