import { Link } from '@inertiajs/react';
import { Fragment, type ComponentProps } from 'react';

import { __ } from '@/lib/i18n';
type Href = ComponentProps<typeof Link>['href'];

export interface Crumb {
    label: string;
    href?: Href;
}

export function Breadcrumb({ crumbs }: { crumbs: Crumb[] }) {
    return (
        <nav aria-label={__('Breadcrumb')}>
            <ol className="m-0 flex list-none flex-wrap items-center gap-2 p-0 text-sm text-muted">
                {crumbs.map((crumb, index) => {
                    const isLast = index === crumbs.length - 1;

                    return (
                        <Fragment key={index}>
                            {index > 0 && <li aria-hidden="true">/</li>}
                            {crumb.href && !isLast ? (
                                <li>
                                    <Link href={crumb.href} className="transition-colors hover:text-primary">
                                        {crumb.label}
                                    </Link>
                                </li>
                            ) : (
                                <li aria-current={isLast ? 'page' : undefined} className="font-medium text-ink">
                                    {crumb.label}
                                </li>
                            )}
                        </Fragment>
                    );
                })}
            </ol>
        </nav>
    );
}
