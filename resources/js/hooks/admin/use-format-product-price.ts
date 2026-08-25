import { useFormatMoney } from '../use-format-money';

interface ProductPriceData {
    price: string | null;
    priceRange?: [string, string] | null;
    hasVariants?: boolean;
    showRange?: boolean;
}

export function useFormatProductPrice() {
    const { formatMoney } = useFormatMoney();

    return ({ price, priceRange, hasVariants = false, showRange = true }: ProductPriceData): string | null => {
        if (showRange && hasVariants && priceRange) {
            const [min, max] = priceRange;
            return min === max ? formatMoney(min) : `${formatMoney(min)} - ${formatMoney(max)}`;
        }

        if (price) {
            return formatMoney(price);
        }

        if (priceRange) {
            const [min, max] = priceRange;
            return min === max ? formatMoney(min) : `${formatMoney(min)} - ${formatMoney(max)}`;
        }

        return null;
    };
}
