import { useEffect, useRef, useState } from 'react';
import type * as RPNInput from 'react-phone-number-input';

import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { PhoneInput } from '@/components/ui/phone-input';
import { useIsDesktop } from '@/hooks/admin/use-desktop';
import { useAddressFieldRules } from '@/hooks/use-address-field-rules';
import { useCountries } from '@/hooks/use-countries';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { OrderAddress, OrderAddressType, TranslationKey } from '@/types';

const LOCALITY_COLUMNS: Record<number, string> = {
    1: 'md:grid-cols-1',
    2: 'md:grid-cols-2',
    3: 'md:grid-cols-3',
};

interface OrderFormAddressProps {
    type: OrderAddressType;
    address: OrderAddress | null;
    onAddressChange: (address: OrderAddress) => void;
    differentBillingAddress?: boolean;
    onDifferentBillingAddressChange?: (value: boolean) => void;
    primary?: boolean;
    errors: Record<string, string>;
}

export function OrderFormAddress({
    type,
    address,
    onAddressChange,
    differentBillingAddress,
    onDifferentBillingAddressChange,
    primary = false,
    errors,
}: OrderFormAddressProps) {
    const { countryOptions } = useCountries();
    const isDesktop = useIsDesktop();
    const [countryCode, setCountryCode] = useState(address?.country_code ?? '');
    const [state, setState] = useState(address?.state ?? '');
    const [phone, setPhone] = useState(address?.phone ?? '');
    const { fieldRules } = useAddressFieldRules(countryCode);
    const localityColumns =
        LOCALITY_COLUMNS[1 + (fieldRules.state.hidden ? 0 : 1) + (fieldRules.postal_code.hidden ? 0 : 1)];

    const addressFormRef = useRef<HTMLDivElement | null>(null);
    const prevDifferentBillingRef = useRef(differentBillingAddress);

    useEffect(() => {
        const prev = prevDifferentBillingRef.current;
        prevDifferentBillingRef.current = differentBillingAddress;

        if (!prev && differentBillingAddress && isDesktop) {
            addressFormRef.current?.scrollIntoView({ behavior: 'smooth' });
        }
    }, [differentBillingAddress, isDesktop]);

    const handleFieldChange = (field: keyof OrderAddress, value: string) => {
        const updatedAddress = { ...address, [field]: value };
        onAddressChange(updatedAddress as OrderAddress);
    };

    const renderAddressFields = () => (
        <CardContent ref={addressFormRef} className="space-y-6">
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                    <FieldLabel htmlFor={`${type}-country-code`}>{__('Country')}</FieldLabel>
                    <AdaptiveSelect
                        id={`${type}-country-code`}
                        name={`${type}_address[country_code]`}
                        value={countryCode}
                        onValueChange={(value) => {
                            setCountryCode(value);
                            handleFieldChange('country_code', value);
                            setState('');
                            handleFieldChange('state', '');
                        }}
                        placeholder={__('Select a country')}
                        options={countryOptions}
                        search
                    />
                    <FieldError>{errors[`${type}_address.country_code`]}</FieldError>
                </Field>
            </div>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                    <FieldLabel htmlFor={`${type}-first-name`}>{__('First name')}</FieldLabel>
                    <Input
                        id={`${type}-first-name`}
                        name={`${type}_address[first_name]`}
                        defaultValue={address?.first_name ?? ''}
                        onBlur={(e) => handleFieldChange('first_name', e.target.value)}
                    />
                    <FieldError>{errors[`${type}_address.first_name`]}</FieldError>
                </Field>
                <Field>
                    <FieldLabel htmlFor={`${type}-last-name`}>{__('Last name')}</FieldLabel>
                    <Input
                        id={`${type}-last-name`}
                        name={`${type}_address[last_name]`}
                        defaultValue={address?.last_name ?? ''}
                        onBlur={(e) => handleFieldChange('last_name', e.target.value)}
                    />
                    <FieldError>{errors[`${type}_address.last_name`]}</FieldError>
                </Field>
            </div>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                    <FieldLabel htmlFor={`${type}-address-line-1`}>{__('Street address')}</FieldLabel>
                    <Input
                        id={`${type}-address-line-1`}
                        name={`${type}_address[address_line_1]`}
                        defaultValue={address?.address_line_1 ?? ''}
                        onBlur={(e) => handleFieldChange('address_line_1', e.target.value)}
                    />
                    <FieldError>{errors[`${type}_address.address_line_1`]}</FieldError>
                </Field>
                <Field>
                    <FieldLabel htmlFor={`${type}-address-line-2`}>{__('Apartment, suite, etc.')}</FieldLabel>
                    <Input
                        id={`${type}-address-line-2`}
                        name={`${type}_address[address_line_2]`}
                        defaultValue={address?.address_line_2 ?? ''}
                        onBlur={(e) => handleFieldChange('address_line_2', e.target.value)}
                    />
                    <FieldError>{errors[`${type}_address.address_line_2`]}</FieldError>
                </Field>
            </div>
            <div className={cn('grid grid-cols-1 gap-4', localityColumns)}>
                <Field>
                    <FieldLabel htmlFor={`${type}-city`}>{__(fieldRules.city.label as TranslationKey)}</FieldLabel>
                    <Input
                        id={`${type}-city`}
                        name={`${type}_address[city]`}
                        defaultValue={address?.city ?? ''}
                        onBlur={(e) => handleFieldChange('city', e.target.value)}
                    />
                    <FieldError>{errors[`${type}_address.city`]}</FieldError>
                </Field>
                {!fieldRules.state.hidden && (
                    <Field>
                        <FieldLabel htmlFor={`${type}-state`}>
                            {__(fieldRules.state.label as TranslationKey)}
                        </FieldLabel>
                        {fieldRules.state.options ? (
                            <AdaptiveSelect
                                id={`${type}-state`}
                                name={`${type}_address[state]`}
                                value={state}
                                onValueChange={(value) => {
                                    setState(value);
                                    handleFieldChange('state', value);
                                }}
                                placeholder={__('Select a :field', {
                                    field: __(fieldRules.state.label as TranslationKey),
                                })}
                                options={fieldRules.state.options}
                                search
                            />
                        ) : (
                            <Input
                                id={`${type}-state`}
                                name={`${type}_address[state]`}
                                value={state}
                                onChange={(e) => setState(e.target.value)}
                                onBlur={(e) => handleFieldChange('state', e.target.value)}
                            />
                        )}
                        <FieldError>{errors[`${type}_address.state`]}</FieldError>
                    </Field>
                )}
                {!fieldRules.postal_code.hidden && (
                    <Field>
                        <FieldLabel htmlFor={`${type}-postal_code`}>
                            {__(fieldRules.postal_code.label as TranslationKey)}
                        </FieldLabel>
                        <Input
                            id={`${type}-postal_code`}
                            name={`${type}_address[postal_code]`}
                            defaultValue={address?.postal_code ?? ''}
                            onBlur={(e) => handleFieldChange('postal_code', e.target.value)}
                        />
                        <FieldError>{errors[`${type}_address.postal_code`]}</FieldError>
                    </Field>
                )}
            </div>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                    <FieldLabel htmlFor={`${type}-phone`}>{__('Phone number')}</FieldLabel>
                    <PhoneInput
                        id={`${type}-phone`}
                        name={`${type}_address[phone]`}
                        defaultCountry={countryCode as RPNInput.Country}
                        value={phone}
                        onChange={(value) => {
                            setPhone(value ?? '');
                            handleFieldChange('phone', value ?? '');
                        }}
                    />
                    <FieldError>{errors[`${type}_address.phone`]}</FieldError>
                </Field>
            </div>
        </CardContent>
    );

    return (
        <Card>
            <CardHeader className="max-sm:has-data-[slot=card-action]:grid-cols-1">
                <CardTitle>{type === 'billing' ? __('Billing address') : __('Shipping address')}</CardTitle>
                <CardDescription>
                    {type === 'billing'
                        ? __('Enter the billing details for the order')
                        : __('Enter a separate delivery address')}
                </CardDescription>

                {type === 'billing' && !primary && (
                    <CardAction className="max-sm:col-start-1 max-sm:row-span-1 max-sm:row-start-3 max-sm:mt-1 max-sm:justify-self-start sm:self-center">
                        <Field orientation="horizontal" className="w-auto shrink-0">
                            <Checkbox
                                id="different-billing-address"
                                checked={differentBillingAddress}
                                onCheckedChange={(checked) => onDifferentBillingAddressChange?.(checked === true)}
                            />
                            <FieldLabel htmlFor="different-billing-address">{__('Use a different address')}</FieldLabel>
                        </Field>
                    </CardAction>
                )}
            </CardHeader>

            {(type === 'shipping' || primary || differentBillingAddress) && renderAddressFields()}
        </Card>
    );
}
