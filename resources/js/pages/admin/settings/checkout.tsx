import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';

import * as CheckoutSettingController from '@/actions/App/Http/Controllers/Admin/CheckoutSettingController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import { FormSubmit } from '@/components/admin/form-submit';
import { Heading } from '@/components/admin/heading';
import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { Checkbox } from '@/components/ui/checkbox';
import { FieldError } from '@/components/ui/field';
import { HelpBlock } from '@/components/ui/help-block';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { __ } from '@/lib/i18n';
import type { CheckoutSettings } from '@/types';

function ProSetting({
    id,
    label,
    description,
    feature,
    children,
}: {
    id: string;
    label: string;
    description: string;
    feature: string;
    children: React.ReactNode;
}) {
    const { open: openProUpgrade } = useProUpgrade();

    return (
        <div
            className="grid cursor-pointer grid-cols-12 gap-6"
            onClick={() => openProUpgrade(feature)}
            role="presentation"
        >
            <div className="col-span-12 space-y-1 md:col-span-6">
                <Label htmlFor={id} className="flex items-center gap-2 text-sm font-medium">
                    {label}
                    <ProBadge />
                </Label>
                <HelpBlock>{description}</HelpBlock>
            </div>
            <div className="col-span-12 space-y-2 opacity-60 md:col-span-6">{children}</div>
        </div>
    );
}

export default function Checkout({ settings }: { settings: CheckoutSettings }) {
    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.guest_checkout_enabled = data.guest_checkout_enabled === 'on';

        return data;
    };

    return (
        <div className="mx-auto max-w-4xl space-y-6">
            <Heading
                title={__('Checkout')}
                description={__('Checkout behavior and payment links')}
                backHref={SettingController.index()}
            />

            <Form
                {...CheckoutSettingController.update.form()}
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
                                <Label htmlFor="guest-checkout-enabled" className="text-sm font-medium">
                                    {__('Guest checkout')}
                                </Label>
                                <HelpBlock>
                                    {__(
                                        'When enabled, shoppers can complete an order without an account. When disabled, they are asked to sign in before reaching checkout.',
                                    )}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Checkbox
                                    id="guest-checkout-enabled"
                                    name="guest_checkout_enabled"
                                    defaultChecked={settings.guest_checkout_enabled ?? true}
                                />
                                <FieldError>{errors.guest_checkout_enabled}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-12 gap-6">
                            <div className="col-span-12 space-y-1 md:col-span-6">
                                <Label htmlFor="checkout-reservation-minutes" className="text-sm font-medium">
                                    {__('Stock reservation window (minutes)')}
                                </Label>
                                <HelpBlock>
                                    {__(
                                        'How long stock is held for a shopper after they start paying. The reservation is released if payment is not completed within this window.',
                                    )}
                                </HelpBlock>
                            </div>
                            <div className="col-span-12 space-y-2 md:col-span-6">
                                <Input
                                    id="checkout-reservation-minutes"
                                    name="checkout_reservation_minutes"
                                    type="number"
                                    inputMode="numeric"
                                    min="1"
                                    max="1440"
                                    defaultValue={String(settings.checkout_reservation_minutes ?? 10)}
                                />
                                <FieldError>{errors.checkout_reservation_minutes}</FieldError>
                            </div>
                        </div>

                        <Separator />

                        <ProSetting
                            id="checkout-sharing-enabled"
                            label={__('Checkout sharing')}
                            description={__(
                                'When enabled, shoppers can send their checkout to someone else to pay with a shared payment link.',
                            )}
                            feature={__('Shared payment links')}
                        >
                            <Checkbox id="checkout-sharing-enabled" checked={false} disabled />
                        </ProSetting>

                        <Separator />

                        <ProSetting
                            id="checkout-shared-payment-hours"
                            label={__('Shared payment link lifetime (hours)')}
                            description={__('How long a shared payment link stays valid before it expires.')}
                            feature={__('Shared payment links')}
                        >
                            <Input
                                id="checkout-shared-payment-hours"
                                type="number"
                                inputMode="numeric"
                                defaultValue="48"
                                disabled
                            />
                        </ProSetting>

                        <Separator />

                        <ProSetting
                            id="abandoned-checkout-delay-minutes"
                            label={__('Abandoned checkout delay (minutes)')}
                            description={__(
                                'Time after a checkout starts before it is treated as abandoned. Sessions younger than this are not shown in the Abandoned checkouts list and do not receive the recovery email.',
                            )}
                            feature={__('Abandoned checkout recovery')}
                        >
                            <Input
                                id="abandoned-checkout-delay-minutes"
                                type="number"
                                inputMode="numeric"
                                defaultValue="60"
                                disabled
                            />
                        </ProSetting>

                        <Separator />

                        <ProSetting
                            id="abandoned-checkout-recovery-link-lifetime-days"
                            label={__('Recovery link lifetime (days)')}
                            description={__(
                                'How long the recovery link in the email stays valid. Clicking it sets a cart cookie, so do not set this longer than necessary.',
                            )}
                            feature={__('Abandoned checkout recovery')}
                        >
                            <Input
                                id="abandoned-checkout-recovery-link-lifetime-days"
                                type="number"
                                inputMode="numeric"
                                defaultValue="7"
                                disabled
                            />
                        </ProSetting>

                        <FormSubmit processing={processing} recentlySuccessful={recentlySuccessful}>
                            {__('Save changes')}
                        </FormSubmit>
                    </>
                )}
            </Form>
        </div>
    );
}

Checkout.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Checkout'), href: CheckoutSettingController.show() },
    ],
};
