import { Deferred } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { ProductReviews } from '@/components/storefront/product/product-reviews';
import { useTabIndicator } from '@/hooks/storefront/use-tab-indicator';
import { __ } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type { ProductDetailData, ProductDetailSettings, ReviewData, SimplePaginated } from '@/types';

interface ProductTabsProps {
    product: ProductDetailData;
    settings: ProductDetailSettings;
    reviews?: SimplePaginated<ReviewData>;
    canReview: boolean;
    active: string;
    onActiveChange: (id: string) => void;
}

interface Tab {
    id: string;
    label: string;
    padding: string;
    panel: ReactNode;
}

function ReviewsSkeleton() {
    return (
        <div className="grid grid-cols-1 gap-8 md:grid-cols-[260px_1fr] lg:gap-12">
            <div className="h-40 animate-pulse rounded-md bg-surface-2" />
            <div className="flex flex-col gap-6">
                <div className="h-24 animate-pulse rounded-md bg-surface-2" />
                <div className="h-24 animate-pulse rounded-md bg-surface-2" />
            </div>
        </div>
    );
}

function DescriptionPanel({ html }: { html: string }) {
    return (
        <div
            className="max-w-none leading-[1.75] text-body [&_h3]:mt-7 [&_h3]:mb-3 [&_h3]:font-bold [&_h3]:text-[color:var(--color-ink)] [&_li]:mt-1 [&_li]:marker:text-muted [&_ol]:mt-3 [&_ol]:list-decimal [&_ol]:ps-5 [&_p]:mt-4 [&_strong]:font-semibold [&_strong]:text-[color:var(--color-ink)] [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:ps-5"
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}

interface ReviewsPanelProps {
    product: ProductDetailData;
    reviews?: SimplePaginated<ReviewData>;
    canReview: boolean;
}

function ReviewsPanel({ product, reviews, canReview }: ReviewsPanelProps) {
    return (
        <Deferred data="reviews" fallback={<ReviewsSkeleton />}>
            <ProductReviews
                productId={product.id}
                rating={product.rating}
                reviewCount={product.review_count}
                distribution={product.rating_distribution}
                reviews={reviews}
                canReview={canReview}
            />
        </Deferred>
    );
}

export function ProductTabs({ product, settings, reviews, canReview, active, onActiveChange }: ProductTabsProps) {
    const description = getTranslation(product.description);

    const tabs: Tab[] = [];

    if (description) {
        tabs.push({
            id: 'desc',
            label: __('Description'),
            padding: 'py-4 sm:pb-0',
            panel: <DescriptionPanel html={description} />,
        });
    }

    if (settings.show_reviews) {
        tabs.push({
            id: 'reviews',
            label: __('Reviews (:count)', { count: product.review_count }),
            padding: 'pt-8',
            panel: <ReviewsPanel product={product} reviews={reviews} canReview={canReview} />,
        });
    }

    const activeIndex = Math.max(
        0,
        tabs.findIndex((tab) => tab.id === active),
    );
    const { tabRefs, indicator, onKeyDown } = useTabIndicator(activeIndex, tabs.length, (index) =>
        onActiveChange(tabs[index].id),
    );

    return (
        <div id="reviews" className="mt-9 scroll-mt-24">
            <div className="border-b border-line-strong">
                <div
                    role="tablist"
                    aria-label={__('Product information')}
                    className="relative -mb-px no-scrollbar flex gap-1 overflow-x-auto"
                    onKeyDown={onKeyDown}
                >
                    {tabs.map((tab, index) => {
                        const isActive = index === activeIndex;
                        return (
                            <button
                                key={tab.id}
                                ref={(el) => {
                                    tabRefs.current[index] = el;
                                }}
                                type="button"
                                role="tab"
                                id={`tab-${tab.id}`}
                                aria-controls={`panel-${tab.id}`}
                                aria-selected={isActive}
                                tabIndex={isActive ? 0 : -1}
                                onClick={() => onActiveChange(tab.id)}
                                className={cn(
                                    'px-5 py-4 text-md font-semibold whitespace-nowrap transition-colors focus-visible:-outline-offset-2',
                                    isActive ? 'text-primary' : 'text-muted transition-colors can-hover:hover:text-ink',
                                )}
                            >
                                {tab.label}
                            </button>
                        );
                    })}
                    <span
                        aria-hidden="true"
                        className="absolute bottom-0 h-0.5 rounded-full bg-primary transition-[left,width] duration-(--duration-base) ease-out-quart"
                        style={{ left: indicator.left, width: indicator.width }}
                    />
                </div>
            </div>

            <div className="max-w-[900px]">
                {tabs.map((tab, index) => (
                    <div
                        key={tab.id}
                        role="tabpanel"
                        id={`panel-${tab.id}`}
                        aria-labelledby={`tab-${tab.id}`}
                        hidden={index !== activeIndex}
                        className={tab.padding}
                    >
                        {tab.panel}
                    </div>
                ))}
            </div>
        </div>
    );
}
