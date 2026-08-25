import { StarIcon } from 'lucide-react';

import { useFormatDate } from '@/hooks/use-format-date';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { ReviewData } from '@/types';

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

function ReviewStars({ rating }: { rating: number }) {
    return (
        <span className="inline-flex" aria-hidden="true">
            {[0, 1, 2, 3, 4].map((i) => (
                <StarIcon key={i} size={13} className={cn(i < rating ? 'fill-amber text-amber' : 'text-line-strong')} />
            ))}
        </span>
    );
}

export function ReviewCard({ review }: { review: ReviewData }) {
    const formatDate = useFormatDate();
    const author = review.user?.name ?? __('Anonymous');
    const date = formatDate(review.created_at, { year: 'numeric', month: 'short', day: 'numeric' });

    return (
        <li className="border-b border-line pb-6 last:border-0">
            <div className="flex items-center gap-3">
                <span
                    aria-hidden="true"
                    className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-tint font-bold text-primary"
                >
                    {initials(author)}
                </span>
                <div>
                    <div className="font-semibold text-ink">{author}</div>
                    <div className="flex items-center gap-2">
                        <ReviewStars rating={review.rating} />
                        <span className="sr-only">{__('Rated :rating out of 5', { rating: review.rating })}</span>
                        <span className="text-xs text-muted">{date}</span>
                    </div>
                </div>
            </div>
            {review.title && <h3 className="mt-3 mb-1 font-semibold text-ink">{review.title}</h3>}
            <p className="mt-3 mb-0 leading-relaxed text-muted">{review.content}</p>
        </li>
    );
}
