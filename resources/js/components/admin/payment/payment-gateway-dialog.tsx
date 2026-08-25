import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { AlertCircleIcon } from 'lucide-react';
import { useMemo } from 'react';

import * as PaymentGatewayController from '@/actions/App/Http/Controllers/Admin/PaymentGatewayController';
import { type SelectableBrand } from '@/components/admin/brand-picker';
import { type SelectableItem as CategorySelectableItem } from '@/components/admin/category-picker';
import { ConditionsTabContent } from '@/components/admin/conditions-tab-content';
import { type SelectableItem as ProductSelectableItem } from '@/components/admin/product-picker';
import { type SelectableItem as RegionSelectableItem } from '@/components/admin/region-picker';
import { RestrictionsTabContent } from '@/components/admin/restrictions-tab-content';
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
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useCurrencies } from '@/hooks/admin/use-currencies';
import { __ } from '@/lib/i18n';
import { driverDefaultCurrencies } from '@/lib/payment-currencies';
import { tabHasErrors } from '@/lib/utils';
import type { PaymentGateway, PaymentGatewayDriver } from '@/types';

import { PaymentGatewayGeneralTabContent } from './payment-gateway-general-tab-content';

export type PaymentGatewayDialogTab = 'general' | 'conditions' | 'restrictions';

export interface AdditionalTab {
    value: string;
    label: string;
    fields: string[];
    content: (props: { paymentGateway?: PaymentGateway; errors: Record<string, string> }) => React.ReactNode;
}

interface PaymentGatewayDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    paymentGateway: PaymentGateway | undefined;
    gatewayDriver: PaymentGatewayDriver;
    tabFields: Record<PaymentGatewayDialogTab, string[]>;
    credentialFields?: (props: { paymentGateway?: PaymentGateway; errors: Record<string, string> }) => React.ReactNode;
    additionalTabs?: AdditionalTab[];
}

export function PaymentGatewayDialog({
    open,
    onOpenChange,
    title,
    description,
    tabFields,
    paymentGateway,
    gatewayDriver,
    credentialFields,
    additionalTabs = [],
}: PaymentGatewayDialogProps) {
    const { currencyNames } = useCurrencies();

    const defaultCurrencies = useMemo(
        () =>
            driverDefaultCurrencies[gatewayDriver].map((code) => ({
                code,
                name: currencyNames[code] ? `${currencyNames[code]} (${code})` : code,
            })),
        [gatewayDriver, currencyNames],
    );

    const hasRefundSyncField = additionalTabs.some((tab) => tab.fields.includes('sync_external_refunds'));

    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.excluded_products = data.excluded_products ?? [];
        data.excluded_categories = data.excluded_categories ?? [];
        data.excluded_brands = data.excluded_brands ?? [];
        data.allowed_regions = data.allowed_regions ?? [];
        data.supported_currencies = data.supported_currencies ?? [];
        data.is_active = data.is_active === 'on';

        if (hasRefundSyncField) {
            data.sync_external_refunds = data.sync_external_refunds === 'on';
        }

        const credentials = data.credentials as Record<string, FormDataConvertible> | undefined;
        if (credentials) {
            credentials.sandbox = credentials.sandbox === 'on';
        }

        return data;
    };

    const handleSuccess = () => {
        onOpenChange(false);
    };

    return (
        <AdaptiveDialog open={open} onOpenChange={onOpenChange}>
            <AdaptiveDialogContent>
                <Form
                    {...(paymentGateway
                        ? PaymentGatewayController.update.form({ gateway: paymentGateway.id })
                        : PaymentGatewayController.store.form())}
                    options={{ preserveScroll: true, only: ['paymentGateways'] }}
                    transform={handleTransform}
                    onSuccess={handleSuccess}
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>{title}</AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>{description}</AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <input type="hidden" name="driver" value={gatewayDriver} />

                            <AdaptiveDialogContentContainer>
                                <Tabs defaultValue="general">
                                    <TabsList
                                        className="grid w-full"
                                        style={{
                                            gridTemplateColumns: `repeat(${3 + additionalTabs.length}, minmax(0, 1fr))`,
                                        }}
                                    >
                                        <TabsTrigger value="general">
                                            {__('General')}
                                            {tabHasErrors(tabFields, 'general', errors) && (
                                                <AlertCircleIcon className="ms-1 text-destructive" />
                                            )}
                                        </TabsTrigger>
                                        {additionalTabs.map((tab) => (
                                            <TabsTrigger key={tab.value} value={tab.value}>
                                                {tab.label}
                                                {tab.fields.some((f) => errors[f]) && (
                                                    <AlertCircleIcon className="ms-1 text-destructive" />
                                                )}
                                            </TabsTrigger>
                                        ))}
                                        <TabsTrigger value="conditions">
                                            {__('Conditions')}
                                            {tabHasErrors(tabFields, 'conditions', errors) && (
                                                <AlertCircleIcon className="ms-1 text-destructive" />
                                            )}
                                        </TabsTrigger>
                                        <TabsTrigger value="restrictions">
                                            {__('Restrictions')}
                                            {tabHasErrors(tabFields, 'restrictions', errors) && (
                                                <AlertCircleIcon className="ms-1 text-destructive" />
                                            )}
                                        </TabsTrigger>
                                    </TabsList>

                                    <TabsContent value="general" className="data-[state=inactive]:hidden" forceMount>
                                        <PaymentGatewayGeneralTabContent
                                            paymentGateway={paymentGateway}
                                            fallbackName={title}
                                            errors={errors}
                                            credentialFields={credentialFields}
                                        />
                                    </TabsContent>
                                    {additionalTabs.map((tab) => (
                                        <TabsContent
                                            key={tab.value}
                                            value={tab.value}
                                            className="data-[state=inactive]:hidden"
                                            forceMount
                                        >
                                            {tab.content({ paymentGateway, errors })}
                                        </TabsContent>
                                    ))}
                                    <TabsContent value="conditions" className="data-[state=inactive]:hidden" forceMount>
                                        <ConditionsTabContent entity={paymentGateway} errors={errors} />
                                    </TabsContent>
                                    <TabsContent
                                        value="restrictions"
                                        className="data-[state=inactive]:hidden"
                                        forceMount
                                    >
                                        <RestrictionsTabContent
                                            excludedProducts={
                                                paymentGateway?.excluded_products as ProductSelectableItem[]
                                            }
                                            excludedCategories={
                                                paymentGateway?.excluded_categories as CategorySelectableItem[]
                                            }
                                            excludedBrands={paymentGateway?.excluded_brands as SelectableBrand[]}
                                            allowedRegions={paymentGateway?.allowed_regions as RegionSelectableItem[]}
                                            supportedCurrencies={
                                                paymentGateway?.supported_currencies ?? defaultCurrencies
                                            }
                                            showSupportedCurrencies
                                            errors={errors}
                                        />
                                    </TabsContent>
                                </Tabs>
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose className="order-1 md:order-0" asChild>
                                    <Button variant="ghost">{__('Cancel')}</Button>
                                </AdaptiveDialogClose>
                                <SubmitButton className="order-0 md:order-1" processing={processing}>
                                    {__('Update gateway')}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
