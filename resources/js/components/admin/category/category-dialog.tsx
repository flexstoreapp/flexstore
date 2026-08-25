import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { AlertCircleIcon } from 'lucide-react';
import { useState } from 'react';

import * as CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
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
import type { Category } from '@/types';

import { CategoryGeneralTabContent } from './category-general-tab-content';

interface CategoryDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    category?: Category;
    parentCategory?: Category;
}

export function CategoryDialog({ open, onOpenChange, category, parentCategory }: CategoryDialogProps) {
    const {
        urlHandle,
        onSourceChange,
        onUrlHandleChange,
        reset: resetUrlHandle,
        needsServerGeneration,
    } = useUrlHandleField({
        autoGenerate: !category,
    });
    const formatDate = useFormatDate();
    const seo = useSeoAutofill({
        title: getTranslation(category?.seo_title),
        description: getTranslation(category?.seo_description),
    });
    const [prevOpen, setPrevOpen] = useState(open);
    const [prevCategoryId, setPrevCategoryId] = useState(category?.id);

    if (open !== prevOpen || category?.id !== prevCategoryId) {
        setPrevOpen(open);
        setPrevCategoryId(category?.id);
        seo.reset(getTranslation(category?.seo_title), getTranslation(category?.seo_description));
        if (open && category) {
            resetUrlHandle(category.url_handle);
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
                    {...(category ? CategoryController.update.form(category) : CategoryController.store.form())}
                    options={{ preserveScroll: true, only: ['categories'] }}
                    transform={handleTransform}
                    onSuccess={handleClose}
                    resetOnSuccess={!category}
                    className="min-w-0"
                >
                    {({ processing, errors }) => (
                        <>
                            <UnsavedChangesAlert />

                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>
                                    {category ? __('Edit category') : __('Add category')}
                                </AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>
                                    {category
                                        ? __('Last updated on :datetime', {
                                              datetime: formatDate(category!.updated_at),
                                          })
                                        : __('Add a new category to your store')}
                                </AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <AdaptiveDialogContentContainer>
                                {parentCategory && <input type="hidden" name="parent_id" value={parentCategory.id} />}

                                <Tabs defaultValue="general">
                                    <TabsList className="grid w-full grid-cols-2">
                                        <TabsTrigger value="general">
                                            {__('General')}
                                            {['name', 'description', 'google_product_category'].some(
                                                (f) => errors[f],
                                            ) && <AlertCircleIcon className="ms-1 text-destructive" />}
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
                                        className="mt-4 min-w-0 data-[state=inactive]:hidden"
                                        forceMount
                                    >
                                        <CategoryGeneralTabContent
                                            category={category}
                                            errors={errors}
                                            onNameChange={handleNameChange}
                                            onDescriptionChange={seo.syncDescriptionFromSource}
                                        />
                                    </TabsContent>

                                    <TabsContent
                                        value="seo"
                                        className="mt-4 min-w-0 data-[state=inactive]:hidden"
                                        forceMount
                                    >
                                        <SeoTabContent
                                            idPrefix="category"
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
                                    {category ? __('Update category') : __('Add category')}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
