import { CheckIcon, StarIcon, XIcon } from 'lucide-react';
import { type FormEvent, type RefObject, useState } from 'react';

import * as ProductReviewController from '@/actions/App/Http/Controllers/Storefront/ProductReviewController';
import { Button } from '@/components/storefront/button';
import { useOverlay } from '@/hooks/storefront/use-overlay';
import { HttpError, httpPost } from '@/lib/http';
import { __, transChoice } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface ReviewModalProps {
    open: boolean;
    productId: number;
    onClose: () => void;
    onSubmitted: () => void;
}

export function ReviewModal({ open, productId, onClose, onSubmitted }: ReviewModalProps) {
    const panelRef = useOverlay(open, onClose);
    const [rating, setRating] = useState(0);
    const [hovered, setHovered] = useState(0);
    const [title, setTitle] = useState('');
    const [content, setContent] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);
    const [submitted, setSubmitted] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (submitting) {
            return;
        }

        setSubmitting(true);
        setErrors({});

        try {
            await httpPost(ProductReviewController.store(productId), { rating, title, content });
            setSubmitted(true);
        } catch (error) {
            if (error instanceof HttpError && error.status === 422) {
                const bag = (error.data?.errors ?? {}) as Record<string, string[]>;
                setErrors(Object.fromEntries(Object.entries(bag).map(([key, messages]) => [key, messages[0]])));
            } else {
                setErrors({ content: __('Something went wrong. Please try again.') });
            }
        } finally {
            setSubmitting(false);
        }
    };

    const active = hovered || rating;

    return (
        <div
            className={cn(
                'fixed inset-0 z-120 flex items-center justify-center p-4 sm:p-6',
                open ? 'visible' : 'invisible delay-(--duration-slow)',
            )}
        >
            <div
                onClick={onClose}
                className={cn(
                    'absolute inset-0 bg-black/40 transition-opacity duration-(--duration-slow) ease-out-quart',
                    open ? 'opacity-100' : 'pointer-events-none opacity-0',
                )}
            />
            <div
                ref={panelRef as RefObject<HTMLDivElement>}
                role="dialog"
                aria-modal="true"
                aria-labelledby="review-title"
                className={cn(
                    'relative max-h-[90vh] w-[520px] max-w-full overflow-y-auto rounded-md bg-surface shadow-lg transition-[opacity,scale] duration-(--duration-base) ease-out-quart focus:outline-hidden',
                    open ? 'scale-100 opacity-100' : 'pointer-events-none scale-[0.96] opacity-0',
                )}
            >
                {submitted ? (
                    <div className="px-6 py-10 text-center sm:px-7">
                        <span
                            aria-hidden="true"
                            className="inline-flex size-14 items-center justify-center rounded-full bg-success/15 text-success"
                        >
                            <CheckIcon size={28} strokeWidth={2.4} />
                        </span>
                        <h3 className="mt-4 font-head text-xl font-bold text-ink">{__('Review submitted')}</h3>
                        <p className="mt-1.5 mb-0 text-muted">
                            {__('Thanks for your feedback! Your review has been submitted for moderation.')}
                        </p>
                        <Button
                            onClick={() => {
                                onSubmitted();
                                onClose();
                            }}
                            className="mt-6"
                        >
                            {__('Done')}
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="flex items-center justify-between border-b border-line px-6 py-5 sm:px-7">
                            <h2 id="review-title" className="m-0 font-head text-3xl font-bold text-ink">
                                {__('Write a review')}
                            </h2>
                            <button
                                type="button"
                                aria-label={__('Close review form')}
                                onClick={onClose}
                                className="-me-2 flex size-10 items-center justify-center rounded-full text-muted transition-colors hover:bg-surface-2 focus-visible:outline-offset-2"
                            >
                                <XIcon size={18} strokeWidth={2} aria-hidden="true" />
                            </button>
                        </div>

                        <form onSubmit={submit} className="flex flex-col gap-5 px-6 py-6 sm:px-7">
                            <div>
                                <span className="mb-2 block text-sm font-bold tracking-label text-ink uppercase">
                                    {__('Your rating')}
                                </span>
                                <div
                                    role="radiogroup"
                                    aria-label={__('Rating out of 5 stars')}
                                    className="flex items-center gap-1.5"
                                    onMouseLeave={() => setHovered(0)}
                                >
                                    {[1, 2, 3, 4, 5].map((value) => (
                                        <button
                                            key={value}
                                            type="button"
                                            role="radio"
                                            aria-checked={rating === value}
                                            aria-label={transChoice(':count star|:count stars', value)}
                                            onMouseEnter={() => setHovered(value)}
                                            onClick={() => setRating(value)}
                                            className="flex size-9 items-center justify-center rounded-sm transition focus-visible:outline-offset-2"
                                        >
                                            <StarIcon
                                                size={22}
                                                className={cn(
                                                    value <= active ? 'fill-amber text-amber' : 'text-line-strong',
                                                )}
                                            />
                                        </button>
                                    ))}
                                    <span aria-live="polite" className="ms-2 text-sm text-muted">
                                        {rating > 0
                                            ? transChoice(':count star|:count stars', rating)
                                            : __('Select a rating')}
                                    </span>
                                </div>
                                {errors.rating && <p className="mt-1.5 mb-0 text-sm text-error">{errors.rating}</p>}
                            </div>

                            <div>
                                <label
                                    htmlFor="review-title-input"
                                    className="mb-2 block text-sm font-semibold text-ink"
                                >
                                    {__('Review title')}{' '}
                                    <span className="font-normal text-muted">({__('optional')})</span>
                                </label>
                                <input
                                    id="review-title-input"
                                    type="text"
                                    value={title}
                                    onChange={(event) => setTitle(event.target.value)}
                                    placeholder={__('Sum it up in a few words')}
                                    className="h-11 w-full rounded-md border border-line-strong bg-surface px-4 transition focus:border-primary focus:outline-none"
                                />
                                {errors.title && <p className="mt-1.5 mb-0 text-sm text-error">{errors.title}</p>}
                            </div>

                            <div>
                                <label
                                    htmlFor="review-content-input"
                                    className="mb-2 block text-sm font-semibold text-ink"
                                >
                                    {__('Your review')}
                                </label>
                                <textarea
                                    id="review-content-input"
                                    value={content}
                                    onChange={(event) => setContent(event.target.value)}
                                    rows={4}
                                    placeholder={__('What did you like or dislike?')}
                                    className="w-full resize-none rounded-md border border-line-strong bg-surface px-4 py-3 transition focus:border-primary focus:outline-none"
                                />
                                {errors.content && <p className="mt-1.5 mb-0 text-sm text-error">{errors.content}</p>}
                            </div>

                            <div className="flex gap-3 pt-1">
                                <Button type="button" variant="outline" onClick={onClose} className="flex-1">
                                    {__('Cancel')}
                                </Button>
                                <Button type="submit" disabled={submitting} className="flex-1">
                                    {submitting ? __('Submitting') : __('Submit review')}
                                </Button>
                            </div>
                        </form>
                    </>
                )}
            </div>
        </div>
    );
}
