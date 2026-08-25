import { usePage } from '@inertiajs/react';
import { Toaster } from 'sonner';

import { ConfirmProvider } from '@/components/admin/confirm';
import { ProUpgradeProvider } from '@/components/admin/pro/pro-upgrade-context';
import { AdminThemeProvider } from '@/contexts/admin-theme-context';

export function AdminShell({ children }: React.PropsWithChildren) {
    const { appearance, adminThemeColor } = usePage().props;

    return (
        <AdminThemeProvider serverAppearance={appearance} serverThemeColor={adminThemeColor}>
            <ConfirmProvider>
                <ProUpgradeProvider>
                    <div data-vaul-drawer-wrapper>
                        <Toaster />
                        {children}
                    </div>
                </ProUpgradeProvider>
            </ConfirmProvider>
        </AdminThemeProvider>
    );
}
