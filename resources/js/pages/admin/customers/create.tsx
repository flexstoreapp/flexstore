import * as CustomerController from '@/actions/App/Http/Controllers/Admin/CustomerController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import { CustomerForm } from '@/components/admin/customer/customer-form';
import { Heading } from '@/components/admin/heading';
import { __ } from '@/lib/i18n';

export default function CustomerCreate() {
    return (
        <div className="mx-auto max-w-xl space-y-6">
            <Heading
                title={__('Add customer')}
                description={__('Add a new customer account')}
                backHref={CustomerController.index()}
            />
            <CustomerForm />
        </div>
    );
}

CustomerCreate.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Customers'), href: CustomerController.index() },
        { title: __('Add customer'), href: CustomerController.create() },
    ],
};
