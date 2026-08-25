import type { ReactNode } from 'react';

import { Breadcrumb, type Crumb } from '@/components/storefront/breadcrumb';
import { Section } from '@/components/storefront/section';

interface PageHeaderProps {
    crumbs: Crumb[];
    heading?: string;
    subheading?: ReactNode;
    action?: ReactNode;
}

export function PageHeader({ crumbs, heading, subheading, action }: PageHeaderProps) {
    return (
        <Section className="pt-6">
            <Breadcrumb crumbs={crumbs} />
            {(heading || action) && (
                <div className="mt-3 flex min-h-11 flex-wrap items-center justify-between gap-4">
                    {heading && <h1 className="m-0 text-7xl font-semibold text-ink">{heading}</h1>}
                    {action && <div className="ms-auto">{action}</div>}
                </div>
            )}
            {subheading && <p className="mt-1 mb-0 text-muted">{subheading}</p>}
        </Section>
    );
}
