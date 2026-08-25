import { type ResolvedComponent } from '@inertiajs/react';
import { LazyMotion, MotionConfig } from 'motion/react';

import { DirectionProvider } from '@/components/ui/direction';

import { createApp } from './create-app';
import { AccountLayout } from './layouts/admin/account-layout';
import { AdminLayout } from './layouts/admin/admin-layout';
import { AuthLayout } from './layouts/admin/auth-layout';
import { StorefrontBuilderLayout } from './layouts/admin/storefront-builder-layout';

const loadMotionFeatures = () => import('./lib/motion-features').then((mod) => mod.default);

createApp({
    resolve: (name) => {
        const pages = import.meta.glob<ResolvedComponent>('./pages/admin/**/*.tsx');
        return pages[`./pages/${name}.tsx`]();
    },
    layout: (name) => {
        if (name.startsWith('admin/auth/') || name === 'admin/license') return AuthLayout;
        if (name.startsWith('admin/account/')) return [AdminLayout, AccountLayout];
        if (name.startsWith('admin/storefront/')) return StorefrontBuilderLayout;
        return AdminLayout;
    },
    wrap: (children, direction) => (
        <DirectionProvider dir={direction}>
            <LazyMotion strict features={loadMotionFeatures}>
                <MotionConfig reducedMotion="user">{children}</MotionConfig>
            </LazyMotion>
        </DirectionProvider>
    ),
});
