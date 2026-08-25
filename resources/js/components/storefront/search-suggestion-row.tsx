import { Link } from '@inertiajs/react';

import * as ProductController from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { ContainedMedia } from '@/components/storefront/contained-media';
import { ProductPrice } from '@/components/storefront/product-price';
import { __ } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type { SearchSuggestion } from '@/types';

interface SearchSuggestionRowProps {
    suggestion: SearchSuggestion;
    active: boolean;
    optionId: string;
    onNavigate: () => void;
}

export function SearchSuggestionRow({ suggestion, active, optionId, onNavigate }: SearchSuggestionRowProps) {
    const outOfStock = suggestion.in_stock === false;

    return (
        <Link
            role="option"
            id={optionId}
            aria-selected={active}
            tabIndex={-1}
            href={ProductController.show(suggestion.url_handle)}
            onClick={onNavigate}
            className={cn('flex gap-3 px-4 py-2.5 transition-colors hover:bg-surface-2', active && 'bg-surface-2')}
        >
            <span
                className={cn('relative block h-9 w-9 shrink-0 overflow-hidden rounded-sm', outOfStock && 'opacity-50')}
            >
                <ContainedMedia
                    media={suggestion.featured_media}
                    source="small"
                    alt={getTranslation(suggestion.title)}
                />
            </span>
            <span className="min-w-0 flex-1">
                <span className="line-clamp-2 text-sm leading-snug font-semibold text-ink">
                    {getTranslation(suggestion.title)}
                </span>
                {outOfStock ? (
                    <span className="block text-2xs font-semibold tracking-label text-muted uppercase">
                        {__('Sold out')}
                    </span>
                ) : (
                    suggestion.category && (
                        <span className="block text-xs text-muted">
                            {__('in :category', { category: getTranslation(suggestion.category) })}
                        </span>
                    )
                )}
            </span>
            <span className={cn('shrink-0', outOfStock && 'opacity-60')}>
                <ProductPrice product={suggestion} size="sm" />
            </span>
        </Link>
    );
}
