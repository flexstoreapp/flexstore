import { usePage } from '@inertiajs/react';
import { ChevronsUpDownIcon } from 'lucide-react';

import { UserInfo } from '@/components/admin/user-info';
import { UserMenuContent } from '@/components/admin/user-menu-content';
import { useDirection } from '@/components/ui/direction';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/admin/use-mobile';
import type { AdminSharedData } from '@/types';

export function NavUser() {
    const { auth } = usePage<AdminSharedData>().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();
    const direction = useDirection();

    const collapsedSide = direction === 'rtl' ? 'right' : 'left';
    const menuSide = isMobile || state !== 'collapsed' ? 'bottom' : collapsedSide;

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="group text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent"
                        >
                            <UserInfo user={auth.user} />
                            <ChevronsUpDownIcon className="ms-auto size-5" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="end"
                        side={menuSide}
                    >
                        <UserMenuContent user={auth.user!} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
