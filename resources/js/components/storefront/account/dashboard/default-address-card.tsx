import { Link } from '@inertiajs/react';

import * as AddressController from '@/actions/App/Http/Controllers/Storefront/AddressController';
import { AddressSummary } from '@/components/storefront/account/address-summary';
import { __ } from '@/lib/i18n';
import type { CustomerAddress } from '@/types';

export function DefaultAddressCard({ address }: { address: CustomerAddress | null }) {
    return (
        <section className="rounded-md border border-line bg-surface p-6">
            <div className="flex items-center justify-between gap-3">
                <h2 className="m-0 text-xl font-semibold text-ink">{__('Default address')}</h2>
                <Link
                    href={AddressController.index()}
                    className="text-sm font-semibold text-primary underline-offset-2 hover:underline"
                >
                    {__('Edit')}
                </Link>
            </div>
            {address ? (
                <AddressSummary address={address} className="mt-5" />
            ) : (
                <p className="mt-5 mb-0 text-sm text-muted">{__('No default address saved yet.')}</p>
            )}
        </section>
    );
}
