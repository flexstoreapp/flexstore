import { StarIcon } from 'lucide-react';
import { memo, useState } from 'react';

import { ReactiveHiddenInput } from '@/components/admin/reactive-hidden-input';
import { cn } from '@/lib/utils';
import { transChoice } from '@/lib/i18n';

interface StarRatingProps {
    rating?: number;
    value?: number;
    onChange?: (rating: number) => void;
    name?: string;
    id?: string;
    className?: string;
    disabled?: boolean;
    readOnly?: boolean;
    size?: 'sm' | 'md';
}

const sizeClasses = {
    sm: 'size-3.5',
    md: 'size-4',
};

const MAX_RATING = 5;

const StarRating = memo(
    ({ rating, value, onChange, name, id, className, disabled = false, readOnly = false, size = 'md' }: StarRatingProps) => {
        const [hoveredRating, setHoveredRating] = useState(0);

        const currentRating = value ?? rating ?? 0;
        const isInteractive = !readOnly && !disabled;
        const starSize = sizeClasses[size];

        const handleStarClick = (starValue: number) => {
            if (isInteractive && onChange) {
                onChange(starValue);
            }
        };

        const handleStarHover = (starValue: number) => {
            if (isInteractive) {
                setHoveredRating(starValue);
            }
        };

        const handleMouseLeave = () => {
            if (isInteractive) {
                setHoveredRating(0);
            }
        };

        const getStarClassName = (starValue: number) => {
            const isFilled = starValue <= currentRating;
            const isHovering = hoveredRating > 0;
            const isWithinHover = starValue <= hoveredRating;
            const willBeUnfilled = isHovering && isFilled && !isWithinHover;
            const willBeFilled = isHovering && !isFilled && isWithinHover;

            return cn(
                starSize,
                isFilled && !willBeUnfilled && 'fill-yellow-400 text-yellow-400',
                willBeUnfilled && 'text-yellow-400/50',
                willBeFilled && 'stroke-yellow-400 stroke-1 text-yellow-400',
                !isFilled && !willBeFilled && 'text-muted-foreground',
                isInteractive && 'transition-all',
            );
        };

        return (
            <div id={id} className={cn("flex items-center gap-0.5", className)}>
                {Array.from({ length: MAX_RATING }, (_, i) => {
                    const starValue = i + 1;

                    if (readOnly) {
                        const fillFraction = Math.min(Math.max(currentRating - i, 0), 1);

                        if (fillFraction > 0 && fillFraction < 1) {
                            return (
                                <span key={starValue} className={cn('relative block', starSize)}>
                                    <StarIcon className={cn(starSize, 'text-muted-foreground')} />
                                    <span
                                        className="absolute inset-y-0 start-0 overflow-hidden"
                                        style={{ width: `${fillFraction * 100}%` }}
                                    >
                                        <StarIcon className={cn(starSize, 'fill-yellow-400 text-yellow-400')} />
                                    </span>
                                </span>
                            );
                        }

                        return (
                            <StarIcon
                                key={starValue}
                                className={cn(
                                    starSize,
                                    fillFraction === 1 ? "fill-yellow-400 text-yellow-400" : "text-muted-foreground",
                                )}
                            />
                        );
                    }

                    return (
                        <button
                            key={starValue}
                            type="button"
                            onClick={() => handleStarClick(starValue)}
                            onMouseEnter={() => handleStarHover(starValue)}
                            onMouseLeave={handleMouseLeave}
                            disabled={disabled}
                            className={cn(
                                "rounded-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none",
                                isInteractive && "cursor-pointer",
                                disabled && "cursor-not-allowed opacity-50",
                            )}
                            aria-label={transChoice(':count star|:count stars', starValue)}
                        >
                            <StarIcon className={getStarClassName(starValue)} />
                        </button>
                    );
                })}
                {name && !readOnly && <ReactiveHiddenInput name={name} value={currentRating || ''} />}
            </div>
        );
    },
);
StarRating.displayName = 'StarRating';

export { StarRating };
