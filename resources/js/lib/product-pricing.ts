export interface ProductPricingSource {
    price_range: [string, string] | null;
    compare_at_price_range: [string, string] | null;
}

export interface ProductPricingVariant {
    price: string | null;
    compare_at_price: string | null;
}

export interface ResolvedProductPricing {
    price: string | null;
    compareAt: string | null;
    range: [string, string] | null;
}

function isDiscounted(price: string | null, compareAt: string | null): boolean {
    return price != null && compareAt != null && Number(compareAt) > Number(price);
}

function collapse(range: [string, string] | null): string | null {
    return range && range[0] === range[1] ? range[0] : null;
}

export function resolveProductPricing(
    product: ProductPricingSource,
    variant: ProductPricingVariant | null = null,
): ResolvedProductPricing {
    if (variant) {
        const price = variant.price;
        const compareAt = variant.compare_at_price;

        return { price, compareAt: isDiscounted(price, compareAt) ? compareAt : null, range: null };
    }

    const range = product.price_range;

    if (range && range[0] !== range[1]) {
        return { price: range[0], compareAt: null, range };
    }

    const price = collapse(range);
    const compareAt = collapse(product.compare_at_price_range);

    return { price, compareAt: isDiscounted(price, compareAt) ? compareAt : null, range: null };
}
