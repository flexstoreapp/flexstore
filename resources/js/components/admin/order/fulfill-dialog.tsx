import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import { useState } from 'react';

import * as OrderShipmentController from '@/actions/App/Http/Controllers/Admin/OrderShipmentController';
import { SubmitButton } from '@/components/admin/submit-button';
import { Thumbnail, ThumbnailRatio } from '@/components/admin/thumbnail';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { QuantityInput } from '@/components/ui/quantity-input';
import { __ } from '@/lib/i18n';
import { mediaSmallThumb } from '@/lib/media';
import { getTranslation } from '@/lib/utils';
import type { Order } from '@/types';

interface FulfillDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    order: Order;
    fulfillableQuantities: Record<number, number>;
}

export function FulfillDialog({ open, onOpenChange, order, fulfillableQuantities }: FulfillDialogProps) {
    const fulfillableItems = (order.items ?? []).filter((item) => fulfillableQuantities[item.id] > 0);
    const [quantities, setQuantities] = useState<Record<number, number>>({});
    const [prevOpen, setPrevOpen] = useState(false);

    if (open && !prevOpen) {
        setQuantities(Object.fromEntries(fulfillableItems.map((item) => [item.id, fulfillableQuantities[item.id]])));
    }

    if (open !== prevOpen) {
        setPrevOpen(open);
    }

    const handleQuantityChange = (itemId: number, delta: number) => {
        setQuantities((prev) => {
            const current = prev[itemId] ?? 0;
            const maxQty = fulfillableQuantities[itemId] ?? 0;
            const newQty = Math.max(0, Math.min(current + delta, maxQty));

            return { ...prev, [itemId]: newQty };
        });
    };

    const totalQuantity = Object.values(quantities).reduce((sum, qty) => sum + qty, 0);

    function handleTransform(data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> {
        const items = Object.entries(quantities)
            .filter(([, qty]) => qty > 0)
            .map(([itemId, qty]) => ({ order_item_id: Number(itemId), quantity: qty }));
        data.items = items as unknown as FormDataConvertible;
        data.notify_customer = data.notify_customer === 'on';

        return data;
    }

    function handleSuccess() {
        setQuantities({});
        onOpenChange(false);
    }

    return (
        <AdaptiveDialog open={open} onOpenChange={onOpenChange}>
            <AdaptiveDialogContent className="sm:max-w-lg" onOpenAutoFocus={(e) => e.preventDefault()}>
                <Form
                    {...OrderShipmentController.store.form(order)}
                    options={{ preserveScroll: true }}
                    transform={handleTransform}
                    onSuccess={handleSuccess}
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <>
                            <UnsavedChangesAlert />

                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>{__('Mark as fulfilled')}</AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>
                                    {__('Select items to fulfill and how to ship them')}
                                </AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <AdaptiveDialogContentContainer>
                                <ThumbnailRatio media={fulfillableItems.map((item) => item.media)}>
                                    <div className="divide-y">
                                        {fulfillableItems.map((item) => {
                                            const maxQty = fulfillableQuantities[item.id];
                                            const quantity = quantities[item.id] ?? 0;

                                            return (
                                                <div
                                                    key={item.id}
                                                    className="flex items-center gap-4 py-3 first:pt-0 last:pb-0"
                                                >
                                                    <Thumbnail
                                                        src={mediaSmallThumb(item.media)}
                                                        alt={getTranslation(item.product_title)}
                                                    />
                                                    <div className="min-w-0 flex-1 space-y-0.5 text-sm">
                                                        <h4 className="truncate leading-tight font-medium">
                                                            {getTranslation(item.product_title)}
                                                        </h4>
                                                        {item.variant_title && (
                                                            <p className="text-muted-foreground">
                                                                {item.variant_title}
                                                            </p>
                                                        )}
                                                    </div>
                                                    <QuantityInput
                                                        itemId={item.id}
                                                        quantity={quantity}
                                                        maxQuantity={maxQty}
                                                        onQuantityChange={handleQuantityChange}
                                                    />
                                                </div>
                                            );
                                        })}
                                    </div>
                                </ThumbnailRatio>

                                <FieldError>{errors.items}</FieldError>

                                <Field>
                                    <FieldLabel htmlFor="tracking_number">{__('Tracking number')}</FieldLabel>
                                    <Input id="tracking_number" name="tracking_number" />
                                    <FieldError>{errors.tracking_number}</FieldError>
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor="tracking_url">{__('Tracking URL')}</FieldLabel>
                                    <Input id="tracking_url" name="tracking_url" type="url" />
                                    <FieldError>{errors.tracking_url}</FieldError>
                                </Field>

                                <Field orientation="horizontal">
                                    <Checkbox id="notify_customer" name="notify_customer" />
                                    <FieldLabel htmlFor="notify_customer" className="font-normal">
                                        {__('Notify customer')}
                                    </FieldLabel>
                                </Field>
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose className="order-1 md:order-0" asChild>
                                    <Button variant="ghost">{__('Cancel')}</Button>
                                </AdaptiveDialogClose>
                                <SubmitButton
                                    className="order-0 md:order-1"
                                    processing={processing}
                                    disabled={totalQuantity === 0}
                                >
                                    {__('Mark as fulfilled')}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
