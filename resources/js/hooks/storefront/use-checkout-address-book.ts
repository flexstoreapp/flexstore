import type { InertiaForm } from '@inertiajs/react';
import { useState } from 'react';

import { addressToFormValues, emptyAddress } from '@/lib/checkout-utils';
import type { AddressFormValues, AddressPrefix, CheckoutFormValues, CustomerAddress, RecoveredCheckout } from '@/types';

export type AddressSelection = number | 'new';

interface UseCheckoutAddressBookParams {
    form: InertiaForm<CheckoutFormValues>;
    requiresShipping: boolean;
    storeCountryCode: string;
    savedAddresses: CustomerAddress[];
    recoveredCheckout: RecoveredCheckout | null;
    defaultAddress: CustomerAddress | null;
    initialShipping: AddressFormValues;
    initialBilling: AddressFormValues;
}

export function useCheckoutAddressBook({
    form,
    requiresShipping,
    storeCountryCode,
    savedAddresses,
    recoveredCheckout,
    defaultAddress,
    initialShipping,
    initialBilling,
}: UseCheckoutAddressBookParams) {
    const addressBookPrefix: AddressPrefix = requiresShipping ? 'shipping_address' : 'billing_address';

    const [committedShipping, setCommittedShipping] = useState<AddressFormValues>(initialShipping);
    const [committedBilling, setCommittedBilling] = useState<AddressFormValues>(initialBilling);

    const [selectedAddressId, setSelectedAddressId] = useState<AddressSelection>(() => {
        if (recoveredCheckout) {
            const recoveredId = recoveredCheckout.customer_address_id;

            return recoveredId !== null && savedAddresses.some((address) => address.id === recoveredId)
                ? recoveredId
                : 'new';
        }

        return defaultAddress ? defaultAddress.id : 'new';
    });

    const commitAddress = (prefix: AddressPrefix, address: AddressFormValues) => {
        if (prefix === 'shipping_address') {
            setCommittedShipping(address);
        } else {
            setCommittedBilling(address);
        }
    };

    const setAddressField = (prefix: AddressPrefix, key: keyof AddressFormValues, value: string) => {
        form.setData(prefix, { ...form.data[prefix], [key]: value });
        form.clearErrors(`${prefix}.${key}`);
    };

    const applyToBookPrefix = (values: AddressFormValues) => {
        form.setData(addressBookPrefix, values);
        commitAddress(addressBookPrefix, values);
    };

    const selectSavedAddress = (address: CustomerAddress) => {
        setSelectedAddressId(address.id);
        const values = addressToFormValues(address);

        if (requiresShipping) {
            form.setData((prev) => ({ ...prev, save_shipping_address: false, shipping_address: values }));
        } else {
            form.setData('billing_address', values);
        }

        commitAddress(addressBookPrefix, values);
    };

    const editSavedAddress = (address: CustomerAddress) => {
        setSelectedAddressId('new');
        applyToBookPrefix(addressToFormValues(address));
    };

    const useNewAddress = () => {
        setSelectedAddressId('new');
        applyToBookPrefix({ ...emptyAddress, country_code: storeCountryCode });
    };

    const regionAddress = requiresShipping ? committedShipping : committedBilling;

    return {
        committedShipping,
        committedBilling,
        commitAddress,
        regionAddress,
        selectedAddressId,
        setAddressField,
        selectSavedAddress,
        editSavedAddress,
        useNewAddress,
    };
}
