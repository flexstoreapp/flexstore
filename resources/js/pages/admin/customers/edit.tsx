import * as CustomerController from '@/actions/App/Http/Controllers/Admin/CustomerController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import { CustomerForm } from '@/components/admin/customer/customer-form';
import { Heading } from '@/components/admin/heading';
import { useFormatDate } from '@/hooks/use-format-date';
import { __ } from '@/lib/i18n';
import type { Customer, CustomerAddress } from '@/types';

interface CustomerEditProps {
    customer: Customer;
    addresses: CustomerAddress[];
}

export default function CustomerEdit({ customer, addresses }: CustomerEditProps) {
    const formatDate = useFormatDate();

    return (
        <div className="mx-auto max-w-xl space-y-6">
            <Heading
                title={customer.name}
                description={__('Last updated on :datetime', { datetime: formatDate(customer.updated_at) })}
                backHref={CustomerController.index()}
            />

            <CustomerForm customer={customer} addresses={addresses} />
        </div>
    );
}

CustomerEdit.layout = ({ customer }: CustomerEditProps) => ({
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Customers'), href: CustomerController.index() },
        { title: customer.name, href: CustomerController.edit({ customer: customer.id }) },
    ],
});
