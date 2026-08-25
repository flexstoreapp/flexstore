import { ZapIcon } from 'lucide-react';

import { __ } from '@/lib/i18n';
import { resolveProductPricing, type ResolvedProductPricing } from '@/lib/product-pricing';
import { cn } from '@/lib/utils';
import type { ProductData } from '@/types';

export interface ProductBadgeData {
    soldOut: boolean;
    flash: boolean;
    percent: number | null;
}

function percentOff(salePrice: string | null, originalPrice: string | null): number | null {
    if (!salePrice || !originalPrice) {
        return null;
    }
    const sale = parseFloat(salePrice);
    const original = parseFloat(originalPrice);
    if (!(original > sale) || original <= 0) {
        return null;
    }
    return Math.round((1 - sale / original) * 100);
}

export function resolveGalleryBadge(
    inStock: boolean,
    isFlash: boolean,
    pricing: ResolvedProductPricing,
): ProductBadgeData {
    return {
        soldOut: !inStock,
        flash: isFlash,
        percent: pricing.range ? null : percentOff(pricing.price, pricing.compareAt),
    };
}

export function resolveCardBadge(product: ProductData, outOfStock: boolean): ProductBadgeData {
    const pricing = resolveProductPricing(product);

    return { soldOut: outOfStock, flash: false, percent: percentOff(pricing.price, pricing.compareAt) };
}

const SIZES = {
    sm: { text: 'gap-1 px-2 py-0.5 text-xs', icon: 12 },
    lg: { text: 'gap-1 px-3 py-1 text-sm', icon: 14 },
} as const;

interface ProductBadgeProps {
    badge: ProductBadgeData;
    size?: 'sm' | 'lg';
}

export function ProductBadge({ badge, size = 'sm' }: ProductBadgeProps) {
    const styles = SIZES[size];
    const base = 'pointer-events-none absolute start-0 top-0 z-10 rounded-ee-sm font-bold text-white';

    if (badge.soldOut) {
        return <span className={cn(base, 'bg-ink', styles.text)}>{__('Sold out')}</span>;
    }

    if (badge.percent === null && !badge.flash) {
        return null;
    }

    return (
        <span className={cn(base, 'flex items-center bg-orange', styles.text)}>
            {badge.flash && <ZapIcon size={styles.icon} className="fill-white" aria-hidden="true" />}
            <span>
                {badge.percent && badge.percent > 0 ? __(':percent% off', { percent: badge.percent }) : __('Sale')}
            </span>
        </span>
    );
}
