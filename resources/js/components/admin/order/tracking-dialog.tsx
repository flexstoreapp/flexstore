import { type FormDataConvertible } from '@inertiajs/core';
import { Form } from '@inertiajs/react';

import * as OrderShipmentController from '@/actions/App/Http/Controllers/Admin/OrderShipmentController';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { OrderShipment } from '@/types';

interface TrackingDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    shipment: OrderShipment;
}

export function TrackingDialog({ open, onOpenChange, shipment }: TrackingDialogProps) {
    function handleTransform(data: Record<string, FormDataConvertible>): Record<string, FormDataConvertible> {
        data.notify_customer = data.notify_customer === 'on';
        return data;
    }

    return (
        <AdaptiveDialog open={open} onOpenChange={onOpenChange}>
            <AdaptiveDialogContent>
                <Form
                    {...OrderShipmentController.update.form({
                        order: shipment.order_id,
                        shipment: shipment.id,
                    })}
                    options={{ preserveScroll: true }}
                    transform={handleTransform}
                    onSuccess={() => onOpenChange(false)}
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <>
                            <UnsavedChangesAlert />

                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>{__('Edit tracking')}</AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>
                                    {__('Update tracking information for this fulfillment')}
                                </AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <AdaptiveDialogContentContainer>
                                <Field>
                                    <FieldLabel htmlFor={`tracking_number_${shipment.id}`}>
                                        {__('Tracking number')}
                                    </FieldLabel>
                                    <Input
                                        id={`tracking_number_${shipment.id}`}
                                        name="tracking_number"
                                        defaultValue={shipment.tracking_number ?? ''}
                                    />
                                    <FieldError>{errors.tracking_number}</FieldError>
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor={`tracking_url_${shipment.id}`}>
                                        {__('Tracking URL')}
                                    </FieldLabel>
                                    <Input
                                        id={`tracking_url_${shipment.id}`}
                                        name="tracking_url"
                                        type="url"
                                        defaultValue={shipment.tracking_url ?? ''}
                                    />
                                    <FieldError>{errors.tracking_url}</FieldError>
                                </Field>

                                <Field orientation="horizontal">
                                    <Checkbox
                                        id={`notify_customer_${shipment.id}`}
                                        name="notify_customer"
                                        defaultChecked
                                    />
                                    <FieldLabel htmlFor={`notify_customer_${shipment.id}`} className="font-normal">
                                        {__('Notify customer')}
                                    </FieldLabel>
                                </Field>
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose className="order-1 md:order-0" asChild>
                                    <Button variant="ghost">{__('Cancel')}</Button>
                                </AdaptiveDialogClose>
                                <SubmitButton className="order-0 md:order-1" processing={processing}>
                                    {__('Save')}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
