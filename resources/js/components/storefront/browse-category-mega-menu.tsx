import type { StorefrontMenuItem } from '@/types';

import { MegaContent, toMegaSections } from './mega-content';

export function BrowseCategoryMegaMenu({ category }: { category: StorefrontMenuItem }) {
    return (
        <div className="w-max max-w-[calc(100vw-19rem)] overflow-hidden rounded-md border border-line bg-surface shadow-md">
            <MegaContent
                sections={toMegaSections(category)}
                featured={category.featured}
                footerHref={category.url}
                columnsClassName="grid-cols-2 xl:grid-cols-3"
            />
        </div>
    );
}
