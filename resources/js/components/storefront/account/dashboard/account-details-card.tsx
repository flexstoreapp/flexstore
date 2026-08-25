import { Link, usePage } from '@inertiajs/react';

import * as ProfileController from '@/actions/App/Http/Controllers/Storefront/ProfileController';
import { __ } from '@/lib/i18n';
import type { StorefrontSharedData } from '@/types';

export function AccountDetailsCard() {
    const { auth } = usePage<StorefrontSharedData>().props;

    return (
        <section className="rounded-md border border-line bg-surface p-6">
            <div className="flex items-center justify-between gap-3">
                <h2 className="m-0 text-xl font-semibold text-ink">{__('Account details')}</h2>
                <Link
                    href={ProfileController.edit()}
                    className="text-sm font-semibold text-primary underline-offset-2 hover:underline"
                >
                    {__('Edit')}
                </Link>
            </div>
            <dl className="mt-5 flex flex-col gap-4">
                <div className="flex items-center justify-between gap-4">
                    <dt className="shrink-0 text-sm text-muted">{__('Name')}</dt>
                    <dd className="m-0 min-w-0 truncate text-end text-base font-semibold text-ink">
                        {auth.user?.name}
                    </dd>
                </div>
                <div className="flex items-center justify-between gap-4 border-t border-line pt-4">
                    <dt className="shrink-0 text-sm text-muted">{__('Email address')}</dt>
                    <dd className="m-0 min-w-0 truncate text-end text-base text-ink">{auth.user?.email}</dd>
                </div>
                <div className="flex items-center justify-between gap-4 border-t border-line pt-4">
                    <dt className="shrink-0 text-sm text-muted">{__('Password')}</dt>
                    <dd className="m-0 text-end text-base text-ink">••••••••</dd>
                </div>
            </dl>
        </section>
    );
}
