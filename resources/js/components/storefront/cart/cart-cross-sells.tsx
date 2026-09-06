import { ProductGrid } from '@/components/storefront/product-grid';
import { __ } from '@/lib/i18n';
import type { ProductData } from '@/types';

export function CartCrossSells({ products }: { products?: ProductData[] }) {
    if (!products || products.length === 0) {
        return null;
    }

    return (
        <section aria-labelledby="cart-cross-sell-heading" className="mt-10">
            <h2 id="cart-cross-sell-heading" className="mb-5 text-4xl font-bold text-ink">
                {__('Pairs well with')}
            </h2>
            <ProductGrid products={products} columns={4} />
        </section>
    );
}
