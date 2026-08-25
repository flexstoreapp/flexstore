import { type UrlMethodPair } from '@inertiajs/core';
import { type LucideIcon } from 'lucide-react';

import { type Permission as PermissionKey } from '@/lib/permissions';

export interface BreadcrumbItem {
    title: React.ReactNode;
    href: UrlMethodPair;
}

export interface NavItem {
    title: string;
    description?: string;
    href?: UrlMethodPair;
    pro?: boolean;
    icon?: LucideIcon;
    permission?: PermissionKey;
    permissions?: PermissionKey[];
    children?: NavItem[];
}
