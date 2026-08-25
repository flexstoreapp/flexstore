import { Link, router } from '@inertiajs/react';
import { useEffect } from 'react';

import * as DashboardController from '@/actions/App/Http/Controllers/Admin/DashboardController';
import { NavMain } from '@/components/admin/nav-main';
import { NavUser } from '@/components/admin/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';

import { AdminLogo } from './admin-logo';

export function AdminSidebar() {
    const { isMobile, setOpenMobile } = useSidebar();

    useEffect(() => {
        if (!isMobile) {
            return;
        }

        return router.on('navigate', () => setOpenMobile(false));
    }, [isMobile, setOpenMobile]);

    const closeOnLinkClick = (event: React.MouseEvent<HTMLDivElement>) => {
        if (isMobile && (event.target as HTMLElement).closest('a[href]')) {
            setOpenMobile(false);
        }
    };

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader onClickCapture={closeOnLinkClick}>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={DashboardController.index()} prefetch>
                                <AdminLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent onClickCapture={closeOnLinkClick}>
                <NavMain />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
