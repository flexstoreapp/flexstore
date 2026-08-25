import { usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { AdminHeader } from '@/layouts/admin/admin-header';
import { AdminShell } from '@/layouts/admin/admin-shell';
import { AdminSidebar } from '@/layouts/admin/admin-sidebar';
import { cn } from '@/lib/utils';
import type { AdminSharedData, BreadcrumbItem } from '@/types';

interface AdminLayoutProps {
    breadcrumbs?: BreadcrumbItem[];
    className?: string;
}

export function AdminLayout({ breadcrumbs = [], className, children }: PropsWithChildren<AdminLayoutProps>) {
    const { sidebarOpen } = usePage<AdminSharedData>().props;

    return (
        <AdminShell>
            <SidebarProvider defaultOpen={sidebarOpen}>
                <AdminSidebar />
                <SidebarInset className="overflow-x-hidden">
                    <AdminHeader breadcrumbs={breadcrumbs} />

                    <div
                        id="main-content"
                        className={cn('flex-1 space-y-6 overflow-auto p-6 md:container md:mx-auto', className)}
                    >
                        {children}
                    </div>
                </SidebarInset>
            </SidebarProvider>
        </AdminShell>
    );
}
