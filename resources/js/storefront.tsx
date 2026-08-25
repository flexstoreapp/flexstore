import { type ResolvedComponent } from '@inertiajs/react';

import { AccountLayout } from './components/storefront/account/account-layout';
import { createApp } from './create-app';
import { StorefrontLayout } from './layouts/storefront/storefront-layout';

const ACCOUNT_SIDEBAR_PAGES = new Set([
    'storefront/account/dashboard',
    'storefront/account/orders/list',
    'storefront/account/downloads/list',
    'storefront/account/addresses/list',
    'storefront/account/profile',
]);

createApp({
    serverHead: true,
    resolve: (name) => {
        const pages = import.meta.glob<ResolvedComponent>('./pages/storefront/**/*.tsx');
        return pages[`./pages/${name}.tsx`]();
    },
    layout: (name) => {
        if (name === 'storefront/error-page' || name === 'storefront/maintenance') return null;
        if (ACCOUNT_SIDEBAR_PAGES.has(name)) return [StorefrontLayout, AccountLayout];
        return StorefrontLayout;
    },
});
