import { Link } from '@inertiajs/react';

import * as BrandProductController from '@/actions/App/Http/Controllers/Storefront/BrandProductController';
import { getTranslation } from '@/lib/utils';
import type { TranslatableField } from '@/types';

interface BuyBoxBrandProps {
    brand: { name: TranslatableField; url_handle: string } | null;
    onNavigate?: () => void;
}

export function BuyBoxBrand({ brand, onNavigate }: BuyBoxBrandProps) {
    if (!brand) {
        return null;
    }

    return (
        <div className="mb-2">
            <Link
                href={BrandProductController.show(brand.url_handle)}
                onClick={onNavigate}
                className="inline-block text-xs font-semibold tracking-label text-muted uppercase transition-colors hover:text-primary"
            >
                <bdi>{getTranslation(brand.name)}</bdi>
            </Link>
        </div>
    );
}
