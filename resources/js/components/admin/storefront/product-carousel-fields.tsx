import { ProductSourceFields } from '@/components/admin/storefront/product-source-fields';
import type { ProductCarouselSettings } from '@/types';

export function ProductCarouselFields({
    settings,
    errors,
}: {
    settings?: ProductCarouselSettings;
    errors: Record<string, string>;
}) {
    return <ProductSourceFields prefix="settings" settings={settings} errors={errors} />;
}
