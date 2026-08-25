import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { resolveProductPricing, type ProductPricingSource, type ProductPricingVariant } from '@/lib/product-pricing';
import { cn } from '@/lib/utils';

const SIZES = {
    sm: { price: 'text-xs sm:text-sm', compare: 'text-2xs sm:text-xs' },
    md: { price: 'text-md sm:text-lg', compare: 'text-xs sm:text-sm' },
    lg: { price: 'text-4xl sm:text-5xl', compare: 'text-2xl font-semibold' },
    xl: { price: 'text-6xl sm:text-7xl', compare: 'text-3xl font-semibold' },
} as const;

type ProductPriceSize = keyof typeof SIZES;

interface ProductPriceProps {
    product: ProductPricingSource;
    variant?: ProductPricingVariant | null;
    size?: ProductPriceSize;
    as?: 'span' | 'div';
    showTaxNote?: boolean;
    className?: string;
}

export function ProductPrice({
    product,
    variant = null,
    size = 'md',
    as: Wrapper = 'span',
    showTaxNote = false,
    className,
}: ProductPriceProps) {
    const { formatMoney } = useFormatMoney();
    const styles = SIZES[size];
    const { price, compareAt, range } = resolveProductPricing(product, variant);

    return (
        <Wrapper className={className}>
            <span className="inline-flex flex-wrap items-baseline gap-x-2 gap-y-0.5 text-ink">
                {range ? (
                    <span className={cn(styles.price, 'font-bold')}>
                        {formatMoney(range[0])} – {formatMoney(range[1])}
                    </span>
                ) : (
                    <>
                        {compareAt && <span className="sr-only">{__('Sale price')}</span>}
                        <span className={cn(styles.price, 'font-bold', compareAt ? 'text-orange' : 'text-ink')}>
                            {price !== null ? formatMoney(price) : ''}
                        </span>
                        {compareAt && (
                            <>
                                <span className="sr-only">{__('Regular price')}</span>
                                <s className={cn(styles.compare, 'text-muted')}>{formatMoney(compareAt)}</s>
                            </>
                        )}
                    </>
                )}
            </span>
            {showTaxNote && <span className="mt-1 block text-xs text-muted">{__('Price incl. tax')}</span>}
        </Wrapper>
    );
}
