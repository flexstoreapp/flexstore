import { useEffect } from 'react';

import { PhoneField } from '@/components/storefront/phone-field';
import { SelectField } from '@/components/storefront/select-field';
import { TextField } from '@/components/storefront/text-field';
import { useAddressFieldRules } from '@/hooks/use-address-field-rules';
import { useCountries } from '@/hooks/use-countries';
import { __ } from '@/lib/i18n';
import type { AddressFieldRules, AddressFormValues, TranslationKey } from '@/types';

interface AddressFieldsProps {
    prefix: 'billing_address' | 'shipping_address';
    data: AddressFormValues;
    errors: Record<string, string>;
    onChange: (key: keyof AddressFormValues, value: string) => void;
    onCommit: (address: AddressFormValues) => void;
    addressFieldRules?: AddressFieldRules;
}

export function AddressFields({ prefix, data, errors, onChange, onCommit, addressFieldRules }: AddressFieldsProps) {
    const { countryOptions } = useCountries();
    const { fieldRules } = useAddressFieldRules(data.country_code, addressFieldRules);
    const field = (name: string) => `${prefix}.${name}`;
    const autofillSection = prefix === 'shipping_address' ? 'shipping' : 'billing';
    const autofill = (token: string) => `${autofillSection} ${token}`;

    const stateOptions = fieldRules.state.options;
    const stateHidden = fieldRules.state.hidden;
    const postalCodeHidden = fieldRules.postal_code.hidden;

    useEffect(() => {
        if (postalCodeHidden && data.postal_code) onChange('postal_code', '');
        if (stateHidden && data.state) onChange('state', '');
        if (!stateHidden && stateOptions && data.state && !stateOptions.some((option) => option.value === data.state)) {
            onChange('state', '');
        }
    }, [stateHidden, postalCodeHidden, stateOptions, data.state, data.postal_code, onChange]);

    const cityLabel = __(fieldRules.city.label as TranslationKey);
    const stateLabel = __(fieldRules.state.label as TranslationKey);
    const postalLabel = __(fieldRules.postal_code.label as TranslationKey);

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <SelectField
                id={field('country_code')}
                label={__('Country')}
                value={data.country_code}
                onChange={(value) => {
                    onChange('country_code', value);
                    onCommit({ ...data, country_code: value });
                }}
                options={countryOptions}
                placeholder={__('Select a country')}
                error={errors[field('country_code')]}
                autoComplete={autofill('country')}
                className="sm:col-span-2"
            />

            <TextField
                id={field('first_name')}
                label={__('First name')}
                value={data.first_name}
                onChange={(value) => onChange('first_name', value)}
                error={errors[field('first_name')]}
                autoComplete={autofill('given-name')}
            />
            <TextField
                id={field('last_name')}
                label={__('Last name')}
                value={data.last_name}
                onChange={(value) => onChange('last_name', value)}
                error={errors[field('last_name')]}
                autoComplete={autofill('family-name')}
            />

            <TextField
                id={field('address_line_1')}
                label={__('Street address')}
                value={data.address_line_1}
                onChange={(value) => onChange('address_line_1', value)}
                error={errors[field('address_line_1')]}
                autoComplete={autofill('address-line1')}
                className="sm:col-span-2"
            />
            <TextField
                id={field('address_line_2')}
                label={__('Apartment, suite, etc.')}
                value={data.address_line_2}
                onChange={(value) => onChange('address_line_2', value)}
                error={errors[field('address_line_2')]}
                autoComplete={autofill('address-line2')}
                optional
                className="sm:col-span-2"
            />

            <div className="flex flex-col gap-4 sm:col-span-2 sm:flex-row sm:items-start">
                <TextField
                    id={field('city')}
                    label={cityLabel}
                    value={data.city}
                    onChange={(value) => onChange('city', value)}
                    onBlur={() => onCommit(data)}
                    error={errors[field('city')]}
                    autoComplete={autofill('address-level2')}
                    className="min-w-0 sm:flex-1"
                />
                {!stateHidden &&
                    (stateOptions ? (
                        <SelectField
                            id={field('state')}
                            label={stateLabel}
                            value={data.state}
                            onChange={(value) => {
                                onChange('state', value);
                                onCommit({ ...data, state: value });
                            }}
                            options={stateOptions}
                            placeholder={__('Select a :field', { field: stateLabel })}
                            error={errors[field('state')]}
                            className="min-w-0 sm:flex-1"
                        />
                    ) : (
                        <TextField
                            id={field('state')}
                            label={stateLabel}
                            value={data.state}
                            onChange={(value) => onChange('state', value)}
                            onBlur={() => onCommit(data)}
                            error={errors[field('state')]}
                            autoComplete={autofill('address-level1')}
                            className="min-w-0 sm:flex-1"
                        />
                    ))}
                {!postalCodeHidden && (
                    <TextField
                        id={field('postal_code')}
                        label={postalLabel}
                        value={data.postal_code}
                        onChange={(value) => onChange('postal_code', value)}
                        onBlur={() => onCommit(data)}
                        error={errors[field('postal_code')]}
                        inputMode="numeric"
                        autoComplete={autofill('postal-code')}
                        className="min-w-0 sm:flex-1"
                    />
                )}
            </div>

            <PhoneField
                id={field('phone')}
                label={__('Phone')}
                value={data.phone}
                onChange={(value) => onChange('phone', value)}
                defaultCountry={data.country_code}
                error={errors[field('phone')]}
                autoComplete={autofill('tel')}
                className="sm:col-span-2"
            />
        </div>
    );
}
