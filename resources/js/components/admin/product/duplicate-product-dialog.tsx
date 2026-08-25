import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';

import * as DuplicateProductController from '@/actions/App/Http/Controllers/Admin/DuplicateProductController';
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
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { Product } from '@/types';

import { DuplicateProductBasicInfo } from './duplicate-product-basic-info';
import { DuplicateProductOptions } from './duplicate-product-options';
import { DuplicateProductStatus } from './duplicate-product-status';

interface DuplicateProductDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    product: Product;
}

export function DuplicateProductDialog({ open, onOpenChange, product }: DuplicateProductDialogProps) {
    const productTitle = `${getTranslation(product.title)} (copy)`;

    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        data.duplicate_category = data.duplicate_category === 'on';
        data.duplicate_digital_files = data.duplicate_digital_files === 'on';
        data.duplicate_brand = data.duplicate_brand === 'on';
        data.duplicate_media = data.duplicate_media === 'on';
        data.duplicate_pricing = data.duplicate_pricing === 'on';
        data.duplicate_tax = data.duplicate_tax === 'on';
        data.duplicate_inventory = data.duplicate_inventory === 'on';
        data.duplicate_skus = data.duplicate_skus === 'on';
        data.duplicate_barcodes = data.duplicate_barcodes === 'on';
        data.duplicate_shipping = data.duplicate_shipping === 'on';
        data.duplicate_seo = data.duplicate_seo === 'on';
        data.is_active = data.is_active === 'on';
        return data;
    };

    const handleClose = () => {
        onOpenChange(false);
    };

    return (
        <AdaptiveDialog open={open} onOpenChange={handleClose}>
            <AdaptiveDialogContent>
                <Form
                    {...DuplicateProductController.store.form({ product: product.id })}
                    options={{ preserveScroll: true }}
                    transform={handleTransform}
                    onSuccess={() => handleClose()}
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>{__('Duplicate product')}</AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>{getTranslation(product.title)}</AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <AdaptiveDialogContentContainer>
                                <DuplicateProductBasicInfo productTitle={productTitle} errors={errors} />
                                <DuplicateProductStatus />
                                <DuplicateProductOptions product={product} />
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose className="order-1 md:order-0" asChild>
                                    <Button variant="ghost">{__('Cancel')}</Button>
                                </AdaptiveDialogClose>
                                <SubmitButton className="order-0 md:order-1" processing={processing}>
                                    {__('Duplicate')}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
