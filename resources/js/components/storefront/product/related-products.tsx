import { GRID_COLS, ProductGrid } from '@/components/storefront/product-grid';
import { SectionFrame } from '@/components/storefront/section-frame';
import { __ } from '@/lib/i18n';
import type { ProductData } from '@/types';

function Heading() {
    return (
        <h2 id="rel-heading" className="mb-5 text-4xl font-bold text-ink">
            {__('You may also like')}
        </h2>
    );
}

export function RelatedProductsSkeleton({ ratio = 1 }: { ratio?: number }) {
    return (
        <section aria-hidden="true">
            <Heading />
            <SectionFrame cols={GRID_COLS[5]}>
                {[0, 1, 2, 3, 4].map((index) => (
                    <div key={index} className="flex h-full animate-pulse flex-col border-e border-b border-line p-5">
                        <div className="-mx-5 -mt-5 mb-4 bg-surface-2" style={{ aspectRatio: ratio }} />
                        <div className="h-2.5 w-1/3 rounded-xs bg-surface-2" />
                        <div className="mt-2 h-3.5 w-4/5 rounded-xs bg-surface-2" />
                        <div className="mt-3 h-4 w-1/3 rounded-xs bg-surface-2" />
                    </div>
                ))}
            </SectionFrame>
        </section>
    );
}

export function RelatedProducts({ products }: { products?: ProductData[] }) {
    if (!products || products.length === 0) {
        return null;
    }

    return (
        <section aria-labelledby="rel-heading">
            <Heading />
            <ProductGrid products={products} columns={5} />
        </section>
    );
}
