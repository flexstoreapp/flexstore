import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { AlertCircleIcon } from 'lucide-react';

import * as ShippingRateController from '@/actions/App/Http/Controllers/Admin/ShippingRateController';
import { type SelectableBrand } from '@/components/admin/brand-picker';
import { type SelectableItem as CategorySelectableItem } from '@/components/admin/category-picker';
import { ConditionsTabContent } from '@/components/admin/conditions-tab-content';
import { type SelectableItem as ProductSelectableItem } from '@/components/admin/product-picker';
import { RestrictionsTabContent } from '@/components/admin/restrictions-tab-content';
import { ShippingRateGeneralTabContent } from '@/components/admin/shipping/shipping-rate-general-tab-content';
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
import { useFormatDate } from '@/hooks/use-format-date';
import { __ } from '@/lib/i18n';
import { tabHasErrors } from '@/lib/utils';
import type { Region, ShippingCarrier, ShippingRate } from '@/types';

type ShippingRateDialogTab = 'general' | 'conditions' | 'restrictions';

const tabFields: Record<
    ShippingRateDialogTab,
    (keyof ShippingRate | 'excluded_products.0' | 'excluded_categories.0' | 'excluded_brands.0')[]
> = {
    general: ['region_id', 'shipping_carrier_id', 'name', 'type', 'rate', 'delivery_time', 'is_active'],
    conditions: [
        'min_order_value',
        'max_order_value',
        'min_weight',
        'min_weight_unit',
        'max_weight',
        'max_weight_unit',
    ],
    restrictions: [
        'excluded_products',
        'excluded_products.0',
        'excluded_categories',
        'excluded_categories.0',
        'excluded_brands',
        'excluded_brands.0',
    ],
};

interface ShippingRateDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    shippingCarriers: ShippingCarrier[];
    region?: Region;
    shippingRate?: ShippingRate;
}

export function ShippingRateDialog({
    open,
    onOpenChange,
    shippingCarriers,
    region,
    shippingRate,
}: ShippingRateDialogProps) {
    const formatDate = useFormatDate();

    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.excluded_products = data.excluded_products ?? [];
        data.excluded_categories = data.excluded_categories ?? [];
        data.excluded_brands = data.excluded_brands ?? [];
        data.is_active = data.is_active === 'on';

        return data;
    };

    return (
        <AdaptiveDialog open={open} onOpenChange={onOpenChange}>
            <AdaptiveDialogContent>
                <Form
                    {...(shippingRate
                        ? ShippingRateController.update.form(shippingRate)
                        : ShippingRateController.store.form())}
                    method={shippingRate ? 'patch' : 'post'}
                    options={{ preserveScroll: true, only: ['shippingCarriers', 'shippingRates'] }}
                    transform={handleTransform}
                    onSuccess={() => onOpenChange(false)}
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>
                                    {shippingRate ? __('Edit shipping rate') : __('Add shipping rate')}
                                </AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>
                                    {shippingRate
                                        ? __('Last updated on :datetime', {
                                              datetime: formatDate(shippingRate.updated_at),
                                          })
                                        : __('Add a new shipping rate to your store')}
                                </AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <AdaptiveDialogContentContainer>
                                <Tabs defaultValue="general">
                                    <TabsList className="grid w-full grid-cols-3">
                                        <TabsTrigger value="general">
                                            {__('General')}
                                            {tabHasErrors(tabFields, 'general', errors) && (
                                                <AlertCircleIcon className="ms-1 text-destructive" />
                                            )}
                                        </TabsTrigger>
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
                                        <ShippingRateGeneralTabContent
                                            key={shippingRate?.id ?? 'new'}
                                            region={region ?? shippingRate?.region}
                                            shippingCarriers={shippingCarriers}
                                            shippingRate={shippingRate}
                                            errors={errors}
                                        />
                                    </TabsContent>
                                    <TabsContent value="conditions" className="data-[state=inactive]:hidden" forceMount>
                                        <ConditionsTabContent entity={shippingRate} errors={errors} />
                                    </TabsContent>
                                    <TabsContent
                                        value="restrictions"
                                        className="data-[state=inactive]:hidden"
                                        forceMount
                                    >
                                        <RestrictionsTabContent
                                            excludedProducts={
                                                shippingRate?.excluded_products as ProductSelectableItem[]
                                            }
                                            excludedCategories={
                                                shippingRate?.excluded_categories as CategorySelectableItem[]
                                            }
                                            excludedBrands={shippingRate?.excluded_brands as SelectableBrand[]}
                                            errors={errors}
                                            showAllowedRegions={false}
                                        />
                                    </TabsContent>
                                </Tabs>
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose className="order-1 md:order-0" asChild>
                                    <Button variant="ghost">{__('Cancel')}</Button>
                                </AdaptiveDialogClose>
                                <SubmitButton className="order-0 md:order-1" processing={processing}>
                                    {shippingRate ? __('Update shipping rate') : __('Add shipping rate')}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
