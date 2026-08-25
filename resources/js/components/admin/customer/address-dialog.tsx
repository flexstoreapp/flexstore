import { type FormDataConvertible, type Method } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type * as RPNInput from 'react-phone-number-input';

import { SubmitButton } from '@/components/admin/submit-button';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import {
    AdaptiveDialog,
    AdaptiveDialogClose,
    AdaptiveDialogContent,
    AdaptiveDialogContentContainer,
    AdaptiveDialogDescription,
    AdaptiveDialogFooter,
    AdaptiveDialogHeader,
    AdaptiveDialogTitle,
} from '@/components/ui/adaptive-dialog';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { PhoneInput } from '@/components/ui/phone-input';
import { useAddressFieldRules } from '@/hooks/use-address-field-rules';
import { useCountries } from '@/hooks/use-countries';
import { useFormatDate } from '@/hooks/use-format-date';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { CustomerAddress, TranslationKey } from '@/types';

const LOCALITY_COLUMNS: Record<number, string> = {
    1: 'md:grid-cols-1',
    2: 'md:grid-cols-2',
    3: 'md:grid-cols-3',
};

interface AddressDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    address?: CustomerAddress | null;
    formAction: { action: string; method: Method };
    formOptions?: Record<string, unknown>;
    resetOnSuccess?: boolean | string[];
}

export function AddressDialog({
    open,
    onOpenChange,
    address,
    formAction,
    formOptions,
    resetOnSuccess,
}: AddressDialogProps) {
    const { countryOptions } = useCountries();
    const formatDate = useFormatDate();
    const [countryCode, setCountryCode] = useState(address?.country_code ?? '');
    const [state, setState] = useState(address?.state ?? '');
    const [phone, setPhone] = useState(address?.phone ?? '');
    const [prevOpen, setPrevOpen] = useState(open);
    const [prevAddressId, setPrevAddressId] = useState(address?.id);
    const { fieldRules } = useAddressFieldRules(countryCode);

    if (open !== prevOpen || address?.id !== prevAddressId) {
        setPrevOpen(open);
        setPrevAddressId(address?.id);
        if (open) {
            setCountryCode(address?.country_code ?? '');
            setState(address?.state ?? '');
            setPhone(address?.phone ?? '');
        }
    }

    const handleCountryChange = (value: string) => {
        setCountryCode(value);
        setState('');
    };

    const localityColumns =
        LOCALITY_COLUMNS[1 + (fieldRules.state.hidden ? 0 : 1) + (fieldRules.postal_code.hidden ? 0 : 1)];

    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.is_default = data.is_default === 'on';
        return data;
    };

    const handleClose = () => {
        onOpenChange(false);
        setCountryCode('');
        setState('');
        setPhone('');
    };

    return (
        <AdaptiveDialog open={open} onOpenChange={handleClose}>
            <AdaptiveDialogContent>
                <Form
                    {...formAction}
                    options={{ preserveScroll: true, ...formOptions }}
                    transform={handleTransform}
                    onSuccess={handleClose}
                    resetOnSuccess={resetOnSuccess}
                >
                    {({ processing, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>
                                    {address ? __('Edit address') : __('Add address')}
                                </AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>
                                    {address
                                        ? __('Last updated on :datetime', {
                                              datetime: formatDate(address.updated_at),
                                          })
                                        : __('Add new address')}
                                </AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <AdaptiveDialogContentContainer>
                                <Field>
                                    <FieldLabel htmlFor="address-country">{__('Country')}</FieldLabel>
                                    <AdaptiveSelect
                                        id="address-country"
                                        name="country_code"
                                        value={countryCode}
                                        onValueChange={handleCountryChange}
                                        placeholder={__('Select a country')}
                                        options={countryOptions}
                                        search
                                    />
                                    <FieldError>{errors.country_code}</FieldError>
                                </Field>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <Field>
                                        <FieldLabel htmlFor="address-first-name">{__('First name')}</FieldLabel>
                                        <Input
                                            id="address-first-name"
                                            name="first_name"
                                            defaultValue={address?.first_name ?? ''}
                                            required
                                        />
                                        <FieldError>{errors.first_name}</FieldError>
                                    </Field>
                                    <Field>
                                        <FieldLabel htmlFor="address-last-name">{__('Last name')}</FieldLabel>
                                        <Input
                                            id="address-last-name"
                                            name="last_name"
                                            defaultValue={address?.last_name ?? ''}
                                            required
                                        />
                                        <FieldError>{errors.last_name}</FieldError>
                                    </Field>
                                </div>
                                <Field>
                                    <FieldLabel htmlFor="address-line-1">{__('Street address')}</FieldLabel>
                                    <Input
                                        id="address-line-1"
                                        name="address_line_1"
                                        defaultValue={address?.address_line_1 ?? ''}
                                        required
                                    />
                                    <FieldError>{errors.address_line_1}</FieldError>
                                </Field>
                                <Field>
                                    <FieldLabel htmlFor="address-line-2">{__('Apartment, suite, etc.')}</FieldLabel>
                                    <Input
                                        id="address-line-2"
                                        name="address_line_2"
                                        defaultValue={address?.address_line_2 ?? ''}
                                    />
                                    <FieldError>{errors.address_line_2}</FieldError>
                                </Field>
                                <div className={cn('grid grid-cols-1 gap-4', localityColumns)}>
                                    <Field>
                                        <FieldLabel htmlFor="address-city">
                                            {__(fieldRules.city.label as TranslationKey)}
                                        </FieldLabel>
                                        <Input
                                            id="address-city"
                                            name="city"
                                            defaultValue={address?.city ?? ''}
                                            required
                                        />
                                        <FieldError>{errors.city}</FieldError>
                                    </Field>
                                    {!fieldRules.state.hidden && (
                                        <Field>
                                            <FieldLabel htmlFor="address-state">
                                                {__(fieldRules.state.label as TranslationKey)}
                                            </FieldLabel>
                                            {fieldRules.state.options ? (
                                                <AdaptiveSelect
                                                    id="address-state"
                                                    name="state"
                                                    value={state}
                                                    onValueChange={setState}
                                                    placeholder={__('Select a :field', {
                                                        field: __(
                                                            fieldRules.state.label as TranslationKey,
                                                        ).toLowerCase(),
                                                    })}
                                                    options={fieldRules.state.options}
                                                    search
                                                />
                                            ) : (
                                                <Input
                                                    id="address-state"
                                                    name="state"
                                                    value={state}
                                                    onChange={(e) => setState(e.target.value)}
                                                />
                                            )}
                                            <FieldError>{errors.state}</FieldError>
                                        </Field>
                                    )}
                                    {!fieldRules.postal_code.hidden && (
                                        <Field>
                                            <FieldLabel htmlFor="address-postal_code">
                                                {__(fieldRules.postal_code.label as TranslationKey)}
                                            </FieldLabel>
                                            <Input
                                                id="address-postal_code"
                                                name="postal_code"
                                                defaultValue={address?.postal_code ?? ''}
                                                required
                                            />
                                            <FieldError>{errors.postal_code}</FieldError>
                                        </Field>
                                    )}
                                </div>
                                <Field>
                                    <FieldLabel htmlFor="address-phone">{__('Phone')}</FieldLabel>
                                    <PhoneInput
                                        id="address-phone"
                                        name="phone"
                                        defaultCountry={countryCode as RPNInput.Country}
                                        value={phone}
                                        onChange={(value) => setPhone(value ?? '')}
                                    />
                                    <FieldError>{errors.phone}</FieldError>
                                </Field>
                                <Field orientation="horizontal">
                                    <Checkbox
                                        id="address-is-default"
                                        name="is_default"
                                        defaultChecked={address?.is_default ?? false}
                                    />
                                    <FieldLabel htmlFor="address-is-default">{__('Set as default address')}</FieldLabel>
                                </Field>
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose className="order-1 md:order-0" asChild>
                                    <Button variant="ghost">{__('Cancel')}</Button>
                                </AdaptiveDialogClose>
                                <SubmitButton className="order-0 md:order-1" processing={processing}>
                                    {address ? __('Update address') : __('Add address')}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
