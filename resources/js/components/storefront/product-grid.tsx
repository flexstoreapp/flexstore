import { ProductCard } from '@/components/storefront/product-card';
import { SectionFrame } from '@/components/storefront/section-frame';
import { mediaBoxRatio } from '@/lib/media';
import type { ProductData } from '@/types';

interface ProductGridProps {
    products: ProductData[];
    columns?: 4 | 5;
    className?: string;
}

export const GRID_COLS: Record<4 | 5, string> = {
    4: 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
    5: 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5',
};

export function ProductGrid({ products, columns = 5, className }: ProductGridProps) {
    const ratio = mediaBoxRatio(products.map((product) => product.featured_media));

    return (
        <SectionFrame cols={GRID_COLS[columns]} className={className}>
            {products.map((product) => (
                <ProductCard key={product.id} product={product} layout="grid" ratio={ratio} />
            ))}
        </SectionFrame>
    );
}
