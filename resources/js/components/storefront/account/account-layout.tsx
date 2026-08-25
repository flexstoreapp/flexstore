import { usePage } from '@inertiajs/react';
import { Children, isValidElement, type PropsWithChildren, type ReactNode } from 'react';

import * as DashboardController from '@/actions/App/Http/Controllers/Storefront/DashboardController';
import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import { AccountNav } from '@/components/storefront/account/account-nav';
import { EmailVerificationBanner } from '@/components/storefront/account/email-verification-banner';
import { type Crumb } from '@/components/storefront/breadcrumb';
import { PageHeader } from '@/components/storefront/page-header';
import { Section } from '@/components/storefront/section';
import { __ } from '@/lib/i18n';
import type { StorefrontSharedData } from '@/types';

function pageTitle(children: ReactNode): string {
    const child = Children.toArray(children).find(isValidElement);

    return (child?.type as { title?: string } | undefined)?.title ?? '';
}

function accountCrumbs(title: string, isDashboard: boolean): Crumb[] {
    const home = { label: __('Home'), href: HomepageController.show() };

    return isDashboard
        ? [home, { label: __('Account') }]
        : [home, { label: __('Account'), href: DashboardController.index() }, { label: title }];
}

export function AccountLayout({ children }: PropsWithChildren) {
    const { props, url } = usePage<StorefrontSharedData>();
    const needsVerification = Boolean(props.auth.user && !props.auth.user.email_verified_at);
    const title = pageTitle(children);
    const isDashboard = url.split('?')[0] === DashboardController.index().url;

    return (
        <>
            <PageHeader crumbs={accountCrumbs(title, isDashboard)} heading={title} />

            <Section className="mt-6 grid grid-cols-1 items-start gap-6 pb-12 lg:grid-cols-[264px_1fr] lg:gap-8">
                <AccountNav />

                <div className="flex min-w-0 flex-col gap-6">
                    {needsVerification && <EmailVerificationBanner />}
                    {children}
                </div>
            </Section>
        </>
    );
}
