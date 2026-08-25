import { StarIcon } from 'lucide-react';

import { __, transChoice } from '@/lib/i18n';
import { cn } from '@/lib/utils';

function Stars({ rating }: { rating: number }) {
    return (
        <span className="inline-flex" aria-hidden="true">
            {[0, 1, 2, 3, 4].map((i) => (
                <StarIcon
                    key={i}
                    size={15}
                    className={cn(i < Math.round(rating) ? 'fill-amber text-amber' : 'text-line-strong')}
                />
            ))}
        </span>
    );
}

interface ProductRatingProps {
    rating: number | null;
    reviewCount: number;
    onReviewsClick?: () => void;
}

export function ProductRating({ rating, reviewCount, onReviewsClick }: ProductRatingProps) {
    const label = reviewCount > 0 ? transChoice(':count review|:count reviews', reviewCount) : __('No reviews yet');

    return (
        <div className="mt-4 flex flex-wrap items-center gap-3">
            <span className="flex items-center gap-2">
                <Stars rating={rating ?? 0} />
                {rating !== null && <span className="font-semibold text-ink">{rating.toFixed(1)}</span>}
            </span>
            {onReviewsClick && reviewCount > 0 ? (
                <button
                    type="button"
                    onClick={onReviewsClick}
                    className="cursor-pointer text-sm text-muted transition-colors hover:text-primary"
                >
                    {label}
                </button>
            ) : (
                <span className="text-sm text-muted">{label}</span>
            )}
        </div>
    );
}
