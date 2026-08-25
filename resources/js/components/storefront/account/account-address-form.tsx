import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

import * as AddressController from '@/actions/App/Http/Controllers/Storefront/AddressController';
import { Button } from '@/components/storefront/button';
import { CheckboxField } from '@/components/storefront/checkbox-field';
import { AddressFields } from '@/components/storefront/checkout/address-fields';
import { useConfirm } from '@/components/storefront/confirm';
import { useUnsavedChangesAlert } from '@/hooks/use-unsaved-changes-alert';
import { __ } from '@/lib/i18n';
import type { AddressFormValues, CustomerAddress } from '@/types';

const PREFIX = 'shipping_address' as const;

type AddressFormData = AddressFormValues & { is_default: boolean };

function initialData(address: CustomerAddress | null): AddressFormData {
    return {
        first_name: address?.first_name ?? '',
        last_name: address?.last_name ?? '',
        address_line_1: address?.address_line_1 ?? '',
        address_line_2: address?.address_line_2 ?? '',
        city: address?.city ?? '',
        state: address?.state ?? '',
        postal_code: address?.postal_code ?? '',
        country_code: address?.country_code ?? '',
        phone: address?.phone ?? '',
        is_default: address?.is_default ?? false,
    };
}

interface AccountAddressFormProps {
    address: CustomerAddress | null;
    onSuccess: () => void;
    onCancel: () => void;
    onDirtyChange?: (dirty: boolean) => void;
}

export function AccountAddressForm({ address, onSuccess, onCancel, onDirtyChange }: AccountAddressFormProps) {
    const form = useForm<AddressFormData>(initialData(address));
    const { confirm } = useConfirm();

    useUnsavedChangesAlert(form.isDirty, { confirm });

    useEffect(() => {
        onDirtyChange?.(form.isDirty);
    }, [form.isDirty, onDirtyChange]);

    const prefixedErrors = Object.fromEntries(
        Object.entries(form.errors).map(([key, message]) => [`${PREFIX}.${key}`, message]),
    );

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess };

        if (address) {
            form.patch(AddressController.update(address.id).url, options);
        } else {
            form.post(AddressController.store().url, options);
        }
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-5">
            <AddressFields
                prefix={PREFIX}
                data={form.data}
                errors={prefixedErrors}
                onChange={(key, value) => form.setData(key, value)}
                onCommit={() => {}}
            />

            <CheckboxField
                id="is_default"
                label={__('Set as default address')}
                checked={form.data.is_default}
                onChange={(checked) => form.setData('is_default', checked)}
            />

            <div className="flex justify-end gap-3">
                <Button variant="outline" size="md" onClick={onCancel}>
                    {__('Cancel')}
                </Button>
                <Button type="submit" size="md" processing={form.processing}>
                    {__('Save address')}
                </Button>
            </div>
        </form>
    );
}
