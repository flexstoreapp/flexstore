import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { ArrowRightIcon } from 'lucide-react';
import { useState } from 'react';

import * as StockAdjustmentController from '@/actions/App/Http/Controllers/Admin/StockAdjustmentController';
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
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type { Product, ProductVariant, StockMovementReason } from '@/types';

interface StockAdjustmentDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    product: Product;
    variant?: ProductVariant | null;
}

const stockMovementReasons: { value: StockMovementReason; label: string }[] = [
    { value: 'manual', label: __('Manual adjustment') },
    { value: 'received', label: __('Received') },
    { value: 'damaged', label: __('Damaged') },
    { value: 'lost', label: __('Lost') },
    { value: 'return', label: __('Return') },
    { value: 'inventory_count', label: __('Inventory count') },
    { value: 'transfer', label: __('Transfer') },
    { value: 'other', label: __('Other') },
];

export function StockAdjustmentDialog({ open, onOpenChange, product, variant }: StockAdjustmentDialogProps) {
    const [quantity, setQuantity] = useState<string>('');
    const [reason, setReason] = useState<StockMovementReason>('manual');
    const [notes, setNotes] = useState<string>('');

    const currentStock = variant?.stock ?? product.stock ?? 0;

    const getNewStock = () => {
        if (!quantity) return currentStock;
        const parsedQuantity = parseInt(quantity, 10);
        if (isNaN(parsedQuantity)) return currentStock;
        return Math.max(0, currentStock + parsedQuantity);
    };

    const newStock = getNewStock();

    const handleTransform = (data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> => {
        return {
            ...data,
            product_id: variant?.product_id ?? product.id,
            product_variant_id: variant?.id ?? null,
        };
    };

    return (
        <AdaptiveDialog open={open} onOpenChange={onOpenChange}>
            <AdaptiveDialogContent>
                <Form
                    {...StockAdjustmentController.store.form()}
                    options={{ preserveScroll: true, only: ['products'] }}
                    transform={handleTransform}
                    onSuccess={() => onOpenChange(false)}
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>{__('Adjust stock')}</AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>
                                    {variant
                                        ? __('Adjust stock for variant :variant', {
                                              variant: getTranslation(variant.title),
                                          })
                                        : __('Adjust stock for :product', { product: getTranslation(product.title) })}
                                </AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <AdaptiveDialogContentContainer>
                                <div className="rounded-lg border bg-accent/50 p-4">
                                    <div className="flex items-center justify-between gap-4">
                                        <div className="flex-1">
                                            <p className="text-sm text-muted-foreground">{__('Current stock')}</p>
                                            <p className="text-2xl font-bold">{currentStock}</p>
                                        </div>
                                        <ArrowRightIcon className="size-5 shrink-0 text-muted-foreground rtl:-scale-x-100" />
                                        <div className="flex-1 text-end">
                                            <p className="text-sm text-muted-foreground">{__('New stock')}</p>
                                            <p
                                                className={cn(
                                                    'text-2xl font-bold',
                                                    newStock > currentStock && 'text-emerald-700 dark:text-emerald-400',
                                                    newStock < currentStock && 'text-destructive',
                                                )}
                                            >
                                                {newStock}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <Field>
                                    <FieldLabel htmlFor="quantity">{__('Quantity change')}</FieldLabel>
                                    <Input
                                        id="quantity"
                                        type="number"
                                        name="quantity"
                                        value={quantity}
                                        onChange={(e) => setQuantity(e.target.value)}
                                        placeholder={__('Enter positive to add, negative to remove')}
                                        required
                                    />
                                    <FieldError>{errors.quantity}</FieldError>
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor="reason">{__('Reason')}</FieldLabel>
                                    <AdaptiveSelect
                                        id="reason"
                                        name="reason"
                                        value={reason}
                                        onValueChange={(value) => setReason(value as StockMovementReason)}
                                        options={stockMovementReasons.map((reason) => ({
                                            value: reason.value,
                                            label: reason.label,
                                        }))}
                                    />
                                    <FieldError>{errors.reason}</FieldError>
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor="notes">{__('Notes')}</FieldLabel>
                                    <Textarea
                                        id="notes"
                                        name="notes"
                                        value={notes}
                                        onChange={(e) => setNotes(e.target.value)}
                                        rows={2}
                                        className="max-h-24"
                                    />
                                    <FieldError>{errors.notes}</FieldError>
                                </Field>
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose asChild>
                                    <Button type="button" variant="ghost">
                                        {__('Cancel')}
                                    </Button>
                                </AdaptiveDialogClose>
                                <SubmitButton processing={processing}>{__('Adjust stock')}</SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
