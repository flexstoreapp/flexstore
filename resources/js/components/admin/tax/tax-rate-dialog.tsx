import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { AlertCircleIcon } from 'lucide-react';
import { useState } from 'react';

import * as TaxRateController from '@/actions/App/Http/Controllers/Admin/TaxRateController';
import { ConditionsTabContent } from '@/components/admin/conditions-tab-content';
import { type SelectableItem } from '@/components/admin/region-picker';
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
import type { TaxCategoryOption, TaxRate } from '@/types';

import { TaxRateGeneralTabContent } from './tax-rate-general-tab-content';

type TaxRateDialogTab = 'general' | 'conditions';

const tabFields: Record<TaxRateDialogTab, string[]> = {
    general: ['name', 'rate', 'priority', 'region_id', 'tax_category', 'is_compound', 'is_active'],
    conditions: ['min_order_value', 'max_order_value'],
};

interface TaxRateDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    taxRate?: TaxRate;
    taxCategories: TaxCategoryOption[];
}

function getSelectedRegion(taxRate?: TaxRate): SelectableItem | null {
    return taxRate?.region ? { id: taxRate.region.id, name: taxRate.region.name } : null;
}

export function TaxRateDialog({ open, onOpenChange, taxRate, taxCategories }: TaxRateDialogProps) {
    const [selectedRegion, setSelectedRegion] = useState<SelectableItem | null>(() => getSelectedRegion(taxRate));
    const [prevTaxRateId, setPrevTaxRateId] = useState<number | undefined>(taxRate?.id);
    const formatDate = useFormatDate();

    if (taxRate?.id !== prevTaxRateId) {
        setPrevTaxRateId(taxRate?.id);
        setSelectedRegion(getSelectedRegion(taxRate));
    }

    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.is_compound = data.is_compound === 'on';
        data.is_active = data.is_active === 'on';
        return data;
    };

    return (
        <AdaptiveDialog open={open} onOpenChange={onOpenChange}>
            <AdaptiveDialogContent>
                <Form
                    {...(taxRate ? TaxRateController.update.form(taxRate) : TaxRateController.store.form())}
                    options={{
                        preserveScroll: true,
                        only: ['taxRates'],
                    }}
                    transform={handleTransform}
                    onSuccess={() => onOpenChange(false)}
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>
                                    {taxRate ? __('Edit tax rate') : __('Add tax rate')}
                                </AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>
                                    {taxRate
                                        ? __('Last updated on :datetime', { datetime: formatDate(taxRate.updated_at) })
                                        : __('Add a new tax rate for your store')}
                                </AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <AdaptiveDialogContentContainer>
                                <Tabs defaultValue="general">
                                    <TabsList className="grid w-full grid-cols-2">
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
                                    </TabsList>

                                    <TabsContent value="general" className="data-[state=inactive]:hidden" forceMount>
                                        <TaxRateGeneralTabContent
                                            taxRate={taxRate}
                                            errors={errors}
                                            taxCategories={taxCategories}
                                            selectedRegion={selectedRegion}
                                            onRegionSelect={(region) => setSelectedRegion(region)}
                                        />
                                    </TabsContent>
                                    <TabsContent value="conditions" className="data-[state=inactive]:hidden" forceMount>
                                        <ConditionsTabContent
                                            entity={taxRate}
                                            errors={errors}
                                            showWeightConditions={false}
                                        />
                                    </TabsContent>
                                </Tabs>
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose className="order-1 md:order-0" asChild>
                                    <Button variant="ghost">{__('Cancel')}</Button>
                                </AdaptiveDialogClose>
                                <SubmitButton className="order-0 md:order-1" processing={processing}>
                                    {taxRate ? __('Update tax rate') : __('Add tax rate')}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
