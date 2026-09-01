import { Link } from '@inertiajs/react';
import { Fragment, type ComponentPropsWithoutRef } from 'react';
import { Separator } from '@/components/ui/separator';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

export function NavFooter({
    items,
    separatorBefore,
    className,
    ...props
}: ComponentPropsWithoutRef<typeof SidebarGroup> & {
    items: NavItem[];
    separatorBefore?: string;
}) {
    return (
        <SidebarGroup
            {...props}
            className={`group-data-[collapsible=icon]:p-0 ${className || ''}`}
        >
            <SidebarGroupContent>
                <SidebarMenu>
                    {items.map((item) => (
                        <Fragment key={item.title}>
                            {separatorBefore === item.title && (
                                <SidebarMenuItem className="py-1">
                                    <Separator className="bg-foreground/10" />
                                </SidebarMenuItem>
                            )}
                            <SidebarMenuItem>
                                <SidebarMenuButton
                                    asChild
                                    className="text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                                >
                                    <Link href={item.href}>
                                        {item.icon && (
                                            <item.icon className="h-5 w-5" />
                                        )}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </Fragment>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
