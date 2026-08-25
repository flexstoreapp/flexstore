import { PlusIcon } from 'lucide-react';

import { CheckboxField } from '@/components/storefront/checkbox-field';
import { RadioCard } from '@/components/storefront/radio-card';
import { useCountries } from '@/hooks/use-countries';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { CustomerAddress } from '@/types';

import { AddressFields } from './address-fields';
import { useCheckout } from './checkout-context';

interface AddressBookSectionProps {
    title: string;
    prefix: 'shipping_address' | 'billing_address';
    showSaveOption: boolean;
}

function formatAddressLine(address: CustomerAddress, countryNames: Record<string, string>): string {
    return [
        address.address_line_1,
        address.address_line_2,
        address.city,
        address.state_name ?? address.state,
        address.postal_code,
        countryNames[address.country_code] ?? address.country_code,
    ]
        .filter(Boolean)
        .join(', ');
}

export function AddressBookSection({ title, prefix, showSaveOption }: AddressBookSectionProps) {
    const {
        savedAddresses,
        addressFieldRules,
        errors,
        isAuthenticated,
        selectedAddressId,
        selectSavedAddress,
        editSavedAddress,
        useNewAddress,
        saveShippingAddress,
        setSaveShippingAddress,
        setAddressField,
        commitAddress,
        shippingAddress,
        billingAddress,
    } = useCheckout();
    const { countryNames } = useCountries();

    const headingId = `co-${prefix}-h`;
    const data = prefix === 'shipping_address' ? shippingAddress : billingAddress;
    const showForm = selectedAddressId === 'new' || savedAddresses.length === 0;

    const addressError = Object.entries(errors)
        .filter(([key]) => key === prefix || key.startsWith(`${prefix}.`))
        .map(([, message]) => message)
        .find(Boolean);

    return (
        <section aria-labelledby={headingId} className="rounded-md border border-line bg-surface p-6 lg:p-7">
            <h2 id={headingId} className="m-0 text-xl font-semibold text-ink">
                {title}
            </h2>

            {savedAddresses.length > 0 && (
                <div className="mt-5 flex flex-col gap-3">
                    <div role="radiogroup" aria-labelledby={headingId} className="flex flex-col">
                        {savedAddresses.map((address) => (
                            <RadioCard
                                key={address.id}
                                checked={selectedAddressId === address.id}
                                onSelect={() => selectSavedAddress(address)}
                                align="start"
                                className="rounded-none not-first:-mt-px first:rounded-t-md last:rounded-b-md"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-semibold text-ink">
                                            {address.first_name} {address.last_name}
                                        </span>
                                        {address.is_default && (
                                            <span className="rounded-xs bg-primary-tint px-2 py-0.5 text-2xs font-bold tracking-label text-primary uppercase">
                                                {__('Default')}
                                            </span>
                                        )}
                                    </div>
                                    <p className="mt-1 mb-0 text-sm leading-relaxed text-muted">
                                        {formatAddressLine(address, countryNames)}
                                    </p>
                                    {address.phone && (
                                        <p className="mt-0.5 mb-0 text-sm text-muted">
                                            <bdi>{address.phone}</bdi>
                                        </p>
                                    )}
                                </div>
                                <button
                                    type="button"
                                    onClick={(event) => {
                                        event.stopPropagation();
                                        editSavedAddress(address);
                                    }}
                                    className="shrink-0 text-sm font-semibold text-primary underline-offset-2 transition can-hover:hover:text-primary-hover can-hover:hover:underline"
                                >
                                    {__('Edit')}
                                </button>
                            </RadioCard>
                        ))}
                    </div>

                    <button
                        type="button"
                        onClick={useNewAddress}
                        aria-pressed={selectedAddressId === 'new'}
                        className={cn(
                            'flex w-full items-center gap-4 rounded-md border border-line-strong bg-surface p-4 text-start transition focus-visible:outline-offset-0',
                            selectedAddressId === 'new' && 'border-primary bg-primary-tint/50',
                        )}
                    >
                        <span
                            aria-hidden="true"
                            className={cn(
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-surface-2 transition',
                                selectedAddressId === 'new' && 'bg-primary text-white',
                            )}
                        >
                            <PlusIcon size={16} />
                        </span>
                        <span className={cn('font-semibold text-ink', selectedAddressId === 'new' && 'text-primary')}>
                            {__('Add new address')}
                        </span>
                    </button>
                </div>
            )}

            {!showForm && addressError && (
                <p role="alert" className="mt-3 mb-0 text-sm text-error">
                    {addressError}
                </p>
            )}

            {showForm && (
                <div className={savedAddresses.length > 0 ? 'mt-5 border-t border-line pt-4' : 'mt-5'}>
                    <AddressFields
                        prefix={prefix}
                        data={data}
                        errors={errors}
                        onChange={(key, value) => setAddressField(prefix, key, value)}
                        onCommit={(address) => commitAddress(prefix, address)}
                        addressFieldRules={addressFieldRules}
                    />
                    {showSaveOption && isAuthenticated && (
                        <div className="mt-4">
                            <CheckboxField
                                id="co-save-addr"
                                label={__('Save this address to my account')}
                                checked={saveShippingAddress}
                                onChange={setSaveShippingAddress}
                            />
                        </div>
                    )}
                </div>
            )}
        </section>
    );
}
