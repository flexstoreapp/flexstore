import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';

import CancelOrderController from '@/actions/App/Http/Controllers/Admin/CancelOrderController';
import { SubmitButton } from '@/components/admin/submit-button';
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
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import { __ } from '@/lib/i18n';
import type { Order } from '@/types';

interface CancelOrderDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    order: Order;
}

const CANCELLATION_REASONS = [
    { value: 'customer_request', label: __('Customer request') },
    { value: 'fraudulent', label: __('Fraudulent order') },
    { value: 'inventory', label: __('Inventory issue') },
    { value: 'other', label: __('Other') },
] as const;

export function CancelOrderDialog({ open, onOpenChange, order }: CancelOrderDialogProps) {
    const isRefundable = order.is_refundable ?? false;
    const canRestock = (order.items ?? []).some((item) => {
        const stockTarget = item.product_variant ?? item.product;
        return !!stockTarget?.track_stock;
    });

    function handleTransform(data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> {
        data.refund = data.refund === 'on';
        data.restock = data.restock === 'on';
        data.notify_customer = data.notify_customer === 'on';

        return data;
    }

    return (
        <AdaptiveDialog open={open} onOpenChange={onOpenChange}>
            <AdaptiveDialogContent className="sm:max-w-lg" onOpenAutoFocus={(e) => e.preventDefault()}>
                <Form
                    action={CancelOrderController.url(order)}
                    method="post"
                    options={{ preserveScroll: true }}
                    transform={handleTransform}
                    onSuccess={() => onOpenChange(false)}
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <>
                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>{__('Cancel order')}</AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>
                                    {__('The order will be permanently marked as canceled')}
                                </AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <AdaptiveDialogContentContainer>
                                <Field>
                                    <FieldLabel>{__('Reason for cancellation')}</FieldLabel>
                                    <RadioGroup name="reason" defaultValue="customer_request">
                                        {CANCELLATION_REASONS.map((reason) => (
                                            <div key={reason.value} className="flex items-center gap-2">
                                                <RadioGroupItem value={reason.value} id={`reason-${reason.value}`} />
                                                <FieldLabel htmlFor={`reason-${reason.value}`} className="font-normal">
                                                    {reason.label}
                                                </FieldLabel>
                                            </div>
                                        ))}
                                    </RadioGroup>
                                    <FieldError>{errors.reason}</FieldError>
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor="reason_note">{__('Notes')}</FieldLabel>
                                    <Textarea id="reason_note" name="reason_note" rows={2} className="max-h-24" />
                                    <FieldError>{errors.reason_note}</FieldError>
                                </Field>

                                <div className="space-y-3">
                                    <Field orientation="horizontal">
                                        <Checkbox
                                            id="refund"
                                            name="refund"
                                            defaultChecked={isRefundable}
                                            disabled={!isRefundable}
                                        />
                                        <FieldLabel htmlFor="refund" className="font-normal">
                                            {__('Refund payment')}
                                        </FieldLabel>
                                    </Field>

                                    {canRestock && (
                                        <Field orientation="horizontal">
                                            <Checkbox id="restock" name="restock" defaultChecked />
                                            <FieldLabel htmlFor="restock" className="font-normal">
                                                {__('Restock items')}
                                            </FieldLabel>
                                        </Field>
                                    )}

                                    <Field orientation="horizontal">
                                        <Checkbox id="notify_customer" name="notify_customer" defaultChecked />
                                        <FieldLabel htmlFor="notify_customer" className="font-normal">
                                            {__('Notify customer')}
                                        </FieldLabel>
                                    </Field>
                                </div>
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose className="order-1 md:order-0" asChild>
                                    <Button variant="ghost">{__('No, keep it')}</Button>
                                </AdaptiveDialogClose>
                                <SubmitButton
                                    className="order-0 md:order-1"
                                    variant="destructive"
                                    processing={processing}
                                >
                                    {__('Cancel order')}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
