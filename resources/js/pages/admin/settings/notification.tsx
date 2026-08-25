import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';

import * as NotificationSettingController from '@/actions/App/Http/Controllers/Admin/NotificationSettingController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import { FormSubmit } from '@/components/admin/form-submit';
import { Heading } from '@/components/admin/heading';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { CheckboxCard } from '@/components/ui/checkbox-card';
import { Field, FieldError } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { __ } from '@/lib/i18n';
import type { NotificationSettings } from '@/types';

export default function Notification({ settings }: { settings: NotificationSettings }) {
    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.notification_admin_new_order = data.notification_admin_new_order === 'on';
        data.notification_admin_order_canceled = data.notification_admin_order_canceled === 'on';
        data.notification_admin_low_stock = data.notification_admin_low_stock === 'on';
        data.notification_admin_new_customer = data.notification_admin_new_customer === 'on';
        data.notification_admin_new_review = data.notification_admin_new_review === 'on';
        data.notification_customer_order_confirmed = data.notification_customer_order_confirmed === 'on';
        data.notification_customer_abandoned_checkout = data.notification_customer_abandoned_checkout === 'on';
        return data;
    };

    return (
        <div className="mx-auto max-w-4xl space-y-6">
            <Heading
                title={__('Notification')}
                description={__('Configure email notifications')}
                backHref={SettingController.index()}
            />

            <Form
                {...NotificationSettingController.update.form()}
                options={{ preserveScroll: true, only: ['settings'] }}
                transform={handleTransform}
                setDefaultsOnSuccess
                className="mb-6 space-y-12"
            >
                {({ processing, recentlySuccessful, errors }) => (
                    <>
                        <UnsavedChangesAlert />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label className="text-sm font-medium">{__('Admin notifications')}</Label>
                                <HelpBlock>{__('Email notifications sent to the store email address')}</HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-6 md:col-span-6">
                                <Field>
                                    <CheckboxCard
                                        id="notification-admin-new-order"
                                        name="notification_admin_new_order"
                                        label={__('New order')}
                                        description={__('Notify when a new order is placed')}
                                        defaultChecked={settings.notification_admin_new_order ?? true}
                                    />
                                    <FieldError>{errors.notification_admin_new_order}</FieldError>
                                </Field>

                                <Field>
                                    <CheckboxCard
                                        id="notification-admin-order-canceled"
                                        name="notification_admin_order_canceled"
                                        label={__('Order canceled')}
                                        description={__('Notify when an order is canceled')}
                                        defaultChecked={settings.notification_admin_order_canceled ?? true}
                                    />
                                    <FieldError>{errors.notification_admin_order_canceled}</FieldError>
                                </Field>

                                <Field>
                                    <CheckboxCard
                                        id="notification-admin-low-stock"
                                        name="notification_admin_low_stock"
                                        label={__('Low stock')}
                                        description={__('Notify when a product reaches the low stock threshold')}
                                        defaultChecked={settings.notification_admin_low_stock ?? true}
                                    />
                                    <FieldError>{errors.notification_admin_low_stock}</FieldError>
                                </Field>

                                <Field>
                                    <CheckboxCard
                                        id="notification-admin-new-customer"
                                        name="notification_admin_new_customer"
                                        label={__('New customer')}
                                        description={__('Notify when a new customer registers')}
                                        defaultChecked={settings.notification_admin_new_customer ?? false}
                                    />
                                    <FieldError>{errors.notification_admin_new_customer}</FieldError>
                                </Field>

                                <Field>
                                    <CheckboxCard
                                        id="notification-admin-new-review"
                                        name="notification_admin_new_review"
                                        label={__('New review')}
                                        description={__('Notify when a new product review is submitted')}
                                        defaultChecked={settings.notification_admin_new_review ?? true}
                                    />
                                    <FieldError>{errors.notification_admin_new_review}</FieldError>
                                </Field>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label className="text-sm font-medium">{__('Customer notifications')}</Label>
                                <HelpBlock>{__('Email notifications sent to customers')}</HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-6 md:col-span-6">
                                <Field>
                                    <CheckboxCard
                                        id="notification-customer-order-confirmed"
                                        name="notification_customer_order_confirmed"
                                        label={__('Order confirmed')}
                                        description={__('Send order confirmation email after checkout')}
                                        defaultChecked={settings.notification_customer_order_confirmed ?? true}
                                    />
                                    <FieldError>{errors.notification_customer_order_confirmed}</FieldError>
                                </Field>

                                <Field>
                                    <CheckboxCard
                                        id="notification-customer-abandoned-checkout"
                                        name="notification_customer_abandoned_checkout"
                                        label={__('Abandoned checkout recovery')}
                                        description={__('Send a recovery email when checkout is abandoned')}
                                        defaultChecked={settings.notification_customer_abandoned_checkout ?? true}
                                    />
                                    <FieldError>{errors.notification_customer_abandoned_checkout}</FieldError>
                                </Field>
                            </div>
                        </div>

                        <FormSubmit processing={processing} recentlySuccessful={recentlySuccessful}>
                            {__('Save changes')}
                        </FormSubmit>
                    </>
                )}
            </Form>
        </div>
    );
}

Notification.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Notification'), href: NotificationSettingController.show() },
    ],
};
