import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { AlertCircleIcon } from 'lucide-react';
import { useState } from 'react';

import * as BrandController from '@/actions/App/Http/Controllers/Admin/BrandController';
import { SeoTabContent } from '@/components/admin/seo-tab-content';
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
import { useSeoAutofill } from '@/hooks/admin/use-seo-autofill';
import { useUrlHandleField } from '@/hooks/admin/use-url-handle-field';
import { useFormatDate } from '@/hooks/use-format-date';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { Brand } from '@/types';

import { BrandGeneralTabContent } from './brand-general-tab-content';

interface BrandDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    brand: Brand | undefined;
}

export function BrandDialog({ open, onOpenChange, brand }: BrandDialogProps) {
    const {
        urlHandle,
        onSourceChange,
        onUrlHandleChange,
        reset: resetUrlHandle,
        needsServerGeneration,
    } = useUrlHandleField({
        initial: brand?.url_handle,
        autoGenerate: !brand,
    });
    const formatDate = useFormatDate();
    const seo = useSeoAutofill({
        title: getTranslation(brand?.seo_title),
        description: getTranslation(brand?.seo_description),
    });
    const [prevOpen, setPrevOpen] = useState(open);
    const [prevBrandId, setPrevBrandId] = useState(brand?.id);

    if (open !== prevOpen || brand?.id !== prevBrandId) {
        setPrevOpen(open);
        setPrevBrandId(brand?.id);
        seo.reset(getTranslation(brand?.seo_title), getTranslation(brand?.seo_description));
        if (open && brand) {
            resetUrlHandle(brand.url_handle);
        }
    }

    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.is_active = data.is_active === 'on';
        return data;
    };

    const handleNameChange = (value: string) => {
        onSourceChange(value);
        seo.syncTitleFromSource(value);
    };

    const handleClose = () => {
        onOpenChange(false);
        resetUrlHandle('');
    };

    return (
        <AdaptiveDialog open={open} onOpenChange={handleClose}>
            <AdaptiveDialogContent>
                <Form
                    {...(brand ? BrandController.update.form(brand) : BrandController.store.form())}
                    options={{ preserveScroll: true, only: ['brands'] }}
                    transform={handleTransform}
                    onSuccess={handleClose}
                    resetOnSuccess={!brand}
                >
                    {({ processing, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>{brand ? __('Edit brand') : __('Add brand')}</AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>
                                    {brand
                                        ? __('Last updated on :datetime', {
                                              datetime: formatDate(brand!.updated_at),
                                          })
                                        : __('Add a new brand to your store')}
                                </AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <AdaptiveDialogContentContainer>
                                <Tabs defaultValue="general">
                                    <TabsList className="grid w-full grid-cols-2">
                                        <TabsTrigger value="general">
                                            {__('General')}
                                            {['name', 'description', 'image'].some((f) => errors[f]) && (
                                                <AlertCircleIcon className="ms-1 text-destructive" />
                                            )}
                                        </TabsTrigger>
                                        <TabsTrigger value="seo">
                                            {__('SEO')}
                                            {['url_handle', 'seo_title', 'seo_description'].some((f) => errors[f]) && (
                                                <AlertCircleIcon className="ms-1 text-destructive" />
                                            )}
                                        </TabsTrigger>
                                    </TabsList>

                                    <TabsContent
                                        value="general"
                                        className="mt-4 data-[state=inactive]:hidden"
                                        forceMount
                                    >
                                        <BrandGeneralTabContent
                                            brand={brand}
                                            errors={errors}
                                            onNameChange={handleNameChange}
                                            onDescriptionChange={seo.syncDescriptionFromSource}
                                        />
                                    </TabsContent>

                                    <TabsContent value="seo" className="mt-4 data-[state=inactive]:hidden" forceMount>
                                        <SeoTabContent
                                            idPrefix="brand"
                                            errors={errors}
                                            urlHandle={urlHandle}
                                            urlHandleNeedsServerGeneration={needsServerGeneration}
                                            seoTitle={seo.seoTitle}
                                            seoDescription={seo.seoDescription}
                                            onUrlHandleChange={onUrlHandleChange}
                                            onSeoTitleChange={seo.handleSeoTitleChange}
                                            onSeoDescriptionChange={seo.handleSeoDescriptionChange}
                                        />
                                    </TabsContent>
                                </Tabs>
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose className="order-1 md:order-0" asChild>
                                    <Button variant="ghost">{__('Cancel')}</Button>
                                </AdaptiveDialogClose>
                                <SubmitButton className="order-0 md:order-1" processing={processing}>
                                    {brand ? __('Update brand') : __('Add brand')}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
