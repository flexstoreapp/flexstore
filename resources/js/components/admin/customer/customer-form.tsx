import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';

import * as CustomerController from '@/actions/App/Http/Controllers/Admin/CustomerController';
import { FormSubmit } from '@/components/admin/form-submit';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { __ } from '@/lib/i18n';
import type { Customer, CustomerAddress } from '@/types';

import { CustomerAddressList } from './customer-address-list';
import { CustomerFormBasicInfo } from './customer-form-basic-info';

interface CustomerFormProps {
    customer?: Customer;
    addresses?: CustomerAddress[];
}

export function CustomerForm({ customer, addresses }: CustomerFormProps) {
    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.add_more = data.add_more === 'on';
        return data;
    };

    return (
        <div className="space-y-6">
            <Form
                {...(customer ? CustomerController.update.form(customer) : CustomerController.store.form())}
                options={{ preserveScroll: true, only: ['customer'] }}
                transform={handleTransform}
                resetOnSuccess={!customer}
                setDefaultsOnSuccess
            >
                {({ processing, errors, recentlySuccessful }) => (
                    <div className="space-y-6">
                        <UnsavedChangesAlert />
                        <CustomerFormBasicInfo customer={customer} errors={errors} />

                        <FormSubmit
                            showAddMore={!customer}
                            processing={processing}
                            recentlySuccessful={recentlySuccessful}
                        >
                            {customer ? __('Update customer') : __('Add customer')}
                        </FormSubmit>
                    </div>
                )}
            </Form>

            {customer && addresses && <CustomerAddressList addresses={addresses} customerId={customer.id} />}
        </div>
    );
}
