import { AddressLines } from '@/components/storefront/address-lines';
import type { OrderContactAddress } from '@/types';

export function AddressCard({ title, address }: { title: string; address: OrderContactAddress }) {
    return (
        <section className="rounded-md border border-line bg-surface p-6">
            <h2 className="m-0 mb-4 text-xl font-semibold text-ink">{title}</h2>
            <AddressLines address={address} />
        </section>
    );
}
