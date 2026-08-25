import { AddressLines } from '@/components/storefront/address-lines';
import type { CustomerAddress } from '@/types';

interface AddressSummaryProps {
    address: CustomerAddress;
    className?: string;
}

export function AddressSummary({ address, className }: AddressSummaryProps) {
    return <AddressLines address={{ ...address, state: address.state_name ?? address.state }} className={className} />;
}
