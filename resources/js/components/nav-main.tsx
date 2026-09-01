import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import { Separator } from '@/components/ui/separator';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavGroup } from '@/types';

export function NavMain({ groups = [] }: { groups: NavGroup[] }) {
    const { isCurrentUrl } = useCurrentUrl();
    const nonEmptyGroups = groups.filter((group) => group.items.length > 0);

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel className="text-muted-foreground">
                Platform
            </SidebarGroupLabel>
            <SidebarMenu>
                {nonEmptyGroups.map((group, groupIndex) => (
                    <Fragment key={groupIndex}>
                        {groupIndex > 0 && (
                            <SidebarMenuItem className="py-1">
                                <Separator className="bg-foreground/10" />
                            </SidebarMenuItem>
                        )}
                        {group.items.map((item) => (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isCurrentUrl(item.href)}
                                    tooltip={{ children: item.title }}
                                >
                                    <Link href={item.href} prefetch>
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </Fragment>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
