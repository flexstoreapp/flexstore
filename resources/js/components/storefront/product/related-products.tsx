import { GRID_COLS, ProductGrid } from '@/components/storefront/product-grid';
import { SectionFrame } from '@/components/storefront/section-frame';
import { __ } from '@/lib/i18n';
import type { ProductData } from '@/types';

function Heading({ id, children }: { id?: string; children: string }) {
    return (
        <h2 id={id} className="mb-5 text-4xl font-bold text-ink">
            {children}
        </h2>
    );
}

export function RelatedProductsSkeleton({ ratio = 1 }: { ratio?: number }) {
    return (
        <section aria-hidden="true">
            <Heading>{__('You may also like')}</Heading>
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

interface RelatedProductsProps {
    products?: ProductData[];
    heading?: string;
    headingId?: string;
}

export function RelatedProducts({ products, heading, headingId = 'rel-heading' }: RelatedProductsProps) {
    if (!products || products.length === 0) {
        return null;
    }

    return (
        <section aria-labelledby={headingId}>
            <Heading id={headingId}>{heading ?? __('You may also like')}</Heading>
            <ProductGrid products={products} columns={5} />
        </section>
    );
}
