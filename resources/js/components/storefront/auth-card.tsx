import type { ReactNode } from 'react';

import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import { PageHeader } from '@/components/storefront/page-header';
import { __ } from '@/lib/i18n';

interface AuthCardProps {
    title: string;
    heading: string;
    subtitle: string;
    children: ReactNode;
    footer?: ReactNode;
}

export function AuthCard({ title, heading, subtitle, children, footer }: AuthCardProps) {
    return (
        <>
            <PageHeader crumbs={[{ label: __('Home'), href: HomepageController.show() }, { label: title }]} />

            <section className="mx-auto w-full max-w-[560px] px-6 pt-8 pb-20">
                <div className="rounded-lg border border-line bg-surface p-8 sm:p-10">
                    <h1 className="m-0 text-7xl font-semibold text-ink">{heading}</h1>
                    <p className="mt-2 mb-0 text-base text-muted">{subtitle}</p>

                    {children}

                    {footer && <p className="mt-8 mb-0 text-center text-base text-muted">{footer}</p>}
                </div>
            </section>
        </>
    );
}
