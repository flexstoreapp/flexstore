import { Breadcrumbs } from '@/components/admin/breadcrumbs';
import { CommandPalette } from '@/components/admin/command-palette';
import { ThemeSwitcher } from '@/components/admin/theme-switcher';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useOsDetector } from '@/hooks/admin/use-os-detector';
import { __ } from '@/lib/i18n';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AdminHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const isMacOs = useOsDetector() === 'macOS';

    return (
        <header className="flex h-16 shrink-0 items-center gap-4 border-b px-6">
            <SidebarTrigger title={__('Toggle sidebar (:shortcut)', { shortcut: `${isMacOs ? '⌘' : 'Ctrl+'}B` })} />
            <div className="min-w-0 flex-1">
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <div className="flex shrink-0 items-center gap-2">
                <CommandPalette />
                <ThemeSwitcher />
            </div>
        </header>
    );
}
