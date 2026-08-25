import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { AlertCircleIcon } from 'lucide-react';

import * as ShippingCarrierController from '@/actions/App/Http/Controllers/Admin/ShippingCarrierController';
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
import { __ } from '@/lib/i18n';
import { tabHasErrors } from '@/lib/utils';
import type { ShippingCarrier, ShippingCarrierDriver } from '@/types';

import { ShippingCarrierGeneralTabContent } from './shipping-carrier-general-tab-content';

export type ShippingCarrierDialogTab = 'general';

export interface ShippingCarrierAdditionalTab {
    value: string;
    label: string;
    fields: string[];
    content: (props: { shippingCarrier?: ShippingCarrier; errors: Record<string, string> }) => React.ReactNode;
}

interface ShippingCarrierDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    shippingCarrier?: ShippingCarrier;
    carrierDriver?: ShippingCarrierDriver;
    fallbackName?: string;
    transform?: (data: Record<string, FormDataConvertible>) => Record<string, FormDataConvertible>;
    tabFields?: Record<ShippingCarrierDialogTab, string[]>;
    credentialFields?: (props: {
        shippingCarrier?: ShippingCarrier;
        errors: Record<string, string>;
    }) => React.ReactNode;
    additionalTabs?: ShippingCarrierAdditionalTab[];
}

export function ShippingCarrierDialog({
    open,
    onOpenChange,
    title,
    description,
    shippingCarrier,
    fallbackName,
    credentialFields,
    transform,
    carrierDriver = 'manual',
    tabFields,
    additionalTabs = [],
}: ShippingCarrierDialogProps) {
    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.is_active = data.is_active === 'on';
        return transform ? transform(data) : data;
    };

    const isTabbed = additionalTabs.length > 0 && tabFields !== undefined;

    return (
        <AdaptiveDialog open={open} onOpenChange={onOpenChange}>
            <AdaptiveDialogContent>
                <Form
                    {...(shippingCarrier
                        ? ShippingCarrierController.update.form(shippingCarrier.id)
                        : ShippingCarrierController.store.form())}
                    options={{ preserveScroll: true, only: ['shippingCarriers', 'regions'] }}
                    transform={handleTransform}
                    onSuccess={() => onOpenChange(false)}
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>{title}</AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>{description}</AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <input type="hidden" name="driver" value={carrierDriver} />

                            <AdaptiveDialogContentContainer>
                                {isTabbed ? (
                                    <Tabs defaultValue="general">
                                        <TabsList
                                            className="grid w-full"
                                            style={{
                                                gridTemplateColumns: `repeat(${1 + additionalTabs.length}, minmax(0, 1fr))`,
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
                                                    {tab.fields.some((field) => errors[field]) && (
                                                        <AlertCircleIcon className="ms-1 text-destructive" />
                                                    )}
                                                </TabsTrigger>
                                            ))}
                                        </TabsList>

                                        <TabsContent
                                            value="general"
                                            className="data-[state=inactive]:hidden"
                                            forceMount
                                        >
                                            <ShippingCarrierGeneralTabContent
                                                shippingCarrier={shippingCarrier}
                                                fallbackName={fallbackName}
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
                                                {tab.content({ shippingCarrier, errors })}
                                            </TabsContent>
                                        ))}
                                    </Tabs>
                                ) : (
                                    <ShippingCarrierGeneralTabContent
                                        shippingCarrier={shippingCarrier}
                                        fallbackName={fallbackName}
                                        errors={errors}
                                        className="space-y-6"
                                        credentialFields={credentialFields}
                                    />
                                )}
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose className="order-1 md:order-0" asChild>
                                    <Button variant="ghost">{__('Cancel')}</Button>
                                </AdaptiveDialogClose>
                                <SubmitButton className="order-0 md:order-1" processing={processing}>
                                    {shippingCarrier ? __('Update carrier') : __('Add carrier')}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
