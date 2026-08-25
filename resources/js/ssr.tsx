import { type Page } from '@inertiajs/core';
import { type ResolvedComponent } from '@inertiajs/react';
import { domMax, LazyMotion, MotionConfig } from 'motion/react';
import { renderToString } from 'react-dom/server';

import { DirectionProvider } from '@/components/ui/direction';

import { AccountLayout } from './components/storefront/account/account-layout';
import { createSsrApp } from './create-app';
import { AccountLayout as AdminAccountLayout } from './layouts/admin/account-layout';
import { AdminLayout } from './layouts/admin/admin-layout';
import { AuthLayout as AdminAuthLayout } from './layouts/admin/auth-layout';
import { StorefrontBuilderLayout } from './layouts/admin/storefront-builder-layout';
import { InstallerLayout } from './layouts/installer/installer-layout';
import { StorefrontLayout } from './layouts/storefront/storefront-layout';
import { loadServerTranslations } from './lib/server-translations';

const ACCOUNT_SIDEBAR_PAGES = new Set([
    'storefront/account/dashboard',
    'storefront/account/orders/list',
    'storefront/account/downloads/list',
    'storefront/account/addresses/list',
    'storefront/account/profile',
]);

export default function render(page: Page) {
    loadServerTranslations(page.component, (page.props.activeLocale as string | undefined) ?? 'en');

    return createSsrApp({
        page,
        render: renderToString,
        serverHead: !page.component.startsWith('admin/') && !page.component.startsWith('installer/'),
        resolve: (name) => {
            const pages = import.meta.glob<ResolvedComponent>('./pages/**/*.tsx');
            return pages[`./pages/${name}.tsx`]();
        },
        layout: (name) => {
            if (name === 'storefront/error-page' || name === 'storefront/maintenance') return null;
            if (name.startsWith('installer/')) return InstallerLayout;
            if (name.startsWith('admin/auth/') || name === 'admin/license') return AdminAuthLayout;
            if (name.startsWith('admin/account/')) return [AdminLayout, AdminAccountLayout];
            if (name.startsWith('admin/storefront/')) return StorefrontBuilderLayout;
            if (name.startsWith('admin/')) return AdminLayout;
            if (ACCOUNT_SIDEBAR_PAGES.has(name)) return [StorefrontLayout, AccountLayout];
            return StorefrontLayout;
        },
        wrap: (children, direction) => (
            <DirectionProvider dir={direction}>
                <LazyMotion strict features={domMax}>
                    <MotionConfig reducedMotion="user">{children}</MotionConfig>
                </LazyMotion>
            </DirectionProvider>
        ),
    });
}
