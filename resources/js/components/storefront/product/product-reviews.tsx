import { router } from '@inertiajs/react';
import { MessageSquareTextIcon, StarIcon } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/storefront/button';
import { ReviewCard } from '@/components/storefront/product/review-card';
import { ReviewModal } from '@/components/storefront/product/review-modal';
import { __, transChoice } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { ReviewData, SimplePaginated } from '@/types';

interface ProductReviewsProps {
    productId: number;
    rating: number | null;
    reviewCount: number;
    distribution: Record<number, number>;
    reviews?: SimplePaginated<ReviewData>;
    canReview: boolean;
}

function SummaryStars({ rating }: { rating: number }) {
    return (
        <div className="mt-1 flex" aria-hidden="true">
            {[0, 1, 2, 3, 4].map((i) => (
                <StarIcon
                    key={i}
                    size={18}
                    className={cn(i < Math.round(rating) ? 'fill-amber text-amber' : 'text-line-strong')}
                />
            ))}
        </div>
    );
}

export function ProductReviews({
    productId,
    rating,
    reviewCount,
    distribution,
    reviews,
    canReview,
}: ProductReviewsProps) {
    const [items, setItems] = useState<ReviewData[]>(reviews?.data ?? []);
    const [nextUrl, setNextUrl] = useState<string | null>(reviews?.next_page_url ?? null);
    const [loadingMore, setLoadingMore] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);
    const [submitted, setSubmitted] = useState(false);

    const loadMore = () => {
        if (!nextUrl || loadingMore) {
            return;
        }

        setLoadingMore(true);
        router.get(
            nextUrl,
            {},
            {
                only: ['reviews'],
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    const next = page.props.reviews as SimplePaginated<ReviewData> | undefined;
                    if (next) {
                        setItems((prev) => [...prev, ...next.data]);
                        setNextUrl(next.next_page_url);
                    }
                },
                onFinish: () => setLoadingMore(false),
            },
        );
    };

    const thankYou = submitted && (
        <p className="mt-6 rounded-md border border-success/40 bg-success/10 p-3 text-sm text-success">
            {__('Thank you for your review! It will appear once approved.')}
        </p>
    );

    const reviewModal = canReview ? (
        <ReviewModal
            open={modalOpen}
            productId={productId}
            onClose={() => setModalOpen(false)}
            onSubmitted={() => setSubmitted(true)}
        />
    ) : null;

    if (reviewCount === 0) {
        return (
            <div className="flex flex-col items-center py-6 text-center">
                <div className="flex size-14 items-center justify-center rounded-full bg-surface-2 text-muted">
                    <MessageSquareTextIcon size={26} strokeWidth={1.5} aria-hidden="true" />
                </div>
                <h3 className="mt-4 font-head text-xl font-semibold text-ink">{__('No reviews yet')}</h3>
                <p className="mt-1 mb-0 max-w-sm text-muted">{__('Be the first to share your thoughts.')}</p>
                {canReview && !submitted && (
                    <Button variant="outline" size="md" onClick={() => setModalOpen(true)} className="mt-5">
                        {__('Write a review')}
                    </Button>
                )}
                {thankYou}
                {reviewModal}
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 gap-8 md:grid-cols-[260px_1fr] lg:gap-12">
            <div>
                <div className="text-[52px] leading-none font-bold text-ink">
                    {rating !== null ? rating.toFixed(1) : '—'}
                </div>
                <SummaryStars rating={rating ?? 0} />
                <p className="mt-1 text-sm text-muted">
                    {transChoice('Based on :count review|Based on :count reviews', reviewCount)}
                </p>

                <ul className="m-0 mt-5 flex list-none flex-col gap-2 p-0">
                    {[5, 4, 3, 2, 1].map((star) => {
                        const percent = Math.round(((distribution[star] ?? 0) / reviewCount) * 100);
                        return (
                            <li key={star} className="flex items-center gap-3 text-sm">
                                <span className="w-10 shrink-0 text-muted">
                                    {star}{' '}
                                    <StarIcon
                                        size={11}
                                        className="inline fill-amber align-middle text-amber"
                                        aria-hidden="true"
                                    />
                                </span>
                                <div
                                    className="h-2 flex-1 overflow-hidden rounded-full bg-line"
                                    role="img"
                                    aria-label={__(':count stars: :percent% of reviews', { count: star, percent })}
                                >
                                    <div className="h-full rounded-full bg-amber" style={{ width: `${percent}%` }} />
                                </div>
                                <span className="w-9 shrink-0 text-end text-muted tabular-nums">{percent}%</span>
                            </li>
                        );
                    })}
                </ul>

                {canReview && !submitted && (
                    <Button variant="outline" size="sm" block onClick={() => setModalOpen(true)} className="mt-6">
                        {__('Write a review')}
                    </Button>
                )}
                {thankYou}
            </div>

            <div>
                <ul className="m-0 flex list-none flex-col gap-6 p-0">
                    {items.map((review) => (
                        <ReviewCard key={review.id} review={review} />
                    ))}
                </ul>

                {nextUrl && (
                    <Button variant="outline" size="md" onClick={loadMore} disabled={loadingMore} className="mt-6">
                        {loadingMore ? __('Loading...') : __('Load more reviews')}
                    </Button>
                )}
            </div>

            {reviewModal}
        </div>
    );
}
