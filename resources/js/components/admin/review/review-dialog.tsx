import { Form } from '@inertiajs/react';
import { useState } from 'react';

import * as ReviewController from '@/actions/App/Http/Controllers/Admin/ReviewController';
import { ProductPicker, type SelectableItem } from '@/components/admin/product-picker';
import { ResourcePickerTrigger } from '@/components/admin/resource-picker';
import { SubmitButton } from '@/components/admin/submit-button';
import { UnsavedChangesAlert } from '@/components/admin/unsaved-changes-alert';
import { UserPicker, type SelectableUser } from '@/components/admin/user-picker';
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
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { StarRating } from '@/components/ui/star-rating';
import { Textarea } from '@/components/ui/textarea';
import { useFormatDate } from '@/hooks/use-format-date';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { Review } from '@/types';

interface ReviewDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    review: Review | undefined;
}

export function ReviewDialog({ open, onOpenChange, review }: ReviewDialogProps) {
    const formatDate = useFormatDate();
    const [productPickerOpen, setProductPickerOpen] = useState(false);
    const [customerPickerOpen, setCustomerPickerOpen] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState<SelectableItem | null>(null);
    const [selectedCustomer, setSelectedCustomer] = useState<SelectableUser | null>(null);
    const [rating, setRating] = useState<number>(review?.rating ?? 0);
    const [prevOpen, setPrevOpen] = useState(open);
    const [prevReviewId, setPrevReviewId] = useState(review?.id);

    if (open !== prevOpen || review?.id !== prevReviewId) {
        setPrevOpen(open);
        setPrevReviewId(review?.id);
        if (open && review) {
            setRating(review.rating);
        }
    }

    const handleClose = () => {
        onOpenChange(false);
        setSelectedProduct(null);
        setSelectedCustomer(null);
        setRating(0);
    };

    return (
        <AdaptiveDialog open={open} onOpenChange={handleClose}>
            <AdaptiveDialogContent>
                <Form
                    {...(review ? ReviewController.update.form({ review: review.id }) : ReviewController.store.form())}
                    options={{ preserveScroll: true, only: ['reviews'] }}
                    onSuccess={handleClose}
                    resetOnSuccess={!review}
                >
                    {({ processing, errors }) => (
                        <>
                            <UnsavedChangesAlert />
                            <AdaptiveDialogHeader>
                                <AdaptiveDialogTitle>
                                    {review ? __('Edit review') : __('Add review')}
                                </AdaptiveDialogTitle>
                                <AdaptiveDialogDescription>
                                    {review
                                        ? __('Last updated on :datetime', {
                                              datetime: formatDate(review.updated_at),
                                          })
                                        : __('Add a new product review')}
                                </AdaptiveDialogDescription>
                            </AdaptiveDialogHeader>

                            <AdaptiveDialogContentContainer>
                                {!review && (
                                    <>
                                        <Field>
                                            <FieldLabel htmlFor="review-product">{__('Product')}</FieldLabel>
                                            <ResourcePickerTrigger
                                                id="review-product"
                                                name="product_id"
                                                value={selectedProduct?.id}
                                                label={getTranslation(selectedProduct?.title)}
                                                placeholder={__('Select a product')}
                                                onOpen={() => setProductPickerOpen(true)}
                                            />
                                            <FieldError>{errors.product_id}</FieldError>
                                        </Field>

                                        <ProductPicker
                                            open={productPickerOpen}
                                            onOpenChange={setProductPickerOpen}
                                            selectedItems={selectedProduct ? [selectedProduct] : []}
                                            onSelectionChange={setSelectedProduct}
                                        />

                                        <Field>
                                            <FieldLabel htmlFor="review-user">{__('Customer')}</FieldLabel>
                                            <ResourcePickerTrigger
                                                id="review-user"
                                                name="user_id"
                                                value={selectedCustomer?.id}
                                                label={
                                                    selectedCustomer &&
                                                    `${selectedCustomer.name} (${selectedCustomer.email})`
                                                }
                                                placeholder={__('Select a customer')}
                                                onOpen={() => setCustomerPickerOpen(true)}
                                            />
                                            <UserPicker
                                                open={customerPickerOpen}
                                                onOpenChange={setCustomerPickerOpen}
                                                selectedItems={selectedCustomer ? [selectedCustomer] : []}
                                                onSelectionChange={setSelectedCustomer}
                                                customersOnly={true}
                                            />
                                            <FieldError>{errors.user_id}</FieldError>
                                        </Field>
                                    </>
                                )}

                                <Field>
                                    <FieldLabel htmlFor="review-rating">{__('Rating')}</FieldLabel>
                                    <StarRating
                                        key={review?.id ?? 'new'}
                                        name="rating"
                                        value={rating}
                                        onChange={setRating}
                                    />
                                    <FieldError>{errors.rating}</FieldError>
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor="review-title">{__('Title')}</FieldLabel>
                                    <Input
                                        id="review-title"
                                        name="title"
                                        defaultValue={review?.title ?? ''}
                                        placeholder={__('Review title (optional)')}
                                    />
                                    <FieldError>{errors.title}</FieldError>
                                </Field>

                                <Field>
                                    <FieldLabel htmlFor="review-content">{__('Review')}</FieldLabel>
                                    <Textarea
                                        id="review-content"
                                        name="content"
                                        defaultValue={review?.content ?? ''}
                                        rows={5}
                                        className="max-h-56"
                                        placeholder={__('Write your review...')}
                                        required
                                    />
                                    <FieldError>{errors.content}</FieldError>
                                </Field>
                            </AdaptiveDialogContentContainer>

                            <AdaptiveDialogFooter>
                                <AdaptiveDialogClose className="order-1 md:order-0" asChild>
                                    <Button variant="ghost">{__('Cancel')}</Button>
                                </AdaptiveDialogClose>
                                <SubmitButton className="order-0 md:order-1" processing={processing}>
                                    {review ? __('Update review') : __('Add review')}
                                </SubmitButton>
                            </AdaptiveDialogFooter>
                        </>
                    )}
                </Form>
            </AdaptiveDialogContent>
        </AdaptiveDialog>
    );
}
