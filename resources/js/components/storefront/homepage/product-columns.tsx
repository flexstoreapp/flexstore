import { Link } from '@inertiajs/react';

import * as ProductController from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { ContainedMedia } from '@/components/storefront/contained-media';
import { imageZoom } from '@/components/storefront/media-image';
import { ProductPrice } from '@/components/storefront/product-price';
import { Section } from '@/components/storefront/section-shell';
import { mediaBoxRatio } from '@/lib/media';
import { getTranslation } from '@/lib/utils';
import type { ProductColumnsSectionData } from '@/types';

export function ProductColumnsSection({ section }: { section: ProductColumnsSectionData }) {
    const columns = section.settings.columns;

    if (columns.length === 0) {
        return null;
    }

    const ratio = mediaBoxRatio(columns.flatMap((column) => column.products.map((product) => product.featured_media)));

    return (
        <Section className="@container">
            <div className="grid grid-cols-1 gap-x-10 gap-y-10 rounded-md border border-line bg-surface p-6 @3xl:grid-cols-3">
                {columns.map((column, index) => (
                    <div key={index}>
                        <div className="flex items-center gap-3 border-b border-line pb-4">
                            <span aria-hidden="true" className="h-5 w-1 shrink-0 rounded-full bg-primary" />
                            <h2 className="m-0 text-2xl font-bold tracking-label text-ink uppercase">
                                {getTranslation(column.heading)}
                            </h2>
                        </div>

                        <ul className="m-0 flex list-none flex-col divide-y divide-line p-0 [&>li:last-child>a]:pb-0">
                            {column.products.map((product) => {
                                const brand = getTranslation(product.brand?.name);

                                return (
                                    <li key={product.id}>
                                        <Link
                                            href={ProductController.show(product.url_handle)}
                                            className="group flex gap-4 py-5"
                                        >
                                            <span
                                                style={{ aspectRatio: ratio }}
                                                className="relative block w-[84px] shrink-0 overflow-hidden rounded-sm bg-surface-2"
                                            >
                                                <ContainedMedia media={product.featured_media} className={imageZoom} />
                                            </span>
                                            <span className="flex min-w-0 flex-col justify-center">
                                                {brand && (
                                                    <span className="truncate text-2xs font-semibold tracking-label text-muted uppercase">
                                                        {brand}
                                                    </span>
                                                )}
                                                <span className="mt-1 line-clamp-2 leading-snug font-semibold text-ink transition group-hover:text-primary">
                                                    {getTranslation(product.title)}
                                                </span>
                                                <span className="mt-2 block text-md">
                                                    <ProductPrice product={product} size="md" />
                                                </span>
                                            </span>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                ))}
            </div>
        </Section>
    );
}
